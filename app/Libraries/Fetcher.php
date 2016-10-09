<?php

namespace App\Libraries;

class Fetcher
{
    public static function get($class)
    {
        $fetcher = __namespace__.'\Fetchers\\'.$class;
        return (new $fetcher);
    }
}
