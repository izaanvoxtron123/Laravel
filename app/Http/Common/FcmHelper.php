<?php

namespace App\Http\Common;

use App\Models\Notifications;
use Illuminate\Support\Facades\Http;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;

class FcmHelper
{
    public static function create($receiver_id, $receiver_role,  $title, $text, $module = null, $supporting_id = null)
    {
        Notifications::create([
            'receiver_id' => $receiver_id,
            'receiver_role' => $receiver_role,
            'title' => $title,
            'text' => $text,
            'module' => $module,
            'supporting_id' => $supporting_id,
        ]);
    }
    public static function push($device_tokens, $title, $text, $module = null, $supporting_id = null)
    {
        $credentialPath = public_path('admin/pvKey.json');

        $credential = new ServiceAccountCredentials(
            "https://www.googleapis.com/auth/firebase.messaging",
            json_decode(file_get_contents($credentialPath), true)
        );

        $token = $credential->fetchAuthToken(HttpHandlerFactory::build());

        foreach ($device_tokens as $device_token) {
            try {
                Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token['access_token']
                ])->post('https://fcm.googleapis.com/v1/projects/voxaccess-34c85/messages:send', [
                    'message' => [
                        'token' => $device_token,
                        'notification' => [
                            'title' => $title,
                            'body' => $text,
                            'image' => asset('admin/img/VA_icon.png')
                        ],
                        'data' => [
                            "module" => "Mario",
                            "id" => "PortugalVSDenmark"
                        ],
                        'webpush' => [
                            'fcm_options' => [
                                'link' => route('redirect_notification', ['module' => $module, 'supporting_id' => $supporting_id])
                            ]

                        ]
                    ]
                ]);
            } catch (\Exception $e) {
            }
        }
    }
}
