<?php
/*
 * Created on 2016��9��29��
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
namespace App\Steal\Tools;

use App\Libraries\Fetchers\Spider;
use Cache;
use Redis;
 
 
class Tools{
	
	protected function _check_CASTGC($args){
		if(Redis::get($args['user_id'].'_CASTGC')){
			return true;
		}	
		return false;
	}
	
	protected function _check_login($args){
		if(Redis::get($args['user_id'].'_cookie')){
			return true;
		}	
		return false;	
	}
	
	protected function _get_ticket($args, $refer, $cookie_tail){
		$user_id = $args['user_id'];
		$url = 'https://pass.hust.edu.cn/cas/login' .
				'?service='
				.$refer;	
			
		if(!$this->_check_CASTGC($args))
			return 401;
	
		$session_header = array(
			'Cookie: '.Redis::get($user_id.'_CASTGC'),
		);
		
		$content = $this->_get_content_302($url, $session_header);

		preg_match_all('/Set-Cookie:(.*);/iU', $content, $score_cookie_str);
		Redis::set($user_id.$cookie_tail, $score_cookie_str[1][1]);
		return $score_cookie_str[1][1];
	}
	
	protected function _get_content($url, $header, $data=[], $cookie='') {
		$curlPost = ''; 
	    $ch = curl_init(); 
	    curl_setopt($ch, CURLOPT_URL, $url); 
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 	     
	
	    curl_setopt($ch, CURLOPT_HEADER, 1);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie); //��ȡcookie 
	    curl_setopt($ch, CURLOPT_HTTPHEADER , $header);  //����IP
	     
	    curl_setopt($ch, CURLOPT_POST, 1);      
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); 
        
	    $rs = curl_exec($ch); //ִ��cURLץȡҳ������
	    curl_close($ch); 
	    return $rs; 
	} 
	
	protected function _get_content_302($url, $header, $data=[], $cookie='') {
		$curlPost = ''; 
	    $ch = curl_init(); 
	    curl_setopt($ch, CURLOPT_URL, $url); 
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    	    		    
		curl_setopt($ch,  CURLOPT_FOLLOWLOCATION, 1); // 302 redirect
				
	    curl_setopt($ch, CURLOPT_HEADER, 1);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie); //��ȡcookie 
	    curl_setopt($ch, CURLOPT_HTTPHEADER , $header);  //����IP
	    
	    curl_setopt($ch, CURLOPT_POST, 1);      
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); 

	    $rs = curl_exec($ch); //ִ��cURLץȡҳ������
	    curl_close($ch); 
	    return $rs; 
	} 
}
?>
