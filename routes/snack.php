<?php
/**
 * Created by PhpStorm.
 * User: fengliang
 * Date: 2020-01-17
 * Time: 11:33
 */
Route::group(['middleware' => ['api','setRequestParameters'],'prefix' => 'v1',], function () {
    Route::group(['prefix' => 'auth',], function () {
        Route::post('login', 'AuthController@login');
    });
    Route::group(['prefix' => 'user',], function () {
        Route::post('/', 'UserController@store');
    });
//,'zAuthorize'
    Route::group(['middleware'=>['auth:api','zCheckParent']], function () {
        Route::group(['prefix' => 'group'], function () {
            Route::post('/expand', 'GroupController@expand');//给组增加属性，允许它装载更多类型的对象
        });
    });
    //,'zCheckGroup','zAuthorize'
    Route::group(['middleware'=>['auth:api']], function () {

        Route::group(['prefix' => 'auth',], function () {
            Route::post('logout', 'AuthController@logout');
        });



        Route::group(['middleware' => 'zUuid','prefix' => 'user'], function () {
            Route::post('/', 'UserController@store');
            Route::post('/index', 'UserController@index');
            Route::post('/fetch', 'UserController@fetch');
            Route::get('/{uuid}', 'UserController@show');
            Route::put('/', 'UserController@update');
            Route::post('/search', 'UserController@search');
            Route::put('/updatepassword', 'UserController@updatePassword');
            Route::post('/clear', 'UserController@clear');
            Route::post('/delete', 'UserController@delete');
//            Route::put('/destroy', 'UserController@destroy');
        });

        Route::group(['prefix' => 'group'], function () {
//            Route::post('/', 'GroupController@store');
            Route::post('/index', 'GroupController@index');
            Route::post('/fetch', 'GroupController@fetch');
            Route::get('/{groupId}', 'GroupController@show');
            Route::post('/search', 'GroupController@search');
            Route::put('/', 'GroupController@update');
//            Route::delete('/{groupId}', 'GroupController@destroy');
            Route::post('/delete', 'GroupController@delete');//批量删除，可以传array
            Route::post('/append', 'GroupController@append');//向组内添加对象，可以传array
            Route::post('/clear', 'GroupController@clear');//从组内移除对象，并不删除，可以传array
            Route::post('/shrink', 'GroupController@shrink');//减少组的属性，不允许它装载某个类型的对象
        });

        Route::group(['prefix' => 'datatype'], function () {
            Route::post('/', 'DatatypeController@store');
            Route::post('/index', 'DatatypeController@index');
            Route::post('/fetch', 'DatatypeController@fetch');
            Route::get('/{groupId}', 'DatatypeController@show');
            Route::post('/search', 'DatatypeController@search');
            Route::put('/', 'DatatypeController@update');
            Route::delete('/{groupId}', 'DatatypeController@destroy');
            Route::post('/delete', 'DatatypeController@delete');//批量删除，参数名为uuid，可以传array
//            Route::post('/clear', 'DatatypeController@clear');//从组内移除对象，并不删除，参数名为uuid，可以传array
        });
    });
});
//use TCG\Voyager\Models\Datatype;
//
//$pageApiVersion = \Zijinghua\Zvoyager\Base::getPageApiVersion();
//$controllerNamespace = config('zvoyager.controller.namespace').'\\'.$pageApiVersion.'\\';
//try {
//    foreach (Datatype::all() as $dataType) {
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
