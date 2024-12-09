<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerLogs;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class CustomerLogsController extends Controller
{

    function getLogs(Request $request, $customer_id)
    {
        // try {

        $logs = CustomerLogs::where('customer_id', $customer_id)->with(['actionBy', 'actionBy.role'])->orderBy('created_at', 'desc')->get();

        // dd($logs);
        // $customer_logs = [];
        // if (count($logs)) {
        //     foreach ($logs as $key => $log) {
        //         $formatted_timestamp = Carbon::parse($log->created_at)->isoFormat('lll');
        //         switch ($log->type) {
        //             case CustomerLogs::CUSTOMER_CREATED:
        //                 if ($log->supporting_id != null) {
        //                     $agent = User::find($log->supporting_id);
        //                     $customer_logs[] = "Customer created by <b>" . $agent->name . "</b> on " . $formatted_timestamp;
        //                 } else {
        //                     $customer_logs[] = "Customer created on " . $formatted_timestamp;
        //                 }
        //                 break;
        //             case CustomerLogs::REPORT_FETCHED:
        //                 $customer_logs[] = "Report Fetched on " . $formatted_timestamp;
        //                 break;
        //             case CustomerLogs::SUBMITTED:
        //                 $customer_logs[] = "Marked as submitted on " . $formatted_timestamp;
        //                 break;
        //             case CustomerLogs::MARKED_INCOMPLETE:
        //                 $customer_logs[] = "Marked as incomplete on " . $formatted_timestamp;
        //                 break;
        //             case CustomerLogs::PROGRESS_UPDATED:
        //                 $customer_logs[] = "Progress updated to " . $log->supporting_text . " on " . $formatted_timestamp;
        //                 break;
        //         }
        //     }
        // }
        // $logs = [
        //     'Customer created on <i>12-12-12</i>',
        //     'Harsham Agent assigned on 12-12-12',
        //     'Report Fetched on 12-12-12',
        //     'Progress updated to NA on 12-12-12',
        //     'Marked as submitted on 12-12-12',
        // ];

        // dd($customer_logs);
        $data['logs'] = $logs;
        $html = view('admin.customer.logs', $data)->render();
        return [
            "status" => true,
            "message" => "success",
            "data" => $html
        ];
        // } catch (Exception $e) {
        //     return [
        //         "status" => false,
        //         "message" => "Something went wrong",
        //         "data" => null
        //     ];
        // }
    }

    function createLog(Request $request)
    {
        try {
            CustomerLogs::createLog($request->customer_id, $request->type, $request->supporting_text, $request->supporting_id, $request->payload);
        } catch (Exception $e) {
        }
    }
}
