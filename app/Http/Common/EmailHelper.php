<?php

namespace App\Http\Common;


class EmailHelper{
    
    public static function sendMail($template_path, $template_data, $to_email, $subject){
        try{
            \Mail::send(['html' => $template_path], $template_data,
                function ($message) use ($to_email,$subject) {
                    $message->to($to_email)
                    ->subject($subject)
                    ->from(env('MAIL_FROM_ADDRESS'));
              });
              return true;
        }catch(Exception $e){
            return false;
        }
    }
}