<?php

Route::get('mobiauth', function(){
    echo 'Hello from mobiauth package!';
});


Route::group(['prefix' => 'api/v2'], function () {
    Route::post('login', 'Mobidev\Auth\MobiAuthController@login');
    Route::post('register', 'Mobidev\Auth\MobiAuthController@register');
    Route::post('registerorlogin', 'Mobidev\Auth\MobiAuthController@registerOrLogin');
    Route::get('addstatus', 'Mobidev\Auth\MobiAuthController@addStatus');
    Route::post('addtype', 'Mobidev\Auth\MobiAuthController@addType');

    Route::group([
        'middleware' => 'auth:api'
    ], function() {
        Route::get('logout', 'Mobidev\Auth\MobiAuthController@logout');
        Route::get('user', 'Mobidev\Auth\MobiAuthController@user');
        Route::post('addstatus', 'Mobidev\Auth\MobiAuthController@addStatus');
        Route::post('addtype', 'Mobidev\Auth\MobiAuthController@addType');
        Route::post('changeuserstatus', 'Mobidev\Auth\MobiAuthController@changeUserStatus');
        Route::post('changeusertype', 'Mobidev\Auth\MobiAuthController@changeUserType');
    });
});