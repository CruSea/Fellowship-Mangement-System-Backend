<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Mail\RestPasswordMail;
use App\Http\Requests\RestPasswordRequest;
use App\User;
use Carbon\Carbon;
use Mail;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
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
        $this->middleware('guest');
    }
    public function resetPassword(RestPasswordRequest $request)
    {
        return $this->getPasswordRestTableRow($request)->count() > 0 ? $this->changePassword($request) : $this->tokenNotFoundResponse();
    }
    private function getPasswordRestTableRow($request) {
        return DB::table('password_resets')->where(['email' => $request->email]);
    }

    private function tokenNotFoundResponse() {
        return response()->json(['error' => 'Token or Email is in correct'], 422);
    }

    private function changePassword($request) {
        $user = User::whereEmail($request->email)->first();
        $user->update(['password' => bcrypt($request->password)]);
        $this->getPasswordRestTableRow($request)->delete();
        return response()->json(['message' => 'Password Successfully Changed'], 201);
    }
    
}
