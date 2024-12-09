<?php

namespace App\Http\Common;

use Illuminate\Http\Request;
use Curl\Curl;

class SmsHelper {
    private $auth_url = "";
    private $url = "";
    private $msisdn = "";
    private $secret = "";
    private $mask = "";

    public function __construct()
    {
        $this->url = env('SMS_URL');
        $this->user = env('SMS_USER');
        $this->password = env('SMS_PASSWORD');
        $this->sender = env('SMS_SENDER');
        $this->response = env('SMS_RESPONSE');
        
    }

    private function getSmsUrl($to,$message) {
        return 'http://smsctp4.eocean.us:24555/api?action=sendmessage&username=merisehatpk92102&password=I8f7%5Eyc3&recipient='.$to.'&originator=92102&messagedata='.$message.'&messagetype=SMS:TEXT';
    }

    public function send($to,$message,$network=null) {
        try{
            $sms_url = $this->getSmsUrl($to,urlencode($message));
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => $sms_url,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'GET',
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            return true;
                // //  dd($sms_url = $this->getSmsUrl($to,urlencode($message)));
                //  $sms_url = $this->getSmsUrl($to,urlencode($message));
                // $curl = new Curl();
                // $curl->get($sms_url);
                // // dd($curl);
                // (end($curl->response->response)=='OK')?true:false;
        } catch(\Exception $e) {
            return true;
        } 
    }
    

}
