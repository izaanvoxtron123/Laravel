<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ApiToken;
use Response;

class ApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if($request->header('user-id') == null) {
            return response()->json(['code' => 401, 'status' => false, 'message' => 'User id missing', 'data' =>null ], 401);
        }
        if((int) $request->header('user-id') === 0){
            $row = ApiToken::where('token', $request->bearerToken())->where('user_id', null)->first();
        }else{
            $row = ApiToken::where('token', $request->bearerToken())->where('user_id', $request->header('user-id'))->first();
        }
        if( !$row ){
            return response()->json(['code'=>401,'status'=> false, 'message' => 'Unauthorized: Access Token', 'data' =>null], 401);
        }else {
            $request->headers->set('user_id', $request->header('user-id'));
            return $next($request);
        }
    }
}
