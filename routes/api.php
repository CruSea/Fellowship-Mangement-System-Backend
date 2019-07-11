<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Auth;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
// use App\Http\Controllers\MessageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/signup', [
    'uses' => 'RegisterController@signup']);

Route::post('/signin', [
    'uses' => 'LoginController@signin'
]);
Route::group(['prefix' => 'user'], function() {
    Route::post('/', [
        'uses' => 'UserController@store'
    ]);
    Route::get('/', ['as' => 'getMe',
        'uses' => 'UserController@getMe',
        //'middleware' => 'superAdmin'
    ]);
    Route::patch('/', ['as' => 'updateUser',
        'uses' => 'UserController@updateUser',
    ]);
    Route::patch('/editPassword', ['as' => 'updatePassword',
        'uses' => 'UserController@updatePassword',
    ]);
    Route::patch('/editStatus/{id}', ['as' => 'updateUserStatus',
        'uses' => 'UserController@updateUserStatus',
    ]);
    Route::patch('/editRole/{id}', ['as' => 'updateUserRole',
        'uses' => 'UserController@updateUserRole',
    ]);
    Route::get('/getUserRole/{id}', ['as' => 'getUserRole',
        'uses' => 'UserController@getUserRole',
    ]);
    Route::delete('/{id}', ['as' => 'deleteUser',
        'uses' => 'UserController@deleteUser',
    ]);
    
});
Route::get('/users', [
    'uses' => 'UserController@getUsers']);
// Route::post('/importUsers', [
//     'uses' => 'UserController@importExcel'
// ]);
// contact Routes
Route::group(['prefix' => 'contact'], function() {
    Route::post('/', [
        'uses' => 'ContactController@addContact'
    ]);
    Route::get('/{id}', [
        'uses' => 'ContactController@getContact'
    ]);
    Route::patch('/{id}', [
        'uses' => 'ContactController@updateContact'
    ]);
    Route::delete('/{id}', [
        'uses' => 'ContactController@deleteContact'
    ]);
});
Route::get('/contacts', [
    'uses' => 'ContactController@getContacts'
]);
Route::post('/importContacts', [
    'uses' => 'ContactController@importContact'
]);
// Route::post('/addTeam', [
//     'uses' => 'ContactController@addTeam',
// ]);

// Team Routes
Route::group(['prefix' => 'team'], function() {
    Route::post('/', [
        'uses' => 'TeamController@addTeam',
    ]);
    Route::get('/{id}', [
        'uses' => 'TeamController@getTeam',
    ]);
    Route::patch('/{id}', [
        'uses' => 'TeamController@updateTeam',
    ]);
    Route::delete('/{id}', [
        'uses' => 'TeamController@deleteTeam',
    ]);
    
    Route::group(['prefix' => '/members/{name}'], function() {
        // assign contact a team
        Route::post('/', [
            'uses' => 'TeamController@assignMembers',
        ]);
        Route::get('/', [
            'uses' => 'TeamController@seeMembers',
        ]);
        Route::patch('/{id}', [
            'uses' => 'TeamController@updateMemberTeam',
        ]);
        Route::delete('/{id}', [
            'uses' => 'TeamController@deleteMember',
        ]);
    });
});
Route::post('team/addMember/{name}', [
    'uses' => 'TeamController@addMember',
]);
Route::post('team/importContacts/{name}', [
    'uses' => 'TeamController@importContactForTeam'
]);
Route::get('/teams', [
    'uses' => 'TeamController@getTeams',
]);

// Message Routes
Route::group(['prefix' => 'message'], function(){
    Route::post('/', [
        'uses' => 'MessageController@sendContactMessage',
    ]);
    Route::get('/{id}', [
        'uses' => 'MessageController@getContactMessage',
    ]);
    Route::delete('/{id}', [
        'uses' => 'MessageController@deleteContactMessage',
    ]);
});
Route::post('/team-message', [
    'uses' => 'MessageController@sendTeamMessage'
]);
Route::get('/team-messages', [
    'uses' => 'MessageController@getTeamMessage'
]);
Route::delete('/team-message/{id}', [
    'uses' => 'MessageController@deleteTeamMessage'
]);
Route::get('/messages', [
    'uses' => 'MessageController@getContactsMessages',
]);
Route::get('/respond-messages', [
    'uses' => 'MessageController@getNegaritRecievedMessage'
]);

// sms-port routes
Route::group(['prefix' => 'sms-port'], function () {
    Route::post('/', [
        'uses' => 'NegaritController@storeSmsPort',
    ]);
    Route::get('/{id}', [
        'uses' => 'NegaritController@getSmsPort',
    ]);
    Route::patch('/{id}', [
        'uses' => 'NegaritController@updateSmsPort',
    ]);
    Route::delete('/{id}', [
        'uses' => 'NegaritController@deleteSmsPort']);
});
Route::get('/sms-ports', [
    'uses' => 'NegaritController@getSmsPorts',
]);

// setting routes
Route::group(['prefix' => 'setting'], function () {
    Route::post('/', [
        'uses' => 'SettingController@createSetting',
    ]);
    Route::get('/{id}', [
        'uses' => 'SettingController@getSetting',
    ]);
    Route::patch('/{id}', [
        'uses' => 'SettingController@updateSetting',
    ]);
    Route::delete('/{id}', [
        'uses' => 'SettingController@deleteSetting',
    ]);
});
Route::get('/settings', [
    'uses' => 'SettingController@getSettings',
]);
Route::get('/campaigns', [
    'uses' => 'SettingController@getCampaigns',
]);
Route::get('/get-sms-ports', [
    'uses' => 'SettingController@getSmsPorts',
]);

// event route
Route::group(['prefix' => 'event'], function() {
    Route::post('/', [
        'uses' => 'EventController@store',
    ]);
    Route::get('/{id}', [
        'uses' => 'EventController@show',
    ]);
    Route::patch('/{id}', [
        'uses' => 'EventController@update',
    ]);
    Route::delete('/{id}', [
        'uses' => 'EventController@delete',
    ]);
});
Route::get('/events', [
    'uses' => 'EventController@getEvents',
]);

// post graduates route
Route::group(['prefix' => 'post-graduate'], function() {
    Route::post('/', [
        'uses' => 'PostGraduatesController@store',
    ]);
    Route::get('/{id}', [
        'uses' => 'PostGraduatesController@show',
    ]);
    Route::patch('/{id}', [
        'uses' => 'PostGraduatesController@update',
    ]);
    Route::delete('/{id}', [
        'uses' => 'PostGraduatesController@delete',
    ]);
});
Route::get('/post-graduates', [
    'uses' => 'PostGraduatesController@getPostGraduates',
]);