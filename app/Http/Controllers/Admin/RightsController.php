<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Role;
use DB;
use Str;
use App\Http\Common\Helper;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;


class RightsController extends Controller
{
    public function show_roles()
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

        return view('admin.rights.roles_list', compact('roles'));
    }

    public function add_role()
    {
        $roles = Role::with('permissions:id')->get(['id', 'name']);
        $all_roles_with_permissions = $roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->map(function ($permission) {
                    return $permission->id;
                })->toArray(),
            ];
        })->toArray();

        $role = null;

        $permissions = Permission::where('parent_id',  null)->get();
        // $permissions = Permission::orderBy('name', 'ASC')->get()->all();
        $selected_permission = [];
        // dd($permissions->toArray());
        return view('admin.rights.add_roles', compact('role'), compact('permissions', 'selected_permission', 'all_roles_with_permissions'));
    }

    public function create_role(Request $request)
    {
        $data = request()->validate(['name' => 'required|max:100',]);
        try {
            $max_id = Role::where('id', '>=', 101)->max('id');
            $newRoleId = $max_id < 101 ? 101 : $max_id + 1;

            $role = Role::create([
                'id' => $newRoleId,
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'translation' => 'en'
            ]);
            if ($request->permissions != null &&  count($request->input('permissions')) > 0) {
                foreach ($request->input('permissions') as $perm_id) {
                    DB::table('role_permissions')->insert(['role_id' => $role->id, 'permission_id' => $perm_id]);
                }
                // return back();
            }
            Helper::toast('success', 'Role created.');
            return redirect()->route('roles.show');
        } catch (\Throwable $th) {
            Helper::toast('error', 'Role creation failed.');
            return back();
            // return redirect()->route('roles.show');
        }
    }

    public function update_role(Request $request, Role $role)
    {
        $data = request()->validate(['name' => 'required|max:100']);
        $role->update(['name' => $data['name']]);
        DB::table('role_permissions')->where('role_id', $role->id)->delete();
        if ($request->permissions != null) {
            foreach ($request->input('permissions') as $perm_id) {
                DB::table('role_permissions')->insert(['role_id' => $role->id, 'permission_id' => $perm_id]);
            }
        }
        Helper::toast('success', 'Role updated.');
        return back();
        return redirect()->route('roles.show');
    }
    public function edit_role(Role $role)
    {
        $roles = Role::with('permissions:id')->get(['id', 'name']);
        $all_roles_with_permissions = $roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->map(function ($permission) {
                    return $permission->id;
                })->toArray(),
            ];
        })->toArray();

        $selected_permission = $role->permissions->pluck('id')->toArray();
        // $nonSelected_permission=[];
        $permissions = Permission::where('parent_id',  null)->get();
        // foreach ($permissions as $key=> $perm) {
        //     foreach ($selected_permission as $sel_perm) {
        //         if($sel_perm->id==$perm->id){
        //             unset($permissions[$key]);
        //         }
        //     }
        // }

        return view('admin.rights.add_roles', compact('role'), compact('permissions', 'selected_permission', 'all_roles_with_permissions'));
    }
}
