<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\SmsRegisteredMembers;
use JWTAuth;

class SmsRegisteredMembersController extends Controller
{
    public function show($id) {
    	try {
	    	$user = JWTAuth::parseToken()->toUser();
	    	if($user instanceof User) {
	    		$regitered_member = SmsRegisteredMembers::find($id);
	    		if(!$regitered_member || $regitered_member->fellowship_id != $user->fellowship_id) {
	    			return response()->json(['error' => 'registered member is not found'], 404);
	    		}
	    		return response()->json(['registered_member' => $regitered_member], 200);
	    	} else {
	    		return response()->json(['error' => 'token expired'], 401);
	    	}
	    } catch(Exception $ex) {
	    	return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
	    }
    }
    public function getMembers() {
    	try {
	    	$user = JWTAuth::parseToken()->toUser();
	    	if($user instanceof User) {
	    		$regitered_members = SmsRegisteredMembers::where('fellowship_id', '=', $user->fellowship_id)->orderBy('id', 'desc')->paginate(5);
	    		return response()->json(['registered_members' => $regitered_members], 200);
	    	} else {
	    		return response()->json(['error' => 'token expired'], 401);
	    	}
	    } catch(Exception $ex) {
	    	return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
	    }
    }
    public function removeRegisteredMember($id) {
    	try{
	    	$user = JWTAuth::parseToken()->toUser();
	    	if($user instanceof User) {
	    		$regitered_member = SmsRegisteredMembers::find($id);
	    		if(!$regitered_member || $regitered_member->fellowship_id != $user->fellowship_id) {
	    			return response()->json(['error' => 'registered member is not found'], 404);
	    		}
	    		if($regitered_member->delete()) {
	    			return response()->json(['response' => 'registered member removed successfully'], 200);
	    		}
	    		else {
	    			return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'registered member is not removed'], 500);
	    		}
	    	} else {
	    		return response()->json(['error' => 'token expired'], 401);
	    	}
	    } catch(Exception $ex) {
	    	return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
	    }
    }
}
