<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{ApiToken, Footer, Menu};
use Illuminate\Support\Facades\Validator;

class ApiTokenController extends Controller
{
    /**
     * This function is used to create a new ApiToken object from the specified data.
     */
    public function getToken(Request $request) {
        try {
            $validation = Validator::make($request->all(),[ 
                'device_id' => ['required', 'string'],
                'platform' => ['required', 'string'],
                'fcm_token' => ['required', 'string'],
            ]);
            if($validation->fails()) {
                return $this->returnResponse(400, $validation->errors()->first());
            }
            $token = md5(uniqid(rand(), true));
            ApiToken::updateOrCreate([
                    'device_id' => $request->device_id,
                ], [
                    'platform' => $request->platform,
                    'fcm_token' => $request->fcm_token,
                    'token' => $token,
                    'app_version' => $request->app_version ?? "v1.0",
                    'expiry_date' => now()->addDays(10),
                    'user_id' => null,
                ]
            );
            $returnData = [
                'token' => "Bearer $token"
            ];
            if($request->header('platform') == 'web'){
                // $returnData['menu'] = Menu::getMenu($request->header('locale'));
                // $returnData['footer'] = Footer::getFooters($request->header('locale'));
            }
            return $this->returnResponse(200, 'Token has been updated successfully.', $returnData);
        } catch(\Exception $e) {
            return $this->returnResponse(500, $e->getMessage());
        }
    }
}
