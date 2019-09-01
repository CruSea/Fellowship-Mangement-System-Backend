<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notification;
use App\User;
use App\SeenNotification;
use App\RemovedNotification;
use JWTAuth;

class NotificationController extends Controller
{
    public function show($id) {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$notification = Notification::find($id);
    			if($notification instanceof Notification && $notification->fellowship_id == $user->fellowship_id) {
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
                $array = array();
                
       //          $notifications = Notification::whereIn('id', $array)->paginate(10);
       //          return response()->json(['notificatoins' => $notifications], 200);
    			$count_all_notification = Notification::where('fellowship_id', '=', $user->fellowship_id)->count();
                $count_seen_notification = 0;
                $seen_notification = SeenNotification::where('user_id', '=', $user->id)->first();
                if($seen_notification) {
                    $count_seen_notification = $seen_notification->no_seen_notification;
                }
                $count_unseen_notification = $count_all_notification - $count_seen_notification;
    			$is_removed = RemovedNotification::where('user_id', '=', $user->id)->exists();
                // $notifications = new Notification();
                $un_removed_notification = Notification::where('fellowship_id', '=', $user->fellowship_id)->get();
                foreach ($un_removed_notification as $noti) {
                    array_push($array, $noti->id);
                }

                if($is_removed) {
                    $removed_notification_id = RemovedNotification::where('user_id', '=', $user->id)->get();
                    foreach ($removed_notification_id as $removed_id) {
                        $array = array_diff($array, array($removed_id->notification_id));
                    }
                    
                }
                $notifications = Notification::whereIn('id', $array)->orderBy('id', 'desc')->paginate(10);

                if($count_all_notification == 0) {
                    return response()->json(['notifications' => $notifications, 'count' => 0], 200);
                }
    			return response()->json(['notifications' => $notifications, 'count' => $count_unseen_notification], 200);
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
    			if($notification instanceof Notification && $notification->fellowship_id == $user->fellowship_id) {
                    $is_removed = RemovedNotification::where([['user_id', '=', $user->id], ['notification_id', '=', $id]])->exists();
                    if($is_removed) {
                        return response()->json(['message' => 'notification already removed'], 200);
                    }
                    $remove_notification = new RemovedNotification();
                    $remove_notification->user_id = $user->id;
                    $remove_notification->notification_id = $notification->id;
                    if($remove_notification->save()) {
                        return response()->json(['message' => 'notification removed successfully'], 200);
                    }else {
                        return response()->json(['error' => 'Ooops! something went wrong'], 500);
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
    public function seenNotification() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $count_unseen_notification = 0;
                $count_notifications = Notification::where('fellowship_id', '=', $user->fellowship_id)->count();
                $seen_notification = SeenNotification::where('user_id', '=', $user->id)->first();
                if($seen_notification) {
                    if($seen_notification->no_seen_notification < $count_notifications) {
                        $count_unseen_notification = $count_notifications - $seen_notification->no_seen_notification; 
                    }
                    $seen_notification->no_seen_notification = $seen_notification->no_seen_notification + $count_unseen_notification;
                    $seen_notification->update();
                } else {
                    $new_seen_notification = new SeenNotification();
                    $new_seen_notification->user_id = $user->id;
                    $new_seen_notification->no_seen_notification = $count_notifications;
                    $new_seen_notification->save();
                }
                return response()->json(['message' => 'notification seen'], 200);
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
}
