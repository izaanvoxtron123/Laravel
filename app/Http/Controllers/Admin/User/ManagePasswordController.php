<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Hash;
use Auth;
class ManagePasswordController extends Controller
{
    //
    public function show_update(){
        return view('admin.user.manage_password');
    }
    public function update(Request $request){
        // return Auth::user()->password;
        $validate = $request->validate([
            'old_password'=>'required',
            'new_password'=>'required',
            'confrim_password'=>'required',
        ]);
        if($request->old_password == Auth::user()->password){
            if($request->new_password== $request->confrim_password){
                $user = User::where('id',Auth::user()->id)->first();
                $user->password = Hash::make($request->new_password);
                $user->save();
                return redirect('/admin/manage-password')->with('status', 'success');
            }else{
                return redirect('/admin/manage-password')->with('status', 'error');
            }
        }else{
            return redirect('/admin/manage-password')->with('status', 'failed');
        }
        return $request->all();
    }
}
