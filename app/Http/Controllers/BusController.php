<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Redis;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class BusController extends Controller
{
    public $test_key = 'simplekey';
    protected $bus_max_items = 2000;
    protected $bus_cache_key = 'bus_raw_data';

    public function storeRaw(Request $req)
    {
        // if ($req->input('key') !== md5(date('Y-m-d', time()))) {
        //     return $this->fail(401, 'unauthorized');
        // }

        $data = $req->all();
        if ( ! empty($data) AND @$data['mac']) {
            $data['stored_at'] = date('Y-m-d H:i:s', time());
            Redis::ltrim($this->bus_cache_key, 0, $this->bus_max_items);
            Redis::lpush($this->bus_cache_key, base64_encode(serialize($data)));
            return $this->success();
        }
        else {
            return $this->fail();
        }
    }


    public function getRaw(Request $req)
    {
        if ($req->input('key') !== $this->test_key) return;

        $bus_id = $req->input('id');

        $items = Redis::lrange($this->bus_cache_key, 0, 99);
        for ($i = 1; $i <= count($items); $i++) {
            $data = unserialize(base64_decode($items[$i - 1]));
            if ($bus_id) {
                if ($data['mac'] == $bus_id) {
                    dump($data);
                }
            }
            else {
                dump($data);
            }
        }
    }


    public function flushRedis(Request $req)
    {
        if ($req->input('key') !== $this->test_key) return;

        Redis::del($this->bus_cache_key);
        return $this->success();
    }
}
