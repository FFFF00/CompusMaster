<?php
/*
 * Created on 2016��9��29��
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
namespace App\Steal\Tools;

use App\Steal\Tools\Tools;

use PHPHtmlParser\Dom;
use Redis;

class Score extends Tools{

	public function getDetail($args){
		$url = 'http://hub.m.hust.edu.cn/cj/cjsearch/findcjinfo.action?xn='.$args['term'].'&xq=0';
		$dom = $this->_dry($args, $url);
		
		$json = array();
		foreach($dom->find('ul.list tr.tablelist1') as $tr){
			$i = 0;
			$j = array();
			foreach($tr->find('td') as $td){
				if($i == 0){
					$j['classname'] = $td->text;
				}elseif($i == 1){
					$j['credit'] = $td->text;
				}elseif($i == 2){
					$j['score'] = $td->text;
				}elseif($i == 3){
					$j['remark'] = $td->text;
				}
				$i ++;
			}
			array_push($json, $j);
		}	
		return $json;
	}
	
	public function getOutline($args){
		$url = 'http://hub.m.hust.edu.cn/cj/cjsearch/findcjxqh_zcj.action';
		$dom = $this->_dry($args, $url);
		
		$json = array();
		foreach($dom->find('ul.list tr.tablelist1') as $tr){	
			$j = array();
			if($tr->find('td')->text != ''){
				$current = $tr->find('td')->text; 
			}else{
				$current .= '第二学期';
			}
			foreach($tr->find('td') as $td){
				if(is_numeric($td->text)){
					array_push($j, $td->text);	
				}
			}
			$json["$current"] = $j;
		}			
		return $json;
	}
	
	public function getEnglishScore($args){		
		$url = 'http://hub.m.hust.edu.cn/cj/cjsearch/findcjxqh_slj.action';
		$dom = $this->_dry($args, $url);	
		
		$json = array();
		foreach($dom->find('ul.list tr.tablelist1') as $tr){
			$i = 0;
			$j = array();
			foreach($tr->find('td') as $td){
				if($i == 0){
					$j['time'] = $td->text;
				}elseif($i == 1){
					$j['subject'] = $td->text;
				}elseif($i == 2){
					$j['score'] = $td->text;
				}elseif($i == 3){
					$j['remark'] = $td->text;
				}
				$i ++;	
			}
			array_push($json, $j);
		}	
		return $json;		
	}
	
	private function _dry($args, $url){
		$user_id = $args['user_id'];
		if($this->_getScoreCookie($args) == 401)
			return 401;
		$session_header = array(
			'Cookie: '.Redis::get($user_id.'_fcj_cookie'),
		);
		$content = $this->_get_content_302($url, $session_header);//echo $content;		
		$dom = new Dom();
		return $dom->loadStr($content, []);
	}
	
	private function _getScoreCookie($args){
		$user_id = $args['user_id'];
		$url = 'https://pass.hust.edu.cn/cas/login' .
				'?service=http%3A%2F%2Fhub.m.hust.edu.cn%2Fcj%2Findex.jsp';	
			
		if(!$this->_check_CASTGC($args))
			return 401;
	
		$session_header = array(
			'Cookie: '.Redis::get($user_id.'_CASTGC'),
		);
		
		$content = $this->_get_content_302($url, $session_header);

		preg_match_all('/Set-Cookie:(.*);/iU', $content, $score_cookie_str);
		Redis::set($user_id.'_fcj_cookie', $score_cookie_str[1][1]);
		return $score_cookie_str[1][1];
	}
}
?>
