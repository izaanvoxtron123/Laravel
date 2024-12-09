<?php

namespace App\Http\Controllers\Admin;

use App\Events\PhoneModified;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CustomerPhoneModificationLogs as MainModel;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class CustomerPhoneModificationLogsController extends Controller
{
    public $folder_name = 'customer_phone_modification_logs'; // For view routes and file calling and saving
    public $module_name = 'Customer Phone Modification Logs'; // For toast And page header

    function fetch(Request $request)
    {
        $query = new MainModel();

        if ($request->response != '') {
            $query = $query->where('response', $request->response);
        }

        $query = $query->orderBy('id', 'desc')->with(['customer', 'user']);

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
