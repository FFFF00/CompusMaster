<?php

namespace App\Libraries\Fetchers;

class Dianfei extends Spider
{
    public $base_url = 'http://202.114.18.218/main.aspx';

    public function fetch($args)
    {
        // convert data to the format identified by school's server
        $args = array_merge($args, $this->parseBuildInfo($args));
        $floor = substr(preg_replace('/[^0-9]/', '', $args['room']), 0, 1) . '层';

        // get viewstate data from page
        $v_data = @$this->getViewState($this->get($this->base_url));

        if ( ! $v_data) return false;

        // query area
        $v_data['programId'] = $args['area'];
        $nv_data = @$this->getViewState($this->post($this->base_url, $v_data));
        if ( ! $nv_data) return false;

        // query building
        $v_data = array_merge($v_data, $nv_data);
        $v_data['txtyq'] = $args['build'];
        $nv_data = @$this->getViewState($this->post($this->base_url, $v_data));
        if ( ! $nv_data) return false;

        // query room
        $v_data = array_merge($v_data, $nv_data, [
            'txtld' => $floor,
            'Txtroom' => $args['room'],
            'ImageButton1.x' => '43',
            'ImageButton1.y' => '20',
        ]);
        // send request
        $dom = $this->loadStr($this->post($this->base_url, $v_data));

        // resolve result page and fill data
        if ($data = @$this->_parse($dom))
        {
            return array_merge([
                'build' => $args['build'],
                'room' => $args['room'],
            ], $data);
        } else {
            return null;
        }
    }


    private function _parse($dom)
    {
        // parse basic data
        $boxes = ['last_update' => '#TextBox2', 'remain' => '#TextBox3'];
        foreach($boxes as $k => $v) {
            $ele = $dom->find($v, 0);
            if ($ele AND ($v = $ele->getAttribute('value'))) {
                $data[$k] = $v;
            } else {
                return null;
            }
        }

        // last month bills
        $bill_items = [];
        try {
            $rows = array_slice($dom->find('#GridView1 tr')->toArray(), 1);
            foreach ($rows as $row) {
                $amount = $this->clean(strip_tags($row->find('td', 0)));
                $money = $this->clean(strip_tags($row->find('td', 1)));
                $date = date('Y-m-d H:i:s', strtotime($this->clean(strip_tags($row->find('td', 2)))));
                $bill_items[] = compact('amount', 'money', 'date');
            }
        } catch (\Exception $e) {
            return null;
        }
        $data['bills'] = $bill_items;

        // resolve data rows
        $data_items = [];
        try {
            $rows = array_slice($dom->find('#GridView2 tr')->toArray(), 1);
            foreach ($rows as $row) {
                $val = $this->clean(strip_tags($row->find('td', 0)));
                $updated_at = $this->clean(strip_tags($row->find('td', 1)));
                $ddate = date('Ymd', strtotime($updated_at));
                $data_items[$ddate] = [
                    'updated_at' => $updated_at,
                    'dianfei' => $val,
                ];
            }
        } catch (\Exception $e) {
            return null;
        }

        $data['recent'] = $data_items;
        return $data;
    }


    public function parseBuildInfo($args)
    {
        $nmap = [
            '一', '二', '三', '四', '五', '六', '七', '八', '九',
            '十', '十一', '十二', '十三', '十四', '十五',
        ];

        $id = $args['build'];
        switch ($args['area'])
        {
            case '东区':
                switch (true)
                {
                    case ($id < 9):
                        $args['build'] = "东{$nmap[$id - 1]}舍";
                        break;
                    case ($id < 14):
                        $args['build'] = "沁苑东{$nmap[$id - 1]}舍";
                        break;
                    case ($id < 20):
                        $tmp_map = ['南一舍', '南二舍', '南三舍', '教七舍', '附中实验楼', '附中主楼'];
                        $args['build'] = $tmp_map[$id - 14];
                }
                break;
            case '西区':
                if ($id < 15) $args['build'] = "西{$nmap[$id - 1]}舍"; 
                break;
            case '韵苑':
                if ($id < 13 || $id == 27) {
                    $args['area'] = '韵苑一期';
                    $args['build'] = "韵苑{$id}栋";
                } elseif ($id < 29) {
                    $args['area'] = '韵苑二期';
                    $args['build'] = "韵苑{$id}栋";
                }
                break;
            case '紫菘':
                if ($id < 15) $args['build'] = "紫菘{$id}栋";
                break;
            case '留学生楼':
                if ($id == 1) $args['build'] = '友谊公寓';
        }

        return $args;
    }
}
