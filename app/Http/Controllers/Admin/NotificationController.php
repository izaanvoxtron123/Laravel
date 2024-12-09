<?php

namespace App\Http\Controllers\Admin;

use App\Http\Common\FcmHelper;
use App\Http\Controllers\Controller;
use App\Models\Notifications;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{

    function test(Request $request)
    {
        $fcm = new FcmHelper;
        $user = Auth::user();
        $tokens = $user->fcm_tokens->pluck('token')->toArray();

        $fcm->push($tokens, "TEST TITLE", "TEST BODY", "report_request", 1);
        $fcm->create(0, 1, "TEST TITLE", "TEST BODY", "report_request", 1);
    }

    function notifications(Request $request, $limit = 1, $offset = null)
    {
        $user = Auth::user();
        if ($limit == 1) {
            $query_limit = 50;
        } else {
            $query_limit = 50 + ($limit - 1) * 20;
        }
        $query = Notifications::where(function ($query) use ($user) {
            $query->where('receiver_id', $user->id)
                ->orWhere(function ($query) use ($user) {
                    $query->where('receiver_id', 0)
                        ->where('receiver_role', $user->role_id);
                });
        })->orderBy('created_at', 'desc');

        $query->limit($query_limit);
        $notifications = $query->get();

        $data = [
            'notifications' => $notifications,
            'limit' => $limit,
            'more_notifications' => $notifications->count() == $query_limit,
        ];
        return view('admin.notifications.view', $data);
    }


    function notificationsAjax(Request $request, $offset = 0)
    {
        try {

            $offset = (int) $offset; // Ensure offset is an integer
            $user = auth()->user();
            $notifications = $user->notifications(20, $offset);

            return [
                "status" => true,
                "data" => $notifications,
            ];
        } catch (\Exception $e) {
            return [
                "status" => false,
                "data" => [],
            ];
        }
    }

    public function readNotifications(Request $request)
    {
        $notification_ids = $request->input('notification_ids');
        Notifications::whereIn('id', $notification_ids)->update(['read_status' => 1]);

        return response()->json(['success' => true]);
    }

    function redirect(Request $request, $module, $supporting_id)
    {
        switch ($module) {
            case "customer_profile";
                return redirect(route('customer-profile', ['customer_id' => encrypt($supporting_id)]));
                break;
            case "report_request";
                return redirect(route('report_request-edit', ['id' => encrypt($supporting_id)]));
                break;

            default:
                return back();
        }

        // return [
        //     "module" => $module,
        //     "supporting_id" => $supporting_id,
        // ];
    }
}
