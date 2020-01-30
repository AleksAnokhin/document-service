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

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});
Route::get('rejected','AuthController@rejected')->name('rejected');

Route::group([
    'prefix' => 'auth'
], function () {
    Route::post('login', 'AuthController@login');
    Route::post('signup', 'AuthController@signup');

    Route::group([
        'middleware' => 'auth:api'
    ], function() {
        Route::get('logout', 'AuthController@logout');
    });
});
Route::group([
    'middleware' => 'auth:api'
], function() {

    //**USER**//

    //get authenticated user
    Route::get('user', 'UsersController@user');
    Route::get('users', 'UsersController@getAll');
    Route::get('users/{id}', 'UsersController@getUser');
    //
    Route::patch('users/{id}', 'UsersController@updateUser');
    //
    Route::delete('users/{id}', 'UsersController@deleteUser');

    //**PERSON**//

    Route::post('person', 'PersonController@createPerson');
    Route::get('persons', 'PersonController@getAll');
    Route::get('person/{id}', 'PersonController@getPerson');
    Route::patch('person/{id}', 'PersonController@updatePerson');
    Route::delete('person/{id}', 'PersonController@deletePerson');

    //**BUSINESS**//

    Route::post('business','BusinessController@createBusiness');
    Route::get('businesses','BusinessController@getAll');
    Route::get('business/{id}', 'BusinessController@getBusiness');
    Route::patch('business/{id}', 'BusinessController@updateBusiness');
    Route::delete('business/{id}', 'BusinessController@deleteBusiness');


    //**PERSON_UPLOADS**//

    Route::post('person_uploads', 'PersonUploaderController@upload')->middleware('check_person_uploads');

    //**BUSINESS_UPLOADS**//

    Route::post('business_uploads', 'BusinessUploaderController@upload')->middleware('check_business_uploads');

    //**WEB_HOOKS**//

    Route::post('applicant_created', 'WebHookController@applicantCreated')->middleware('check_sum_sub_webhook');
    Route::post('applicant_pending', 'WebHookController@applicantPending')->middleware('check_sum_sub_webhook');
    Route::post('applicant_on_hold', 'WebHookController@applicantOnHold')->middleware('check_sum_sub_webhook');
    Route::post('applicant_prechecked', 'WebHookController@applicantPrechecked')->middleware('check_sum_sub_webhook');
    Route::post('applicant_reviewed','WebHookController@applicantReviewed')->middleware('final_sum_sub_webhook');

});


//*************************************** ADMIN_PART ***************************************//

Route::group([
    'middleware' => ['auth:api','admin_gate'],
    'prefix' => 'admin'
],function() {

    //***DATA_SECTION***//

    //persons

    Route::get('persons_to_check_all', 'Admin\PersonsAdminController@allToCheck');
    Route::get('persons_to_check_docset', 'Admin\PersonsAdminController@allToCheckDocs');
    Route::get('persons_to_check/{id}', 'Admin\PersonsAdminController@oneToCheck');
    Route::get('persons_to_check_docset/{id}', 'Admin\PersonsAdminController@oneToCheckDocs');

    //business

    Route::get('business_to_check_all', 'Admin\BusinessAdminController@allToCheck');
    Route::get('business_to_check_docset', 'Admin\BusinessAdminController@allToCheckDocs');
    Route::get('business_to_check/{id}', 'Admin\BusinessAdminController@oneToCheck');
    Route::get('business_to_check_docset/{id}', 'Admin\BusinessAdminController@oneToCheckDocs');

    //***FILES_SECTION***//

    //persons

    Route::get('person_file/{id}/{filename}', 'Admin\PersonsAdminController@getPersonsFile')->middleware('protect_person_verified_docs');


    //business

    Route::get('business_file/{id}/{filename}', 'Admin\BusinessAdminController@getBusinessFile')->middleware('protect_business_verified_docs');


    //***STATUSES_SECTION***//

    //approve

    Route::post('final_approve', 'Admin\StatusesController@finalApprove');


    //declines

    Route::post('decline_doc', 'Admin\StatusesController@declineDoc');


    //***GET REQUESTS ANALYTICS***//

    //under construction




});

