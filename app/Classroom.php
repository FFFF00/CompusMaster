<?php

namespace App;

class Classroom extends BaseClassroom
{
    protected $table = 'classroom';

    protected function _where($args)
    {
        return [
            'cdate' => date('Ymd', strtotime($args['date'])),
            'build' => $args['build'],
            'period' => $args['period'],
        ];
    }
}
