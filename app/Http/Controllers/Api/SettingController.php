<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{InfoModal, SiteContent};

class SettingController extends Controller
{
    /**
     * Get the site contents details for privacy policy
     */
    public function privacyContent(Request $request)
    {
        try {
            return $this->returnResponse(200, '', SiteContent::where('slug', 'privacy-policy')->first());
        } catch(\Exception $e) {
            return $this->returnResponse(500, $e->getMessage());
        }
    }
    /**
     * Get the Info Modal details by getting key
     */
    public function getInfoModalDetail(Request $request, $key)
    {
        try {
            $getInfoModal = InfoModal::where(['key' => $key, 'lang_id' => $request->header('locale')])->first();
            if(!$getInfoModal){
                return $this->returnResponse(400, 'Invalid key.');
            }
            return $this->returnResponse(200, '', $getInfoModal);
        } catch(\Exception $e) {
            return $this->returnResponse(500, $e->getMessage());
        }
    }
}
