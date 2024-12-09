<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Offices as MainModel;
use App\Models\User;
use App\Http\Common\Helper;
use App\Imports\LeadsImport;
use App\Models\CustomerLogs;
use App\Models\Leadcenter;
use App\Models\MIds;
use App\Models\OfficeIps;
use App\Models\OfficeMids;
use App\Models\WhitelistedIps;
use Auth;
use Carbon\Carbon;
use Exception;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\Datatables\Datatables;

class OfficesController extends Controller
{
    public function __construct(array $attributes = array())
    {
        $input_elements = [
            [
                "element_type" => "input",
                "input_type" => "text",
                "label" => "Name",
                "name" => "name",
                "placeholder" => "Enter Name",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required"],
            ],

            [
                "element_type" => "input",
                "input_type" => "text",
                "label" => "Email Domain",
                "name" => "email_domain",
                "placeholder" => "Enter Email Domain",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required"],
            ],

            [
                "label" => "Allowed IPs",
                "element_type" => "dropdown",
                "name" => "ip_id[]",
                "options" => WhitelistedIps::where(['status' => 1])->get(),
                "value_element" => "id",
                "label_element" => "ip",
                "select_element" => "ip_id",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required", 'multiple' => 'true'],
            ],

        ];
        $this->input_elements = $input_elements;
    }

    public $folder_name = 'offices'; // For view routes and file calling and saving
    public $module_name = 'Offices'; // For toast And page header
    public $input_elements;
    public $progress;

    public function view(Request $request)
    {
        if ($request->isMethod('post')) {
            foreach ($request->sequence as $key => $id) {
                $sequence = $key + 1;
                MainModel::where('id', $id)->update(['sequence' => $sequence]);
            }
            return back();
        } else {
            $agents = User::where([
                'role_id' => User::AGENT_ROLE
            ])->orderBy('name')->get();

            $data['result'] = MainModel::all();
            $data['folder_name'] = $this->folder_name;
            $data['module_name'] = $this->module_name;
            $file = "admin." . $this->folder_name . ".view";
            return view($file, $data);
        }
    }

    public function form(Request $request, $id = null)
    {
        // dd($request->post());
        $request->validate(MainModel::getValidationRules($id));
        // $sequence = MainModel::count();
        // $sequence = $sequence + 1;
        $data = [
            'name' => $request->name,
            'email_domain' => $request->email_domain,
            'can_login' => $request->can_login,
            'status' => $request->status,
        ];
        if ($request->hasFile('source')) {
            $data['source'] = $request->source->store($this->folder_name, Helper::STATIC_ASSET_DISK);
        }
        if ($id) {
            // $current_state = MainModel::find($id);
            if (MainModel::where('id', $id)->update($data)) {

                OfficeIps::where('office_id', $id)->delete();
                OfficeMids::where('office_id', $id)->delete();
                foreach ($request->ips as $key => $ip) {
                    OfficeIps::create([
                        'office_id' => $id,
                        'ip_id' => $ip
                    ]);

                    foreach ($request->mids as $key => $mid) {
                        OfficeMids::create([
                            'office_id' => $id,
                            'mid_id' => $mid
                        ]);
                    }
                }
                Helper::toast('success', $this->module_name . ' Updated.');
            }
        } else {
            if ($created_data = MainModel::create($data)) {

                foreach ($request->ips as $key => $ip) {
                    OfficeIps::create([
                        'office_id' => $created_data->id,
                        'ip_id' => $ip
                    ]);
                }

                foreach ($request->mids as $key => $mid) {
                    OfficeMids::create([
                        'office_id' => $created_data->id,
                        'mid_id' => $mid
                    ]);
                }
                Helper::toast('success', $this->module_name . ' created.');
            }
        }
        return redirect()->route($this->folder_name . '-view');
    }

    public function add(Request $request)
    {
        if ($request->isMethod('post')) {
            return $this->form($request);
        } else {
            $data['page_header'] = "Add " . $this->module_name;
            $data['result'] = null;
            $data['office_ips'] = [];
            $data['ips'] = WhitelistedIps::where(['status' => 1])->get();

            $data['office_mids'] = [];
            $data['mids'] = MIds::where(['status' => 1])->get();

            $input_elements = $this->input_elements;
            $data['include_status_radio'] = 1;
            $file = "admin." . $this->folder_name . ".form";
            return view($file, $data);
            return view('general_crud.general_view', $data)->with(compact('input_elements'));
        }
    }

    public function edit(Request $request, $id)
    {
        $id = decrypt($id);
        if ($request->isMethod('post')) {
            return $this->form($request, $id);
        } else {
            $data['page_header'] = "Edit " . $this->module_name;
            $data['result'] = MainModel::find($id);
            $data['office_ips'] = OfficeIps::where('office_id', $id)->pluck('ip_id')->toArray();
            $data['ips'] = WhitelistedIps::where(['status' => 1])->get();

            $data['office_mids'] = OfficeMids::where('office_id', $id)->pluck('mid_id')->toArray();
            $data['mids'] = MIds::where(['status' => 1])->get();

            $data['include_status_radio'] = 1;
            $input_elements = $this->input_elements;

            $file = "admin." . $this->folder_name . ".form";
            return view($file, $data);
            return view('general_crud.general_view', $data)->with(compact('input_elements'));
        }
    }
}
