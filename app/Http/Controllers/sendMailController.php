<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use App\Mail\RestPasswordMail;
use Mail;

class sendMailController extends Controller
{
	use SendsPasswordResetEmails;

    public function sendMail() {
    	$token = "Eyosias Desta";
    	$to_email = "eyosiasdesta10@gmail.com";
    	$data = array('name'=>"Sam Jose", "body" => "Test mail");
    	Mail::to($to_email)->send(new RestPasswordMail($token), $data, function($message) use ($token, $to_email) {
		    $message->to($to_email, $token)
		            ->subject('Hello '.$token);
		    $message->from('mamesmsdesta@gmail.com','Eyosias Desta');
		});
    	// Mail::to($email)->send(new RestPasswordMail($token));
		// $to_email = "mamesmsdesta@gmail.com";
		// $data = array("name"=>"Eyosias Desta", "body" => "A test mail one");
		// Mail::send(new RestPasswordMail($token), $data, function($message) use ($token, $to_email) {
		// $message->to($to_email, $token)
		// ->subject("Laravel Test Mail");
		// $message->from("SENDER_EMAIL_ADDRESS","Test Mail");
		// return response()->json(['message' => 'email successfully sent'], 200);
		// });
		// print_r(error_get_last());
		return response()->json(['message' => 'email successfully sent2', 'error' => error_get_last()], 200);
    }
}
