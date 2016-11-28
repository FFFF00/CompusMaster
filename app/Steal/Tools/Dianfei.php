<?php
/*
 * Created on 2016��11��28��
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
 namespace App\Steal\Tools;
 
 use PHPHtmlParser\Dom;
 use Cache;
 use Redis;
 
 class Dianfei extends Tools{
 	public function getDaifei($args, $cookie_tail){
 		$url = 'http://one.hust.edu.cn:8080/elecFee/startAction.do';

 		$user_id = $args['user_id'];
 		
 		if(!Redis::get($user_id.$cookie_tail))
 			$this->_get_ticket($args, $url, $cookie_tail);
 		
 		$header = array (
			'Cookie:'.Redis::get($user_id.$cookie_tail),
		); 
		
 		$content = $this->_get_content_302($url, $header); 			
		$dom = new Dom();
		$dom->loadStr($content, []);
 		try{	
 			$data = $this->_parse($dom);
 		} catch (\Exception $e) {
            return 401;
        }		
        return $data;
 	}
 	
 	public function setDormitory($args, $cookie_tail){
 		$refer = 'http://one.hust.edu.cn:8080/elecFee/setDormitoryPre.do';
 		$url = 'http://one.hust.edu.cn:8080/elecFee/saveDormitory.do';
 		
 		$args = $this->parseBuildInfo($args); 	
 		$user_id = $args['user_id'];
 		
 		if(!Redis::get($user_id.$cookie_tail))
 			$this->_get_ticket($args, $refer, $cookie_tail);
 		
 		$header = array (
			'Cookie:'.Redis::get($user_id.$cookie_tail),			
		); 	
		$data = array(      
        	'programId' => $args['area'],
        	'txtyq' => $args['build'],
        	'txtld' => substr($args['room'],0,1).'层',
        	'Txtroom' => $args['room'],     
    	);  
		$content = $this->_get_content_302($url, $header, $data);
				
		$dom = new Dom();
		$dom->loadStr($content, []);
		
		return $data = $this->_parse($dom);	
 	}
 	
 	private function _parse($dom){
 		$data = [];
 		$data['build'] = $dom->find('div.dfcx_title_bar')->find('span.dfcx_title_02')[0]->text;
 		$data['room'] = $dom->find('div.dfcx_title_bar')->find('span.dfcx_title_02')[1]->text;
 		$data['last_update'] = $dom->find('div.dfcx_box_02')->find('p.dfcx_bar_03')->text;
        $data['last_update'] = explode('：', $data['last_update'], 2)[1]; 		
 		$data['remain'] = $dom->find('div.dfcx_box_02 p.dfcx_bar_02')->find('font.dfcx_name_02')->text;
	 		
 		$data_recent = [];
 		foreach($dom->find('div.dfcx_list div.dfcx_row') as $row){
 			$updated_at = $row->find('p.dfcx_time')->text;
 			$date = date('Ymd', strtotime($updated_at));
 			
 			$data_recent[$date] = [
                'updated_at' => $updated_at,
                'dianfei' => $row->find('p.dfcx_electric')->text,
            ];
			
		}
		$data['recent'] = $data_recent;
		
		return $data;
 	}
 	
 	 public function parseBuildInfo($args){
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
?>
