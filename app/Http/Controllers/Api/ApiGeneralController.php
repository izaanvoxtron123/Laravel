<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Settings;

class ApiGeneralController extends Controller
{
    public function preferences(Request $request){
        try{
            $settings = Settings::getValues($this->requiredSettings);
            $data = [
                'settings' => $settings
            ];
            return $this->returnResponse(200, 'Content Fetched.',$data);
        }catch(\Exception $e) {
            return $this->returnResponse(500, $e->getMessage());
        }
    }
}
