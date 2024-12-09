<?php

namespace App\Http\Controllers\Admin;

use App\Http\Common\Helper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAccounts;
use App\Models\CustomerLogs;
use App\Models\Offices;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Auth;
use Exception;

class ApprovedCustomers extends Controller
{
    public $folder_name = 'approved_customers'; // For view routes and file calling and saving
    public $module_name = 'M Id'; // For toast And page header
    public $input_elements;

    function view()
    {
        $data['folder_name'] = $this->folder_name;
        $data['module_name'] = $this->module_name;
        $data['result'] = [];
        return view('admin.approved_customer.view', $data);
    }

    public function fetch(Request $request)
    {
        $customers = Customer::with('charge_accounts')->select([
            'id',
            'comments',
            'first_name',
            'middle_initial',
            'last_name',
            'phone',
            'address',
            'dob',
            'ssn',
            'mmn',
            'charge',
            'no_of_ac',
            'city',
            'state',
            'zip',
            'sale_status'
        ])->orderBy('id', 'DESC')->where('is_complete', 1);

        if (Auth::user()->can('view-same-mid-customers')) {
            $customers->where('m_id', Auth::user()->m_id);
        }



        if ($request->has('sale_status') && $request->sale_status != 'Select') {
            $customers->where('sale_status', $request->sale_status);
        }

        return DataTables::of($customers)
            ->addColumn('accounts', function ($customer) {
                return $customer->charge_accounts->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'noc' => $account->noc,
                        'account_number' => $account->account_number,
                        'exp' => $account->exp,
                        'cvv1' => $account->cvv1,
                        'charge_card' => $account->charge_card,
                        "charge" => $account->charge ?? "",
                    ];
                });
            })
            ->make(true);
    }

    public function edit(Request $request, $id)
    {
        $customer = Customer::find($id);

        if ($request->has('comments')) {
            if ($customer->comments != $request->comments) {
                CustomerLogs::createLog($id, CustomerLogs::CUSTOMER_COMMENT_UPDATED, $request->comments);
            }
        }

        if ($request->has('sale_status')) {
            if ($customer->sale_status != $request->sale_status && $request->sale_status != "Select") {
                CustomerLogs::createLog($id, CustomerLogs::SALE_STATUS_UPDATED, $request->sale_status);
            }
        }


        $customer->update($request->except('accounts'));

        // if ($request->has('accounts')) {
        //     foreach ($request->accounts as $accountData) {
        //         $account = $customer->accounts()->find($accountData['id']);
        //         if ($account) {
        //             $account->update($accountData);
        //         }
        //     }
        // }

        return response()->json(['success' => 'Customer updated successfully.']);
    }


    public function editAccount(Request $request, $id)
    {
        $account = CustomerAccounts::find($id);
        $account->update($request->only(['account_number', 'noc', 'cvv1', 'exp', 'charge_card', 'charge']));

        return response()->json(['success' => 'Account updated successfully.']);
    }


    // REAPPROVAL 

    function fetchReApprovalCustomers(Request $request)
    {
        $completed_customers = "";
        if (Auth::user()->role_id == User::RNA_SPECIALIST_ROLE) {
            $completed_customers = Customer::where('is_complete', true)
                ->where('sale_status', "RNA")
                ->where('specialist_rna_id', Auth::user()->id)
                ->with(['agent', 'MId'])->orderBy('id', 'DESC');
        } else if (Auth::user()->role_id == User::CB_SPECIALIST_ROLE) {
            $completed_customers = Customer::where('is_complete', true)
                ->where('sale_status', "Chargebacks")
                ->where('specialist_cb_id', Auth::user()->id)
                ->with(['agent', 'MId'])->orderBy('id', 'DESC');
        } else if (Auth::user()->role_id == User::DECLINE_SPECIALIST_ROLE) {
            $completed_customers = Customer::where('is_complete', true)
                ->where('sale_status', "Decline")
                ->where('specialist_decline_id', Auth::user()->id)
                ->with(['agent', 'MId'])->orderBy('id', 'DESC');
        } else {
            $completed_customers = Customer::where('is_complete', true)
                ->where('sale_status', "RNA")
                ->orWhere('sale_status', "Chargebacks")
                ->orWhere('sale_status', "Decline")
                ->where('specialist_decline_id', Auth::user()->id)
                ->with(['agent', 'MId'])->orderBy('id', 'DESC');
        }

        return Datatables::of($completed_customers)->make(true);
    }

    function viewReApprovalCustomers(Request $request)
    {
        $data['folder_name'] = $this->folder_name;
        $data['module_name'] = $this->module_name;
        $data['result'] = [];
        $data['is_agent'] = Auth::user()->role_id == User::AGENT_ROLE;
        return view('admin.approved_customer.view_re_approval_customers', $data);
    }

    function proceedForApproval(Request $request, $customer_id)
    {

        if (Customer::where('id', $request->customer_id)->update([
            'sale_status' => null,
        ])) {
            Helper::toast('success', 'Successfully moved for approval.');
        } else {
            Helper::toast('error', "Something Went Wrong");
        }
        return back();
    }

    function changeBulkStatus(Request $request)
    {
        try {
            foreach ($request->customers as $customer_id) {
                Customer::where("id", $customer_id)->update([
                    'sale_status' => $request->status
                ]);
                CustomerLogs::createLog($customer_id, CustomerLogs::SALE_STATUS_UPDATED, $request->status);
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

    function getSpecialist(Request $request, $type)
    {
        try {
            if ($type == "rna") {
                $roleId = User::RNA_SPECIALIST_ROLE;
                $relation = 'rna_specialists';
            } elseif ($type == "cb") {
                $roleId = User::CB_SPECIALIST_ROLE;
                $relation = 'cb_specialists';
            } else {
                $roleId = User::DECLINE_SPECIALIST_ROLE;
                $relation = 'decline_specialists';
            }


            $offices = Offices::where(['status' => 1])
                ->whereHas($relation)
                ->with([$relation => function ($query) use ($roleId) {
                    // Optionally, apply additional filters here if needed
                    $query->where('role_id', $roleId);
                }])
                ->get()
                ->map(function ($office) use ($relation) {
                    $office->agents = $office->$relation;
                    unset($office->$relation); // Remove the original relation key
                    return $office;
                })
                ->toArray();


            // $specialists = User::where([
            //     'role_id' => $roleId,
            //     'status' => 1,
            // ])->get();

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

    function assignSpecialist(Request $request)
    {
        try {
            foreach ($request->customers as $customer_id) {
                $customer = Customer::where(['id' => $customer_id])->select('id', 'office_id')->first();
                if ($customer->office_id == $request->office_id) {
                    $data_to_update = [
                        'through_re_approval' => 1,
                    ];
                    if ($request->specialist_type == "rna") {
                        $data_to_update['specialist_rna_id'] = $request->specialist;
                        $data_to_update['sale_status'] = "RNA";
                    }
                    if ($request->specialist_type == "cb") {
                        $data_to_update['specialist_cb_id'] = $request->specialist;
                        $data_to_update['sale_status'] = "Chargebacks";
                    }
                    if ($request->specialist_type == "decline") {
                        $data_to_update['specialist_decline_id'] = $request->specialist;
                        $data_to_update['sale_status'] = "Decline";
                    }
                    Customer::where("id", $customer_id)->update($data_to_update);
                    CustomerLogs::createLog($customer_id, CustomerLogs::SPECIALIST_ASSIGNED, null, $request->specialist);
                    CustomerLogs::createLog($customer_id, CustomerLogs::SALE_STATUS_UPDATED, $data_to_update['sale_status']);
                }
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
}
