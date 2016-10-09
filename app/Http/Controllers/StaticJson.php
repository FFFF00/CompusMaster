<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class StaticJson extends Controller
{
    public function control()
    {
        return $this->success([
            'dianfei' => true,
            'classroom' => false,
            'ctable' => true,
            'lib' => true,
            'bus' => false,
        ]);
    }


    public function news()
    {
        return $this->success([
            [
                'title' => '华中大在线 - 华中科技大学门户网站',
                'url' => 'http://www.hustonline.net',
            ],
            [
                'title' => '一瞬 - 不多，一瞬就够',
                'url' => 'http://yishun.co',
            ],
            [
                'title' => '校园二手街 - 最安全方便的校内二手街',
                'url' => 'http://hust.2shoujie.com',
            ],
            [
                'title' => 'iKnow - 华中科技大学校内问答社区',
                'url' => 'http://ik.hustonline.net',
            ],
            [
                'title' => '校园通更新啦，快点击更新吧！',
                'url' => 'http://www.wandoujia.com/apps/net.bingyan.hustpass',
            ],
            [
                'title' => '然后-故事才刚刚开始 上线啦！',
                'url' => 'http://ranhou.hustonline.net/web/download.html',
            ]
        ]);
    }
}
