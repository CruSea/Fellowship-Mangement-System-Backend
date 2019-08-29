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
    			$count_all_notification = Notification::where('fellowship_id', '=', $user->fellowship_id)->count();
                $count_seen_notification = 0;
                $seen_notification = SeenNotification::where('user_id', '=', $user->id)->first();
                if($seen_notification) {
                    $count_seen_notification = $seen_notification->no_seen_notification;
                }
                $count_unseen_notification = $count_all_notification - $count_seen_notification;
    			$is_removed = RemovedNotification::where('user_id', '=', $user->id)->exists();
                $notifications = new Notification();
                if($is_removed) {
                    $removed_notification_id = RemovedNotification::where('user_id', '=', $user->id)->get();
                    $notifications = new Notification();
                    foreach ($removed_notification_id as $removed_id) {
                        $notifications = Notification::where('id', '!=', $removed_id);
                    }

                }
                
    			// $notifications = Notification::where('fellowship_id', '=', $user->fellowship_id)->orderBy('id', 'desc')->paginate(1000);
                if($count_all_notification == 0) {
                    return response()->json(['notifications' => $notifications->orderBy('id', 'desc')->paginate(10), 'count' => 0], 200);
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
                    $remove_notification = new RemovedNotification();
                    $remove_notification->user_id = $user->id;
                    $remove_notification->notification_id = $notification->id;
                    $remove_notification->save();
    				// if($notification->delete()) {
    				// 	return response()->json(['message' => 'notification removed successfully'], 200);
    				// } else {
    				// 	return response()->json(['message' => 'Ooosp! something went wrong', 'error' => 'notification is not deleted'], 500);
    				// }
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
                // $notifications = Notification::all();
                // Notification::where([['is_seen', false], ['fellowship_id', '=', $user->fellowship_id]])
                //     ->chunkById(100, function ($notifications) {
                //         foreach ($notifications as $notification) {
                //             Notification::where('id', $notification->id)
                //                 ->update(['is_seen' => true]);
                //         }
                //     });
                $count_unseen_notification = 0;
                $count_notifications = Notification::where('fellowship_id', '=', $user->fellowship_id)->count();
                $seen_notification = SeenNotification::where('user_id', '=', $user->id)->first();
                if($seen_notification) {
                    // $seen_notification->user_id = $user->id;
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
                // $count = Notification::
                // foreach ($notifications as $notification) {
                //     $notification->is_seend = true;
                //     $notification->update();
                // }
                // if($notification instanceof Notification) {
                //     $notification->is_seen = true;
                //     $notification->update();
                // }
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
}
