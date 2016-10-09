<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

abstract class BaseClassroom extends Model
{
    protected $dateFormat = 'U';

    protected $guarded = [];

    protected abstract function _where($args);

    public function get($args)
    {
        if ($row = self::where($this->_where($args))->first()) {
            $row->data = unserialize(base64_decode($row->data));
            return $row->toArray();
        }
    }


    public function store($args, $data)
    {
        $row = self::firstOrNew($this->_where($args));
        $row->data = base64_encode(serialize($data));
        return $row->save();
    }
}
