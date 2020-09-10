<?php

use Illuminate\Http\Request;

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


//
// Auth Required: There is a ->middleware('auth:api') in __construct() for these:
//

Route::get('context', 'ContextController@index');


//Route::apiResource('events', 'EventController');
Route::get('events', 'EventController@index');
Route::post('events', 'EventController@store');
Route::get('events/{event}', 'EventController@show');
Route::put('events/{event}', 'EventController@update');
Route::patch('events/{event}', 'EventController@update');
Route::delete('events/{event}', 'EventController@destroy');

Route::post('events/{event}/going', 'EventController@post_going');
Route::post('events/{event}/notgoing', 'EventController@post_notgoing');

Route::post('events/{event}/alerting', 'EventController@post_going');
Route::post('events/{event}/notalerting', 'EventController@post_notgoing');

Route::post('events/{event}/comment', 'EventController@post_comment');


//Route::apiResource('organizations', 'OrganizationController');
Route::get('organizations', 'OrganizationController@index');
Route::post('organizations', 'OrganizationController@store');
Route::get('organizations/{organization}', 'OrganizationController@show');
Route::put('organizations/{organization}', 'OrganizationController@update');
Route::patch('organizations/{organization}', 'OrganizationController@update');
Route::delete('organizations/{organization}', 'OrganizationController@destroy');

Route::post('organizations/{organization}/join/{user}', 'OrganizationController@post_join');
Route::post('organizations/{organization}/leave/{user}', 'OrganizationController@post_leave');

Route::get('organizations/{organization}/members', 'OrganizationController@get_members');
Route::post('organizations/{organization}/members/{user}', 'OrganizationController@post_members');
Route::delete('organizations/{organization}/members/{user}', 'OrganizationController@delete_members');

Route::get('organizations/{organization}/posters', 'OrganizationController@get_posters');
Route::post('organizations/{organization}/posters/{user}', 'OrganizationController@post_posters');
Route::delete('organizations/{organization}/posters/{user}', 'OrganizationController@delete_posters');

Route::get('organizations/{organization}/admins', 'OrganizationController@get_admins');
Route::post('organizations/{organization}/admins/{user}', 'OrganizationController@post_admins');
Route::delete('organizations/{organization}/admins/{user}', 'OrganizationController@delete_admins');

// Friends:
Route::get('users/friends/requests/pending', 'UserController@requests_pending');
Route::post('users/friends/requests/accept/{sender}', 'UserController@requests_accept');
Route::post('users/friends/requests/deny/{sender}', 'UserController@requests_deny');
Route::get('users/friends/requests/denied', 'UserController@requests_denied');
Route::post('users/friends/block/{friend}', 'UserController@block');
Route::get('users/friends/blocked', 'UserController@blocked');
Route::post('users/friends/unblock/{friend}', 'UserController@unblock');
Route::get('users/friends', 'UserController@friends');
Route::get('users/friends_of_friends', 'UserController@friends_of_friends');
Route::get('users/friends_of_user/{user}', 'UserController@friends_of_user');
Route::get('users/mutual_friends/{other}', 'UserController@friends_of_friends');
Route::post('users/friends/requests/{recipient}', 'UserController@post_friend_request');


// Users:
// NOTE: There is no new user POST to 'users/'. That is handled by auth/register, below.
// NOTE: There is no 'users/delete'. That must be handled by the admin panel.
Route::get('users/me', 'UserController@get_me');

Route::get('users/{user}', 'UserController@show');
Route::get('users', 'UserController@index');

// Update a User:
Route::put('users/{user}', 'UserController@update');
Route::patch('users/{user}', 'UserController@update');

// Stuff under auth prefix, e.g. /api/auth/register etc. Handled by jwt-auth.
Route::post('auth/register', 'AuthController@register');
Route::post('auth/login', 'AuthController@login');
Route::post('auth/facebook', 'AuthController@facebook');
Route::post('auth/google_ios', 'AuthController@google_ios');
Route::post('auth/google_android', 'AuthController@google_android');
Route::post('auth/google_web', 'AuthController@google_web');
Route::post('auth/logout', 'AuthController@logout');
Route::post('auth/refresh', 'AuthController@refresh');
//Route::post('auth/me', 'AuthController@me');


/* Web example; does this closure cache work with  php artisan route:cache? 
https://www.techrrival.com/deploy-laravel-production-server-ubuntu-github/

Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {

	Route::post('register', 'AuthController@register');
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');

});
*/
