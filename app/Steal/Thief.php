<?php
namespace App\Steal;

class Thief
{
    public static function get($class)
    {
        $thief = __namespace__.'\Tools\\'.$class;
        return (new $thief);
    }
}
 

