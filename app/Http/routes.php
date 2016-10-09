<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
//use Illuminate\Http\Request;

Route::get('/welcome', function () {
    return view('welcome');
});

Route::group(['prefix'=>'course'],function(){
	Route::any('upload', 'CourseController@upload');
	Route::any('queryAll', 'CourseController@queryAll');
	Route::any('queryByInfo', 'CourseController@queryByInfo');
	Route::any('rubCourse', 'CourseController@rubCourse');
	Route::any('getAcademy', 'CourseController@getAcademy');
});


Route::any('banner/upload', 'BannerController@upload');
Route::any('banner/getAll', 'BannerController@getAllBanners');
Route::any('banner/delete', 'BannerController@deleteById');

// school bus api
Route::group(['prefix' => 'bus'], function() {
    Route::post('raw', 'BusController@storeRaw');
    Route::get('raw', 'BusController@getRaw');
    Route::delete('raw', 'BusController@flushRedis');
});

// library api
Route::group(['prefix' => 'lib'], function() {
    Route::get('hot', 'LibraryController@hot');
    Route::get('borrowings', 'LibraryController@borrowings');
    Route::post('bookRenew', 'LibraryController@bookRenew');
    Route::get('borrowHistory', 'LibraryController@borrowHistory');
    Route::get('{id}', 'LibraryController@info');
    Route::get('/', 'LibraryController@search');
});


// dianfei api
Route::group(['prefix' => 'dianfei'], function() {
    // check dianfei
    Route::get('/', 'DianfeiController@index');

    // email notification
    Route::group(['prefix' => 'notify'], function() {
        Route::post('/', 'DianfeiController@bind');
        Route::delete('/', 'DianfeiController@unbind');

        // confirm bind/unbind(link in email)
        Route::get('bind', 'DianfeiController@confirmBind')->where('id', '[0-9a-z]{20}');
        Route::get('unbind', 'DianfeiController@confirmUnbind')->where('id', '[0-9a-z]{20}');

        // update notify level
        Route::put('/', 'DianfeiController@updateInfo');
    });
});

Route::group(['prefix' => 'wxy_auth'], function() {
	Route::match(['get', 'post'], '/login', 'AuthThiefController@login');
	Route::match(['get', 'post'], '/refresh', 'AuthThiefController@refresh');
});

// classroom api
Route::group(['prefix' => 'classroom'], function() {
	Route::match(['get', 'post'], '/', 'ClassroomController@index');
	Route::match(['get', 'post'], '/getFreeroom', 'ClassroomController@getFreeroom');
	Route::match(['get', 'post'], '/login', 'ClassroomController@login');
	Route::match(['get', 'post'], '/test', 'ClassroomController@test');
});

Route::group(['prefix' => 'schoolcard'], function() {
	Route::match(['get', 'post'], '/getAccount', 'StealController@getAccount');
});

Route::group(['prefix' => 'score'], function() {
	Route::match(['get', 'post'], '/getScore', 'ScoreController@getScore');
});

// ��֤·��...
Route::any('auth/login', 'Auth\AuthController@login');
//Route::post('auth/login', 'Auth\AuthController@postLogin');
//Route::get('auth/logout', 'Auth\AuthController@getLogout');
// ע��·��...
Route::any('auth/register', 'Auth\AuthController@register');
//Route::post('auth/register', 'Auth\AuthController@postRegister');

// static api
Route::get('compass/control.json', 'StaticJson@control');
Route::get('hotnews', 'StaticJson@news');


// compatible with old dianfei api
Route::group([
    'prefix' => 'electricity',
    'middleware' => 'api.compatible',
], function() {
    Route::post('/', 'DianfeiController@index');
    Route::post('/unbind', 'DianfeiController@unbind');
    Route::post('/bind', 'DianfeiController@bind');
});


// classroom api
Route::group(['middleware' => 'api.compatible'], function() {
    Route::match(['get', 'post'], '/classroom', 'ClassroomController@index');
});
