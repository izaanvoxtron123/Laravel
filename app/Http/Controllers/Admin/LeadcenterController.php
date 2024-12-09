<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LeadcenterExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leadcenter as MainModel;
use App\Models\User;
use App\Http\Common\Helper;
use App\Imports\LeadcenterImport;
use App\Imports\LeadsImport;
use App\Jobs\LeadcenterImportCSV;
use App\Jobs\ProcessLeadcenterExportInTxt;
use App\Models\CustomerExports;
use App\Models\Leadcenter;
use App\Models\Offices;
use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\Datatables\Datatables;

class LeadcenterController extends Controller
{
    public $folder_name = 'leadcenter'; // For view routes and file calling and saving
    public $module_name = 'Leadcenter'; // For toast And page header

    function fetch(Request $request)
    {
        if (Auth::user()->role_id == User::SUPERADMIN_ROLE || Auth::user()->role_id == User::MANAGER_ROLE) {
            $query = MainModel::where('is_used', 0);
        } elseif (Auth::user()->role_id == User::AGENT_ROLE) {
            $query = MainModel::where('is_used', 0)->where('agent_id', Auth::user()->id)->where('is_complete', 0);
        } elseif (Auth::user()->role_id == User::RND_ROLE) {
            $query = MainModel::where('is_used', 0)->where('rnd_agent_id', Auth::user()->id)->where('in_rnd', 1)->where('is_complete', 0);
        } else {
            $query = MainModel::where('is_used', 0)->where('in_rnd', 1);
        }

        if ($request->agent_filter != '') {
            $query = $query->where('agent_id', $request->agent_filter);
        }
        if ($request->rnd_agent_filter != '') {
            $query = $query->where('rnd_agent_id', $request->rnd_agent_filter);
        }
        if ($request->is_completed_filter != '') {
            $query = $query->where('is_complete', $request->is_completed_filter);
        }
        if ($request->has_phone_filter != '') {
            if ($request->has_phone_filter == "1") {
                $query = $query->whereNotNull('phone');
            } else {
                $query = $query->whereNull('phone');
            }
        }
        if ($request->is_rc_lead_filter != '') {
            $query = $query->where('is_rc', $request->is_rc_lead_filter);
        }


        if ($request->has_rnd_agent_filter != '') {
            if ($request->has_rnd_agent_filter == "1") {
                $query = $query->whereNotNull('rnd_agent_id')->where('rnd_agent_id', '!=', '0');
            } else {
                $query = $query->whereNull('rnd_agent_id')->orWhere('rnd_agent_id', '0');
            }
        }


        if ($request->has_agent_filter != '') {
            if ($request->has_agent_filter == "1") {
                $query = $query->whereNotNull('agent_id')->where('agent_id', '!=', '0');
            } else {
                $query = $query->whereNull('agent_id')->orWhere('agent_id', '0');
            }
        }
        $query = $query->with(['agent', 'rnd_agent'])->orderBy('id', 'desc');

        return DataTables::of($query)->make(true);
    }
    function view()
    {
        $agents = User::where([
            'role_id' => User::AGENT_ROLE
        ])->orderBy('name')->get();

        $rnd_agents = User::where([
            'role_id' => User::RND_ROLE
        ])->orderBy('name')->get();

        $data['folder_name'] = $this->folder_name;
        $data['module_name'] = $this->module_name;
        $data['agents'] =  $agents;
        $data['rnd_agents'] =  $rnd_agents;
        $file = "admin." . $this->folder_name . ".view";
        return view($file, $data);
    }

    function upload(Request $request)
    {
        if (!isset($request->file)) {
            Helper::toast('error', "Please Upload File");
            return back();
        }
        try {
            $file = request()->file('file');

            $filePath = $file->store("temp_lc_csvs");
            LeadcenterImportCSV::dispatch($filePath);

            Helper::toast('success', ' Started process, will be finished shortly.');
        } catch (\Exception $e) {
            Helper::toast('error', ' Something Went Wrong.' . $e->getMessage());
        };
        return back();
    }

    function complete(Request $request, $leadcenter_id)
    {
        if (MainModel::where('id', $leadcenter_id)->update(['is_complete' => true])) {
            Helper::toast('success', ' Successfully marked as complete.');
        } else {
            Helper::toast('error', "Something Went Wrong");
        }
        return back();
    }

    function incomplete(Request $request, $leadcenter_id)
    {
        if (MainModel::where('id', $leadcenter_id)->update(['is_complete' => false])) {
            Helper::toast('success', ' Successfully marked as Incomplete.');
        } else {
            Helper::toast('error', "Something Went Wrong");
        }
        return back();
    }


    function bulkComplete(Request $request)
    {
        try {
            foreach ($request->leads as $lead_id) {
                MainModel::where("id", $lead_id)->update([
                    'is_complete' => $request->action == "complete" ? true : false
                ]);
            }
            return [
                "status" => true,
                "message" => "success",
                "data" => null
            ];
        } catch (Exception $e) {
            return [
                "status" => false,
                "message" => "Something went wrong",
                "data" => null
            ];
        }
    }

    function fillDetails(Request $request, $id)
    {
        $id = decrypt($id);

        if ($request->isMethod('post')) {
            if (MainModel::where('id', $id)->update(['phone' => json_encode($request->phone_numbers)])) {
                Helper::toast('success', ' Successfully filled details.');
            } else {
                Helper::toast('error', "Something Went Wrong");
            }
            return redirect(route('leadcenter-view'));
        } else {
            $lead = MainModel::find($id);
            $data['page_header'] = "Fill Details ";
            $data['folder_name'] = $this->folder_name;
            $data['module_name'] = $this->module_name;
            $data['lead'] = $lead;
            $file = "admin." . $this->folder_name . ".fill_details";
            return view($file, $data);
        }
    }

    function getAgents(Request $request)
    {
        try {
            $agents = User::where([
                'role_id' => User::AGENT_ROLE,
                'status' => 1,
                'office_id' => null,
            ])->get()
                ->toArray();

            $offices = Offices::where(['status' => 1])
                ->whereHas('agents')
                ->with('agents')
                ->get()
                ->toArray();

            $primary = [
                "name" => "Primary",
                "agents" => $agents,
            ];

            $offices[count($offices)] = $primary;

            // $resultArray = array_merge($primary, $offices);

            return [
                "status" => true,
                "message" => "success",
                "data" => $offices
            ];
        } catch (Exception $e) {
            return [
                "status" => false,
                "message" => "Something went wrong",
                "data" => null
            ];
        }
    }

    function assignAgent(Request $request)
    {

        try {
            // foreach ($request->leads as $lead_id) {
            //     MainModel::where("id", $lead_id)->update([
            //         'agent_id' => $request->agent
            //     ]);
            // }
            // return [
            //     "status" => true,
            //     "message" => "success",
            //     "data" => null
            // ];

            $num_leads = count($request->leads);
            $num_agents = count($request->agents);

            if ($num_leads <  $num_agents) {
                return [
                    "status" => false,
                    "message" => "Not enough leads, please assign manually.",
                    "data" => null
                ];
            }

            // Calculate leads per agent and remainder
            $leads_per_agent = intdiv($num_leads, $num_agents);
            $remainder = $num_leads % $num_agents;

            $lead_index = 0;

            foreach ($request->agents as $agent_id) {
                $num_leads_for_this_agent = $leads_per_agent + ($remainder > 0 ? 1 : 0);
                $remainder--;

                for ($i = 0; $i < $num_leads_for_this_agent; $i++) {
                    MainModel::where("id", $request->leads[$lead_index])->update([
                        'agent_id' => $agent_id
                    ]);
                    $lead_index++;
                }
            }

            return [
                "status" => true,
                "message" => "Leads have been successfully distributed",
                "data" => null
            ];
        } catch (Exception $e) {
            return [
                "status" => false,
                "message" => "Something went wrong",
                "data" => null
            ];
        }
    }

    function unassignAgent(Request $request)
    {
        try {
            foreach ($request->leads as $lead_id) {
                MainModel::where("id", $lead_id)->update([
                    'agent_id' => null
                ]);
            }
            return [
                "status" => true,
                "message" => "success",
                "data" => null
            ];
        } catch (Exception $e) {
            return [
                "status" => false,
                "message" => "Something went wrong",
                "data" => null
            ];
        }
    }

    function getRnDAgents(Request $request)
    {
        try {
            $rnd_agents = User::where([
                'role_id' => User::RND_ROLE,
                'status' => 1
            ])->get();

            return [
                "status" => true,
                "message" => "success",
                "data" => $rnd_agents
            ];
        } catch (Exception $e) {
            return [
                "status" => false,
                "message" => "Something went wrong",
                "data" => null
            ];
        }
    }

    function moveToRnd(Request $request)
    {

        try {
            foreach ($request->leads as $lead_id) {
                MainModel::where("id", $lead_id)->update([
                    'in_rnd' => 1,
                ]);
            }
            return [
                "status" => true,
                "message" => "success",
                "data" => null
            ];
        } catch (Exception $e) {
            return [
                "status" => false,
                "message" => "Something went wrong",
                "data" => null
            ];
        }
    }

    function assignToRnDAgent(Request $request)
    {

        try {
            foreach ($request->leads as $lead_id) {
                MainModel::where("id", $lead_id)->update([
                    'in_rnd' => 1,
                    'rnd_agent_id' => $request->rnd_agent
                ]);
            }
            return [
                "status" => true,
                "message" => "success",
                "data" => null
            ];
        } catch (Exception $e) {
            return [
                "status" => false,
                "message" => "Something went wrong",
                "data" => null
            ];
        }
    }

    function UnassignToRnd(Request $request)
    {
        try {
            foreach ($request->leads as $lead_id) {
                MainModel::where("id", $lead_id)->update([
                    'in_rnd' => 0,
                    'rnd_agent_id' => null
                ]);
            }
            return [
                "status" => true,
                "message" => "success",
                "data" => null
            ];
        } catch (Exception $e) {
            return [
                "status" => false,
                "message" => "Something went wrong",
                "data" => null
            ];
        }
    }

    function getOffices(Request $request)
    {
        try {
            $offices = Offices::where(['status' => 1])->withCount('agents')->get();

            // $resultArray = array_merge($primary, $offices);

            return [
                "status" => true,
                "message" => "success",
                "data" => $offices
            ];
        } catch (Exception $e) {
            return [
                "status" => false,
                "message" => "Something went wrong",
                "data" => null
            ];
        }
    }

    function assignOffice(Request $request)
    {
        try {
            $agents = User::where("office_id", $request->office)->where('role_id', User::AGENT_ROLE)->pluck('id')->toArray();
            $num_agents = count($agents);

            if ($num_agents == 0) {
                return [
                    "status" => false,
                    "message" => "No agents found for the given office",
                    "data" => null
                ];
            }

            $leads = $request->leads;
            $num_leads = count($leads);

            if ($num_leads < $num_agents) {
                return [
                    "status" => false,
                    "message" => "Not enough leads, please assign manually.",
                    "data" => null
                ];
            }

            // Calculate leads per agent and remainder
            $leads_per_agent = intdiv($num_leads, $num_agents);
            $remainder = $num_leads % $num_agents;

            $lead_index = 0;

            foreach ($agents as $agent_id) {
                $num_leads_for_this_agent = $leads_per_agent + ($remainder > 0 ? 1 : 0);
                $remainder--;

                for ($i = 0; $i < $num_leads_for_this_agent; $i++) {
                    MainModel::where("id", $leads[$lead_index])->update([
                        'agent_id' => $agent_id
                    ]);
                    $lead_index++;
                }
            }

            return [
                "status" => true,
                "message" => "Leads have been successfully distributed",
                "data" => null
            ];
        } catch (Exception $e) {
            return [
                "status" => false,
                "message" => "Something went wrong",
                "data" => null
            ];
        }
    }

    function delete(Request $request)
    {
        try {
            // foreach ($request->leads as $lead_id) {
            MainModel::whereIn("id", $request->leads)->delete();
            // }
            return [
                "status" => true,
                "message" => "success",
                "data" => null
            ];
        } catch (Exception $e) {
            return [
                "status" => false,
                "message" => "Something went wrong",
                "data" => null
            ];
        }
    }

    function downloadInCsv(Request $request)
    {
        try {
            $filename = "leads-batch-" . Carbon::now()->format('m-d-Y_H-i_A') . ".csv";
            Excel::queue(new LeadcenterExport($request->leads), 'public/leadcenter_exports/' . $filename);

            CustomerExports::create([
                'path' => 'leadcenter_exports/' . $filename,
                'filename' => $filename,
            ]);
            return [
                'status' => true,
                "message" => 'Downloading Started. This will be visible shortly in Customer Exports.'
            ];
            // Helper::toast('success', ' Downloading Started. This will be visible shortly in Customer Exports.');
        } catch (\Exception $e) {
            return [
                'status' => false,
                "message" => 'Something Went Wrong.' . $e->getMessage()
            ];
            // Helper::toast('error', ' Something Went Wrong.' . $e->getMessage());
        };
        // return back();
    }

    function downloadInTxtZip(Request $request)
    {
        try {
            ProcessLeadcenterExportInTxt::dispatch($request->leads);

            return [
                'status' => true,
                "message" => 'Downloading Started. This will be visible shortly in Customer Exports.'
            ];
            // Helper::toast('success', ' Downloading Started. This will be visible shortly in Customer Exports.');
        } catch (\Exception $e) {
            return [
                'status' => false,
                "message" => 'Something Went Wrong.' . $e->getMessage()
            ];
        }
    }

    function findPersons(Request $request)
    {
        try {
            $name = $request->first_name . ' ' . $request->middle_name . ' ' . $request->last_name;
            $street_line_1 = $request->street;
            $street_line_2 = $request->street_line_2;
            $city = $request->city;
            $postal_code = $request->zip;
            $state_code = $request->state;
            $country_code = "US";

            // Set your API key
            $api_key = env("TRESTLE_API_KEY");

            // Build the URL
            $url = 'https://api.trestleiq.com/3.1/person?' . http_build_query([
                'name' => $name,
                'address.street_line_1' => $street_line_1,
                'address.street_line_2' => $street_line_2,
                'address.city' => $city,
                'address.postal_code' => $postal_code,
                'address.state_code' => $state_code,
                'address.country_code' => $country_code,
            ]);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'x-api-key: ' . $api_key
                ),
            ));

            $response = json_decode(curl_exec($curl));

            curl_close($curl);

            $trestle_phone_numbers = [];
            if ($response->count_person) {
                foreach ($response->person as $key =>  $person) {
                    if ($key >= 5) {
                        break;
                    }
                    foreach ($person->phones as $trestle_phone) {
                        // $trestle_phone_numbers[] =  str_replace(" ", "", str_replace("+1", "", $trestle_phone->phone_number));
                        $trestle_phone_numbers[] =   str_replace("+1", "", $trestle_phone->phone_number);
                    }
                }
            }
            return [
                'status' => true,
                'message' => "Successful",
                'data' => $trestle_phone_numbers,
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => "Something went wrong"
            ];
        }
    }
}
