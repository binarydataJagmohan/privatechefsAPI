<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AllergyController;
use App\Http\Controllers\Api\ServiceChoiceController;
use App\Http\Controllers\Api\DishCategoryController;
use App\Http\Controllers\Api\DishesController;
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
    Route::post('/forget-password', [App\Http\Controllers\Api\UserController::class, 'forget_password']);
    Route::post('/check-user-reset-password-verfication', [App\Http\Controllers\Api\UserController::class, 'check_user_reset_password_verfication']);
    Route::post('/updated-reset-password', [App\Http\Controllers\Api\UserController::class, 'updated_reset_password']);
});


Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::get('/get-all-cuisine', [App\Http\Controllers\Api\MenuController::class, 'get_all_cuisine']);
    Route::post('/save-chef-menu', [App\Http\Controllers\Api\MenuController::class, 'save_chef_menu']);
    Route::get('/get-single-chef-menu/{id}', [App\Http\Controllers\Api\MenuController::class, 'get_single_chef_menu']);
    Route::post('/update-chef-menu', [App\Http\Controllers\Api\MenuController::class, 'update_chef_menu']);
    Route::get('/delete-single-menu/{id}', [App\Http\Controllers\Api\MenuController::class, 'delete_single_menu']);
    Route::post('/update-person-price', [App\Http\Controllers\Api\MenuController::class, 'update_person_price']);
    Route::get('/update-chef-dish-count', [App\Http\Controllers\Api\MenuController::class, 'update_person_price_count']);
});


//chef edit profile
Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::get('/get-chef-detail/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'get_chef_detail']);
    Route::post('/update-chef-profile/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'update_chef_profile']);
    Route::post('/update-chef-resume/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'update_chef_resume']);
    Route::get('/get-chef-resume/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'get_chef_resume']);
    Route::get('/get-all-chef-menu/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'get_all_chef_menu']);
    
    Route::post('/save-chef-menu-items', [App\Http\Controllers\Api\ChefDetailController::class, 'save_chef_menu_items']);
    Route::get('/delete-chef-menu-item/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'delete_chef_menu_item']);
    Route::get('/get_chef_by_filter',[App\Http\Controllers\Api\ChefDetailController::class,'get_chef_by_filter']);
    Route::get('/getAllChefDetails', [App\Http\Controllers\Api\ChefDetailController::class, 'getAllChefDetails']);
});
 

//Allergy or service Edit,update,get Routes
Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::post('/saveAllergy', [AllergyController::class, 'saveAllergy']);
    Route::get('/allergyDelete/{id}', [AllergyController::class, 'allergyDelete']);
    Route::post('/saveService', [ServiceChoiceController::class, 'saveService']);
    Route::get('/serviceDelete/{id}', [ServiceChoiceController::class, 'serviceDelete']);
    Route::get('/getSingleAllergyDetails/{id}', [AllergyController::class, 'getSingleAllergyDetails']);
    Route::post('/updateAllergy/{id}', [AllergyController::class, 'updateAllergy']);
    Route::get('/getSingleServiceDetail/{id}', [ServiceChoiceController::class, 'getSingleServiceDetail']);
    Route::post('/serviceUpdate/{id}',[ServiceChoiceController::class,'serviceUpdate']);
    
    Route::get('/getDishecategory',[DishCategoryController::class,'getDishecategory']);
    Route::get('/get-chef-dishes/{id}',[DishesController::class,'get_chef_dishes']);
    Route::get('/delete-dish/{id}',[DishesController::class,'dish_delete']);
    Route::get('/get-single-dish/{id}',[DishesController::class,'get_single_dish']);

    Route::post('/add-chef-dish',[DishesController::class,'add_chef_dish']);
    Route::get('/fetch-dish-category-by-id',[DishesController::class,'fetch_dish_category_by_id']);

    Route::get('/get-item-by-category/{id}',[DishesController::class,'get_item_by_category']);

});
//chef edit profile
//user edit profile
Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::get('/get-single-user-profile/{id}', [App\Http\Controllers\Api\UserController::class, 'get_single_user_profile']);
    Route::post('/update-user-profile/{id}', [App\Http\Controllers\Api\UserController::class, 'update_user_profile']);
});

//notification
Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::get('/notification-for-user-admin/{id}', [App\Http\Controllers\Api\NotificationController::class, 'notification_for_user_admin']);
    Route::post('/notification-status/{id}', [App\Http\Controllers\Api\NotificationController::class, 'notification_status']);
});

//villas
Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::get('/get-all-villas', [App\Http\Controllers\Api\VillasController::class, 'get_all_villas']);
    Route::get('/get-single-villas/{id}', [App\Http\Controllers\Api\VillasController::class, 'get_single_villas']);
    Route::post('/save-villa', [App\Http\Controllers\Api\VillasController::class, 'save_villa']);
    
    Route::post('/delete-villas/{id}', [App\Http\Controllers\Api\VillasController::class, 'deleteVillas']);
});

Route::post('/update-villas/{id}', [App\Http\Controllers\Api\VillasController::class, 'update_villas']);

Route::group(['middleware' => ['api']], function ($router) {
    Route::get('/getServiceDetails', [ServiceChoiceController::class, 'getServiceDetails']);
   
    Route::get('/getAllergyDetails', [AllergyController::class, 'getAllergyDetails']);
    Route::post('/save-booking', [App\Http\Controllers\Api\BookingController::class, 'save_booking']);
});
//cuisine
Route::group(['middleware' => ['api']], function ($router) {

    Route::post('/save-cuisine', [App\Http\Controllers\Api\CuisineController::class, 'save_cuisine']);
    Route::get('/get-all-cuisine', [App\Http\Controllers\Api\CuisineController::class, 'get_all_cuisine']);
    Route::get('/cuisine-delete/{id}', [App\Http\Controllers\Api\CuisineController::class, 'cuisine_delete']);
    Route::get('/get-single-cuisine/{id}', [App\Http\Controllers\Api\CuisineController::class, 'get_single_cuisine']);
    Route::post('/update-cuisine/{id}', [App\Http\Controllers\Api\CuisineController::class, 'update_cuisine']);

});


//receipt
Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::get('/get-all-booking',[App\Http\Controllers\Api\BookingController::class,'get_all_booking']);
    Route::post('/save-receipt',[App\Http\Controllers\Api\ReceiptController::class,'save_receipt']);
    Route::post('/update-receipt/{id}',[App\Http\Controllers\Api\ReceiptController::class,'update_receipt']);
    Route::post('/update-receipt-images/{id}',[App\Http\Controllers\Api\ReceiptController::class,'update_receipt_images']);
    Route::post('/delete-receipt/{id}',[App\Http\Controllers\Api\ReceiptController::class,'deleteReceipt']);
    Route::get('/get-receipt',[App\Http\Controllers\Api\ReceiptController::class,'get_receipt']);
    Route::get('/get-single-receipt/{id}',[App\Http\Controllers\Api\ReceiptController::class,'get_single_receipt']);
});


Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::get('/get-cuision',[App\Http\Controllers\Api\ChefDetailController::class,'get_cuision']);
    Route::get('/get-all-users',[App\Http\Controllers\Api\UserController::class,'get_all_users']);
});

Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::get('/get-User-By-Booking',[App\Http\Controllers\Api\BookingController::class,'get_User_By_Booking']);
    Route::get('/get-User-By-Booking/{id}',[App\Http\Controllers\Api\BookingController::class,'get_User_By_Booking_Id']);
    Route::get('/get-user-chef-by-booking',[App\Http\Controllers\Api\BookingController::class,'get_user_chef_by_booking']);
     Route::get('/get-user-chef-filter-by-booking/{type}',[App\Http\Controllers\Api\BookingController::class,'get_user_chef_filter_by_booking']);
     Route::get('/get-User-Booking-id/{id}',[App\Http\Controllers\Api\BookingController::class,'get_User_Booking_id']);
});
