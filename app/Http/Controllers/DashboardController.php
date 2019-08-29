<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Contact;
use App\Event;
use App\Team;
use App\TodayMessage;
use App\SentMessage;
use App\countMessage;
use App\ContactTeam;
use App\ContactEvent;

use JWTAuth;

class DashboardController extends Controller
{
    public function underGraduateMembersNumber() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$under_graduate_contact = Contact::where([['is_under_graduate', '=', 1], ['fellowship_id', '=', $user->fellowship_id]])->get();
    			$count = $under_graduate_contact->count();
    			return response()->json(['count' => $count], 200);
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
    public function ThisYearGraduateMembersNumber() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$this_year_graduate = Contact::where([['is_this_year_gc', '=', 1],['fellowship_id', '=', $user->fellowship_id]])->get();
    			$count = $this_year_graduate->count();
    			return response()->json(['count' => $count], 200);
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
    public function postGraduateMembersNumber() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$count = Contact::where([['is_under_graduate', '=', 0],['fellowship_id', '=', $user->fellowship_id]])->count();
    			// $count = $post_graduate_contact->count();
    			return response()->json(['count' => $count], 200);
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
    public function NumberOfTeams() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$team = Team::where('fellowship_id', '=', $user->fellowship_id);
    			$count = $team->count();
    			return response()->json(['count' => $count], 200);
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
    public function NumberOfEvents() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$event = Event::where('fellowship_id', '=', $user->fellowship_id);
    			$count = $event->count();
    			return response()->json(['count' => $count], 200);
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
    public function eventList() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$events = Event::where('fellowship_id', '=', $user->fellowship_id)->orderBy('id', 'desc')->paginate(10);
    			$count = count($events);
    			return response()->json(['count' => $events], 200);
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
    public function notifyTodayMessges() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $today_messages = TodayMessage::where('fellowship_id', '=', $user->fellowship_id)->orderBy('id', 'desc')->get();
                $count = TodayMessage::count();
                // if($count == 0) {
                //     return response()->json(['message'=> 'no message will be sent today'], 404);
                // }
                return response()->json(['today_messages' => $today_messages], 200);
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
    public function numberOfTodaySentMessages() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $today_sent_message = countMessage::where([['fellowship_id', '=', $user->fellowship_id], ['type', '=', 'today']])->first();
                $count_message = 0;
                if($today_sent_message) {
                    $count_message = $today_sent_message->count;
                }
                
                return response()->json(['count' => $count_message], 200);
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
    public function numberOflastMonthSentMessages() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $today_sent_message = countMessage::where([['fellowship_id', '=', $user->fellowship_id], ['type', '=', 'monthly']])->first();
                $count_message = 0;
                if($today_sent_message) {
                    $count_message = $today_sent_message->count;
                }
                return response()->json(['count' => $count_message], 200);
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    } 
    public function numberOfAllMessages() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $total_successfully_sent_message = SentMessage::where([['fellowship_id', '=', $user->fellowship_id], ['is_sent', '=', true]])->count();
                return response()->json(['count' => $total_successfully_sent_message], 200);
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
    public function getTeams() {
        try{
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $teams = Team::where('fellowship_id', '=', $user->fellowship_id)->orderBy('id', 'desc')->get();
                foreach ($teams as $team) {
                    $team->created_by = json_decode($team->created_by);
                    $countUnderGraduate = Contact::whereIn('id', ContactTeam::where('team_id','=', 
                    $team->id)->select('contact_id')->get())->where('is_under_graduate', '=', 1)->count();
                    $team->underGraduatesNumber = $countUnderGraduate;
                    $countPostGraduate  = Contact::whereIn('id', ContactTeam::where('team_id','=', 
                    $team->id)->select('contact_id')->get())->where('is_under_graduate', '=', 0)->count();
                    $team->postGraduatesNumber = $countPostGraduate;
                }
                return response()->json(['teams' => $teams], 200);
                
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
    public function getEvents() {
        try{
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $events = Event::where('fellowship_id', '=', $user->fellowship_id)->orderBy('id', 'desc')->get();
                foreach ($events as $event) {
                    $event->created_by = json_decode($event->created_by);
                    $countMembers = Contact::whereIn('id', ContactEvent::where('event_id','=', 
                    $event->id)->select('contact_id')->get())->count();
                    $event->numberOfMembers = $countMembers;
                    $countSmsRegisteredMembers  = Contact::whereIn('id', ContactEvent::where([['event_id','=', 
                    $event->id], ['through_sms', '=', true]])->select('contact_id')->get())->count();
                    $event->numberOfSmsMembers = $countSmsRegisteredMembers;
                }
                return response()->json(['events' => $events], 200);
                
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
}
