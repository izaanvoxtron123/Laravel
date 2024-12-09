<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Role, User as MainModel};
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public $folder_name = 'user_management'; // For view routes and file calling and saving
    public $module_name = 'Users'; // For toast And page heade

    public function view(){
        // $data['roles'] = Role::where('id', MainModel::CUSTOMER_ROLE)->get();
        // $file = "admin.".$this->folder_name.".view";
        return view("admin.".$this->folder_name.".view");
    }
    
    public function fetch(Request $request){
        $query = MainModel::where('role_id', MainModel::CUSTOMER_ROLE)->with(['role']);
        if( $request->subscribed == 1 ) {
            $query = $query->whereHas('subscription');
        }
        if( $request->newsletter_subscribed == 1 ) {
            $query = $query->whereHas('newsletter');
        }
        if( $request->status != '' ) {
            $query = $query->where('status', $request->status);
        }
        return DataTables::of($query)->make(true);
    }
}
