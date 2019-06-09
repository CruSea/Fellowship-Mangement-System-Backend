<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use App\SmsPort;
use Illuminate\Http\Request;
use JWTAuth;

class NegaritController extends Controller
{
    public function storeSmsPort(Request $request) {
        try {
            $rule = [
                'port_name' => 'required|string|min:4'
            ];
            $validator = Validator::make($request->all(), $rule);
            if($validator->fails()) {
                return response()->json(['error' => 'min length of port name is 4'], 500);
            }
            $rules = [
                'fellowship_id' => 'required|integer',
                'negarit_sms_port_id' => 'required|integer',
                'negarit_campaign_id' => 'required|integer'
            ];
            $validators = Validator::make($request->all(), $rules);
            if($validators->fails()) {
                return response()->json(['message' => 'validation error', 'error' => 'values are not valid'], 500);
            }
            $api_port_rule = [
                'api_key' => 'required|string',
                'port_type' => 'required|string'
            ];
            $api_port_validator = Validator::make($request->all(), $api_port_rule);
            if($api_port_validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => 'something went wrong in port type or api key'], 500);
            }
            // check weather the sms port name exists before
            $check_smsPort_existance = DB::table('sms_ports')->where('port_name', $request->input('port_name'))->exists();
            if($check_email_existance) {
                return response()->json(['error' => 'Ooops! this port name is occupied'], 403);
            }
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'user is not found'], 404);
            }
            $fellowship_id = $user->fellowship_id;
            $smsPort = new SmsPort();
            $smsPort->port_name = $request->input('port_name');
            $smsPort->fellowship_id = $fellowship_id;
            $smsPort->port_type = $request->input('port_type');
            $smsPort->api_key = $request->input('api_key');
            $smsPort->negarit_sms_port_id = $request->input('negarit_sms_port_id');
            $smsPort->negarit_campaign_id = $request->input('negarit_campaign_id');
            if($smsPort->save()) {
                return response()->json(['message' => 'port saved successfully'], 200);
            }
            return response()->json(['message' => 'something went wrong', 'error' => 'sms port is not saved'], 500);
        } catch(Exception $ex) {
            return resposne()->json(['message' => 'Ooops! something went wrong', 'error' => $ex], 500);
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