<?php
/*
 * Created on 2016��9��29��
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Steal\Thief;

use Validator;
use Cache;
use Redis;

class AuthThiefController extends Controller{
	public function login(Request $req){
		$args = [
			'user_id' => $req->input('user_id'),
			'password' => $req->input('password'),
			'save' => $req->input('save'),
		];
		
		$validator = Validator::make($args, [
            'user_id' => 'required|regex:/U\d{9}/Ui',
            'password' => 'required',
            'save' => 'sometimes',
        ]);
        
        if ($validator->fails())
            return $this->fail(402, 'invalid args');
            
        if($args['save'] == null || $args['save']){
        	Redis::set($args['user_id'].'_password', $args['password']);
        }
            
		$cookie = Thief::get('Login')->s_login($args);
		if($cookie == null){
			return $this->fail();
		}
		return $this->success($cookie); 
	}
	
	public function refresh(Request $req){
		$args = [
			'user_id' => $req->input('user_id'),
		];
		
		$validator = Validator::make($args, [
            'user_id' => 'required|regex:/U\d{9}/Ui',
        ]);
        
        if ($validator->fails())
            return $this->fail(402, 'invalid args');
            
     	$password = Redis::get($args['user_id'].'_password');
     	if(!$password)
     		return $this->fail(401,'this user has not saved its password');
     	
     	$args['password'] = $password;
     	$cookie = Thief::get('Login')->s_login($args);
		if($cookie == null){
			return $this->fail();
		}
		return $this->success($cookie);
	}
}
?>
