<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Http\Common\{Constant, FcmHelper, ResponseHelper};
use App\Models\{ApiToken, ReferenceWidget, User};
use Illuminate\Support\Facades\Storage;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    protected $requiredSettings = [
        'account_number',
        'account_title',
        'bank',
        'branch',
        'iban',
        'bank_detail_text',
        'facebook_link',
        'instagram_link',
        'youtube_link',
        'promotion_text',
        'promotion_text_ur',
        'promotion_link',
        'promotion_link_ur',
        'copyright',
        'copyright_ur',
        'newsletter_footer_description',
        'newsletter_footer_description_ur',
        'twitter_link',
        'lindedin_link',
        'cancellation_timing',
        'whatsapp_number',
    ];
    /**
     * This method is used to return the generized response in json formate
     */
    public function returnResponse($statusCode, $message, $data = null)
    {
        return ResponseHelper::returnJsonResponse($statusCode, $message, $data);
    }
    /**
     * This method is used to get User Details by authorize token from header
     */
    public function getUserByToken($header)
    {
        return User::find($this->getUserIdFromHeader($header));
    }
    /**
     * This method is used to get User ID from request headers
     */
    public function getUserIdFromHeader($header)
    {
        return $header['user-id'][0];
    }
    /**
     * This method is used to get Constant's value
     */
    public function getConstantByValue($value)
    {
        return (new Constant)->$value;
    }
    /**
     * This method is used to get the uploaded file's name
     */
    public function getFileName($fileObject)
    {
        return time() . '_' . str_replace(' ', '_', str_replace(' ', '_',  $fileObject->getClientOriginalName()));
    }
    /**
     * This method is used to get User Details by authorize token from header
     */
    public function uploadFile($fileObject, $userId)
    {
        return Storage::disk($this->getConstantByValue("STORAGE_DISK_TYPE"))->putFileAs(
            $this->getConstantByValue("FILE_UPLOAD_PATH") . $userId, // folder name where the file is uploaded
            $fileObject,
            $this->getFileName($fileObject)
        );  
    }
    /**
     * This method is used to get the uploaded file's name
     */
    public function deleteFile($fileName, $userId)
    {
        $filePath = public_path(Constant::FILE_UPLOAD_PATH . $userId . '/' . $fileName);
        if(file_exists($filePath)){
            \unlink($filePath);
        }
    }
    public function updateGeneralWidgetData($request, $reference_id, $params = [])
    {
        return ReferenceWidget::where('id', $reference_id)->update([
            'heading' => $request->reference_heading,
            'description' => $request->reference_description,
            'redirect_url' => $request->reference_redirect_url,
            'ad_window_id' => $request->reference_ad_window_id,
            'status' => $request->status,
        ]);
    }

    public function uniqueSlug($slug,$existingSlugs,$id)
    {
        $id = $id??rand(2,99999);
        $slugs = [];
        foreach($existingSlugs as $existingSlug)
        {
            $slugs[] = $existingSlug->slug;
        }
        $newSlug = (in_array($slug,$slugs))?$slug.'-'.$id: $slug;
        if(in_array($newSlug,$slugs))
        {
            $newSlug = $slug.'-'.rand(2,99999);
        }
        return $newSlug;
    }

    public function setLangSession($lang_id){
        \Session::put('content_lang_id', $lang_id);
    }

    public function getLangSession(){
        return \Session::get('content_lang_id') ?? 1;
    }

    public function getSession($variable){
        return \Session::get($variable);
    }

    public function getTokenDetailsById($userId){
        return ApiToken::where('user_id', $userId)->get();
    }

    public function sendPushNotificationsById($userId, $title, $body, $payload = []){
        $getTokens = $this->getTokenDetailsById($userId);
        if(count($getTokens)){
            foreach($getTokens as $getToken){
                if(isset($getToken->fcm_token)){
                    FcmHelper::push($getToken->fcm_token, $title,$body, $payload);
                }
            }
        }
    }
}
