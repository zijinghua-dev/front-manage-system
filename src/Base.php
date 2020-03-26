<?php
namespace Zijinghua\FM;

class Base
{
    public static function snackRoute()
    {
        return require __DIR__.'/../routes/snack.php';
    }

    public static function test()
    {
        echo '132';
    }
}