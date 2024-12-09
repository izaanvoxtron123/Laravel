<?php

namespace App\Http\Controllers\Admin;

use App\Http\Common\FcmHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReportRequest as MainModel;
use App\Models\Report;
use App\Http\Common\Helper;
use App\Jobs\PushNotifications;
use App\Models\Customer;
use App\Models\CustomerLogs;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Yajra\Datatables\Datatables;

class ReportRequestController extends Controller
{
    public function __construct(array $attributes = array())
    {
        $input_elements = [
            [
                "label" => "Report Type",
                "element_type" => "dropdown",
                "name" => "type",
                "options" => [
                    [
                        "type" => "Equifax Prequal Vantage 4",
                    ],
                    [
                        "type" => "Equifax FICO 9",
                    ],
                ],
                "value_element" => "type",
                "label_element" => "type",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required"],
            ],
            // [
            //     "label" => "Customer",
            //     "element_type" => "dropdown",
            //     "name" => "customer_id",
            //     "options" => Customer::where('status', 1)->where('agent_id', auth()->id)->get(),
            //     "value_element" => "id",
            //     "label_element" => "first_name",
            //     "additional_ids" => [],
            //     "additional_classes" => [],
            //     "html_params" => ["required" => "required"],
            // ],
            [
                "label" => "Manager",
                "element_type" => "dropdown",
                "name" => "manager_id",
                "options" => User::where(['status' => 1, 'role_id'  => User::MANAGER_ROLE])->get(),
                "value_element" => "id",
                "label_element" => "name",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => [],
            ],
            [
                "label" => "Priority",
                "element_type" => "dropdown",
                "name" => "priority",
                "options" => [
                    [
                        "priority" => "default",
                        "label" => "Default",
                    ],
                    [
                        "priority" => "high",
                        "label" => "High",
                    ],
                ],
                "value_element" => "priority",
                "label_element" => "label",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required"],
            ],

            // [
            //     "label" => "Approval Status",
            //     "element_type" => "dropdown",
            //     "name" => "approval_status",
            //     "options" => [
            //         [
            //             "approval_status_label" => "Pending",
            //             "approval_status" => "pending",
            //         ],
            //         [
            //             "approval_status_label" => "Approved",
            //             "approval_status" => "approved",
            //         ],
            //         [
            //             "approval_status_label" => "Declined",
            //             "approval_status" => "declined",
            //         ],
            //     ],
            //     "value_element" => "approval_status",
            //     "label_element" => "approval_status_label",
            //     "additional_ids" => [],
            //     "additional_classes" => [],
            //     "html_params" => ["required" => "required"],
            // ],

        ];

        $this->input_elements = $input_elements;
    }

    public $folder_name = 'report_request'; // For view routes and file calling and saving
    public $module_name = 'Report Request'; // For toast And page header
    public $input_elements;



    function fetch(Request $request)
    {
        $user = Auth::user();

        $query = "";
        if ($user->can("view-all-report-requests")) {
            $query = MainModel::orderBy('id', 'desc');
        } elseif ($user->can("view-tagged-in-report-requests")) {
            $query = MainModel::where([
                'manager_id' => $user->id
            ])->orderBy('id', 'desc');
        } elseif ($user->can("view-requested-report-requests")) {
            $query = MainModel::where([
                'agent_id' => $user->id,
                'progress' => 'pending',
            ])->orderBy('id', 'desc');
        }


        if ($request->first_name != '') {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->first_name . '%'); // Allows partial match
            });
        }

        if ($request->last_name != '') {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('last_name', 'like', '%' . $request->last_name . '%'); // Allows partial match
            });
        }


        if ($request->requested_on != '') {
            $query->whereDate('created_at', $request->requested_on);
        }
        // if ($request->filled('search.value')) {
        //     $search = $request->input('search.value');
        //     $query->whereHas('customer', function($q) use ($search) {
        //         $q->where('first_name', 'like', '%' . $search . '%')
        //           ->orWhere('last_name', 'like', '%' . $search . '%');
        //     });
        // }


        $query->where("approval_status", "!=", "declined");
        $query->with("customer", "manager", "agent");

        return DataTables::of($query)->make(true);
    }

    public function view(Request $request)
    {
        if ($request->isMethod('post')) {
            foreach ($request->sequence as $key => $id) {
                $sequence = $key + 1;
                MainModel::where('id', $id)->update(['sequence' => $sequence]);
            }
            return back();
        } else {
            // $user = Auth::user();
            // $report_requests = [];
            // if ($user->can("view-all-report-requests")) {
            //     $report_requests = MainModel::orderBy('id', 'desc')->get();
            // } elseif ($user->can("view-tagged-in-report-requests")) {
            //     $report_requests = MainModel::where([
            //         'manager_id' => $user->id
            //     ])->orderBy('id', 'desc')->get();
            // } elseif ($user->can("view-requested-report-requests")) {
            //     $report_requests = MainModel::where([
            //         'agent_id' => $user->id
            //     ])->orderBy('id', 'desc')->get();
            // }

            $data['folder_name'] = $this->folder_name;
            $data['module_name'] = $this->module_name;

            $data['is_agent'] = Auth::user()->role_id == User::AGENT_ROLE;
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
            'manager_id' => $request->manager_id,
            'agent_id' => Auth::user()->id,
            'type' => $request->type,
            'priority' => $request->priority,
            // 'approval_status' => $request->approval_status,
            'status' => $request->status,
        ];
        if ($request->customer_id) {
            $data['customer_id'] = $request->customer_id;
        }
        if ($request->hasFile('source')) {
            $data['source'] = $request->source->store($this->folder_name, Helper::STATIC_ASSET_DISK);
        }
        if ($id) {
            if (MainModel::where('id', $id)->update($data)) {
                Helper::toast('success', $this->module_name . ' Updated.');
            }
        } else {
            $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
            $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

            $latest_report_exists =  MainModel::where('customer_id', $request->customer_id)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->exists();
            if ($latest_report_exists) {
                Helper::toast('error', ' Report Request for the current month already exists, please contact manager.');
                return redirect()->route($this->folder_name . '-view');
            }


            $created_data = MainModel::create($data);
            if ($created_data) {
                CustomerLogs::createLog($request->customer_id, CustomerLogs::REPORT_REQUESTED);


                $fcm = new FcmHelper;
                $user_ids = User::whereIn('role_id', [User::SUPERADMIN_ROLE])
                    ->where('status', true)
                    ->pluck('id')
                    ->toArray();
                $user_ids[] = $request->manager_id;
                PushNotifications::dispatch($user_ids, Auth::user()->name . " Requested Report For " . $created_data->customer->first_name . " " . $created_data->customer->middle_initial . " " . $created_data->customer->last_name, "New report request is waiting for response.", "customer_profile", $created_data->customer_id);
                $fcm->create(0, User::SUPERADMIN_ROLE, Auth::user()->name . " Requested Report For " . $created_data->customer->first_name . " " . $created_data->customer->middle_initial . " " . $created_data->customer->last_name, "New report request is waiting for response.", "customer_profile", $created_data->customer_id);
                $fcm->create($request->manager_id ?? 0, User::MANAGER_ROLE,  Auth::user()->name . " Requested Report For " . $created_data->customer->first_name . " " . $created_data->customer->middle_initial . " " . $created_data->customer->last_name, "New report request is waiting for response.", "customer_profile", $created_data->customer_id);


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
            $customers = Customer::where('status', 1)->where('agent_id', Auth::user()->id)->get();
            // $customers = Customer::limit(10)->get();
            $options = [];
            foreach ($customers as $key => $customer) {
                $options[$key]['id'] = $customer->id;
                $options[$key]['title'] = $customer->first_name . ' ' . $customer->last_name;
            }
            array_push($this->input_elements, [
                "label" => "Customer",
                "element_type" => "dropdown",
                "name" => "customer_id",
                "options" => $options,
                "value_element" => "id",
                "label_element" => "title",
                "additional_ids" => [],
                "additional_classes" => [],
                "additional_classes" => ['select2-single'],
                "html_params" => ["required" => "required", "multiple" => "multiple"],
            ]);

            $data['page_header'] = "Add " . $this->module_name;
            $data['result'] = null;
            $input_elements = $this->input_elements;
            $data['include_status_radio'] = 1;
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
            $data['is_agent'] = Auth::user()->role_id == User::AGENT_ROLE;
            $data['include_status_radio'] = 1;
            $input_elements = $this->input_elements;
            return view('general_crud.general_view', $data)->with(compact('input_elements'));
        }
    }

    function attach(Request $request)
    {
        try {
            if ($request->report_id) {
                $report_request = MainModel::where([
                    'id' => $request->request_id,
                ])->first();

                $report_request->update([
                    'report_id' => $request->report_id,
                    'progress' => 'fulfilled',
                ]);

                Report::where([
                    'request_id' => $request->request_id
                ])->update([
                    'request_id' => null
                ]);

                Report::where([
                    'id' => $request->report_id,
                ])->update([
                    'request_id' => $request->request_id
                ]);
                CustomerLogs::createLog($report_request->customer_id, CustomerLogs::REPORT_ATTACHED, null, $request->report_id);
            } else {
                MainModel::where([
                    'id' => $request->request_id,
                ])->update([
                    'report_id' => null,
                    'progress' => 'pending',
                ]);

                Report::where([
                    'request_id' => $request->request_id
                ])->update([
                    'request_id' => null
                ]);
            }

            return [
                "status" => true,
                "message" => "success",
                "data" => null
            ];
        } catch (\Exception $e) {
            return [
                "status" => false,
                "message" => "Something went wrong",
                "data" => null
            ];
        }
    }


    function approvalStatus(Request $request)
    {
        try {
            MainModel::where([
                'id' => $request->request_id,
            ])->update([
                'approval_status' => $request->approval_status,
            ]);

            return [
                "status" => true,
                "message" => "success",
                "data" => null
            ];
        } catch (\Exception $e) {
            return [
                "status" => false,
                "message" => "Something went wrong",
                "data" => null
            ];
        }
    }
}
