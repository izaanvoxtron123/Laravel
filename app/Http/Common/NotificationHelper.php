<?php

namespace App\Http\Common;

use Illuminate\Http\Request;
use App\Models\Orders;
use App\Models\Contractor;
use App\Models\Notifications;
use Illuminate\Support\Arr;
use DB;


abstract class NotificationHelper {
    static public function create($notification,$payload) {
        // following payload should be replicated for all notifications.
        //   $notification = [
        //     'receiver_id' => 1, // user_id 
        //     'receiver_type' => 1, // user's role_id
        //     'title' => $title,
        //     'text' => $text,
        // ];
        $notification['payload'] = json_encode($payload);
        Notifications::create($notification);
    }

    static public function createAdminNotification($title,$text,$payload) {
        // following payload should be replicated for all notifications
        $notification = [
            'receiver_id' => 0, // user_id (For admin roles leave it 0. )
            'receiver_type' => 1, // user's role_id
            'title' => $title,
            'text' => $text,
            'payload' => $payload,
        ];

        self::create($notification,$payload);
    }

    

}
