<?php

namespace App\Http\Controllers\Admin;

use App\Events\PhoneModified;
use App\Exports\CustomerExport;
use App\Exports\RCLExport;
use App\Http\Common\FcmHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer as MainModel;
use App\Models\User;
use App\Http\Common\Helper;
use App\Imports\LeadsImport;
use App\Jobs\ImportCustomersCSV;
use App\Jobs\ProcessCustomerExportInTxt;
use App\Jobs\PushNotifications;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerAccounts;
use App\Models\CustomerExports;
use App\Models\CustomerLogs;
use App\Models\CustomerPhoneModificationLogs;
use App\Models\Leadcenter;
use App\Models\Personnel;
use App\Models\CustomerPhones;
use App\Models\Offices;
use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpParser\Node\Stmt\For_;
use Yajra\Datatables\Datatables;
use Symfony\Component\HttpFoundation\StreamedResponse;


class CustomerController extends Controller
{
    public function __construct(array $attributes = array())
    {

        $input_elements = [
            [
                "element_type" => "input",
                "input_type" => "text",
                "label" => "First Name",
                "name" => "first_name",
                "placeholder" => "Enter First Name",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required"],
            ],
            [
                "element_type" => "input",
                "input_type" => "text",
                "label" => "Middle Initial",
                "name" => "middle_initial",
                "placeholder" => "Enter Middle Initial",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => [],
            ],
            [
                "element_type" => "input",
                "input_type" => "text",
                "label" => "Last Name",
                "name" => "last_name",
                "placeholder" => "Enter Last Name",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required"],
            ],


            [
                "element_type" => "input",
                "input_type" => "text",
                "label" => "Phone",
                "name" => "phone",
                "placeholder" => "Enter Phone",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required"],
            ],
            [
                "element_type" => "input",
                "input_type" => "email",
                "label" => "Email",
                "name" => "email",
                "placeholder" => "Enter Email",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => [],
            ],
            [
                "element_type" => "input",
                "input_type" => "number",
                "label" => "Social Security Number",
                "name" => "ssn",
                "placeholder" => "Enter Social Security Number",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required"],
            ],
            [
                "element_type" => "input",
                "input_type" => "date",
                "label" => "Date Of Birth",
                "name" => "dob",
                "placeholder" => "Enter Date Of Birth",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => [],
            ],
            [
                "element_type" => "input",
                "input_type" => "number",
                "label" => "Age",
                "name" => "age",
                "placeholder" => "Enter Age",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => [],
            ],
            [
                "element_type" => "input",
                "input_type" => "number",
                "label" => "House Number",
                "name" => "house_number",
                "placeholder" => "Enter House Number",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required"],
            ],
            [
                "element_type" => "input",
                "input_type" => "text",
                "label" => "Quadrant",
                "name" => "quadrant",
                "placeholder" => "NW",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => [],
            ],
            [
                "element_type" => "input",
                "input_type" => "text",
                "label" => "Street Name",
                "name" => "street_name",
                "placeholder" => "Enter Street Name",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required"],
            ],
            [
                "element_type" => "input",
                "input_type" => "text",
                "label" => "Street Type",
                "name" => "street_type",
                "placeholder" => "DR",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => [],
            ],
            [
                "element_type" => "input",
                "input_type" => "text",
                "label" => "City",
                "name" => "city",
                "placeholder" => "DURHAM",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required"],
            ],
            [
                "element_type" => "input",
                "input_type" => "text",
                "label" => "State",
                "name" => "state",
                "placeholder" => "NC",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required"],
            ],
            [
                "element_type" => "input",
                "input_type" => "number",
                "label" => "Zip",
                "name" => "zip",
                "placeholder" => "Enter Zip",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required"],
            ],


            [
                "label" => "Address",
                "element_type" => "textarea",
                "name" => "address",
                "editor" => 0,
                "rows" => 3,
                "placeholder" => "Please Address",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => [],
            ],

            [
                "label" => "Metadata",
                "element_type" => "textarea",
                "name" => "meta",
                "editor" => 0,
                "rows" => 5,
                "placeholder" => "Please Enter Metadata",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => [],
            ],

        ];

        $progress = MainModel::getProgress();

        $this->progress = $progress;
        $this->input_elements = $input_elements;
    }

    public $folder_name = 'customer'; // For view routes and file calling and saving
    public $module_name = 'Customer'; // For toast And page header
    public $input_elements;
    public $progress;


    public function fetch(Request $request)
    {
        $user = Auth::user();
        $query = MainModel::where('is_complete', 0)
            ->where('in_rework', 0)
            ->with(['agent', 'reports', 'recordings']);


        if ($user->role_id == User::AGENT_ROLE) {
            $query->where('agent_id', $user->id);
        }
        if ($user->role_id == User::CLOSER_ROLE || $user->role_id == User::TEAM_LEAD_ROLE) {
            $query->where('closer_id', $user->id);
            $query->OrWhere('to_person_id', $user->id);
        }

        if ($request->agent != '') {
            $query = $query->where('agent_id', $request->agent);
        }
        if ($request->progress != '') {
            $query = $query->whereIn('progress', $request->progress);
        }
        if ($request->status != '') {
            $query = $query->where('status', $request->status);
        }
        if ($request->first_name != '') {
            $query = $query->where('first_name', 'like', '%' . $request->first_name . '%');
        }
        if ($request->last_name != '') {
            $query = $query->where('last_name', 'like', '%' . $request->last_name . '%');
        }
        if ($request->house_number != '') {
            $query = $query->where('house_number', 'like', '%' . $request->house_number . '%');
        }
        if ($request->state != '') {
            $query = $query->where('state', 'like', '%' . $request->state . '%');
        }
        if ($request->phone != '') {
            // Customer::where(function ($query) use ($number) {
            //     $query->where('phone', 'LIKE', '%' . $number . '%')
            //         ->orWhere('secondary_phones', 'LIKE', '%' . $number . '%');
            // })
            $query = $query->where('phone', 'like', '%' . $request->phone . '%')
                ->orWhere('secondary_phones', 'LIKE', '%' . $request->phone . '%')
                ->orWhereHas('phones', function ($q) use ($request) {
                    $q->where('phone_number', 'like', '%' . $request->phone . '%');
                });
        }

        if ($request->start_date != '' && $request->end_date != '') {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        } elseif ($request->start_date != '') {
            $query->where('created_at', '>=', $request->start_date);
        } elseif ($request->end_date != '') {
            $query->where('created_at', '<=', $request->end_date);
        }

        $query = $query->orderBy('id', 'desc');

        if (Auth::user()->can('view-same-mid-customers')) {
            $query = null;
        }

        return Datatables::of($query)->make(true);
    }

    public function view(Request $request)
    {
        // $customer = MainModel::find(1);
        // dd($customer);
        if ($request->isMethod('post')) {
            foreach ($request->sequence as $key => $id) {
                $sequence = $key + 1;
                MainModel::where('id', $id)->update(['sequence' => $sequence]);
            }
            return back();
        } else {
            if (auth()->user()->cannot('access-customers')) {
                return redirect(route('admin.home'));
            }
            $agents = User::where([
                'role_id' => User::AGENT_ROLE
            ])->orderBy('name')->get();

            $data['is_agent'] =  Auth::user()->role_id == User::AGENT_ROLE;
            $data['progress'] =  $this->progress;
            $data['agents'] =  $agents;
            $data['CARD_VIEWED'] = CustomerLogs::CARD_VIEWED;
            $data['META_VIEWED'] = CustomerLogs::META_VIEWED;
            $data['RECORDING_PLAYED'] = CustomerLogs::RECORDING_PLAYED;
            $data['folder_name'] = $this->folder_name;
            $data['module_name'] = $this->module_name;
            $file = "admin." . $this->folder_name . ".view";
            return view($file, $data);
        }
    }

    public function form(Request $request, $id = null)
    {
        $request->validate(MainModel::getValidationRules($id));
        // $sequence = MainModel::count();
        // $sequence = $sequence + 1;


        $data = [
            'lead_id' => $request->lead_id,
            'closer_id' => $request->closer_id,
            'to_person_id' => $request->to_person_id,
            'first_name' => $request->first_name,
            'middle_initial' => $request->middle_initial,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'ssn' => $request->ssn,
            'dob' => $request->dob,
            'age' => $request->age,
            'mmn' => $request->mmn,
            'house_number' => null,
            // 'quadrant' => $request->quadrant,
            'street_name' => $request->street_name,
            'street_type' => null,
            'city' => $request->city,
            'state' => $request->state,
            'zip' => $request->zip,
            // 'address' => $request->address,
            'meta' => $request->meta,
            // 'secondary_phones' => preg_replace("/[^0-9,]/", "", $request->secondary_phones),
            // 'status' => $request->status,
            'progress' => $request->progress,

            'score' => $request->score,
            'no_of_oc' => $request->no_of_oc,
            'no_of_ac' => $request->no_of_ac,
            'td' => $request->td,
            'ta' => $request->ta,
            'd_to_ir' => $request->d_to_ir,
            'charge' => $request->charge,
        ];

        if ($request->hasFile('source')) {
            $data['source'] = $request->source->store($this->folder_name, Helper::STATIC_ASSET_DISK);
        }

        if ($id) {
            $current_state = MainModel::find($id);
            $current_state->phones;

            if ($request->agent_id) {
                $agent = User::find($request->agent_id);
                if ($agent && isset($agent->office)) {
                    $office_id = $agent->office->id;
                }

                $data['agent_id'] = $request->agent_id;
                $data['office_id'] = $office_id ?? 0;
            }


            $changes = [];
            foreach ($data as $key => $value) {
                if ($key != "phone" && $current_state->$key != $value) {
                    $changes[$key] = [
                        'old' => $current_state->$key,
                        'new' => $value
                    ];
                }
            }

            if (count($changes) > 0) {
                MainModel::where('id', $id)->update($data);
                CustomerLogs::createLog($id, CustomerLogs::CUSTOMER_INFO_UPDATED, null, null, json_encode($changes));
            }


            $current_addresses = $current_state->addresses->toArray();
            $new_addresses = $request->addresses ?? [];

            $address_changes = $this->compareAddresses($current_addresses, $new_addresses);
            // dd($address_changes);

            if (count($address_changes)) {
                CustomerAddress::where('customer_id', $id)->delete();
                if ($request->addresses && count($request->addresses) > 0) {
                    foreach ($request->addresses as $address) {
                        $secondary_address = [
                            'customer_id' => $id,
                            'added_by ' => Auth::user()->id,
                            // 'house_number' => $address['house_number'],
                            // 'quadrant' => $address['quadrant'],
                            'street_name' => $address['street_name'],
                            // 'street_type' => $address['street_type'],
                            'city' => $address['city'],
                            'state' => $address['state'],
                            'zip' => $address['zip'],
                            // 'address' => $address['address'],
                        ];
                        CustomerAddress::create($secondary_address);
                    }
                    CustomerLogs::createLog($id, CustomerLogs::CUSTOMER_ADDRESSES_UPDATED, null, null, json_encode($address_changes));
                }
            }

            $current_accounts = $current_state->accounts->toArray(); // Assuming $current_state has a relation 'accounts'
            $new_accounts = $request->accounts ?? [];
            $account_changes = $this->compareAccounts($current_accounts, $new_accounts);

            // dd($account_changes);

            if (!empty($account_changes)) {
                CustomerAccounts::where('customer_id', $id)->delete();

                if ($request->accounts && count($request->accounts) > 0) {
                    foreach ($request->accounts as $account) {
                        $customer_accounts = [
                            'customer_id' => $id,
                            'added_by ' => Auth::user()->id,
                            'noc' => $account['noc'],
                            'account_name' => $account['account_name'],
                            'toll_free' => $account['toll_free'],
                            'exp' => $account['exp'],
                            'account_number' => $account['account_number'],
                            'cvv1' => $account['cvv1'],
                            'cvv2' => $account['cvv2'],
                            'balance' => $account['balance'],
                            'available' => $account['available'],
                            'lp' => $account['lp'],
                            'dp' => $account['dp'],
                            'apr' => $account['apr'],
                            'poa' => $account['poa'],
                            'full_name' => $account['full_name'],
                            // 'address' => $account['address'],
                            'ssn' => $account['ssn'],
                            'mmm' => $account['mmm'],
                            'dob' => $account['dob'],
                            'relation' => $account['relation'],
                            'charge_card' => isset($account['charge_card']) ? true : false,
                            'charge' => $account['charge'],
                        ];
                        CustomerAccounts::create($customer_accounts);
                    }
                }

                CustomerLogs::createLog($id, CustomerLogs::CUSTOMER_CARDS_UPDATED, null, null, json_encode($account_changes));
            }

            if (true) {




                CustomerPhones::where('customer_id', $id)->delete();

                if ($request->secondary_phone_numbers && count($request->secondary_phone_numbers) > 0) {
                    foreach ($request->secondary_phone_numbers as $secondary_phone_number) {
                        $secondary_phone_numbers = [
                            'customer_id' => $id,
                            'added_by ' => Auth::user()->id,
                            'phone_number' => $secondary_phone_number,
                        ];
                        CustomerPhones::create($secondary_phone_numbers);
                    }
                }


                // if ($current_state->progress != $request->progress) {
                //     CustomerLogs::createLog($id, CustomerLogs::PROGRESS_UPDATED, $request->progress);
                // }


                // DETECT IF PHONE NUMBERS HAVE CHANGED
                // Initialize $request_phones with an empty array if $request->phone is null
                $request_phones = [];
                if (!is_null($request->phone)) {
                    $request_phones[] = $request->phone;
                }

                // Check if $request->secondary_phone_numbers is null or not an array
                if (!is_null($request->secondary_phone_numbers) && is_array($request->secondary_phone_numbers)) {
                    $request_phones = array_merge($request_phones, $request->secondary_phone_numbers);
                }

                // Initialize $current_phones with an empty array if $current_state->phone is null
                $current_phones = [];
                if (!is_null($current_state->phone)) {
                    $current_phones[] = $current_state->phone;
                }

                // Check if $current_state->phones is null or not an instance of the expected collection
                if (!is_null($current_state->phones) && $current_state->phones instanceof \Illuminate\Support\Collection) {
                    $current_phones = array_merge($current_phones, $current_state->phones->pluck('phone_number')->toArray());
                }

                // Remove null values
                $request_phones = array_filter($request_phones);
                $current_phones = array_filter($current_phones);

                // Sort arrays
                sort($request_phones);
                sort($current_phones);

                // Compare arrays
                $has_changes = $request_phones !== $current_phones;

                if ($has_changes) {
                    CustomerPhoneModificationLogs::create([
                        'customer_id' => $id,
                        'action_by ' => Auth::user()->id,
                        'before' => json_encode($current_phones),
                        'after' => json_encode($request_phones),
                    ]);
                    event(new PhoneModified(json_encode($current_phones), json_encode($request_phones)));
                }
                Helper::toast('success', $this->module_name . ' Updated.');
            }
        } else {
            if (Auth::user()->role_id != User::AGENT_ROLE) {
                // Find the agent and check if the agent and office exist
                $agent = User::find($request->agent_id);
                if ($agent && isset($agent->office)) {
                    $office_id = $agent->office->id;
                }
            } else {
                // Check if the authenticated user has an office
                if (isset(Auth::user()->office_id)) {
                    $office_id = Auth::user()->office_id;
                }
            }

            $data['agent_id'] = Auth::user()->role_id != User::AGENT_ROLE ? $request->agent_id : Auth::user()->id;
            $data['office_id'] = $office_id ?? 0;

            if ($created_data = MainModel::create($data)) {

                if ($request->addresses && count($request->addresses) > 0) {
                    foreach ($request->addresses as $address) {
                        $secondary_address = [
                            'customer_id' => $created_data->id,
                            'added_by ' => Auth::user()->id,
                            // 'house_number' => $address['house_number'],
                            // 'quadrant' => $address['quadrant'],
                            'street_name' => $address['street_name'],
                            // 'street_type' => $address['street_type'],
                            'city' => $address['city'],
                            'state' => $address['state'],
                            'zip' => $address['zip'],
                            // 'address' => $address['address'],
                        ];
                        CustomerAddress::create($secondary_address);
                    }
                }

                if ($request->accounts && count($request->accounts) > 0) {
                    foreach ($request->accounts as $account) {
                        $customer_accounts = [
                            'customer_id' => $created_data->id,
                            'added_by ' => Auth::user()->id,
                            'noc' => $account['noc'],
                            'account_name' => $account['account_name'],
                            'toll_free' => $account['toll_free'],
                            'exp' => $account['exp'],
                            'account_number' => $account['account_number'],
                            'cvv1' => $account['cvv1'],
                            'cvv2' => $account['cvv2'],
                            'balance' => $account['balance'],
                            'available' => $account['available'],
                            'lp' => $account['lp'],
                            'dp' => $account['dp'],
                            'apr' => $account['apr'],
                            'poa' => $account['poa'],
                            'full_name' => $account['full_name'],
                            'address' => $account['address'],
                            'ssn' => $account['ssn'],
                            'mmm' => $account['mmm'],
                            'dob' => $account['dob'],
                            'relation' => $account['relation'],
                            'charge_card' => isset($account['charge_card']) ? true : false,
                            'charge' => $account['charge'],
                        ];
                        CustomerAccounts::create($customer_accounts);
                    }
                }



                if ($request->secondary_phone_numbers && count($request->secondary_phone_numbers) > 0) {
                    foreach ($request->secondary_phone_numbers as $secondary_phone_number) {
                        $secondary_phone_numbers = [
                            'customer_id' => $created_data->id,
                            'added_by ' => Auth::user()->id,
                            'phone_number' => $secondary_phone_number,
                        ];
                        CustomerPhones::create($secondary_phone_numbers);
                    }
                }

                CustomerLogs::createLog($created_data->id, CustomerLogs::CUSTOMER_CREATED, null, Auth::user()->role_id == User::AGENT_ROLE ? Auth::user()->id : null);
                if (isset($request->lead_id)) {
                    Leadcenter::where('id', $request->lead_id)->update(['is_used' => 1]);
                }
                Helper::toast('success', $this->module_name . ' created.');
            }
        }

        $previousUrl = $request->input('previous_url', route($this->folder_name . '-view'));

        return redirect()->to($previousUrl);
        // return redirect()->route($this->folder_name . '-view');
        return redirect()->back();
    }

    public function add(Request $request)
    {
        session(['previous_url' => url()->previous()]);

        if ($request->isMethod('post')) {
            return $this->form($request);
        } else {
            $data['page_header'] = "Add " . $this->module_name;
            $data['progress'] =  $this->progress;
            $data['result'] = null;
            $data['agents'] = User::where(['role_id' =>  User::AGENT_ROLE, 'status' => 1])->get(['id', 'name']);

            $data['closers'] = User::where(['role_id' =>  User::CLOSER_ROLE, 'status' => 1, 'office_id' => Auth::user()->office_id])->get(['id', 'name']);
            $data['t_o_persons'] = User::where(function ($query) {
                $query->where('role_id', User::CLOSER_ROLE)
                    ->orWhere('role_id', User::TEAM_LEAD_ROLE);
            })->where('status', 1)->where('office_id', Auth::user()->office_id)->get(['id', 'name', 'role_id']);

            $input_elements = $this->input_elements;
            $data['include_status_radio'] = 1;

            $file = "admin." . $this->folder_name . ".form";
            return view($file, $data);
            return view('general_crud.general_view', $data)->with(compact('input_elements'));
        }
    }

    public function edit(Request $request, $id)
    {
        session(['previous_url' => url()->previous()]);

        $id = decrypt($id);
        if ($request->isMethod('post')) {
            return $this->form($request, $id);
        } else {
            $data['page_header'] = "Edit " . $this->module_name;
            $data['progress'] =  $this->progress;

            $customer = MainModel::find($id);

            if (!$customer) {
                return redirect()->back();
            }
            CustomerLogs::createLog($id, CustomerLogs::EDIT_INITIATED);

            $data['agents'] = User::where(['role_id' =>  User::AGENT_ROLE, 'status' => 1])->get(['id', 'name']);


            $data['closers'] = User::where(['role_id' =>  User::CLOSER_ROLE, 'status' => 1, 'office_id' => $customer->office_id])->get(['id', 'name']);
            $data['t_o_persons'] = User::where(function ($query) {
                $query->where('role_id', User::CLOSER_ROLE)
                    ->orWhere('role_id', User::TEAM_LEAD_ROLE);
            })->where('status', 1)->where('office_id', $customer->office_id)->get(['id', 'name', 'role_id']);

            $stateTimezones = [
                // Pacific Time
                'CA' => 'America/Los_Angeles', // California
                'NV' => 'America/Los_Angeles', // Nevada
                'OR' => 'America/Los_Angeles', // Oregon (except some eastern parts)
                'WA' => 'America/Los_Angeles', // Washington
                'AK' => 'America/Anchorage',  // Alaska (Pacific Alaska considered)
            
                // Mountain Time
                'AZ' => 'America/Phoenix', // Arizona (does not observe DST)
                'CO' => 'America/Denver', // Colorado
                'ID' => 'America/Denver', // Idaho (southern part)
                'MT' => 'America/Denver', // Montana
                'NM' => 'America/Denver', // New Mexico
                'UT' => 'America/Denver', // Utah
                'WY' => 'America/Denver', // Wyoming
            
                // Central Time
                'AL' => 'America/Chicago', // Alabama
                'AR' => 'America/Chicago', // Arkansas
                'IA' => 'America/Chicago', // Iowa
                'IL' => 'America/Chicago', // Illinois
                'IN' => 'America/Chicago', // Indiana (some western counties)
                'KS' => 'America/Chicago', // Kansas
                'KY' => 'America/Chicago', // Kentucky (western part)
                'LA' => 'America/Chicago', // Louisiana
                'MN' => 'America/Chicago', // Minnesota
                'MS' => 'America/Chicago', // Mississippi
                'MO' => 'America/Chicago', // Missouri
                'ND' => 'America/Chicago', // North Dakota (eastern part)
                'NE' => 'America/Chicago', // Nebraska (eastern part)
                'OK' => 'America/Chicago', // Oklahoma
                'SD' => 'America/Chicago', // South Dakota (eastern part)
                'TN' => 'America/Chicago', // Tennessee (western part)
                'TX' => 'America/Chicago', // Texas
                'WI' => 'America/Chicago', // Wisconsin
            
                // Eastern Time
                'CT' => 'America/New_York', // Connecticut
                'DE' => 'America/New_York', // Delaware
                'FL' => 'America/New_York', // Florida (eastern part)
                'GA' => 'America/New_York', // Georgia
                'IN' => 'America/New_York', // Indiana (eastern part)
                'KY' => 'America/New_York', // Kentucky (eastern part)
                'ME' => 'America/New_York', // Maine
                'MD' => 'America/New_York', // Maryland
                'MA' => 'America/New_York', // Massachusetts
                'MI' => 'America/New_York', // Michigan
                'NH' => 'America/New_York', // New Hampshire
                'NJ' => 'America/New_York', // New Jersey
                'NY' => 'America/New_York', // New York
                'NC' => 'America/New_York', // North Carolina
                'OH' => 'America/New_York', // Ohio
                'PA' => 'America/New_York', // Pennsylvania
                'RI' => 'America/New_York', // Rhode Island
                'SC' => 'America/New_York', // South Carolina
                'VT' => 'America/New_York', // Vermont
                'VA' => 'America/New_York', // Virginia
                'WV' => 'America/New_York', // West Virginia
            ];
            

            $state = $customer->state; // Get the state or default to UTC
            $timezone = $stateTimezones[$state] ?? 'UTC'; // Map state to timezone
            $formattedTime = Carbon::now($timezone)->format('h:i:s A'); // Get current time in state timezone


            $data['result'] = $customer;
            $data['formattedTime'] = $formattedTime;
            $data['include_status_radio'] = 1;
            $input_elements = $this->input_elements;

            $file = "admin." . $this->folder_name . ".form";
            return view($file, $data);
            return view('general_crud.general_view', $data)->with(compact('input_elements'));
        }
    }



    public function markPhoneAsPrimary(Request $request)
    {
        try {
            CustomerPhones::where("id", $request->phone_id)->update([
                'is_primary' => !$request->is_primary
            ]);
            return [
                "status" => true,
                "message" => "success",
                "data" => null
            ];
        } catch (Exception $e) {
            return [
                "status" => false,
                // "message" => "Something went wrong",
                "message" => $e->getMessage(),
                "data" => null
            ];
        }
    }

    function profile(Request $request, $customer_id)
    {
        $customer_id = decrypt($customer_id);
        $customer = MainModel::find($customer_id);

        if (!$customer) {
            return redirect()->back();
        }
        CustomerLogs::createLog($customer_id, CustomerLogs::PROFILE_VIEWED);

        $reports = $customer->reports;
        $score = "Not Available";
        if (count($reports)) {
            foreach ($reports as $key => $report) {
                if (isset($report->report['data']['models'][0]['score'])) {
                    $score = $report->report['data']['models'][0]['score'];
                    break;
                }
            }
        }


        $data['folder_name'] = $this->folder_name;
        $data['module_name'] = $this->module_name;
        $data['result'] = [];
        $data['customer'] = $customer;
        $data['CARD_VIEWED'] = CustomerLogs::CARD_VIEWED;
        $data['META_VIEWED'] = CustomerLogs::META_VIEWED;
        $data['RECORDING_PLAYED'] = CustomerLogs::RECORDING_PLAYED;
        $data['score'] = $score;
        $file = "admin." . $this->folder_name . ".profile";
        return view($file, $data);
    }

    function fetchCompleted(Request $request)
    {
        if (Auth::user()->can('view-same-mid-customers')) {
            $completed_customers = MainModel::where('is_complete', true)
                ->where('sale_status', "!=", "Approved")
                ->where('sale_status', "!=", "Charged")
                ->where('m_id', Auth::user()->m_id)
                ->with(['agent', 'MId', 'rna_specialist', 'cb_specialist', 'decline_specialist']);
        } else {
            $completed_customers = MainModel::where('is_complete', true)->with(['agent', 'MId', 'rna_specialist', 'cb_specialist', 'decline_specialist'])->orderBy('id', 'DESC');
        }

        if ($request->office) {
            $completed_customers->where('office_id', $request->office);
        }

        return Datatables::of($completed_customers)->make(true);
    }

    function completed(Request $request)
    {
        // if (Auth::user()->can('view-same-mid-customers')) {
        //     $completed_customers = MainModel::where('is_complete', true)
        //         ->where('m_id', Auth::user()->m_id)
        //         ->get();
        // } else {
        //     $completed_customers = MainModel::where('is_complete', true)->get();
        // }
        // dd($completed_customers);
        $data['is_agent'] = Auth::user()->role_id == User::AGENT_ROLE;
        $data['offices'] = Offices::where('status', true)->get();
        $data['folder_name'] = $this->folder_name;
        $data['module_name'] = $this->module_name;
        // $data['result'] = $completed_customers;
        $file = "admin." . $this->folder_name . ".completed_customers";
        return view($file, $data);
    }

    function metadata(Request $request)
    {
        if (MainModel::where('id', $request->customer_id)->update(['meta' => $request->meta])) {
            Helper::toast('success', 'Customer Metadata Updated.');
        } else {
            Helper::toast('error', 'Something went wrong.');
        }
        return back();
    }

    function complete(Request $request)
    {
        if (MainModel::where('id', $request->customer_id)->update([
            'is_complete' => true,
            'm_id' => $request->m_id,
            'completed_on' => now(),
        ])) {

            CustomerLogs::createLog($request->customer_id, CustomerLogs::SUBMITTED, null, $request->m_id);

            $customer = Customer::find($request->customer_id);
            $fcm = new FcmHelper;
            $user_ids = User::whereIn('role_id', [User::SUPERADMIN_ROLE, User::MANAGER_ROLE])
                ->where('status', true)
                ->pluck('id')
                ->toArray();
            PushNotifications::dispatch($user_ids, Auth::user()->name . " Completed Customer " . $customer->first_name . " " . $customer->middle_initial . " " . $customer->last_name, "New customer is marked as submitted.", "customer_profile", $request->customer_id);
            // $fcm->push($tokens, Auth::user()->name . " Completed Customer " . $customer->first_name . " " . $customer->middle_initial . " " . $customer->last_name, "New customer is marked as submitted.", "customer_profile", $request->customer_id);
            $fcm->create(0, User::SUPERADMIN_ROLE, Auth::user()->name . " Completed Customer " . $customer->first_name . " " . $customer->middle_initial . " " . $customer->last_name, "New customer is marked as submitted.", "customer_profile", $request->customer_id);
            $fcm->create(0, User::MANAGER_ROLE, Auth::user()->name . " Completed Customer " . $customer->first_name . " " . $customer->middle_initial . " " . $customer->last_name, "New customer is marked as submitted.", "customer_profile", $request->customer_id);
            Helper::toast('success', 'Successfully marked as submitted.');
        } else {
            Helper::toast('error', "Something Went Wrong");
        }
        return back();
    }

    function incomplete(Request $request, $customer_id)
    {
        if (MainModel::where('id', $customer_id)->update(['is_complete' => false])) {

            CustomerLogs::createLog($customer_id, CustomerLogs::MARKED_INCOMPLETE);

            Helper::toast('success', ' Successfully marked as incomplete.');
        } else {
            Helper::toast('error', "Something Went Wrong");
        }
        return back();
    }

    function upload(Request $request)
    {
        if (!isset($request->file)) {
            Helper::toast('error', "Please Upload File");
            return back();
        }
        try {
            $file = request()->file('file');
            ImportCustomersCSV::dispatch($file);
            Helper::toast('success', ' Started process, will be finished shortly.');
        } catch (\Exception $e) {
            Helper::toast('error', ' Something Went Wrong.' . $e->getMessage());
        };
        return back();
    }

    function download(Request $request, $customer_id)
    {
        try {
            $customer = MainModel::find($customer_id);

            // dd($customer);
            $content = "";

            if (!empty($customer->first_name)) $content .= "First name : " . $customer->first_name . "\n";
            if (!empty($customer->last_name)) $content .= "Last name : " . $customer->last_name . "\n";
            if (!empty($customer->phone)) $content .= "Phone : " . $customer->phone . "\n";

            // if (!empty($customer->secondary_phones)) {
            $content .= "Secondary Phones : " . $customer->secondary_phones . "\n";
            foreach ($customer->phones->where('is_primary', '1') as $key => $phone) {
                if (!empty($phone->phone_number)) $content .= $phone->phone_number . "\n";
            }
            // }

            if (!empty($customer->house_number)) $content .= "House number : " . $customer->house_number . "\n";
            if (!empty($customer->street_name)) $content .= "Street name : " . $customer->street_name . "\n";
            if (!empty($customer->street_type)) $content .= "Street type : " . $customer->street_type . "\n";
            if (!empty($customer->city)) $content .= "City : " . $customer->city . "\n";
            if (!empty($customer->state)) $content .= "State : " . $customer->state . "\n";
            if (!empty($customer->zip)) $content .= "Zip : " . $customer->zip . "\n";

            if (!empty($customer->addresses)) {
                $content .= "\nSecondary Addresses : \n";
                foreach ($customer->addresses as $key => $address) {
                    if (!empty($address->house_number)) $content .= "House number : " . $address->house_number . "\n";
                    if (!empty($address->street_name)) $content .= "Street name : " . $address->street_name . "\n";
                    if (!empty($address->street_type)) $content .= "Street type : " . $address->street_type . "\n";
                    if (!empty($address->city)) $content .= "City : " . $address->city . "\n";
                    if (!empty($address->state)) $content .= "State : " . $address->state . "\n";
                    if (!empty($address->zip)) $content .= "Zip : " . $address->zip . "\n";
                }
                $content .= "\n";
            }

            if (!empty($customer->ssn)) $content .= "Social security : " . $customer->ssn . "\n";
            if (!empty($customer->dob)) $content .= "Date of birth : " . Carbon::parse($customer->dob)->isoFormat('MM/DD/YYYY') . "\n";
            if (!empty($customer->mmn)) $content .= "MMN : " . $customer->mmn . "\n";
            if (!empty($customer->email)) $content .= "Email : " . $customer->email . "\n";


            if (!empty($customer->accounts)) {
                $content .= "\nBANKING: \n";
                foreach ($customer->accounts->sortByDesc('charge_card') as $key => $card) {
                    if (!empty($card->charge)) $content .= "Charge on this card : " . $card->charge . "\n";
                    if (isset($card->charge_card)) $content .= "Charge Card : " . ($card->charge_card ? 'Yes' : 'No') . "\n";
                    if (!empty($card->noc)) $content .= "NOC : " . $card->noc . "\n";
                    if (!empty($card->account_name)) $content .= "Bank Name : " . $card->account_name . "\n";
                    if (!empty($card->exp)) $content .= "Exp : " . $card->exp . "\n";
                    if (!empty($card->account_number)) $content .= "Card # : " . preg_replace('/(\d{4})(?=\d)/', '$1-', $card->account_number) . "\n";
                    if (!empty($card->cvv1)) $content .= "CVV/CVV (First CVV) : " . $card->cvv1 . "\n";
                    if (!empty($card->balance)) $content .= "Balance : " . $card->balance . "\n";
                    if (!empty($card->available)) $content .= "Available : " . $card->available . "\n";
                    if (!empty($card->lp)) $content .= "LP : " . $card->lp . "\n";
                    if (!empty($card->dp)) $content .= "DP : " . $card->dp . "\n";
                    if (!empty($card->apr)) $content .= "APR% : " . $card->apr . "\n";
                    if (!empty($card->poa)) $content .= "POA : " . $card->poa . "\n";
                    if (!empty($card->full_name)) $content .= "Full Name : " . $card->full_name . "\n";
                    if (!empty($card->ssn)) $content .= "SSN : " . $card->ssn . "\n";
                    if (!empty($card->mmm)) $content .= "Mmm : " . $card->mmm . "\n";
                    if (!empty($card->dob)) $content .= "DOB : " . $card->dob . "\n";
                    if (!empty($card->relation)) $content .= "Relation : " . $card->relation . "\n";

                    // if (!empty($card->toll_free)) $content .= "Toll Free : " . $card->toll_free . "\n";
                    // if (!empty($card->cvv2)) $content .= "CVV/CVV (Second CVV) : " . $card->cvv2 . "\n";

                    $content .= "\n";
                }
            }


            if (!empty($customer->no_of_oc)) $content .= "Total Cards : " . $customer->no_of_oc . "\n";
            if (!empty($customer->td)) $content .= "Total Debt : " . $customer->td . "\n";
            if (!empty($customer->charge)) $content .= "Total Charge : " . $customer->charge . "\n";


            if (!empty($customer->agent)) $content .= "Agent  : " . $customer->agent->name . "\n";
            if (!empty($customer->to_person)) $content .= "TO  : " . $customer->to_person->name . "\n";
            if (!empty($customer->closer)) $content .= "CLOSER  : " . $customer->closer->name . "\n";


            if (!empty($customer->meta)) $content .= "\nMetaData: \n" . $customer->meta . "\n";




            // if (!empty($customer->no_of_ac)) $content .= "No. of Accounts : " . $customer->no_of_ac . "\n";
            // if (!empty($customer->ta)) $content .= "Total Available : " . $customer->ta . "\n";
            // if (!empty($customer->d_to_ir)) $content .= "Debt to Income Ratio % : " . $customer->d_to_ir . "\n";
            // if (!empty($customer->progress)) $content .= "Progress : " . $customer->progress . "\n";
            // if (!empty($customer->meta)) $content .= "\nMetadata \n" . $customer->meta;
            // if (!empty($customer->score)) $content .= "Score : " . $customer->score . "\n";
            // $content .= ("Comments : " . $customer->comments) . "\n";

            $response = \Response::make($content);

            // Set the appropriate headers for a TXT file
            $response->header('Content-Type', 'text/plain');
            $response->header('Content-Disposition', 'attachment; filename="' . $customer->first_name . ' ' . $customer->last_name . '.txt"');

            CustomerLogs::createLog($customer->id, CustomerLogs::DOWNLOADED);

            return $response;
        } catch (\Exception $e) {
            Helper::toast('error', ' Something Went Wrong.' . $e->getMessage());
        };
        return back();
    }

    function downloadInCsv(Request $request)
    {
        try {
            $filename = "batch-" . Carbon::now()->format('m-d-Y_H-i_A') . ".csv";
            Excel::queue(new CustomerExport($request->customers), 'public/customer_exports/' . $filename);

            CustomerExports::create([
                'path' => 'customer_exports/' . $filename,
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
            // $customer = MainModel::find($request->customers[0]);
            // dd($customer->phones);

            ProcessCustomerExportInTxt::dispatch($request->customers);

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

    function getLead(Request $request)
    {
        try {
            if (Auth::user()->role_id == User::AGENT_ROLE) {
                $lead =   Leadcenter::where([
                    'agent_id' => Auth::user()->id,
                    'is_used' => 0,
                ])->first();
            } else {
                $lead =  Leadcenter::where([
                    'agent_id' => 0,
                    'is_used' => 0,
                ])->first();
            }
            if ($lead) {
                auth()->user()->last_lead_used_at = now()->format('Y-m-d H:i:s');
                auth()->user()->save();

                $lead->phone = $lead->phone ? json_decode($lead->phone) : $lead->phone;
                return [
                    'status' => true,
                    'data' => $lead,
                    'message' => "Success",
                ];
            } else {
                return [
                    'status' => false,
                    'data' => null,
                    'message' => "No lead found",
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => "Something went wrong " . $e->getMessage(),
            ];
        }
    }

    function moveToRework(Request $request)
    {
        try {
            foreach ($request->customers as $customer_id) {
                MainModel::where("id", $customer_id)->update([
                    'in_rework' => 1
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

    function exportAsRcl(Request $request)
    {
        try {
            $filename = "RCL-batch-" . Carbon::now()->format('m-d-Y_H-i_A') . ".csv";
            Excel::queue(new RCLExport($request->customers), 'public/customer_exports_as_rcl/' . $filename);

            CustomerExports::create([
                'path' => 'customer_exports_as_rcl/' . $filename,
                'filename' => $filename,
            ]);

            return [
                'status' => true,
                "message" => 'Downloading Started. This will be visible shortly in Customer Exports.'
            ];

            // foreach ($request->customers as $customer_id) {
            //     // MainModel::where("id", $customer_id)->update([
            //     //     'in_rework' => 1
            //     // ]);
            // }
            // return [
            //     "status" => true,
            //     "message" => "success",
            //     "data" => null
            // ];
        } catch (Exception $e) {
            return [
                "status" => false,
                "message" => "Something went wrong",
                "data" => null
            ];
        }
    }



    function assignCustomerToAgent(Request $request)
    {
        try {
            $agent = User::find($request->agent);
            $office_id = 0;
            if ($agent && $agent->office_id) {
                $office_id = $agent->office_id;
            }
            foreach ($request->customers as $customer_id) {
                MainModel::where("id", $customer_id)->update([
                    'agent_id' => $request->agent,
                    'office_id' => $office_id,
                ]);
            }
            return [
                "status" => true,
                "message" => "success",
                "data" => null
            ];

            // $num_leads = count($request->leads);
            // $num_agents = count($request->agents);

            // if ($num_leads <  $num_agents) {
            //     return [
            //         "status" => false,
            //         "message" => "Not enough leads, please assign manually.",
            //         "data" => null
            //     ];
            // }

            // // Calculate leads per agent and remainder
            // $leads_per_agent = intdiv($num_leads, $num_agents);
            // $remainder = $num_leads % $num_agents;

            // $lead_index = 0;

            // foreach ($request->agents as $agent_id) {
            //     $num_leads_for_this_agent = $leads_per_agent + ($remainder > 0 ? 1 : 0);
            //     $remainder--;

            //     for ($i = 0; $i < $num_leads_for_this_agent; $i++) {
            //         MainModel::where("id", $request->leads[$lead_index])->update([
            //             'agent_id' => $agent_id
            //         ]);
            //         $lead_index++;
            //     }
            // }

            // return [
            //     "status" => true,
            //     "message" => "Leads have been successfully distributed",
            //     "data" => null
            // ];
        } catch (Exception $e) {
            return [
                "status" => false,
                "message" => "Something went wrong",
                "data" => null
            ];
        }
    }


    function fetchInRework(Request $request)
    {
        $query = MainModel::where('in_rework', true)
            ->with('agent')
            ->orderBy('id', 'desc');

        return Datatables::of($query)->make(true);
    }

    function inRework(Request $request)
    {
        // $in_rework_customers = MainModel::where('in_rework', true)->limit(10)->get();

        $data['folder_name'] = $this->folder_name;
        $data['module_name'] = $this->module_name;
        $data['result'] = [];
        $file = "admin." . $this->folder_name . ".in_rework_customers";
        return view($file, $data);
    }

    public function play($foldername, $filename)
    {
        $filepath = $foldername . "/" . $filename;
        // dd($filepath);
        // dd($foldername, $filename);

        $disk = Storage::disk('sftp');

        if (!$disk->exists($filepath)) {
            return "exists";
            // abort(404);
        } else {
            return "NOT exists";
        }

        $stream = $disk->readStream($filepath);

        return new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => 'audio/mpeg',
            'Content-Length' => $disk->size($filepath),
            'Content-Disposition' => 'inline',
        ]);
    }



    protected function compareAddresses(array $current_addresses, array $new_addresses)
    {
        $changes = [];

        // Compare old and new addresses
        foreach ($current_addresses as $index => $current_address) {
            if (isset($new_addresses[$index])) {
                $new_address = $new_addresses[$index];

                // Ensure that the new address has the expected structure before comparing
                foreach ($current_address as $key => $value) {
                    if (array_key_exists($key, $new_address) && $new_address[$key] != $value) {
                        $changes[$index][$key] = [
                            'old' => $value,
                            'new' => $new_address[$key],
                        ];
                    }
                }
            } else {
                // Current address removed
                $changes[$index] = [
                    'removed' => $current_address
                ];
            }
        }

        // Check for new addresses added
        for ($i = count($current_addresses); $i < count($new_addresses); $i++) {
            if (!empty($new_addresses[$i])) { // Check if the address exists
                $changes[$i] = [
                    'added' => $new_addresses[$i]
                ];
            }
        }

        return $changes;
    }

    protected function compareAccounts(array $current_accounts, array $new_accounts)
    {
        $changes = [];

        foreach ($current_accounts as $index => $current_account) {
            if (isset($new_accounts[$index])) {
                $new_account = $new_accounts[$index];

                foreach ($current_account as $key => $value) {
                    if (array_key_exists($key, $new_account)) {
                        if ($key === 'charge_card') {
                            // Normalize both values to booleans before comparing
                            $old_value = (bool) $value;
                            $new_value = (bool) $new_account[$key];

                            if ($old_value !== $new_value) {
                                $changes[$index][$key] = [
                                    'old' => $old_value,
                                    'new' => $new_value,
                                ];
                            }
                        } else if ($new_account[$key] != $value) {
                            // General comparison for other fields
                            $changes[$index][$key] = [
                                'old' => $value,
                                'new' => $new_account[$key],
                            ];
                        }
                    }
                }
            } else {
                // Account was removed
                $changes[$index] = [
                    'removed' => $current_account,
                ];
            }
        }

        // Check for newly added accounts
        for ($i = count($current_accounts); $i < count($new_accounts); $i++) {
            if (!empty($new_accounts[$i])) {
                $changes[$i] = [
                    'added' => $new_accounts[$i],
                ];
            }
        }

        return $changes;
    }
}
