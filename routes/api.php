<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['api']], function ($router) {        
    Route::post('/register', [App\Http\Controllers\Api\UserController::class, 'user_register']);
    Route::post('/login', [App\Http\Controllers\Api\UserController::class, 'user_login']);
    Route::post('/check-user-email-verfication', [App\Http\Controllers\Api\UserController::class, 'check_user_email_verfication']);
    Route::post('/forget-password',[App\Http\Controllers\Api\UserController::class,'forget_password']);
    Route::post('/check-user-reset-password-verfication',[App\Http\Controllers\Api\UserController::class,'check_user_reset_password_verfication']);
    Route::post('/updated-reset-password',[App\Http\Controllers\Api\UserController::class,'updated_reset_password']);

});

<<<<<<< HEAD
Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {        
    Route::get('/get-all-cuisine', [App\Http\Controllers\Api\MenuController::class, 'get_all_cuisine']);
    Route::post('/save-chef-menu', [App\Http\Controllers\Api\MenuController::class, 'save_chef_menu']);
=======
Route::group(['middleware' => ['api']], function ($router) {        
    Route::get('/get-chef-detail/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'get_chef_detail']);
    Route::post('/update-chef-profile/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'update_chef_profile']);
    Route::post('/update-chef-resume/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'update_chef_resume']);
    Route::get('/get-chef-resume/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'get_chef_resume']);
>>>>>>> d6a25d4b8a5e075937aad41ebaf17972f9bc43c4
});


//chef edit profile
Route::group(['middleware' => ['api','jwt.auth']], function ($router) {        
    Route::get('/get-chef-detail/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'get_chef_detail']);
    Route::post('/update-chef-profile/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'update_chef_profile']);
    Route::post('/update-chef-resume/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'update_chef_resume']);
    Route::get('/get-chef-resume/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'get_chef_resume']);
});

//chef edit profile