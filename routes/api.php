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
Route::post('/sendResetPasswordLink', [
    'uses' => 'ForgotPasswordController@sendEmail',
]);
Route::post('/resetPassword', [
    'uses' => 'ResetPasswordController@resetPassword'
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
    'uses' => 'UserController@getUsers'
]);
Route::group(['prefix' => 'fellowship'], function() {
    Route::patch('/', [
    'uses' => 'FellowshipController@update',
]);
Route::get('/', [
    'uses' => 'FellowshipController@show',
]);
});

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
Route::get('/exportContacts', [
    'uses' => 'ContactController@exportContact',
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
Route::get('/team/exportContacts/{name}', [
    'uses' => 'TeamController@exportTeamContact',
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
Route::post('/post-graduate-team-message', [
    'uses' => 'MessageController@sendPostGraduateTeamMessage',
]);
Route::get('/post-graduate-team-message', [
    'uses' => 'MessageController@getPostGraduateTeamMessage',
]);
Route::post('/fellowship-message', [
    'uses' => 'MessageController@sendFellowshipMessage',
]);
Route::get('/fellowship-message', [
    'uses' => 'MessageController@getFellowshipMessage',
]);
Route::post('/post-graduate-fellowship-message', [
    'uses' => 'MessageController@sendPostGraduateFellowshipMessage',
]);
Route::get('/post-graduate-fellowship-message', [
    'uses' => 'MessageController@getPostGraduateFellowshipMessage',
]);
Route::post('/event-message', [
    'uses' => 'MessageController@sendEventMessage',
]);
Route::get('/event-message', [
    'uses' => 'MessageController@getEventMessage',
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
    Route::group(['prefix' => 'members/{name}'], function() {
        Route::post('', [
            'uses' => 'EventController@assignContact',
        ]);
        Route::get('/', [
            'uses' => 'EventController@seeContacts',
        ]);
        Route::delete('/{id}', [
            'uses' => 'EventController@deleteContact',
        ]);
    });
});
Route::get('/events', [
    'uses' => 'EventController@getEvents',
]);
Route::post('event/addContact/{name}', [
    'uses' => 'EventController@addContact',
]);
Route::post('event/importContacts/{name}', [
    'uses' => 'EventController@importContactForEvent'
]);

// post GraduateControllers route
Route::group(['prefix' => 'post-graduate'], function() {
    Route::post('/', [
        'uses' => 'ContactController@addContact',
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

Route::group(['prefix' => 'post-graduate-team'], function() {
    // Route::post('/', [
    //     'uses' => 'TeamController@addTeam',
    // ]);
    Route::get('/{id}', [
        'uses' => 'TeamController@getTeam',
    ]);
    // 
    // Route::patch('/{id}', [
    //     'uses' => 'TeamController@updateTeam',
    // ]);
    // Route::delete('/{id}', [
    //     'uses' => 'TeamController@deleteTeam',
    // ]);
    Route::group(['prefix' => '/members/{name}'], function() {
        // Route::post('/', [
        //     'uses' => 'PostGraduateTeamController@addPostGraduateMember',
        // ]);
        Route::get('/', [
            'uses' => 'PostGraduateTeamController@seeMembers',
        ]);

    });
});

// graduates route
Route::group(['prefix' => 'graduate'], function () {
    Route::post('/', [
        'uses' => 'ContactController@addContact'
    ]);
    Route::get('/{id}', [
        'uses' => 'GraduateController@show',
    ]);
    Route::patch('/{id}', [
        'uses' => 'GraduateController@update'
    ]);
    Route::delete('/{id}', [
        'uses' => 'GraduateController@delete',
    ]);
});
Route::get('/graduates', [
    'uses' => 'GraduateController@getGraduates',
]);
Route::group(['prefix' => 'scheduled-message'], function(){
    Route::post('/team', [
        'uses' => 'ScheduledMessageController@addMessageForTeam',
    ]);
    Route::post('/fellowship', [
        'uses' => 'ScheduledMessageController@addMessageForFellowship',
    ]);
    Route::post('/event', [
        'uses' => 'ScheduledMessageController@addMessageForEvent',
    ]);
    Route::post('/contact', [
        'uses' => 'ScheduledMessageController@addMessageForSingleContact',
    ]);
    Route::get('/{id}', [
        'uses' => 'ScheduledMessageController@getMessage',
    ]);
    Route::patch('/{id}', [
        'uses' => 'ScheduledMessageController@updateMessage',
    ]);
    Route::delete('/{id}', [
        'uses' => 'ScheduledMessageController@deleteMessage',
    ]);
});
Route::get('/scheduled-messages', [
    'uses' => 'ScheduledMessageController@getMessages',
]);
Route::group(['prefix' => 'alarm-message'], function() {
    Route::post('/team', [
        'uses' => 'AlarmMessageController@addMessageForTeam',
    ]);
    Route::post('/fellowship', [
        'uses' => 'AlarmMessageController@addMessageForFellowship',
    ]);
    Route::post('/event', [
        'uses' => 'AlarmMessageController@addMessageForEvent',
    ]);
    Route::post('/contact', [
        'uses' => 'AlarmMessageController@addMessageForSingleContact',
    ]);
    Route::get('/{id}', [
        'uses' => 'AlarmMessageController@getMessage',
    ]);
    Route::patch('/{id}', [
        'uses' => 'AlarmMessageController@updateMessage',
    ]);
    Route::delete('/{id}', [
        'uses' => 'AlarmMessageController@deleteMessage',
    ]);
});
Route::get('/alarm-messages', [
    'uses' => 'AlarmMessageController@getMessages',
]);

Route::group(['prefix' => 'send-registration-message'], function() {
    Route::post('/team', [
        'uses' => 'EventRegistrationController@SendRegistrationFormForTeam',
    ]);
    Route::post('/fellowship', [
        'uses' => 'EventRegistrationController@sendRegistrationFormForFellowship',
    ]);
    Route::post('/contact', [
        'uses' => 'EventRegistrationController@sendRegistrationFormForSingleContact',
    ]);
    Route::get('/{id}', [
        'uses' => 'EventRegistrationController@getEventRegistrationForm',
    ]);
});
Route::get('/send-registration-messages', [
    'uses' => 'EventRegistrationController@getEventRegistrationForms',
]);
// Route::group(['prefix' => 'send-event-message'], function() {
//     Route::post('/', [
//         'uses' => 'SendMessageForEventRegistrationController@sendMessage'
//     ]);
    // Route::get('/', [
    //     // 'uses' => ''
    // ]);
// });
Route::group(['prefix' => 'parse-registration-message'], function() {
    Route::post('/', [
        'uses' => 'ParseRegistrationMessage@RegisterMembers'
    ]);
});