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
    public function storeSmsPort(Request $request) {
        try {
            $setting = Setting::where('name', '=', 'API_KEY')->first();
            if(!$setting) {
                return response()->json(['message' => 'API_KEY is not found', 'error' => 'please add api key in setting from negarit api'], 404);
            }
            $API_KEY = $setting->value;
            $rule = [
                'port_name' => 'required|string|min:4|unique:sms_ports'
            ];
            $validator = Validator::make($request->all(), $rule);
            if($validator->fails()) {
                return response()->json(['error' => 'min length of port name is 4'], 500);
            }
            $rules = [
                'negarit_sms_port_id' => 'required|integer',
                'negarit_campaign_id' => 'required|integer'
            ];
            $validators = Validator::make($request->all(), $rules);
            if($validators->fails()) {
                return response()->json(['message' => 'validation error', 'error' => $validators->messages()], 500);
            }
            $port_rule = [
                'port_type' => 'required|string'
            ];
            $port_validator = Validator::make($request->all(), $port_rule);
            if($port_validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => $port_validator->messages()], 500);
            }
            // check weather the sms port name exists before
            // $check_smsPort_existance = DB::table('sms_ports')->where('port_name', $request->input('port_name'))->exists();
            // if($check_smsPort_existance) {
            //     return response()->json(['error' => 'Ooops! this port name is occupied'], 403);
            // }
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'user is not found'], 404);
            }
            $fellowship_id = $user->fellowship_id;
            $smsPort = new SmsPort();
            $smsPort->port_name = $request->input('port_name');
            $smsPort->fellowship_id = $fellowship_id;
            


            $smsPort->api_key = $API_KEY;
            $smsPort->negarit_sms_port_id = $request->input('negarit_sms_port_id');
            $smsPort->negarit_campaign_id = $request->input('negarit_campaign_id');
            $smsPort->port_type = $request->input('port_type');
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
            $smsPort = SmsPort::find($id);
            if(!$smsPort) {
                return response()->json(['error' => 'sms port is not found'], 404);
            }
            return response()->json(['sms-port', $smsPort], 200);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getSmsPorts() {
        try {
            $smsPort = new SmsPort();
            return response()->json(['sms-ports' => $smsPort->paginate(10)], 200);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function updateSmsPort($id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            $smsPort = SmsPort::find($id);
            $request = request()->only('port_name', 'port_type', 'api_key', 'negarit_sms_port_id', 'negarit_campaign_id');
            if(!$user) {
                return response()->json(['error' => 'user is not found'], 404);
            }
            if(!$smsPort) {
                return response()->json(['message' => 'an error found', 'error' => 'sms port is not foudn'], 404);
            }
            $rule = [
                'port_name' => 'required|string|min:4'
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['error' => 'min length of port name is 4'], 500);
            }
            $rules = [
                'negarit_sms_port_id' => 'required|integer',
                'negarit_campaign_id' => 'required|integer'
            ];
            $validators = Validator::make($request, $rules);
            if($validators->fails()) {
                return response()->json(['message' => 'validation error', 'error' => $validators->messages()], 500);
            }
            $port_rule = [
                'api_key' => 'required|string',
                'port_type' => 'required|string'
            ];
            $port_validator = Validator::make($request, $port_rule);
            if($port_validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => $port_validator->messages()], 500);
            }
            // check weather the sms port name exists before
            $check_smsPort_existance = DB::table('sms_ports')->where('port_name', $request['port_name'])->exists();
            if($check_smsPort_existance && $request['port_name'] != $smsPort->port_name) {
                return response()->json(['error' => 'Ooops! this port name is occupied'], 403);
            }
            $smsPort->port_name = isset($request['port_name']) ? $request['port_name'] : $smsPort->port_name;
            $smsPort->port_type = isset($request['port_type']) ? $request['port_type'] : $smsPort->port_type;
            $smsPort->api_key = isset($request['api_key']) ? $request['api_key'] : $smsPort->api_key;
            $smsPort->negarit_sms_port_id = isset($request['negarit_sms_port_id']) ? $request['negarit_sms_port_id'] : $smsPort->negarit_sms_port_id;
            $smsPort->negarit_campaign_id = isset($request['negarit_campaign_id']) ? $request['negarit_campaign_id'] : $smsPort->negarit_campaign_id;
            if($smsPort->update()) {
                return response()->json(['message' => 'port updated successfully'], 200);
            }
            return response()->json(['message' => 'something went wrong', 'error' => 'sms port is not saved'], 500);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
}

// protected $fillable = ['message', 'sent_to', 'status', 'sent_by', 'created_at', 'updated_at'];
//     protected $table = 'sent_messages';

// $table->increments('id');
//         $table->string('message');
//         $table->string('sent_to');
//         // $table->string('status');
//         $table->boolean('is_sent');
//         $table->boolean('is_delivered');
//         $table->integer('sms_port_id')->unsigned()->nullable();
//         $table->foreign('sms_port_id')->references('id')->on('sms_ports')->onDelete('cascade');
//         $table->string('sent_by');
//         $table->timestamps();
//         });