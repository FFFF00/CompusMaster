<?php
/*
 * Created on 2016��9��11��
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
namespace App\Steal\Tools;

use Cache;
use Redis;

class Login extends Tools{
	public function s_login($args){
		$user_id = $args['user_id'];
		$password = $args['password'];
$user_id = 'U201414564';
$password = '110017';
		$url = 'https://pass.hust.edu.cn/cas/login' .
				'?service=http%3A%2F%2Fhub.m.hust.edu.cn%2F';
	 	$cookie = $this->_get_cookie($url);

	 	$header = array(
			'Cookie: '.$cookie,
			);
	 	$encrypt_user_id = $this->RSA($user_id);
	 	$encrypt_password = $this->RSA($password);
	 	
		$post = array (
			'username' => $encrypt_user_id,
			'password' => $encrypt_password,
			'code' => 'code',
			'lt' => 'LT-NeusoftAlwaysValidTicket',
			'execution' => 'e1s1',
			'_eventId' => 'submit',
		);
		$this->_login_post($url, $post, $header, $user_id);
	    
		$session_header = array(
			'Cookie: '.Redis::get($user_id.'_CASTGC'),
		);

		$session_url = Redis::get('session_url');    
		$rs = $this->_get_content($session_url, $session_header);
		
		preg_match('/Set-Cookie:(.*);/iU', $rs, $cookie_str);
	    Redis::set($user_id.'_cookie', $cookie_str[1]);
	    preg_match('/Location: (http:\/\/[^\s]*)/', $rs, $url_str);
	    //Cache::put('url', $url_str[1], 30*24);//var_dump(Redis::get('url'));
	    
	    return Redis::get($user_id.'_cookie');
 	}
 	 																																		
	private function _login_post($url, $post, $header, $user_id) { 
	    $ch = curl_init();//��ʼ��curlģ�� 
	    curl_setopt($ch, CURLOPT_URL, $url);//��¼�ύ�ĵ�ַ 
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//�Ƿ��Զ���ʾ���ص���Ϣ
	    
	    curl_setopt($ch, CURLOPT_HEADER, 1);
	    curl_setopt($ch, CURLOPT_HTTPHEADER , $header);  //����IP
	     
	    curl_setopt($ch, CURLOPT_POST, 1);//post��ʽ�ύ 	
	    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));//Ҫ�ύ����Ϣ 
	    $rs = curl_exec($ch);//ִ��cURL 
	    curl_close($ch);//�ر�cURL��Դ�������ͷ�ϵͳ��Դ
	    
	    preg_match('/CASTGC=(.*);/iU', $rs, $cookie_str);
	    Redis::set($user_id.'_CASTGC', $cookie_str[0]);
	    preg_match('/Location: (http:\/\/[^\s]*)/', $rs, $url_str);
	    Redis::set('session_url', $url_str[1]);

	    return $rs; 
	} 
	
	private function _get_cookie($url, $header=array()){
	    $ch = curl_init(); 
	    curl_setopt($ch, CURLOPT_URL, $url); 
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

	    curl_setopt($ch, CURLOPT_HEADER, 1);
	    curl_setopt($ch, CURLOPT_HTTPHEADER , $header);  //����IP

	    $rs = curl_exec($ch); //ִ��cURLץȡҳ������	    
	    curl_close($ch); 
	    
	    preg_match('/Set-Cookie:(.*);/iU', $rs, $str);
	    $cookie = $str[1];
	    return $cookie; 
	}
	
	private function RSA($data){
		$reshex = '';
		for($i = 0; $i < strlen($data); $i ++){
			$char = substr($data,$i,1);
			$num = ord($char);
			$numhex = dechex($num);
			$reshex = $numhex.$reshex;
		}

		$res = '';
		$reslen = strlen($reshex);
		for($i = 0; $i < $reslen; $i ++){
			$char = substr($reshex,$i,1);
			$char = hexdec($char);
			$right = bcpow('16',$reslen - $i - 1);
			$a = bcmul($char, $right);
			$res = bcadd($a, $res);
		}

		$ehex = '10001';
		$edec = '65537';
		$mhex = '89b7ad1090fe776044d393a097e52f99fc3f97690c90215ecb01f1b3dfc4d8b0226a4b16f51a884e0c1545180eb40365dbec848cc0df52f515512e2317bf9d82b6f4c9cafcc94082fd86c97e77a4d3aa44cba54f8d94f5757ce3cc82c3adf31082738cfe531b4b4675f35a0c8401745dbed15c92d0747c6349915378fff22b9b';
		$mdec = '96708506425950469012371695452708133904014990604349062573191664075633606379117175445629245663515709833592339937521961089562474212551305213935288416294607967220351818795815677441678041772408148844722575762660342757677398679466134633743167442303795192689235513752939691553542043509014637281724647589004245609371';
		$result = bcpowmod($res, $edec, $mdec);
		
		$r = $this->decToHex($result);
		if(strlen($r) == 255){
			$r = '0'.$r;
		}
		return $r;
	}
	
	private function decToHex($n){
		$r='';
		while ($n){
		  //$n����2����$m������$k
		  $k = 0;
		  $m = '';
		  do{
		    $k = $k*10 + substr($n,0,1);
		    if ($m != '' || $k > 1) $m .= floor($k/16);
		    $k = $k % 16;
		    $n = substr($n,1);
		    //$r=$k . $r;
		  }while($n != '');
		  //echo "r=$r;m=$m\n";//break;
		  //��һ�ֳ���
		  $n = $m;
		  if($k > 9) $k = dechex($k);
		  $r = $k . $r;
		}
		return $r;
	}
}
?>
