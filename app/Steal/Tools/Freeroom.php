<?php
/*
 * Created on 2016��8��30��
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 namespace App\Steal\Tools;

 use Cache;
 use Redis;
 
 class Freeroom{
 	public function getFreeroom($args){
 		$url = 'http://hub.m.hust.edu.cn/aam/room/selectFreeRoom.action' .
 				'?buildingCode=' .$args['buildingCode'].
 				'&borrowDate=' .$args['borrowDate'].
 				'&section='.$args['section'];
 				
 		//$url = 'http://hub.m.hust.edu.cn/aam/room/selectFreeRoom.action?buildingCode=%27C050,%E8%A5%BF%E4%BA%94%E6%A5%BC%27&borrowDate=%272016-09-11%27&section=%271-2%E8%8A%82%27';
 		$user_id = $args['user_id'];
 		$header = array (
			'Cookie:'.Redis::get($user_id.'_cookie'),
		); 
 		//var_dump($this->_get_content($url, $header));
 		return $this->_get_content($url, $header);
 	}
 	
	private function _get_content($url, $header, $cookie='') {
		$curlPost = ''; 
	    $ch = curl_init(); 
	    curl_setopt($ch, CURLOPT_URL, $url); 
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		
	    curl_setopt($ch, CURLOPT_HEADER, 1);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie); //��ȡcookie 
	    curl_setopt($ch, CURLOPT_HTTPHEADER , $header);  //����IP
	    $rs = curl_exec($ch); //ִ��cURLץȡҳ������
	    //can't get content successfully
	    if(curl_getinfo($ch,CURLINFO_HTTP_CODE) != 200)
	    	return curl_getinfo($ch,CURLINFO_HTTP_CODE);
	    curl_close($ch);   
	    return $rs; 
	} 
 }

?>
