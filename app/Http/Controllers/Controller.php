<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesResources;

abstract class Controller extends BaseController
{
	//use AuthorizesRequests, AuthorizesResources, DispatchesJobs, ValidatesRequests;
    use DispatchesJobs, ValidatesRequests;

    protected function success($code=null, $msg=null, $data=null)
    {
        $json = [
            'code' => 200,
            'msg' => 'success',
            'data' => [],
        ];

        foreach([$code, $msg, $data] as $v)
        {
            switch (true)
            {
                case (is_integer($v)):
                    $json['code'] = $v;
                    break;
                case (is_string($v)):
                    $json['msg'] = $v;
                    break;
                case (is_array($v)):
                    $json['data'] = $v;
            }
        }
        return response()->json($json);
    }


    protected function fail($code=null, $msg=null, $data=null)
    {
        $json = [
            'code' => 400,
            'msg' => 'failed',
            'data' => [],
        ];

        foreach([$code, $msg, $data] as $v)
        {
            switch (true)
            {
                case (is_integer($v)):
                    // var_dump($v);
                    $json['code'] = $v;
                    break;
                case (is_string($v)):
                    $json['msg'] = $v;
                    break;
                case (is_array($v)):
                    $json['data'] = $v;
            }
        }

        return response()->json($json);
    }
}

