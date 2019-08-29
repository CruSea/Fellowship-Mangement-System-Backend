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

Route::post('/sendMail', [
    'uses' => 'sendMailController@sendMail',
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
    Route::post('/search', [
        'uses' => 'UserController@searchUser',
    ]);
    
});
Route::group(['prefix' => 'users'], function() {
    Route::get('/', [
        'uses' => 'UserController@getUsers'
    ]);
    Route::get('/all', [
        'uses' => 'UserController@getUsers',
    ]);
});
Route::group(['prefix' => 'fellowship'], function() {
    Route::patch('/', [
        'uses' => 'FellowshipController@update',
    ]);
    Route::get('/', [
        'uses' => 'FellowshipController@show',
    ]);
});
Route::group(['prefix' => 'notification'], function() {
    Route::get('/{id}', [
        'uses' => 'NotificationController@show',
    ]);
    Route::delete('/{id}', [
        'uses' => 'NotificationController@delete',
    ]);
});
Route::delete('/notification-seen', [
    'uses' => 'NotificationController@seenNotification',
]);
Route::get('/notifications', [
    'uses' => 'NotificationController@getNotifications',
]);
Route::get('/under_graduates_number', [
    'uses' => 'DashboardController@underGraduateMembersNumber',
]);
Route::get('/this_year_graduates_number', [
    'uses' => 'DashboardController@ThisYearGraduateMembersNumber',
]);
Route::get('/post_graduates_number', [
    'uses' => 'DashboardController@postGraduateMembersNumber',
]);
Route::get('/number_of_teams', [
    'uses' => 'DashboardController@NumberOfTeams',
]);
Route::get('/number_of_events', [
    'uses' => 'DashboardController@NumberOfEvents',
]);
Route::get('/events_list', [
    'uses' => 'DashboardController@eventList',
]);
Route::get('/notify_today_messages', [
    'uses' => 'DashboardController@notifyTodayMessges',
]);
Route::get('/today_messages', [
    'uses' => 'DashboardController@numberOfTodaySentMessages',
]);
Route::get('/last_month_messages', [
    'uses' => 'DashboardController@numberOflastMonthSentMessages',
]);
Route::get('/total_messages', [
    'uses' => 'DashboardController@numberOfAllMessages',
]);
Route::get('/get_teams', [
    'uses' => 'DashboardController@getTeams',
]);
Route::get('/get_events', [
    'uses' => 'DashboardController@getEvents',
]);

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
    Route::post('/search', [
        'uses' => 'ContactController@searchContact',
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
    Route::post('/search', [
        'uses' => 'TeamController@searchTeam',
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
        'uses' => 'MessageController@removeContactMessage',
    ]);
    Route::post('/search', [
        'uses' => "MessageController@searchContactMessage",
    ]);
});
Route::group(['prefix' => 'team-message'], function() {
    Route::post('/', [
        'uses' => 'MessageController@sendTeamMessage'
    ]);
    Route::delete('/{id}', [
        'uses' => 'MessageController@deleteTeamMessage'
    ]);
    Route::post('/search', [
        'uses' => 'MessageController@searchTeamMessage',
    ]);
});
Route::get('/team-messages', [
    'uses' => 'MessageController@getTeamMessage'
]);
Route::group(['prefix' => 'post-graduate-team-message'], function() {
    Route::post('/', [
        'uses' => 'MessageController@sendPostGraduateTeamMessage',
    ]);
    Route::get('/', [
        'uses' => 'MessageController@getPostGraduateTeamMessage',
    ]);
    Route::post('/search', [
        'uses' => 'MessageController@searchPostGradauteTeamMessage',
    ]);
});
Route::group(['prefix' => 'fellowship-message'], function() {
    Route::post('/', [
        'uses' => 'MessageController@sendFellowshipMessage',
    ]);
    Route::post('/search', [
        'uses' => 'MessageController@searchFellowshipMessage',
    ]);
    Route::get('/', [
        'uses' => 'MessageController@getFellowshipMessage',
    ]);
    Route::patch('/{id}', [
        'uses' => 'MessageController@deleteFellowshipMessage',
    ]);
});
Route::group(['prefix' => 'post-graduate-fellowship-message'], function() {
    Route::post('/', [
        'uses' => 'MessageController@sendPostGraduateFellowshipMessage',
    ]);
    Route::get('/', [
        'uses' => 'MessageController@getPostGraduateFellowshipMessage',
    ]);
    Route::post('/search', [
        'uses' => 'MessageController@searchPostGraduateFellowshipMessage',
    ]);
});
Route::group(['prefix' => 'event-message'], function() {
    Route::post('/', [
        'uses' => 'MessageController@sendEventMessage',
    ]);
    Route::get('/', [
        'uses' => 'MessageController@getEventMessage',
    ]);
    Route::post('/search', [
        'uses' => 'MessageController@searchEventMessage',
    ]);
    Route::patch('/{id}', [
        'uses' => 'MessageController@deleteEventMessage',
    ]);
});
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
    Route::post('/search', [
        'uses' => 'EventController@searchEvent',
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
Route::get('/event/exportContacts/{name}', [
    'uses' => 'EventController@exportEventContact',
]);

// post GraduateControllers route
Route::group(['prefix' => 'post-graduate'], function() {
    Route::post('/', [
        'uses' => 'PostGraduatesController@addPostGraduateContact',
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
    Route::post('/search', [
        'uses' => 'PostGraduatesController@searchPostGraduate',
    ]);
});
Route::post('/importPostGraduateContacts', [
    'uses' => 'PostGraduatesController@importPostGraduateContact',
]);
Route::get('/exportPostGraduateContacts', [
    'uses' => 'PostGraduatesController@exportPostGraduateContact',
]);
Route::get('/post-graduates', [
    'uses' => 'PostGraduatesController@getPostGraduates',
]);

Route::group(['prefix' => 'post-graduate-team'], function() {
    Route::post('/', [
        'uses' => 'TeamController@addTeam',
    ]);
    Route::post('/importContacts/{name}', [
        'uses' => 'PostGraduateTeamController@importPostGraduateContactForTeam',
    ]);
    Route::get('/exportContacts/{name}', [
        'uses' => 'PostGraduateTeamController@exportPostGraduateTeamContact',
    ]);
    // Route::get('/{id}', [
    //     'uses' => 'PostGraduateTeamController@addPostGraduateMember',
    // ]);
    // 
    // Route::patch('/{id}', [
    //     'uses' => 'TeamController@updateTeam',
    // ]);
    // Route::delete('/{id}', [
    //     'uses' => 'TeamController@deleteTeam',
    // ]);
    Route::group(['prefix' => '/members/{name}'], function() {
        Route::post('/', [
            'uses' => 'PostGraduateTeamController@addPostGraduateMember',
        ]);
        Route::get('/', [
            'uses' => 'PostGraduateTeamController@seeMembers',
        ]);
        Route::delete('/{id}', [
            'uses' => 'PostGraduateTeamController@deleteMember',
        ]);

    });
});

// graduates route
Route::group(['prefix' => 'graduate'], function () {
    Route::post('/', [
        'uses' => 'GraduateController@addGraduate'
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
    Route::post('/search', [
        'uses' => 'GraduateController@searchGraduate',
    ]);
});
Route::post('/assign-graduate', [
    'uses' => 'GraduateController@assignGraduate'
]);
Route::post('/importGraduate', [
    'uses' => 'GraduateController@importGraduate',
]);
Route::get('/exportGraduate', [
    'uses' => 'GraduateController@exportThisYearGraduates',
]);
Route::get('/graduates', [
    'uses' => 'GraduateController@getGraduates',
]);
Route::group(['prefix' => 'scheduled-message'], function(){
    Route::post('/team', [
        'uses' => 'ScheduledMessageController@addMessageForTeam',
    ]);
    Route::post('/postGraduateTeam', [
        'uses' => 'ScheduledMessageController@addMessageForPostGraduateTeam'
    ]);
    Route::post('/fellowship', [
        'uses' => 'ScheduledMessageController@addMessageForFellowship',
    ]);
    Route::post('/postGraduateFellowship', [
        'uses' => 'ScheduledMessageController@addMessageForPostGraduateFellowship',
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
    Route::post('/search', [
        'uses' => 'ScheduledMessageController@searchScheduledMessage',
    ]);
});
Route::get('/scheduled-messages', [
    'uses' => 'ScheduledMessageController@getMessages',
]);
Route::group(['prefix' => 'alarm-message'], function() {
    Route::post('/team', [
        'uses' => 'AlarmMessageController@addMessageForTeam',
    ]);
    Route::post('/postGraduateTeam', [
        'uses' => 'AlarmMessageController@addMessageForPostGraduateTeam',
    ]);
    Route::post('/fellowship', [
        'uses' => 'AlarmMessageController@addMessageForFellowship',
    ]);
    Route::post('/postGraduateFellowship', [
        'uses' => 'AlarmMessageController@addMessageForPostGraduateFellowship',
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
    Route::post('/search', [
        'uses' => 'AlarmMessageController@searchAlarmMessage',
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
    Route::delete('/{id}', [
        'uses' => 'EventRegistrationController@deleteEventRegistrationForm',
    ]);
});
Route::post('/send-registration-for-unkown-contacts', [
    'uses' => 'EventRegistrationController@sendRegistrationForunknownContacts',
]);
Route::post('/send-registration-for-unkown-contact', [
    'uses' => 'EventRegistrationController@sendRegistrationForSingleunknownContact'
]);
Route::post('/search-registration-messages', [
    'uses' => 'EventRegistrationController@searchEventRegistration',
]);
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
Route::group(['prefix' => 'registration-key'], function() {
    Route::post('/', [
        'uses' => 'RegistrationKeyController@store',
    ]);
    Route::get('/{id}', [
        'uses' => 'RegistrationKeyController@show',
    ]);
    Route::patch('/{id}', [
        'uses' => 'RegistrationKeyController@update',
    ]);
    Route::delete('/{id}', [
        'uses' => 'RegistrationKeyController@delete',
    ]);
});
Route::get('/registration-keys', [
    'uses' => 'RegistrationKeyController@getRegistrationKeys',
]);
Route::group(['prefix' => 'parse-registration-message'], function() {
    Route::post('/', [
        'uses' => 'ParseRegistrationMessage@RegisterMembers'
    ]);
});

Route::group(['prefix' => 'sms_registered_member'], function() {
    Route::get('/{id}', [
        'uses' => 'SmsRegisteredMembersController@show',
    ]);
    Route::delete('/{id}', [
        'uses' => 'SmsRegisteredMembersController@removeRegisteredMember'
    ]);
});
Route::get('/sms_registered_members', [
    'uses' => 'SmsRegisteredMembersController@getMembers',
]);