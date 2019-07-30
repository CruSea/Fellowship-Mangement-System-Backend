<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Mail\RestPasswordMail;
use App\User;
use Carbon\Carbon;
use Mail;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }
    public function sendEmail(Request $request) {
        if(!$this->validateEmail($request->email)) {
            return $this->failedResponse();
        }
        $this->send($request->email);
        return $this->successResponse();
    }

    public function send($email) {
        $token = $this->createToken($email);
        Mail::to($email)->send(new RestPasswordMail($token));
    }
    public function createToken($email) {
        $old_token = DB::table('password_resets')->where('email', $email)->first();
        if($old_token) {
            return $old_token->token;
        }
        $token = str_random(60);
        $this->saveToken($token, $email);
        return $token;
    }
    public function saveToken($token, $email) {
        DB::table('password_resets')->insert([
                'email' => $email,
                'token' => $token,
                'created_at' => Carbon::now(),
        ]);
    }

    public function validateEmail($email) {
        return !!User::where('email', $email)->first();
    }

    public function failedResponse() {
        return response()->json(['error' => 'Email does\'t found'], 404);
    }
    public function successResponse() {
        return response()->json(['response' => 'Reset Email is send successfully, please check your inbox.'], 200);
    }
}
