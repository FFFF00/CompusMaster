<?php

if ( ! function_exists('str_random')) {
    function str_random($length=32)
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            return substr(bin2hex(openssl_random_pseudo_bytes(ceil($length / 2))), 0, $length);
        } else {
            $pool ="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
            $str = '';
            for($i = 0; $i < $length; $i++) {
                $str .= $pool[rand(0, strlen($pool) - 1)];
            }

            return $str;
        }
    }
}


if ( ! function_exists('md5_random')) {
    function md5_random($str=null, $length=null)
    {
        $s = '';
        $l = 32;
        foreach([$str, $length] as $v) {
            is_integer($v) AND ($l = $v);
            is_string($v) AND ($s = $v);
        }

        return substr(md5(str_random(50).$s.microtime()), 0, $l);
    }
}


if ( ! function_exists('array_replace_key')) {
    function array_replace_key($key, $replace, $arr)
    {
        if (isset($arr[$key])) {
            $arr[$replace] = $arr[$key];
            if ($key !== $replace) unset($arr[$key]);
        }

        return $arr;
    }
}