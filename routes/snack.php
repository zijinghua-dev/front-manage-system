<?php
/**
 * Created by PhpStorm.
 * User: fengliang
 * Date: 2020-01-17
 * Time: 11:33
 */

use TCG\Voyager\Models\DataType;

$pageApiVersion = \Zijinghua\Zvoyager\Base::getPageApiVersion();
$controllerNamespace = config('zvoyager.controller.namespace').'\\'.$pageApiVersion.'\\';
try {
    foreach (DataType::all() as $dataType) {
        $breadController = $dataType->controller
            ? Str::start($dataType->controller, '\\')
            : $controllerNamespace.'BaseController';

        Route::resource(strtolower($pageApiVersion).'/'.$dataType->slug, $breadController);
    }
} catch (\InvalidArgumentException $e) {
    throw new \InvalidArgumentException("Custom routes hasn't been configured because: ".$e->getMessage(), 1);
} catch (\Exception $e) {
    // do nothing, might just be because table not yet migrated.
}
