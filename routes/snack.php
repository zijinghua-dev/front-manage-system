<?php
/**
 * Created by PhpStorm.
 * User: fengliang
 * Date: 2020-01-17
 * Time: 11:33
 */
Route::group(['middleware' => ['api','checkExternalNames'],'prefix' => 'v1',], function () {
    Route::group(['prefix' => 'user',], function () {
        Route::post('/', 'UserController@store');
        Route::post('/index', 'UserController@index');
        Route::post('/fetch', 'UserController@fetch');
        Route::get('/{uuid}', 'UserController@show');
        Route::put('/', 'UserController@update');
        Route::post('/search', 'UserController@search');
        Route::put('/updatepassword', 'UserController@updatePassword');
    });
    Route::group(['prefix' => 'auth',], function () {
        Route::post('login', 'AuthController@login');
    });
    Route::group(['prefix' => 'group',], function () {
        Route::post('/', 'GroupController@store');
        Route::post('/index', 'GroupController@index');
        Route::post('/fetch', 'GroupController@fetch');
        Route::get('/{uuid}', 'GroupController@show');
        Route::put('/', 'GroupController@update');
    });
});
//use TCG\Voyager\Models\DataType;
//
//$pageApiVersion = \Zijinghua\Zvoyager\Base::getPageApiVersion();
//$controllerNamespace = config('zvoyager.controller.namespace').'\\'.$pageApiVersion.'\\';
//try {
//    foreach (DataType::all() as $dataType) {
//        $breadController = $dataType->controller
//            ? Str::start($dataType->controller, '\\')
//            : $controllerNamespace.'BaseController';
//
//        Route::resource(strtolower($pageApiVersion).'/'.$dataType->slug, $breadController);
//    }
//} catch (\InvalidArgumentException $e) {
//    throw new \InvalidArgumentException("Custom routes hasn't been configured because: ".$e->getMessage(), 1);
//} catch (\Exception $e) {
//    // do nothing, might just be because table not yet migrated.
//}
