<?php

namespace App\Http\Controllers\Auth;

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
        // $user = User::where('id',Auth::user()->id)->first();
        //         $user->password = Hash::make($request->old_password );
        //         $user->save();
        //         return back()->with('status','success');
        // $value = Hash::make($request->old_password );
        // return $value .'-----'.Auth::user()->password;
        // return Auth::user()->password;
        $validate = $request->validate([
            'old_password'=>'required',
            'new_password'=>'required',
            'confrim_password'=>'required',
        ]);
        if(Hash::check($request->old_password, Auth::user()->password)){
            // return 'hello woooooooo';
            if($request->new_password == $request->confrim_password){
                $user = User::where('id',Auth::user()->id)->first();
                $user->password = Hash::make($request->new_password);
                $user->save();
                return redirect()->route('manage-password')->with('status', 'success');
            }else{
                return redirect()->route('manage-password')->with('status', 'error');
            }
        }else{
            // return 'test';
            return redirect()->route('manage-password')->with('status', 'failed');
        }
        return $request->all();
    }
}
