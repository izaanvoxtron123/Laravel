<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{
    User as MainModel,
    Customer,
    CustomerLogs,
    CustomerPhones,
    Fcm_Tokens,
    ReportRequest,
    Leadcenter,
    MIds,
    Offices,
    PhoneValidationLogs,
    User,
};
use App\Models\Role;
use Auth;
use Yajra\Datatables\Datatables;
use App\Http\Common\Helper;
use App\Imports\UsersImport;
use Hash;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public $folder_name = 'admin_users'; // For view routes and file calling and saving
    public $module_name = 'User'; // For toast And page header


    public function dashboard(Request $request)
    {

        // $customer_db = DB::connection('mysql3')->table('customers');

        // $from = date('2024-07-01');
        // $to = date('2024-07-31');

        // $customer_count = $customer_db->whereNotNull('deleted_at')->where('is_complete', 0)->whereBetween('created_at', [$from, $to])->count();


        // // $customer_count = $customer_db->whereNotNull('deleted_at')->where('is_complete', 0)->whereBetween('created_at', [$from, $to])->update([
        // //     'deleted_at' => null,
        // // ]);

        // dd($customer_count);


        // $customer_count =  $customer_db->where('checked_for_office_id', false)->count();
        // dd($customer_count);

        // chargebacks@vps.com // 192
        // declines@vps.com // 234
        // rna@vps.com // 235

        // chargebacks2@vps.com // 246
        // declines&na@vps.com // 245

        // vox3decline@vps.com // 191
        // chargebacks3@vps.com // 307

        // decline@permatech.com  // 449


        // decline1@vps.com  // 190
        // rna3@vps.com  //  479

        // rna2@vps.com // 478

        // $agent_db = DB::connection('mysql3')->table("users");
        // $agent = $agent_db->where([
        //     'email' => "rna2@vps.com"
        // ])->get();

        // dd($agent);

        // })->get()->pluck('id')->toArray();


        // $customer_db = DB::connection('mysql3')->table('customers');

        // $customers = $customer_db->where([
        //     'agent_id' => 478,
        //     'is_complete' => false,
        //     'in_rework' => false,
        // // ])->whereNotExists(function ($query) {
        // ])->whereExists(function ($query) {
        //     $query->select(DB::raw(1))
        //         ->from('reports')
        //         ->whereColumn('reports.customer_id', 'customers.id');
        // // })->get()->pluck('id')->toArray();
        // })->update([
        //     'is_complete' => true,
        //     'through_re_approval' => true,
        //     'sale_status' => 'RNA',
        //     'specialist_rna_id' => '478'
        // ]);

        // $leads_db = DB::connection('mysql3')->table('leadcenter');

        // $from = date('2024-09-14');
        // $to = date('2024-09-30');

        // $leads_count = $leads_db->where('is_used', 1)->whereBetween('created_at', [$from, $to])->count();

        // dd($leads_count);

        $total_managers = MainModel::where('role_id', MainModel::MANAGER_ROLE)->count();
        $total_agents = MainModel::where('role_id', MainModel::AGENT_ROLE)->count();
        $total_rnd_agents = MainModel::where('role_id', MainModel::RND_ROLE)->count();

        $online_managers = MainModel::where('role_id', MainModel::MANAGER_ROLE)->where('is_online', 1)->count();
        $online_agents = MainModel::where('role_id', MainModel::AGENT_ROLE)->where('is_online', 1)->count();
        $online_rnd_agents = MainModel::where('role_id', MainModel::RND_ROLE)->where('is_online', 1)->count();

        $pending_requests = ReportRequest::where('progress', 'pending')->count();
        $total_customers = Customer::count();
        $complete_customers = Customer::where('is_complete', 1)->count();
        $total_leads = Leadcenter::count();
        $incomplete_leads = Leadcenter::where('is_complete', 0)->count();
        $offices = Offices::where('status', 1)->get();



        $data['total_managers'] = $total_managers;
        $data['total_agents'] = $total_agents;
        $data['total_rnd_agents'] = $total_rnd_agents;

        $data['online_managers'] = $online_managers;
        $data['online_agents'] = $online_agents;
        $data['online_rnd_agents'] = $online_rnd_agents;

        $data['pending_requests'] = $pending_requests;
        $data['total_customers'] = $total_customers;
        $data['complete_customers'] = $complete_customers;
        $data['total_leads'] = $total_leads;
        $data['incomplete_leads'] = $incomplete_leads;
        $data['offices'] = $offices;


        return view('admin.dashboard', $data);
    }

    public function view()
    {

        $user = Auth::user();
        $roles = new Collection(); // Initialize as an empty collection

        if ($user->can('view-all-users')) {
            $roles = Role::get(); // Get all roles as a collection
        }

        if ($user->can('view-rnd-agents')) {
            $rnd_roles = Role::where('id', User::RND_ROLE)->get();
            $roles = $roles->merge($rnd_roles); // Merge collections
        }

        if ($user->can('view-agents')) {
            $agent_roles = Role::where('id', User::AGENT_ROLE)->get();
            $roles = $roles->merge($agent_roles); // Merge collections
        }

        if ($user->can('view-closers')) {
            $agent_roles = Role::where('id', User::CLOSER_ROLE)->get();
            $roles = $roles->merge($agent_roles); // Merge collections
        }

        if ($user->can('view-team-leads')) {
            $agent_roles = Role::where('id', User::TEAM_LEAD_ROLE)->get();
            $roles = $roles->merge($agent_roles); // Merge collections
        }

        if ($user->can('view-rna-specialist')) {
            $agent_roles = Role::where('id', User::RNA_SPECIALIST_ROLE)->get();
            $roles = $roles->merge($agent_roles); // Merge collections
        }

        if ($user->can('view-chg-bck-specialist')) {
            $agent_roles = Role::where('id', User::CB_SPECIALIST_ROLE)->get();
            $roles = $roles->merge($agent_roles); // Merge collections
        }

        if ($user->can('view-decline-specialist')) {
            $agent_roles = Role::where('id', User::DECLINE_SPECIALIST_ROLE)->get();
            $roles = $roles->merge($agent_roles); // Merge collections
        }

        $data['roles'] = $roles;
        $file = "admin." . $this->folder_name . ".view";
        return view($file, $data);
    }

    public function fetch(Request $request)
    {
        $query = MainModel::with(['role']);
        if ($request->status != '') {
            $query = $query->where('status', $request->status);
        }
        return Datatables::of($query)->make(true);
    }

    public function form(Request $request, $id = null)
    {
        $request->validate(MainModel::getRules($id, $request->role, $request->office));
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'office_id' => $request->office,
            'phone' => $request->phone,
            'role_id' => $request->role,
            'gender' => $request->gender,
            'status' => $request->status,
            'multi_device_login' => $request->multi_device_login,
        ];

        if (
            $request->role == MainModel::AGENT_ROLE
            || $request->role == MainModel::CLOSER_ROLE
            || $request->role == MainModel::TEAM_LEAD_ROLE
            || $request->role == MainModel::RNA_SPECIALIST_ROLE
            || $request->role == MainModel::CB_SPECIALIST_ROLE
            || $request->role == MainModel::DECLINE_SPECIALIST_ROLE
        ) {
            if ($request->office) {
                $data['email'] = $request->username . '@' . $request->suffix;
            }
        }

        if ($request->role == MainModel::FE_ROLE && $request->m_id) {
            $data['m_id'] = $request->m_id;
        }
        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }
        // if($request->role == MainModel::PICKUP_DISPATCHER_ROLE){
        //     $data['city_id'] = $request->city;
        // }
        // if($request->role == MainModel::DELIVERY_TRANSPORTER_ROLE){
        //     $data['region_id'] = $request->region;
        // }
        // if($request->role == MainModel::OUTLET_MANAGER_ROLE){
        //     $data['outlet_id'] = $request->outlet;
        // }
        if ($request->hasFile('image')) {
            $image = $request->image->store($this->folder_name, 'public');
            $data['image'] = $image;
        }

        if ($id) {
            if (MainModel::where('id', $id)->update($data)) {
                $createOrUpdate['where']['user_id'] = $id;
                $createOrUpdate['data']['role_id'] = $request->role;
                Helper::toast('success', $this->module_name . ' Updated.');
            }
        } else {
            if ($created_data = MainModel::create($data)) {
                $createOrUpdate['where']['user_id'] = $created_data->id;
                $createOrUpdate['data']['role_id'] = $request->role;
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
            $user = Auth::user();
            $roles = new Collection(); // Initialize as an empty collection

            if ($user->can('view-all-users')) {
                $roles = Role::get(); // Get all roles as a collection
            }

            if ($user->can('view-rnd-agents')) {
                $rnd_roles = Role::where('id', User::RND_ROLE)->get();
                $roles = $roles->merge($rnd_roles); // Merge collections
            }

            if ($user->can('view-agents')) {
                $agent_roles = Role::where('id', User::AGENT_ROLE)->get();
                $roles = $roles->merge($agent_roles); // Merge collections
            }

            if ($user->can('view-closers')) {
                $agent_roles = Role::where('id', User::CLOSER_ROLE)->get();
                $roles = $roles->merge($agent_roles); // Merge collections
            }

            if ($user->can('view-team-leads')) {
                $agent_roles = Role::where('id', User::TEAM_LEAD_ROLE)->get();
                $roles = $roles->merge($agent_roles); // Merge collections
            }

            if ($user->can('view-rna-specialist')) {
                $agent_roles = Role::where('id', User::RNA_SPECIALIST_ROLE)->get();
                $roles = $roles->merge($agent_roles); // Merge collections
            }

            if ($user->can('view-chg-bck-specialist')) {
                $agent_roles = Role::where('id', User::CB_SPECIALIST_ROLE)->get();
                $roles = $roles->merge($agent_roles); // Merge collections
            }

            if ($user->can('view-decline-specialist')) {
                $agent_roles = Role::where('id', User::DECLINE_SPECIALIST_ROLE)->get();
                $roles = $roles->merge($agent_roles); // Merge collections
            }

            $data['page_header'] = "Add " . $this->module_name;
            // $data['roles'] = Role::where('id', '!=', '2')->get();
            $data['is_agent'] =  Auth::user()->role_id == MainModel::AGENT_ROLE;
            $data['roles'] = $roles;
            $data['offices'] = Offices::where('status', 1)->get();
            $data['m_ids'] = MIds::where('status', 1)->get();
            $data['result'] = null;
            $file = "admin." . $this->folder_name . ".form";
            return view($file, $data);
        }
    }

    public function edit(Request $request, $id)
    {
        $id = decrypt($id);
        if ($request->isMethod('post')) {
            return $this->form($request, $id);
        } else {
            $user = Auth::user();
            $roles = new Collection(); // Initialize as an empty collection

            if ($user->can('view-all-users')) {
                $roles = Role::get(); // Get all roles as a collection
            }

            if ($user->can('view-rnd-agents')) {
                $rnd_roles = Role::where('id', User::RND_ROLE)->get();
                $roles = $roles->merge($rnd_roles); // Merge collections
            }

            if ($user->can('view-agents')) {
                $agent_roles = Role::where('id', User::AGENT_ROLE)->get();
                $roles = $roles->merge($agent_roles); // Merge collections
            }

            if ($user->can('view-closers')) {
                $agent_roles = Role::where('id', User::CLOSER_ROLE)->get();
                $roles = $roles->merge($agent_roles); // Merge collections
            }

            if ($user->can('view-team-leads')) {
                $agent_roles = Role::where('id', User::TEAM_LEAD_ROLE)->get();
                $roles = $roles->merge($agent_roles); // Merge collections
            }

            if ($user->can('view-rna-specialist')) {
                $agent_roles = Role::where('id', User::RNA_SPECIALIST_ROLE)->get();
                $roles = $roles->merge($agent_roles); // Merge collections
            }

            if ($user->can('view-chg-bck-specialist')) {
                $agent_roles = Role::where('id', User::CB_SPECIALIST_ROLE)->get();
                $roles = $roles->merge($agent_roles); // Merge collections
            }

            if ($user->can('view-decline-specialist')) {
                $agent_roles = Role::where('id', User::DECLINE_SPECIALIST_ROLE)->get();
                $roles = $roles->merge($agent_roles); // Merge collections
            }

            $data['page_header'] = "Edit " . $this->module_name;
            // $data['roles'] = Role::where('id', '!=', '2')->get();
            $data['is_agent'] =  Auth::user()->role_id == MainModel::AGENT_ROLE;
            $data['roles'] = $roles;
            $data['offices'] = Offices::where('status', 1)->get();
            $data['m_ids'] = MIds::where('status', 1)->get();
            $data['result'] = MainModel::find($id);
            $file = "admin." . $this->folder_name . ".form";
            return view($file, $data);
        }
    }
    function upload(Request $request)
    {
        if (!isset($request->file)) {
            Helper::toast('error', "Please Upload File");
            return back();
        }
        try {
            Excel::import(new UsersImport, request()->file('file'));
            Helper::toast('success', ' Successfully Inserted.');
        } catch (\Exception $e) {
            // dd($e);
            Helper::toast('error', ' Something Went Wrong.' . $e->getMessage());
        };
        return back();
    }

    function validatePhoneNumber(Request $request, $number)
    {
        $response = "Invalid Number";
        if (strlen($number) == 10) {
            // Extract the area code from the phone number
            $area_code = substr($number, 0, 3);

            // Define the allowed area codes
            $allowed_area_codes = ['800', '833', '844', '855', '866', '877', '888'];

            // Check if the area code is in the allowed list
            // if (!in_array($area_code, $allowed_area_codes)) {
            $customers = Customer::where(function ($query) use ($number) {
                $query->where('phone', 'LIKE', '%' . $number . '%')
                    ->orWhere('secondary_phones', 'LIKE', '%' . $number . '%');
            })->get(['id']);

            $customerPhone = CustomerPhones::where('phone_number', $number)
                ->first();

            $Leads = Leadcenter::where(function ($query) use ($number) {
                $query->where('phone', 'LIKE', '%' . $number . '%');
            })->get(['id']);

            if (count($customers) || count($Leads) || isset($customerPhone)) {
                $response = 'Allowed';
            } else {
                $response = 'Not Allowed';
            }
            // } else {
            //     $response = 'Allowed';
            // }
        } else {
            $response = 'Invalid Number';
        }

        PhoneValidationLogs::create([
            'source_ip' => $request->ip(),
            'phone' => $number,
            'response' => $response,
        ]);
        return $response;
    }

    function Forcelogout(Request $request, $id = null)
    {
        if ($id) {
            MainModel::where('id', $id)
                ->update([
                    'is_online' => false
                ]);

            DB::table('sessions')
                ->where('user_id', $id)
                ->delete();
        } else {

            MainModel::where('role_id', '!=', MainModel::SUPERADMIN_ROLE)
                ->where('role_id', '!=', MainModel::MANAGER_ROLE)
                ->update([
                    'is_online' => false
                ]);

            $user_ids = MainModel::where('role_id', '!=', MainModel::SUPERADMIN_ROLE)
                ->where('role_id', '!=', MainModel::MANAGER_ROLE)
                ->select('id')
                ->pluck('id')
                ->toArray();

            DB::table('sessions')
                ->whereIn('user_id', $user_ids)
                ->delete();
        }

        return back();
    }

    function assignLeadsToCustomer(Request $request)
    {

        $customers = Customer::where([
            'is_complete' => 0,
            'in_rework' => 0,
            'agent_id' => null,
        ])->whereHas('logs', function ($query) {
            $query->where('type', 'customer_created');
            $query->where('supporting_id', '!=', null);
        })->limit(1000)->select('id')->with('logs')->get();
        if (!count($customers)) return "Completed";
        foreach ($customers as $customer) {
            echo "\n" . $customer->id .  "----LOG-----" . $customer->logs[0]->id . "<br>";
            echo Customer::where('id', $customer->id)->update(['agent_id' => $customer->logs[0]->supporting_id]);
        }
        return;
    }

    function reports(Request $request, $id)
    {
        $id = decrypt($id);

        $data['user'] = MainModel::find($id);
        $file = "admin." . $this->folder_name . ".reports";
        return view($file, $data);
    }

    function saveFcmToken(Request $request)
    {
        try {
            $exists = Fcm_Tokens::where([
                'user_id' => Auth::user()->id,
                'token' => $request->token,
            ])->select('id')->first();
            if (!$exists) {
                Fcm_Tokens::create([
                    'user_id' => Auth::user()->id,
                    'role_id' => Auth::user()->role_id,
                    'token' => $request->token,
                ]);
                return "created";
            }

            return "exists";
        } catch (\Exception $e) {
            return "error: " . $e->getMessage();
        }
    }
}
