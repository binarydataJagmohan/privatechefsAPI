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

    Route::get('/get-all-chef-by-location', [App\Http\Controllers\Api\UserController::class, 'get_chef_by_location']);


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

    Route::post('/save-chef-dish-images', [App\Http\Controllers\Api\DishesController::class, 'saveChefDishImages']);
     Route::get('/delete-chef-dish-image/{id}', [DishesController::class, 'deleteChefDishImage']);

});

Route::get('/get-all-dish-gallery/{id}', [DishesController::class, 'getAllChefDishGallery']);


//chef edit profile
//user edit profile
Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::get('/get-single-user-profile/{id}', [App\Http\Controllers\Api\UserController::class, 'get_single_user_profile']);
    Route::post('/update-user-profile/{id}', [App\Http\Controllers\Api\UserController::class, 'update_user_profile']);
    Route::post('/update-users-image/{id}', [App\Http\Controllers\Api\UserController::class, 'update_users_image']);
});
Route::get('/get-single-chef-profile/{id}', [App\Http\Controllers\Api\UserController::class, 'get_single_chef_profile']);

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
    Route::get('/get-all-concierge', [App\Http\Controllers\Api\UserController::class, 'getAllConcierge']);
    Route::post('/approved-concierge-profile', [App\Http\Controllers\Api\UserController::class, 'approveConciergeProfile']);
});

Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::post('/admin-cancel-and-reopen-booking/{id}', [App\Http\Controllers\Api\BookingController::class, 'admin_cancel_and_reopen_booking']);

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

    Route::post('/updated-applied-booking-by-key-value', [App\Http\Controllers\Api\BookingController::class, 'updated_applied_booking_by_key_value']);

    Route::get('/get-admin-chef-by-booking', [App\Http\Controllers\Api\BookingController::class, 'get_admin_chef_by_booking']);

    Route::get('/get-admin-chef-filter-by-booking/{type}', [App\Http\Controllers\Api\BookingController::class, 'get_admin_chef_filter_by_booking']);

    Route::get('/get-admin-assigned-booking', [App\Http\Controllers\Api\BookingController::class, 'get_admin_assigned_booking']);

    Route::get('/get-admin-applied-filter-by-booking/{type}', [App\Http\Controllers\Api\BookingController::class, 'get_admin_applied_filter_by_booking']);

    Route::get('/delete-booking/{id}', [App\Http\Controllers\Api\BookingController::class, 'delete_booking']);

    Route::post('/resend-payment-link', [App\Http\Controllers\Api\BookingController::class, 'ResendPaymentLink']);

    Route::post('/updated-applied-booking-job', [App\Http\Controllers\Api\BookingController::class, 'updated_applied_booking_job']);

    Route::get('/get-edit-booking-data/{id}', [App\Http\Controllers\Api\BookingController::class, 'get_edit_booking_data']);
    Route::post('/update-booking', [App\Http\Controllers\Api\BookingController::class, 'update_booking']);

    Route::get('/get-user-chef-offer/{id}', [App\Http\Controllers\Api\BookingController::class, 'get_user_chef_offer']);

    Route::get('/get-all-bookings', [App\Http\Controllers\Api\BookingController::class, 'get_all_bookings']);
    Route::get('/get-chef-bookings/{id}', [App\Http\Controllers\Api\BookingController::class, 'get_chef_bookings']);

    Route::post('/assigned-booking-by-admin-without-db', [App\Http\Controllers\Api\BookingController::class, 'AssignedBookingByAdminWithoutDatabse']);

   Route::post('/assigned-villa-by-booking', [App\Http\Controllers\Api\VillasController::class, 'AssignedVillaByBooking']);

   Route::get('/get-admin-villa-by-booking/{id}', [App\Http\Controllers\Api\VillasController::class, 'get_admin_villa_by_booking']);

   Route::get('/get-user-booking-payment/{userid}', [App\Http\Controllers\Api\BookingController::class, 'getUserBookingPayment']);
});
Route::post('/update-allergy-additonal-info', [App\Http\Controllers\Api\UserController::class, 'updateAllergyAdditonalInfo']);

Route::get('/get-allergy-additonal-info/{user_id}', [App\Http\Controllers\Api\UserController::class, 'getAllergyAdditonalInfo']);


Route::group(['middleware' => ['api']], function ($router) {
    Route::post('/save-contact', [App\Http\Controllers\Api\ContactController::class, 'save_contact']);
});


Route::get('/get-instagram-images', [App\Http\Controllers\Api\InstagramController::class, 'getInstagramImages']);


Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::post('/get-user-message-data/', [App\Http\Controllers\Api\UserChatController::class, 'get_user_message_data']);
    Route::post('/contact-chef-by-user/', [App\Http\Controllers\Api\UserChatController::class, 'contact_chef_by_user']);

    Route::post('/get-click-user-chef-chat-data/', [App\Http\Controllers\Api\UserChatController::class, 'get_click_user_chef_chat_data']);
    Route::post('/contact-chef-by-user-with-share-file/', [App\Http\Controllers\Api\UserChatController::class, 'contact_chef_by_user_with_share_file']);

    Route::post('/contact-chef-by-user-with-single-booking/', [App\Http\Controllers\Api\UserChatController::class, 'contact_chef_by_user_with_single_booking']);

    Route::post('delete-chat-message/{id}', [App\Http\Controllers\Api\UserChatController::class, 'delete_chat_message']);
});

Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::post('/get-chef-message-data/', [App\Http\Controllers\Api\ChefChatController::class, 'get_chef_message_data']);
    Route::post('/contact-user-by-chef/', [App\Http\Controllers\Api\ChefChatController::class, 'contact_user_by_chef']);

    Route::post('/get-click-chef-user-chat-data/', [App\Http\Controllers\Api\ChefChatController::class, 'get_click_chef_user_chat_data']);
    Route::post('/contact-user-by-chef-with-share-file/', [App\Http\Controllers\Api\ChefChatController::class, 'contact_user_by_chef_with_share_file']);
    Route::get('/get-admin-data', [App\Http\Controllers\Api\ChefChatController::class, 'get_admin_data']);

    Route::post('delete-chat-message/{id}', [App\Http\Controllers\Api\ChefChatController::class, 'delete_chat_message']);
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
    Route::post('/admin-create-chef', [App\Http\Controllers\Api\UserController::class, 'admin_create_chef']);

    Route::get('/get-chef-location-by-admin/{id}', [App\Http\Controllers\Api\UserController::class, 'get_chef_location']);

    Route::get('/get-single-location-by-admin/{id}', [App\Http\Controllers\Api\UserController::class, 'get_single_location']);
    Route::post('/delete-single-location-by-admin/{id}', [App\Http\Controllers\Api\UserController::class, 'delete_single_location']);
    Route::post('/update-chef-profile-by-admin/{id}', [App\Http\Controllers\Api\UserController::class, 'admin_update_chef_profile']);
    Route::get('/get-chef-detail-by-admin/{id}', [App\Http\Controllers\Api\UserController::class, 'get_chef_detail']);
    Route::get('/get-current-location-by-admin/{id}', [App\Http\Controllers\Api\UserController::class, 'get_current_location']);
    Route::get('/get-chef-resume-by-admin/{id}', [App\Http\Controllers\Api\UserController::class, 'get_chef_resume']);

    Route::post('/update-chef-resume-by-admin/{id}', [App\Http\Controllers\Api\UserController::class, 'update_chef_resume']);
    Route::post('/update-chef-location-by-admin/{id}', [App\Http\Controllers\Api\UserController::class, 'update_chef_location']);
    Route::post('/save-chef-location-by-admin', [App\Http\Controllers\Api\UserController::class, 'save_chef_location']);


    Route::post('/delete-chef/{id}', [App\Http\Controllers\Api\UserController::class, 'delete_chef']);
    Route::get('/get-all-concierge-chef/{id}', [App\Http\Controllers\Api\UserController::class, 'get_all_concierge_chef']);


    Route::get('get-chef-all-location-by-concierge', [App\Http\Controllers\Api\UserController::class, 'get_chef_all_location_by_concierge']);

    Route::get('/get-concierge-receipt/{id}', [App\Http\Controllers\Api\ReceiptController::class, 'get_concierge_receipt']);
    Route::get('/get-concierge-villas/{id}', [App\Http\Controllers\Api\VillasController::class, 'get_concierge_villas']);
    Route::get('/get-all-concierge-bookings/{id}', [App\Http\Controllers\Api\BookingController::class, 'get_all_concierge_bookings']);
    Route::get('/single-concierge-invoice/{id}', [App\Http\Controllers\Api\InvoiceController::class, 'single_concierge_invoice']);
    Route::get('/get-notification-concierge/{id}', [App\Http\Controllers\Api\NotificationController::class, 'get_notification_concierge']);
    Route::get('/get-concierge-filter-by-booking/{id}/{type}', [App\Http\Controllers\Api\BookingController::class, 'get_concierge_filter_by_booking']);
});


Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::post('/get-admin-message-data/', [App\Http\Controllers\Api\AdminChatController::class, 'get_admin_message_data']);
    Route::post('/contact-by-admin-to-user-and-chef/', [App\Http\Controllers\Api\AdminChatController::class, 'contact_by_admin_to_user_and_chef']);
    Route::post('/get-click-admin-chef-user-chat-data/', [App\Http\Controllers\Api\AdminChatController::class, 'get_click_admin_chef_user_chat_data']);
    Route::post('/contact-by-admin-to-user-and-chef-with-share-file/', [App\Http\Controllers\Api\AdminChatController::class, 'contact_by_admin_to_user_and_chef_with_share_file']);
    Route::get('get-all-user-data', [App\Http\Controllers\Api\AdminChatController::class, 'get_all_user_data']);
    Route::post('/send-message-by-admin-to-user', [App\Http\Controllers\Api\AdminChatController::class, 'send_message_to_user_by_admin']);
    Route::post('/create-group-by-admin', [App\Http\Controllers\Api\AdminChatController::class, 'create_group_by_admin']);
    Route::post('/get-admin-message-data-by-filter/', [App\Http\Controllers\Api\AdminChatController::class, 'get_admin_message_data_by_filter']);
    Route::get('get-all-concierge-user-data/{id}', [App\Http\Controllers\Api\AdminChatController::class, 'get_all_concierge_user_data']);

    Route::post('delete-chat-message/{id}', [App\Http\Controllers\Api\AdminChatController::class, 'delete_chat_message']);
});

Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::get('get-booking-count', [App\Http\Controllers\Api\BookingController::class, 'get_bookings_count']);
    Route::get('get-applied-bookings-count/{id}', [App\Http\Controllers\Api\BookingController::class, 'get_applied_chef_bookings_count']);
    Route::get('concierge-bookings-count/{id}', [App\Http\Controllers\Api\BookingController::class, 'concierge_bookings_count']);
});

Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::get('get-admin-calender-bookings', [App\Http\Controllers\Api\CalenderController::class, 'get_admin_calender_bookings']);
    Route::get('get-chef-calender-bookings/{id}', [App\Http\Controllers\Api\CalenderController::class, 'get_chef_calender_bookings']);
    Route::get('get-concierge-calender-bookings/{id}', [App\Http\Controllers\Api\CalenderController::class, 'get_concierge_calender_bookings']);
});

Route::get('get-data', [App\Http\Controllers\Api\UserController::class, 'get_data']);
Route::get('get-all-chef', [App\Http\Controllers\Api\UserController::class, 'get_all_chef']);

// Concierge chat api start

Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::post('get-concierge-message-data', [App\Http\Controllers\Api\ConciergeChatController::class, 'get_concierge_message_data']);
    Route::post('contact-by-concierge-to-user-and-chef', [App\Http\Controllers\Api\ConciergeChatController::class, 'contact_by_concierge_to_user_and_chef']);
    Route::post('get-click-concierge-chef-user-chat-data', [App\Http\Controllers\Api\ConciergeChatController::class, 'get_click_concierge_chef_user_chat_data']);
    Route::post('contact-by-concierge-to-user-and-chef-with-share-file', [App\Http\Controllers\Api\ConciergeChatController::class, 'contact_by_concierge_to_user_and_chef_with_share_file']);
    Route::post('send-message-to-user-by-concierge', [App\Http\Controllers\Api\ConciergeChatController::class, 'send_message_to_user_by_concierge']);
    Route::post('create-group-by-concierge', [App\Http\Controllers\Api\ConciergeChatController::class, 'create_group_by_concierge']);
    Route::get('get-all-concierge-user-data/{id}', [App\Http\Controllers\Api\ConciergeChatController::class, 'get_all_concierge_user_data']);

    Route::post('delete-chat-message/{id}', [App\Http\Controllers\Api\ConciergeChatController::class, 'delete_chat_message']);  
});

// Concierge chat api end

Route::group(['middleware' => ['api', 'jwt.auth']], function ($router) {
    Route::get('get-chef-menu', [App\Http\Controllers\Api\ChefDetailController::class, 'get_chef_menu']);
    Route::post('assigned-booking-by-admin', [App\Http\Controllers\Api\BookingController::class, 'assigned_booking_by_admin']);
    Route::get('get-settings', [App\Http\Controllers\Api\PagesController::class, 'get_settings']);
    Route::get('get-single-setting/{id}', [App\Http\Controllers\Api\PagesController::class, 'get_single_setting']);
    Route::post('update-page-info/{id}', [App\Http\Controllers\Api\PagesController::class, 'update_page_info']);
    Route::post('top-rated-chef/{id}', [App\Http\Controllers\Api\PagesController::class, 'top_rated_chef']);
    Route::get('get-top-rated-chef/{id}', [App\Http\Controllers\Api\PagesController::class, 'get_top_rated_chef']);
    Route::get('get-chef-all-location', [App\Http\Controllers\Api\ChefDetailController::class, 'get_chef_all_location']);
    Route::get('chef-location-filter', [App\Http\Controllers\Api\ChefDetailController::class, 'chef_location_filter']);
    Route::get('chef-price-filter', [App\Http\Controllers\Api\ChefDetailController::class, 'chef_price_filter']);

// 27 oct
    Route::get('get-user-all-location', [App\Http\Controllers\Api\UserController::class, 'get_user_all_location']);
    Route::get('user-location-filter', [App\Http\Controllers\Api\UserController::class, 'user_location_filter']);

    Route::post('hired-assigned-booking-by-admin', [App\Http\Controllers\Api\BookingController::class, 'hired_assigned_booking_by_admin']);

    Route::post('add-reviews', [App\Http\Controllers\Api\ReviewController::class, 'addReviews']);
});

Route::group(['middleware' => ['api']], function ($router) {
    Route::get('get-slug-setting/{slug}', [App\Http\Controllers\Api\PagesController::class, 'get_slug_setting']);
    Route::get('get-all-top-rated-chef', [App\Http\Controllers\Api\ChefDetailController::class, 'get_all_top_rated_chef']);
    Route::get('get-all-location', [App\Http\Controllers\Api\ChefDetailController::class, 'get_all_location']);
    Route::get('get-location-by-slug/{slug}', [App\Http\Controllers\Api\ChefDetailController::class, 'get_location_by_slug']);
    Route::post('update-setting/{id}',[App\Http\Controllers\Api\SettingController::class,'update_setting']);

    Route::post('get-chef-details-by-location', [App\Http\Controllers\Api\ChefDetailController::class, 'getChefDetailByLocation']);

    Route::post('send-message-to-user-by-admin', [App\Http\Controllers\Api\UserController::class, 'sendMessageToUserByAdmin']);

    Route::post('/save-payment', [App\Http\Controllers\Api\BookingController::class, 'savePayment']);

    Route::post('update-new-setting',[App\Http\Controllers\Api\SettingController::class,'UpdateNewSetting']);

    Route::get('subscribe/{email}',[App\Http\Controllers\Api\SettingController::class,'Subscribe']);

    Route::get('/get-all-chef-by-location-onfronted', [App\Http\Controllers\Api\UserController::class, 'get_chef_by_location_onfronted']);

    Route::get('get-all-chef-reviews/{id}', [App\Http\Controllers\Api\ReviewController::class, 'getAllChefReview']);

    Route::get('/chefDelete/{id}', [App\Http\Controllers\Api\UserController::class, 'chefDelete']);
    Route::get('/userDelete/{id}', [App\Http\Controllers\Api\UserController::class, 'userDelete']);
    Route::get('/userDeactivate/{id}', [App\Http\Controllers\Api\UserController::class, 'userDeactivate']);

});
