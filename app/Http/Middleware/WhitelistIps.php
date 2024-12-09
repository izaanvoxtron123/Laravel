<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\WhitelistedIps;
use Closure;
use Illuminate\Http\Request;

class WhitelistIps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (\Auth::user()->can('bypass-whitelisting')) {
            return $next($request);
        }

        if (\Auth::user()->role_id == User::AGENT_ROLE) {
            $office = \Auth::user()->office;
            if ($office) {
                $assigned_ips = $office->ips->pluck('ip')->toArray();
                if (in_Array($request->ip(), $assigned_ips)) {
                    return $next($request);
                } else {
                    abort(403, "You are restricted to access the site.");
                }
            } else {
                $WhitelistedPrimaryIps = WhitelistedIps::where('status', 1)->where('is_primary', 1)->pluck('ip')->toArray();
                if (!in_array($request->ip(), $WhitelistedPrimaryIps)) {
                    abort(403, "You are restricted to access the site.");
                } else {
                    return $next($request);
                }
            }
        }

        $WhitelistedIps = WhitelistedIps::where('status', 1)->pluck('ip')->toArray();
        if (!in_array($request->ip(), $WhitelistedIps)) {
            abort(403, "You are restricted to access the site.");
        }

        return $next($request);
    }
}
