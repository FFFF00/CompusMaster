<?php
/*
 * Created on 2016��9��27��
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
namespace App\Steal\Tools;

use App\Steal\Tools\Tools;

use Redis;

class Schoolcard extends Tools{
	function getAccount($args){
		$user_id = $args['user_id'];
		$url = 'https://pass.hust.edu.cn/cas/login' .
				'?service=http%3A%2F%2Fecard.m.hust.edu.cn%3A80%2Fwechat-web%2FQueryController%2FQueryurl.html';
				
		if(!$this->_check_login());
			return 401;
		
		$session_header = array(
			'Cookie: '.Redis::get($user_id.'_CASTGC'),
		);
		
		return $this->_get_content_302($url, $session_header);
	}
	
//	private function _get_content($url, $header, $cookie='') {
//		$curlPost = ''; 
//	    $ch = curl_init(); 
//	    curl_setopt($ch, CURLOPT_URL, $url); 
//	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
//		
//	    curl_setopt($ch, CURLOPT_HEADER, 1);
//	    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie); //��ȡcookie 
//	    curl_setopt($ch, CURLOPT_HTTPHEADER , $header);  //����IP
//	    $rs = curl_exec($ch); //ִ��cURLץȡҳ������
//	    curl_close($ch); 
//	    return $rs; 
//	} 
} 
?>
