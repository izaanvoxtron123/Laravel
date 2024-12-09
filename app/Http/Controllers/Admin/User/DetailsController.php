<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ User};
use Auth;
use DataTables;
use App\Http\Common\Helper;
class DetailsController extends Controller
{
    //
    public function show($id){
        $user = User::where('id',$id)->first();
        if($user->role_id=='1'){
            $user = User::where('id',$id)->first();
        }else{
            $user = User::where('id',$id)->first();
        }
        // return $user['subscription']['subscriptionPackage'];
        return view('admin.user.profile',['user'=>$user]);
    }
    public function show_all(Request $request){

        $user = User::get();
        
        return view('admin.user.all_users',['users'=>$user]);
    }
    public function edit_profile(Request $request){
        $user = Auth::user();
        if($request->isMethod('post')){
            $request->validate([
                'phone' => ['required', 'regex:/(03)[0-9]{9}$/']
            ]);
            
            $user->name = $request->name;
            $user->phone = $request->phone;
            $user->gender = $request->gender;
            $user->birth_date = $request->date_of_birth;
            
            if($request->hasFile('image')){
                $user->image =  $request->image->store('user', Helper::STATIC_ASSET_DISK);
            }
            $user->save();
            if($user){
                return back()->with('success', 'Success');
            }else{
                return back()->with('error', 'Success');
            }
        }else{
            return view('admin.user.user_profile',['user'=>$user]);
        }
    }
}
