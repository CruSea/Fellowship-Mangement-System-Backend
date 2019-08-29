<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Setting;
use JWTAuth;
class SettingController extends Controller
{
    protected $root_url;
    public function __construct() {
        $this->middleware('ability:,create-setting', ['only' => ['createSetting', 'getCampaigns', 'getSmsPorts']]);
        $this->middleware('ability:,get-setting', ['only' => ['getSetting', 'getSettings']]);
        $this->middleware('ability:,update-setting', ['only' => ['updateSetting']]);
        $this->middleware('ability:,delete-setting', ['only' => ['deleteSetting']]);
        // $this->root_url = "https://api.negarit.net/api/";
        $this->root_url = "https://api.negarit.net/api/";
    }
    public function createSetting() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $request = request()->only('value');
            $rule = [
                // 'name' => 'required|string|unique:settings',
                'value' => 'required|string|min:1'
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 500);
            }
            $old_setting = Setting::where([['name', '=', "API_KEY"], ['fellowship_id', '=', $user->fellowship_id]])->first();
            if($old_setting instanceof Setting) {
                $old_setting->name = $old_setting->name;
                $old_setting->fellowship_id = $old_setting->fellowship_id;
                $old_setting->value = $request['value'];
                if($old_setting->update()) {
                    return response()->json(['message' => 'setting successfully updated'], 200);
                }
                else {
                    return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'failed to create setting'], 500);
                }
            } else {
                $new_setting = new Setting();
                $new_setting->name = "API_KEY";
                $new_setting->fellowship_id = $user->fellowship_id;
                $new_setting->value = $request['value'];
                if($new_setting->save()) {
                    return response()->json(['message' => 'setting successfully created'], 200);
                } 
                else {
                    return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'failed to create setting'], 500);
                }
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getSetting($id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $setting = Setting::find($id);
            if($setting instanceof Setting && $setting->fellowship_id == $user->fellowship_id) {
                return response()->json(['setting', $setting], 200);
            }
            return response()->json(['message' => '404 error found', 'error' => 'setting was not fuond'], 404);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getSettings() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $settings = Setting::where('fellowship_id', '=', $user->fellowship_id)->get();
            $countSetting = $settings->count();
            if($countSetting == 0) {
                return response()->json(['settings' => $settings], 200);
            }
            return response()->json(['settings' => $settings], 200);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function updateSetting($id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $request = request()->only('value');
            $old_setting = Setting::find($id);

            
            if($old_setting instanceof Setting && $old_setting->fellowship_id == $user->fellowship_id) {
                $rule = [
                    'value' => 'required|string|min:1',
                ];
                $validator = Validator::make($request, $rule);
                if($validator->fails()) {
                    return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 500);
                }
                $old_setting->value = isset($request['value']) ? $request['value'] : $old_setting->value;
                if($old_setting->update()) {
                    return response()->json(['message' => 'setting updated successfully'], 200);
                }
                return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'setting is not updated successfully'], 500);
            }
            return response()->json(['message' => '404 error found', 'error' => 'setting was not found'], 404);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getCampaigns() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $API_KEY = Setting::where([['name', '=', 'API_KEY'], ['fellowship_id', '=', $user->fellowship_id]])->first();
            // return $this->root_url.'api_request/campaigns?API_KEY='.$API_KEY->value;
            if($API_KEY instanceof Setting) {
                $response = $this->sendGetRequest('https://api.negarit.net/api/', 'api_request/campaigns?API_KEY='.$API_KEY->value);
                $decoded_response = json_decode($response);
                if($decoded_response) {
                    if(isset($decoded_response->campaigns)) {
                        $campaigns = $decoded_response->campaigns;
                        for($i = 0; $i < count($campaigns); $i++) {
                            $campaign[] = ['id' => $campaigns[$i]->id, 'name' => $campaigns[$i]->name];
                        }
                        return response()->json(['campaigns' => $campaign], 200);
                    } else {
                        return response()->json(['response' => count($decoded_response)], 500);
                    }
                } else {
                    return response()->json(['message' => 'Ooops! something went wrong', 'response' => $decoded_response], 500);
                }
            } else {
                return response()->json(['message' => '404 error found', 'error' => 'API Key was not found'], 404);
            }
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
            $setting = Setting::where([['name', '=', 'API_KEY'], ['fellowship_id', '=', $user->fellowship_id]])->first();
            if($setting instanceof Setting) {
                $API_KEY = $setting->value;
                $negarit_response = $this->sendGetRequest($this->root_url, 'api_request/sms_ports?API_KEY='.$API_KEY);
                $decoded_response = json_decode($negarit_response);
                if($decoded_response) {
                    if(isset($decoded_response->sms_ports)) {
                        $smsPorts = $decoded_response->sms_ports;
                        for($i = 0; $i < count($smsPorts); $i++) {
                            $sms_name[] = ['id' => $smsPorts[$i]->id, 'name' => $smsPorts[$i]->name];
                        }
                        return response()->json(['sms ports' => $sms_name], 200);
                    }
                    return response()->json(['response' => $decoded_response], 500);
                }
                return response()->json(['message' => 'error found', 'error' => $decoded_response], 500);
            }
            return response()->json(['message' => 'error found', 'error' => 'setting was not found'], 404);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function deleteSetting($id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $setting = Setting::find($id);
            if($setting instanceof Setting && $setting->fellowship_id == $user->fellowship_id) {
                if($setting->delete()) {
                    return response()->json(['message' => 'setting deleted successfully'], 200);
                }
                else {
                    return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'setting is not deleted successfully'], 500);
                }
                return response()->json(['message' => '404 error found', 'error' => 'setting was not found'], 404);
            }
            return response()->json(['message' => 'setting was not found'], 400);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
}
