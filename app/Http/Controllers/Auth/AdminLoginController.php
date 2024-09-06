<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;use DB;
use Illuminate\Support\Facades\Hash;
use App\User;
class AdminLoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::ADMINHOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm(){


        return view('auth.login');
    }

    public function postLogin(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $validate_admin = DB::table('users')->select('*')->where('email', $request->email)->first();

        if ($validate_admin) {
            \Log::info('Found user: ', ['email' => $request->email]);
            if (Hash::check($request->password, $validate_admin->password)) {
                Auth::loginUsingId($validate_admin->id);
                \Session::put('success','You are logged in successfully!!');
                return redirect()->route('admin-dashboard');
            } else {
                \Log::info('Password check failed for user: ', ['email' => $request->email]);
                return redirect()->back()->with('error','Your username or password is incorrect.');
            }
        } else {
            \Log::info('User not found with email: ', ['email' => $request->email]);
            return redirect()->back()->with('error','Your username or password is incorrect.');
        }
    }


    // custom logout function
    // redirect to login page
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($response = $this->loggedOut($request)) {
            return $response;
        }

        return $request->wantsJson()
            ? new Response('', 204)
            : redirect('/admin/login');
    }
}
