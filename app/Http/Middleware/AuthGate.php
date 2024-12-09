<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Gate;
use Closure;
use App\Models\Role;
use App\Models\User;

class AuthGate
{
    public function handle($request, Closure $next)
    {
        
        $user=auth()->user();

        if(!app()->runningInConsole() && $user){

            $permissionArray = [];
            $role = $user->role;

            foreach ($role->permissions as $permissions) {
                $permissionArray[$permissions->slug][]=$role->id;
            }

            $user_role_ids = $user->role->pluck('id')->toArray();

            foreach ($permissionArray as $title => $roles) {
                Gate::define($title,function(User $user) use($roles,$user_role_ids){
                    return count(array_intersect( $user_role_ids ,$roles)) > 0;
                });
            }

        }
        return $next($request);
    }
}
