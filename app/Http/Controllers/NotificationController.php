<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notification;

class NotificationController extends Controller
{
    public function getNotification() {
    	try {
    		
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops'])
    	}
    }
}
