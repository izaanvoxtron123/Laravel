<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ExternalCallLogs as MainModel;
use App\Models\User;
use Auth;
use Yajra\Datatables\Datatables;

class ExternalCallLogsController extends Controller
{
    public $folder_name = 'external_call_logs'; // For view routes and file calling and saving
    public $module_name = 'Daily Call Logs'; // For toast And page header

    function fetch(Request $request)
    {
        $query = new MainModel();

        if ($request->response != '') {
            $query = $query->where('response', $request->response);
        }
        $query = $query->select(['server_ip', 'extension', 'number_dialed', 'caller_code', 'start_time', 'end_time'])
            ->orderBy('uniqueid', 'desc');

        return DataTables::of($query)->make(true);
    }
    function view()
    {
        $data['folder_name'] = $this->folder_name;
        $data['module_name'] = $this->module_name;
        $file = "admin." . $this->folder_name . ".view";
        return view($file, $data);
    }
}
