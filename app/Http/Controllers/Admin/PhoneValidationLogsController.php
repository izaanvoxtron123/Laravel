<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PhoneValidationLogs as MainModel;
use App\Models\User;
use Auth;
use Yajra\Datatables\Datatables;

class PhoneValidationLogsController extends Controller
{
    public $folder_name = 'phone_validation_logs'; // For view routes and file calling and saving
    public $module_name = 'Phone Validation Logs'; // For toast And page header

    function fetch(Request $request)
    {
        $query = new MainModel();


        if ($request->response != '') {
            $query = $query->where('response', $request->response);
        }
        $query = $query->orderBy('id', 'desc');

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
