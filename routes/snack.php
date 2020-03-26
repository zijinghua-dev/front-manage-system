<?php
/**
 * Created by PhpStorm.
 * User: fengliang
 * Date: 2020-01-17
 * Time: 11:33
 */

use TCG\Voyager\Models\DataType;

try {
    foreach (DataType::all() as $dataType) {
        if (\Illuminate\Support\Str::startsWith($dataType->model_name, ['App', '\App'])) {
            $breadController = $dataType->controller
                ? Str::start($dataType->controller, '\\')
                : 'Api\V1\Page\BaseController';

            Route::resource($dataType->slug, $breadController);
        }
    }
} catch (\InvalidArgumentException $e) {
    throw new \InvalidArgumentException("Custom routes hasn't been configured because: ".$e->getMessage(), 1);
} catch (\Exception $e) {
    // do nothing, might just be because table not yet migrated.
}
