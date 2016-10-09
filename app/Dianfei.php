<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dianfei extends Model
{
    protected $table = 'dianfei';

    protected $dateFormat = 'U';

    protected $fillable = ['area', 'build', 'room', 'email', 'notify'];

    public static function isBind($email)
    {
        return self::where('email', $email)->first();
    }


    public static function unbind($email)
    {
        return self::where('email', $email)->delete();
    }
}
