<?php
namespace Zijinghua\Zvoyager;

class Base
{
    public static function snackRoute()
    {
        return require __DIR__.'/../routes/snack.php';
    }

    public static function getPageApiVersion()
    {
        return request()->header('ZVOYAGER_PAGE_API_VERSION') ?: config('zvoyager.page_api.version');
    }
}