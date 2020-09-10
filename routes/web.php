<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


// This sets up auth for non-API, HTML-based web URLs, incl. login form, logout, etc.
// https://stackoverflow.com/questions/39196968/laravel-5-3-new-authroutes
Auth::routes();

Route::get('/', 'HomeController@root');
Route::get('/home', 'HomeController@index')->name('home');
