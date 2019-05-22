<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\User;
use Tymon\JWTAuth\Exceptions\JWTException;
use JWTAuth;

class LoginController extends Controller
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
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    protected function signin(Request $request) {
        try {
            $rules = [
                'email' => 'required|email|max:255',
                'password' => 'required|min:6|max:255',
            ];
            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()) {
                return response()->json(['status' => false, 'error' => 'validation error'], 500);
            }
            $credential = $request->only('email', 'password');
           // $status = DB::table('users')->where('email', $request->input('email'))->value('status');
            try {
                if(!$token = JWTAuth::attempt($credential)) {
                    return response()->json(['message' => 'Invalid token'], 401);
                }
                $status = JWTAuth::toUser($token);
                if($status->status == false) {
                    return response()->json(['error' => 'Inactive Account', 'message' => 'your account is not active yet'], 500);
                }
                return response()->json(['token' => $token,'message' => 'you logged in successfully'], 200);
            }catch(JWTException $e) {
                return response()->json(['message' => 'token is not created'], 500);
            }
        }
       catch(Exception $ex) {
           return response()->json(['status' => false, 'error' => $exception->getMessage()], 500);
       }
    }
}
