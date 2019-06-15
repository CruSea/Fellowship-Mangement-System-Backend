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
    
    Route::group(['prefix' => '{name}/members'], function() {
        // assign contact a team
        Route::post('/{id}', [
            'uses' => 'TeamController@assignMembers',
        ]);
        Route::get('/', [
            'uses' => 'TeamController@seeMembers',
        ]);
        Route::patch('/{id}', [
            'uses' => 'TeamController@updateMember',
        ]);
        Route::delete('/{id}', [
            'uses' => 'TeamController@deleteMember',
        ]);
    });
});
Route::post('{name}/addMember', [
    'uses' => 'TeamController@addMember',
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
Route::post('group-message', [
    'uses' => 'MessageController@sendGroupMessage'
]);
Route::get('messages', [
    'uses' => 'MessageController@getContactsMessages',
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
});
Route::get('/sms-ports', [
    'uses' => 'NegaritController@getSmsPorts',
]);
