<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use App\SmsPort;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Setting;
use JWTAuth;

class NegaritController extends Controller
{
    public function __construct() {
        $this->middleware('ability:,store-sms-port', ['only' => ['storeSmsPort']]);
        $this->middleware('ability:,get-sms-port', ['only' => ['getSmsPort', 'getSmsPorts']]);
        $this->middleware('ability:,update-sms-port', ['only' => ['updateSmsPort']]);
        $this->middleware('ability:,delete-sms-port', ['only' => ['deleteSmsPort']]);
    }
    public function storeSmsPort(Request $request) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }

            $setting = Setting::where([['name', '=', 'API_KEY'], ['fellowship_id', '=', $user->fellowship_id]])->first();
            if(!$setting) {
                return response()->json(['error' => 'setting was not found'], 404);
            }
            $API_KEY = $setting->value;
            $rule = [
                'port_name' => 'required|string|min:4',
                'negarit_sms_port_id' => 'required|integer',
                'negarit_campaign_id' => 'required|integer',
                'port_type' => 'required|string'
            ];
            $validator = Validator::make($request->all(), $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 500);
            }
            
            $fellowship_id = $user->fellowship_id;

            // check sms port existance before
            $fellowship_smsPort = SmsPort::where([['port_name', '=', $request->input('port_name')],['fellowship_id', '=', $fellowship_id]])->first();
            if($fellowship_smsPort) {
                return response()->json(['error' => 'sms port has already been taken'], 400);
            }

            $smsPort = new SmsPort();
            $smsPort->port_name = $request->input('port_name');
            $smsPort->fellowship_id = $fellowship_id;
            
            $smsPort->api_key = $API_KEY;
            $smsPort->negarit_sms_port_id = $request->input('negarit_sms_port_id');
            $smsPort->negarit_campaign_id = $request->input('negarit_campaign_id');
            $smsPort->port_type = $request->input('port_type');
            $smsPort->created_by = $user;
            if($smsPort->save()) {
                return response()->json(['message' => 'port saved successfully'], 200);
            }
            return response()->json(['message' => 'something went wrong', 'error' => 'sms port is not saved'], 500);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex], 500);
        }
    }
    public function getSmsPort($id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $smsPort = SmsPort::find($id);
            if(!$smsPort || $smsPort->fellowship_id != $user->fellowship_id) {
                return response()->json(['error' => 'sms port is not found'], 404);
            }
            $smsPort->created_by = json_decode($smsPort->created_by);
            return response()->json(['sms_port', $smsPort], 200);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getSmsPorts() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $smsPorts = SmsPort::where('fellowship_id', '=', $user->fellowship_id)->orderBy('id', 'desc')->paginate(10);
            $countSmsPorts = $smsPorts->count();
            if($countSmsPorts == 0) {
                return response()->json(['sms_ports' => $smsPorts], 200);
            }
            for($i = 0; $i < $countSmsPorts; $i++) {
                    $smsPorts[$i]->created_by = json_decode($smsPorts[$i]->created_by);
                }
            return response()->json(['sms_ports' => $smsPorts], 200);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function updateSmsPort($id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            $request = request()->only('port_name', 'port_type', 'api_key', 'negarit_sms_port_id', 'negarit_campaign_id');

            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }

            $smsPort = SmsPort::find($id);
            
            if(!$smsPort || $smsPort->fellowship_id != $user->fellowship_id) {
                return response()->json(['message' => 'an error found', 'error' => 'sms port is not foudn'], 404);
            }
            $rule = [
                'port_name' => 'required|string|min:4',
                'negarit_sms_port_id' => 'required|integer',
                'negarit_campaign_id' => 'required|integer',
                'port_type' => 'required|string'
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
            }
            // check weather the sms port name exists before
            $check_smsPort_existance = SmsPort::where([['port_name', '=',$request['port_name']], ['fellowship_id', '=', $user->fellowship_id]])->exists();
            if($check_smsPort_existance && $request['port_name'] != $smsPort->port_name) {
                return response()->json(['message' => 'duplication error', 'error' => 'Sms Port has already been taken.'], 400);
            }

            $smsPort->port_name = isset($request['port_name']) ? $request['port_name'] : $smsPort->port_name;
            $smsPort->port_type = isset($request['port_type']) ? $request['port_type'] : $smsPort->port_type;
            $smsPort->api_key = isset($request['api_key']) ? $request['api_key'] : $smsPort->api_key;
            $smsPort->negarit_sms_port_id = isset($request['negarit_sms_port_id']) ? $request['negarit_sms_port_id'] : $smsPort->negarit_sms_port_id;
            $smsPort->negarit_campaign_id = isset($request['negarit_campaign_id']) ? $request['negarit_campaign_id'] : $smsPort->negarit_campaign_id;
            $smsPort->created_by = $user;
            if($smsPort->update()) {
                return response()->json(['message' => 'port updated successfully'], 200);
            }
            return response()->json(['message' => 'something went wrong', 'error' => 'sms port is not saved'], 500);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function deleteSmsPort($id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $smsPort = SmsPort::find($id);
            if(!$smsPort || $smsPort->fellowship_id != $user->fellowship_id) {
                return response()->json(['error' => 'sms port is not found'], 404);
            }
            if($smsPort->delete()) {
                return response()->json(['message' => 'sms port deleted successfully'], 200);
            } else {
                return response()->json(['message' => 'Ooops! something went wrong','error' => 'sms port is not deleted'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
}