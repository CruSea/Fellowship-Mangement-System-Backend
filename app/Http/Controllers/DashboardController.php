<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Contact;
use App\Event;
use App\Team;

use JWTAuth;

class DashboardController extends Controller
{
    public function underGraduateMembersNumber() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$under_graduate_contact = Contact::where([['is_under_graduate', '=', 1], ['fellowship_id', '=', $user->fellowship_id]])->get();
    			$count = $under_graduate_contact->count();
    			if($count == 0) {
    				return response()->json(['message' => 'not found yet'], 404);
    			}
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
    			if($count == 0) {
    				return response()->json(['message' => 'not found yet'], 404);
    			}
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
    			$post_graduate_contact = Contact::where([['is_under_graduate', '=', 0],['fellowship_id', '=', $user->fellowship_id]])->get();
    			$count = $post_graduate_contact->count();
    			if($count == 0) {
    				return response()->json(['message' => 'not found yet'], 404);
    			}
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
    			if($count == 0) {
    				return response()->json(['message' => 'not found yet'], 404);
    			}
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
    			if($count == 0) {
    				return response()->json(['message' => 'not found yet'], 404);
    			}
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
    			$events = Event::where('fellowship_id', '=', $user->fellowship_id)->paginate(10);
    			$count = count($events);
    			if($count == 0) {
    				return response()->json(['message' => 'event not found yet'], 404);
    			}
    			return response()->json(['count' => $events], 200);
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
}
