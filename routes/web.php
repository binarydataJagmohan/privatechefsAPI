<?php

use Illuminate\Support\Facades\Route;
use Auth;
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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pravicy', function () {
    return view('privacy');
});

Route::get('/terms-condition', function () {
    return view('terms-condition');
});

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


Route::get('authorized/google', [App\Http\Controllers\Api\UserController::class, 'redirectToGoogle']);
Route::get('authorized/google/callback', [App\Http\Controllers\Api\UserController::class, 'handleGoogleCallback']);
