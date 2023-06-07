<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AllergyController;
use App\Http\Controllers\Api\ServiceChoiceController;
use App\Http\Controllers\Api\DishCategoryController;
use App\Http\Controllers\Api\DishesController;
use App\Http\Controllers\Api\TestimonialController;


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

    Route::get('authorized/google', [App\Http\Controllers\Api\UserController::class, 'redirectToGoogle']);
    Route::post('authorized/google/callback', [App\Http\Controllers\Api\UserController::class, 'handleGoogleCallback']);
    Route::post('/social-data-save', [App\Http\Controllers\Api\UserController::class, 'social_data_save']);
    Route::post('/select-role/{id}', [App\Http\Controllers\Api\UserController::class, 'select_role']);
    Route::get('/get-email-data/{email}', [App\Http\Controllers\Api\UserController::class, 'get_email_data']);
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
    Route::post('/update-chef-image/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'update_chef_image']);
    Route::post('/update-chef-resume/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'update_chef_resume']);
    Route::get('/get-chef-resume/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'get_chef_resume']);
    Route::get('/get-all-chef-menu/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'get_all_chef_menu']);

    Route::post('/save-chef-menu-items', [App\Http\Controllers\Api\ChefDetailController::class, 'save_chef_menu_items']);
    Route::get('/delete-chef-menu-item/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'delete_chef_menu_item']);
    Route::get('/get_chef_by_filter', [App\Http\Controllers\Api\ChefDetailController::class, 'get_chef_by_filter']);
    Route::get('/getAllChefDetails', [App\Http\Controllers\Api\ChefDetailController::class, 'getAllChefDetails']);

    Route::post('/update-chef-location/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'update_chef_location']);
    Route::post('/save-chef-location', [App\Http\Controllers\Api\ChefDetailController::class, 'save_chef_location']);
    Route::get('/get-chef-location/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'get_chef_location']);
    Route::get('/get-current-location/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'get_current_location']);
    Route::post('/update-location-status/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'update_location_status']);
    Route::get('/get-single-location/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'get_single_location']);
    Route::post('/delete-single-location/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'delete_single_location']);

    Route::post('/approve-chef-profile/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'approve_chef_profile']);
    Route::get('/get-chef-approval/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'get_chef_approval']);
    Route::post('/approval-msg/{id}', [App\Http\Controllers\Api\ChefDetailController::class, 'approval_msg']);

    Route::get('/get-chef-booking/{id}', [App\Http\Controllers\Api\BookingController::class, 'get_chef_booking']);

    Route::get('/get-single-receipt-admin/{id}', [App\Http\Controllers\Api\ReceiptController::class, 'get_single_receipt_admin']);
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
    Route::post('/serviceUpdate/{id}', [ServiceChoiceController::class, 'serviceUpdate']);

    Route::get('/getDishecategory', [DishCategoryController::class, 'getDishecategory']);
    Route::get('/get-chef-dishes/{id}', [DishesController::class, 'get_chef_dishes']);
    Route::get('/delete-dish/{id}', [DishesController::class, 'dish_delete']);
    Route::get('/get-single-dish/{id}', [DishesController::class, 'get_single_dish']);

    Route::post('/add-chef-dish', [DishesController::class, 'add_chef_dish']);
    Route::get('/fetch-dish-category-by-id', [DishesController::class, 'fetch_dish_category_by_id']);

    Route::get('/get-item-by-category/{id}', [DishesController::class, 'get_item_by_category']);
});
//chef edit profile
//user edit profile
Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::get('/get-single-user-profile/{id}', [App\Http\Controllers\Api\UserController::class, 'get_single_user_profile']);
    Route::post('/update-user-profile/{id}', [App\Http\Controllers\Api\UserController::class, 'update_user_profile']);
    Route::post('/update-users-image/{id}', [App\Http\Controllers\Api\UserController::class, 'update_users_image']);
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
    Route::post('/change-booking-status/{id}', [App\Http\Controllers\Api\BookingController::class, 'change_booking_status']);
});
//cuisine
Route::group(['middleware' => ['api']], function ($router) {

    Route::post('/save-cuisine', [App\Http\Controllers\Api\CuisineController::class, 'save_cuisine']);
    Route::get('/get-all-cuisine', [App\Http\Controllers\Api\CuisineController::class, 'get_all_cuisine']);
    Route::get('/cuisine-delete/{id}', [App\Http\Controllers\Api\CuisineController::class, 'cuisine_delete']);
    Route::get('/get-single-cuisine/{id}', [App\Http\Controllers\Api\CuisineController::class, 'get_single_cuisine']);
    Route::post('/update-cuisine/{id}', [App\Http\Controllers\Api\CuisineController::class, 'update_cuisine']);
});

//testimonial
Route::group(['middleware' => ['api']], function ($router) {
    Route::post('/save-testimonial', [App\Http\Controllers\Api\TestimonialController::class, 'save_testimonial']);
    Route::get('/get-testnomial', [App\Http\Controllers\Api\TestimonialController::class, 'gettestnomial']);
    Route::get('/get-single-testnomial/{id}', [TestimonialController::class, 'getSingleTestimonial']);
    Route::post('/update-testimonial/{id}', [TestimonialController::class, 'updateTestimonial']);
    Route::get('/testimonial-Delete/{id}', [TestimonialController::class, 'testimonialDelete']);
});




//receipt
Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::get('/get-all-booking', [App\Http\Controllers\Api\BookingController::class, 'get_all_booking']);
    Route::post('/save-receipt', [App\Http\Controllers\Api\ReceiptController::class, 'save_receipt']);
    Route::post('/update-receipt/{id}', [App\Http\Controllers\Api\ReceiptController::class, 'update_receipt']);
    Route::post('/update-receipt-images/{id}', [App\Http\Controllers\Api\ReceiptController::class, 'update_receipt_images']);
    Route::post('/delete-receipt/{id}', [App\Http\Controllers\Api\ReceiptController::class, 'deleteReceipt']);
    Route::get('/get-receipt', [App\Http\Controllers\Api\ReceiptController::class, 'get_receipt']);
    Route::get('/get-chef-receipt/{id}', [App\Http\Controllers\Api\ReceiptController::class, 'get_chef_receipt']);
    Route::get('/get-single-receipt/{id}', [App\Http\Controllers\Api\ReceiptController::class, 'get_single_receipt']);
});


Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::get('/get-cuision', [App\Http\Controllers\Api\ChefDetailController::class, 'get_cuision']);
    Route::get('/get-all-users', [App\Http\Controllers\Api\UserController::class, 'get_all_users']);
});

Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::get('/get-User-By-Booking/{id}', [App\Http\Controllers\Api\BookingController::class, 'get_User_By_Booking_Id']);
    Route::get('/get-user-chef-by-booking/{userid}', [App\Http\Controllers\Api\BookingController::class, 'get_user_chef_by_booking']);
    Route::get('/get-user-chef-filter-by-booking/{userid}/{type}', [App\Http\Controllers\Api\BookingController::class, 'get_user_chef_filter_by_booking']);
    Route::get('/get-User-Booking-id/{id}', [App\Http\Controllers\Api\BookingController::class, 'get_User_Booking_id']);
    Route::post('/save-chef-applied-booking-job', [App\Http\Controllers\Api\BookingController::class, 'save_chef_applied_booking_job']);
    Route::get('/get-chef-applied-booking/{userid}', [App\Http\Controllers\Api\BookingController::class, 'get_chef_applied_booking']);

    Route::get('/get-current-user-by-booking/{userid}', [App\Http\Controllers\Api\BookingController::class, 'get_current_user_by_booking']);

    Route::get('/get-chef-applied-filter-by-booking/{userid}/{type}', [App\Http\Controllers\Api\BookingController::class, 'get_chef_applied_filter_by_booking']);

    Route::get('/get-user-filter-by-booking/{userid}/{type}', [App\Http\Controllers\Api\BookingController::class, 'get_user_filter_by_booking']);

    Route::get('/get-single-user-assign-booking/{id}', [App\Http\Controllers\Api\BookingController::class, 'get_single_user_assign_booking']);

    Route::post('/updated-applied-booking-by-key-value/', [App\Http\Controllers\Api\BookingController::class, 'updated_applied_booking_by_key_value']);

    Route::get('/get-admin-chef-by-booking', [App\Http\Controllers\Api\BookingController::class, 'get_admin_chef_by_booking']);

    Route::get('/get-admin-chef-filter-by-booking/{type}', [App\Http\Controllers\Api\BookingController::class, 'get_admin_chef_filter_by_booking']);

    Route::get('/get-admin-assigned-booking', [App\Http\Controllers\Api\BookingController::class, 'get_admin_assigned_booking']);

    Route::get('/get-admin-applied-filter-by-booking/{type}', [App\Http\Controllers\Api\BookingController::class, 'get_admin_applied_filter_by_booking']);

    Route::get('/delete-booking/{id}', [App\Http\Controllers\Api\BookingController::class, 'delete_booking']);

    Route::post('/updated-applied-booking-job/', [App\Http\Controllers\Api\BookingController::class, 'updated_applied_booking_job']);

    Route::get('/get-edit-booking-data/{id}', [App\Http\Controllers\Api\BookingController::class, 'get_edit_booking_data']);
    Route::post('/update-booking', [App\Http\Controllers\Api\BookingController::class, 'update_booking']);

    Route::get('/get-user-chef-offer/{id}', [App\Http\Controllers\Api\BookingController::class, 'get_user_chef_offer']);

    Route::get('/get-all-bookings', [App\Http\Controllers\Api\BookingController::class, 'get_all_bookings']);
    Route::get('/get-chef-bookings/{id}', [App\Http\Controllers\Api\BookingController::class, 'get_chef_bookings']);
});
Route::post('/updateAllergyCusine/{id}', [App\Http\Controllers\Api\UserController::class, 'updateAllergyCusine']);

Route::group(['middleware' => ['api']], function ($router) {
    Route::post('/save-contact', [App\Http\Controllers\Api\ContactController::class, 'save_contact']);
});


Route::get('/get-instagram-images', [App\Http\Controllers\Api\InstagramController::class, 'getInstagramImages']);


Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::post('/get-user-message-data/', [App\Http\Controllers\Api\UserChatController::class, 'get_user_message_data']);
    Route::post('/contact-chef-by-user/', [App\Http\Controllers\Api\UserChatController::class, 'contact_chef_by_user']);

    Route::post('/get-click-user-chef-chat-data/', [App\Http\Controllers\Api\UserChatController::class, 'get_click_user_chef_chat_data']);
    Route::post('/contact-chef-by-user-with-share-file/', [App\Http\Controllers\Api\UserChatController::class, 'contact_chef_by_user_with_share_file']);
});

Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::post('/get-chef-message-data/', [App\Http\Controllers\Api\ChefChatController::class, 'get_chef_message_data']);
    Route::post('/contact-user-by-chef/', [App\Http\Controllers\Api\ChefChatController::class, 'contact_user_by_chef']);

    Route::post('/get-click-chef-user-chat-data/', [App\Http\Controllers\Api\ChefChatController::class, 'get_click_chef_user_chat_data']);
    Route::post('/contact-user-by-chef-with-share-file/', [App\Http\Controllers\Api\ChefChatController::class, 'contact_user_by_chef_with_share_file']);
});

Route::group(['middleware' => ['api']], function ($router) {
    Route::get('/update-user-to-offline/{id}', [App\Http\Controllers\Api\UserController::class, 'update_user_to_offline']);
});

//invoice
Route::group(['middleware' => ['api']], function ($router) {
    Route::post('/save-invoice', [App\Http\Controllers\Api\InvoiceController::class, 'save_invoice']);
    Route::get('/get-chef-invoice/{id}', [App\Http\Controllers\Api\InvoiceController::class, 'get_chef_invoice']);
    Route::post('/update-invoice/{id}', [App\Http\Controllers\Api\InvoiceController::class, 'update_invoice']);
    Route::get('/get-single-invoice/{id}', [App\Http\Controllers\Api\InvoiceController::class, 'get_single_invoice']);
    Route::post('/delete-invoice/{id}', [App\Http\Controllers\Api\InvoiceController::class, 'delete_invoice']);
    Route::get('/get-all-invoice', [App\Http\Controllers\Api\InvoiceController::class, 'get_all_invoice']);
    Route::get('/single-invoice/{id}', [App\Http\Controllers\Api\InvoiceController::class, 'single_invoice']);
});

// concierge
Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::post('/create-user', [App\Http\Controllers\Api\UserController::class, 'create_user']);
    Route::post('/delete-user/{id}', [App\Http\Controllers\Api\UserController::class, 'delete_user']);
    Route::get('/get-all-concierge-users/{id}', [App\Http\Controllers\Api\UserController::class, 'get_all_concierge_users']);
    Route::get('/get-concierge-chef-by-booking/{id}', [App\Http\Controllers\Api\BookingController::class, 'get_concierge_chef_by_booking']);
    Route::get('/get-concierge-assigned-booking/{id}', [App\Http\Controllers\Api\BookingController::class, 'get_concierge_assigned_booking']);
    Route::post('/create-chef', [App\Http\Controllers\Api\UserController::class, 'create_chef']);
    Route::post('/delete-chef/{id}', [App\Http\Controllers\Api\UserController::class, 'delete_chef']);
    Route::get('/get-all-concierge-chef/{id}', [App\Http\Controllers\Api\UserController::class, 'get_all_concierge_chef']);
    Route::get('/get-concierge-receipt/{id}', [App\Http\Controllers\Api\ReceiptController::class, 'get_concierge_receipt']);
    Route::get('/get-concierge-villas/{id}', [App\Http\Controllers\Api\VillasController::class, 'get_concierge_villas']);
});
