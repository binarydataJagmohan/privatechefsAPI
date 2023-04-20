<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AllergyController;
use App\Http\Controllers\Api\ServiceChoiceController;
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


Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {        
    Route::get('/get-all-cuisine', [App\Http\Controllers\Api\MenuController::class, 'get_all_cuisine']);
    Route::post('/save-chef-menu', [App\Http\Controllers\Api\MenuController::class, 'save_chef_menu']);
    Route::get('/get-single-chef-menu/{id}', [App\Http\Controllers\Api\MenuController::class, 'get_single_chef_menu']);
    Route::post('/update-chef-menu', [App\Http\Controllers\Api\MenuController::class, 'update_chef_menu']);
    Route::get('/delete-single-menu/{id}', [App\Http\Controllers\Api\MenuController::class, 'delete_single_menu']);
    Route::post('/update-person-price', [App\Http\Controllers\Api\MenuController::class, 'update_person_price']);

});


//chef edit profile
Route::group(['middleware' => ['api','jwt.auth']], function ($router) {        
    Route::get('/get-chef-detail/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'get_chef_detail']);
    Route::post('/update-chef-profile/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'update_chef_profile']);
    Route::post('/update-chef-resume/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'update_chef_resume']);
    Route::get('/get-chef-resume/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'get_chef_resume']);
    Route::get('/get-all-chef-menu/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'get_all_chef_menu']);
    Route::get('/getAllChefDetails',[App\Http\Controllers\Api\ChefDetailController::class, 'getAllChefDetails']);
    Route::post('/save-chef-dishes', [App\Http\Controllers\Api\ChefDetailController::class, 'save_chef_dishes']);
    Route::get('/delete-single-dish/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'delete_single_dish']);
    
});

Route::group(['middleware' => ['api','jwt.auth']], function ($router) { 

Route::post('/saveAllergy',[AllergyController::class, 'saveAllergy']);
Route::get('/getAllergyDetails',[AllergyController::class,'getAllergyDetails']);
Route::get('/allergyDelete/{id}',[AllergyController::class,'allergyDelete']);
Route::post('/saveService',[ServiceChoiceController::class,'saveService']);
Route::get('/getServiceDetails',[ServiceChoiceController::class,'getServiceDetails']);
Route::get('/serviceDelete/{id}',[ServiceChoiceController::class,'serviceDelete']);

});


//chef edit profile
//user edit profile
Route::group(['middleware' => ['api','jwt.auth']], function ($router) {        
    Route::get('/get-single-user-profile/{id}', [App\Http\Controllers\Api\UserController::class, 'get_single_user_profile']);
    Route::post('/update-user-profile/{id}', [App\Http\Controllers\Api\UserController::class, 'update_user_profile']);
});

//notification
Route::group(['middleware' => ['api','jwt.auth']], function ($router) {        
    Route::get('/notification-for-user-admin/{id}', [App\Http\Controllers\Api\NotificationController::class, 'notification_for_user_admin']);
    Route::post('/notification-status/{id}', [App\Http\Controllers\Api\NotificationController::class, 'notification_status']);
});
