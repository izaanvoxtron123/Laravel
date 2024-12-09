<?php

namespace App\Http\Common;

use App\Http\Common\EmailHelper;
use App\Activity\Activity;
use App\Models\{ApiToken, Setting, User};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Helper
{

    public const STATIC_ASSET_DISK = "public";

    static public function ApiErrorResponse($data, $code)
    {
        return response()->json($data, $code);
    }

    static public function getSmsText($type, $code)
    {
        return "Your MeriSehat code is " . $code . ". For further information please visit https://www.merisehat.pk.";
    }

    public static function toast($type, $message, $title = '')
    {
        Session::flash('title', $title);
        Session::flash('type', $type);
        Session::flash('message', $message);
    }

    public static function sweatAlert($type, $message, $title = '')
    {
        Session::flash('title', $title);
        Session::flash('type', $type);
        Session::flash('message', $message);
    }

    static public function createActivityLog($template_id, $params = [])
    {
        try {
            if (Auth::check()) {
                $user = Auth::user()->id;
            } else {
                $user = 1;
            }
            Activity::create([
                'activity_template_id' => $template_id,
                'user_id' => $user,
                'activity_time' => date('Y-m-d h:i:s'),
                'json_params' => json_encode($params)
            ]);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    static public function generateOTP($n = 4)
    {
        // return '0000';
        // 'otp' => $otp = rand(1000, 9999),
        $generator = "1357902468";
        $result = "";
        for ($i = 1; $i <= $n; $i++) {
            $result .= substr($generator, (rand() % (strlen($generator))), 1);
        }
        return $result;
    }

    static public function get_fcm_by_token($token)
    {

        try {
            return ApiToken::where('token', $token)->first()->fcm_token;
        } catch (\Exception $e) {
            return null;
        }
    }

    static public function sendOTP($user_id)
    {
        try {
            $user = User::find($user_id);

            if ($user) {
                $otp = Helper::generateOTP();
                $user->otp = $otp;
                $user->save();
                // $sms = new SmsHelper;
                // $sms->send($user->phone,'Your phone verification OTP is '.$otp);
                return [
                    'status' => true,
                    'code' => 200,
                    'message' => "Successfully sent OTP.",
                    'otp' => $otp
                ];
            } else {
                return [
                    'status' => false,
                    'code' => 200,
                    'message' => "No User Found"
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => "Something went wrong"
            ];
        }
    }

    static public function sendOTPViaEmail($user_id)
    {
        try {
            $user = User::find($user_id);

            if ($user) {
                $otp = Helper::generateOTP();
                $user->otp = $otp;
                $user->save();
                $settings = Setting::getValues(['email', 'terms_condition_content_id', 'privacy_content_id']);
                $otp_params = [
                    'otp' => $otp,
                    'policy_url' => 'https://web.skyfreightalarabia.com/term-condition',
                    'terms_url' => 'https://web.skyfreightalarabia.com/privacy-policy',
                    'email' => $settings['email'],
                    'year' => date("Y"),
                    'logo_url' => asset('admin/img/logo_new.png'),
                ];
                EmailHelper::sendEmail($user->name, $user->email, 'Sky Freight - password reset OTP', EmailHelper::$password_change_otp_template, $otp_params);
                return [
                    'status' => true,
                    'code' => 200,
                    'message' => "Successfully sent OTP.",
                    'otp' => $otp
                ];
            } else {
                return [
                    'status' => false,
                    'code' => 200,
                    'message' => "Email address not found"
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => "Something went wrong"
            ];
        }
    }

    public static function get_distance($lat1, $lon1, $lat2, $lon2, $unit = "km")
    {


        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "km") {
            return ($miles * 1.609344);
        } else if ($unit == "n") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }




    public static function generate_qr($qty = 0)
    {
        $qr_codes = [];
        $row = 0;
        while ($row < $qty) {
            $random_number_1 = str_replace('0.', '', substr(rand(), 2, 2));
            $random_number_2 = str_replace('0.', '', substr(rand(), 2, 2));
            $random_number = $random_number_1 . $random_number_2;
            $uniqid_1 = substr(uniqid(), 4, 4);
            $uniqid_2 = substr(uniqid(), 9, 10);
            $code = $uniqid_1 . "-" . $random_number . "-" . $uniqid_2;
            $qr_code = "SF-" . $code;
            if (!in_array($qr_code, $qr_codes)) {
                array_push($qr_codes, $qr_code);
                $row++;
            }
        }
        return $qr_codes;
    }

    /**
     * This method is used to generate masking of an Email
     */
    public static function emailMasking($value)
    {
        if (!$value) {
            return "";
        }
        $exploadedEmail   = explode("@", $value);
        $lengthCount = strlen($exploadedEmail[0]) > 3 ? 3 : strlen($exploadedEmail[0]);
        return substr($exploadedEmail[0], 0, $lengthCount) . str_repeat('*', strlen($exploadedEmail[0]) - $lengthCount) . "@" . end($exploadedEmail);
    }
    /**
     * This method is used to generate masking of a phone number
     */
    public static function phoneMasking($value)
    {
        return substr($value, 0, 3) . str_repeat('*', 6) . substr($value, 9);
    }
    /**
     * This method is used to sanitize data
     */
    public static function sanitizeData($value)
    {
        $value = str_replace(' ,', ', ', $value);
        $value = str_replace(', ,', ', ', $value);
        $value = str_replace(',,', ',', $value);
        $value = str_replace('  ', ' ', $value);
        $value = str_replace('.,', ',', $value);
        $value = str_replace(',.', ',', $value);
        return $value;
    }

    public static function getRandomPassword()
    {
        return "Doctor@123";
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }
}
