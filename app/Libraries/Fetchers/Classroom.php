<?php

namespace App\Libraries\Fetchers;

class Classroom extends Spider
{

    public function __construct ()
    {
        $this->base_url = 'http://202.114.5.131/classroom.aspx';

        $this->build_map = [
            'D9' => 7,
            'D12' => 1,
            'X5' => 5,
            'D5' => 11,
            'X12' => 13,
        ];

        $this->period_map = [
            'AM' => 1,
            'PM' => 2,
            'NIG' => 3,
            'WHOLE' => 4,
        ];
    }


    public function classroom ($args)
    {
        $rows = $this->_fetchClassroomRows($args);
        $data = [];
        foreach ($rows as $row) {
            if (preg_match('/1.*?2|5.*?6/', $row[0])) {
                $data['partA'] = $row[1];
            }
            elseif (preg_match('/3.*?4|7.*?8/', $row[0])) {
                $data['partB'] = $row[1];
            }
            else {
                $data['whole'] = $row[1];
            }
        }

        return $data;
    }


    protected function _fetchClassroomRows ($args)
    {
        // get viewstate data
        $v_data = $this->getViewState($this->base_url);

        // post data and fetch classrooms
        $post_data = [
            'datepicker2' => @date('Y-m-d', strtotime($args['date'])),
            'ddlBuild2' => @$this->build_map[$args['build']],
            'ddlTime' => @$this->period_map[$args['period']],
            'btSearch2' => "查询",
        ];

        try {
            $html = $this->post($this->base_url, array_merge($v_data, $post_data));
            $data_rows = @$this->loadStr($html)->find('#divZixi table', 0)->find('tr');
            if ( ! @count($data_rows)) throw new \Exception;

            // resolve data
            $data_items = [];
            foreach ($data_rows as $data_row)
            {
                $item_name = strip_tags($this->clean($data_row->find('td', 0)));
                $item_txt = preg_replace('/\s+/', ' ', strip_tags($this->clean($data_row->find('td', 1))));
                $data_items[] = [$item_name, $item_txt];
            }
            return $data_items;
        } catch(\Exception $e) {
            return [];
        }
    }


    public function roomcourse ($args)
    {
        $data_items = [];

        // if there has no rooms passed, return directly
        if ( ! $args['rooms']) return [];

        // post building field, then extract viewstate data from
        // response html
        $v_data = array_merge($this->getViewState($this->base_url), [
            'ddlBuild' => $this->build_map[$args['build']],
        ]);
        $v_data = $this->getViewState($this->post($this->base_url, $v_data));

        // post data and fetch classrooms
        $post_data = [
            'datepicker' => date('Y-m-d', strtotime($args['date'])),
            'ddlBuild' => $this->build_map[$args['build']],
            'btSearch' => "查询",
        ];

        // convert query args into string so that we can add multiple
        // `listBoxClass`
        $post_str = http_build_query(array_merge($v_data, $post_data));
        foreach ($args['rooms'] as $v) {
            $post_str .= ('&listBoxClass='.$v);
        }

        // ignore thead row and resolve the result rows in tbody
        $rows = @array_slice($this->loadStr($this->post($this->base_url, $post_str))
                                  ->find('#gvMain tr')->toArray(), 1);
        try {
            foreach ($rows as $row) {
                $room = strip_tags($this->clean($row->find('td', 0)->text));
                $data = [
                    'A' => strip_tags($this->clean($row->find('td', 1)->innerHtml)),
                    'B' => strip_tags($this->clean($row->find('td', 2)->innerHtml)),
                    'C' => strip_tags($this->clean($row->find('td', 3)->innerHtml)),
                    'D' => strip_tags($this->clean($row->find('td', 4)->innerHtml)),
                    'E' => strip_tags($this->clean($row->find('td', 5)->innerHtml)),
                ];
                $data_items[$room] = $data;
            }
        } catch(\Exception $e){
            return null;
        };

        return $data_items;
    }
}
