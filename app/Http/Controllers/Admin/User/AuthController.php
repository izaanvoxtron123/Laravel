<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{User};
use Illuminate\Support\Facades\Auth;
use App\Http\Common\Constant;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Hash;
use App\Http\Common\Helper;
use App\Http\Common\EmailHelper;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        if ($request->isMethod('post')) {

            $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);
            $constants = new Constant();
            $user = User::where(['email' => $request->email])
                ->first();
            if ($user) {

                if (!$user->status) {
                    return back()->withErrors(['invalid_credentials' => 'Error Logging in, Please contact Administrator.']);
                }

                if (Hash::check($request->password, $user->password)) {

                    if (!$user->multi_device_login) {
                        if ($user->is_online) {
                            // return back()->withErrors(['invalid_credentials' => 'Please logout from other device.']);

                            User::where('id', $user->id)
                                ->update([
                                    'is_online' => false
                                ]);

                            DB::table('sessions')
                                ->where('user_id', $user->id)
                                ->delete();
                        }
                    }

                    if ($user->office) {
                        if ($user->office->status != true || $user->office->can_login != true)
                            return back()->withErrors(['invalid_credentials' => 'Error Logging in, Please contact Administrator.']);
                    }

                    Auth::login($user);

                    $user->is_online = true;
                    $user->save();

                    $request->session()->regenerate();
                    return redirect()->intended(RouteServiceProvider::HOME);
                } else {
                    return back()->withErrors(['invalid_credentials' => 'Invalid Credentials']);
                }
            } else {
                return back()->withErrors(['invalid_credentials' => 'Invalid Credentials']);
            }
        } else {
            if (!Auth::check()) {
                return view('auth.login');
            } else {
                // Redirect to a different page if the user is already logged in
                return redirect()->route('admin.home');
            }
        }
    }

    public function signup(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'name' => ['required'],
                'phone' => ['required'],
                'email' => ['required', 'email'],
                'password' => 'required|confirmed|min:6',
            ]);
            $otp = Helper::generateOTP();
            $constants = new Constant();
            $user = [
                'role_id' => $constants->ARTIST_ROLE_ID,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->name),
                'otp' => $otp,
            ];

            if ($created_user = User::create($user)) {
                Helper::toast('success', 'User Created.');
                $template_path = 'email_templates.artist_signup';
                $template_data = [
                    "otp" => $otp,
                ];
                $to_email = $request->email;
                $subject = "Welcome to SketchBook";
                EmailHelper::sendMail($template_path, $template_data, $to_email, $subject);

                return redirect(route('verify', ['id' => encrypt($created_user->id)]));
            } else {
                Helper::toast('error', 'Something went wrong');
            }
        } else {
            return view('auth.signup');
        }
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        $user->is_online = false;
        $user->save();

        Auth::logout();
        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function verify(Request $request, $id)
    {
        $id = decrypt($id);
        if ($request->isMethod('POST')) {
            $user = User::find($id);
            if (!isset($user)) {
                Helper::toast('error', 'Something went wrong');
            }
            if ($user->otp != $request->verification_code) {
                return back()->withErrors(['invalid_credentials' => 'Invalid Verification Code']);
            }
            $user->is_email_verified = 1;
            $user->save();

            Auth::login($user);
            Helper::toast('success', 'Email Verified.');
            return redirect(route('admin.home'));
        } else {
            return view('auth.verify_email', ['id' => $id]);
        }
    }
}
