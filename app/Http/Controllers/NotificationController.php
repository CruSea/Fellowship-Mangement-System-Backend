<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notification;
use App\User;
use JWTAuth;

class NotificationController extends Controller
{
    public function show($id) {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$notification = Notification::find($id);
    			if($notification instanceof Notification) {
    				return response()->json(['notification' => $notification], 200);
    			} else {
    				return response()->json(['error' => 'notification not found'], 404);
    			}
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
    public function getNotifications() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$count = Notification::count();
    			if($count == 0) {
    				return response()->json(['notification' => 0], 404);
    			} 
    			$notifications = Notification::paginate(10);
    			return response()->json(['notifications' => $notifications], 200);
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
    public function delete($id) {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$notification = Notification::find($id);
    			if($notification instanceof Notification) {
    				if($notification->delete()) {
    					return response()->json(['message' => 'notification removed successfully'], 200);
    				} else {
    					return response()->json(['message' => 'Ooosp! something went wrong', 'error' => 'notification is not deleted'], 500);
    				}
    			} else {
    				return response()->json(['error' => 'notfication not found'], 404);
    			}
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
}
