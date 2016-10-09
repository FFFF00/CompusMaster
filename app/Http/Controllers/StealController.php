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
use Fetcher;
use Cache;
use Redis;

class StealController extends Controller{
	public function getAccount(Request $req){
		$args = [
			'user_id' => $req->input('user_id'),
			//'password' => $req->input('password'),
		];
		
		return Thief::get('Schoolcard')->getAccount($args);
	}
	
	
}
?>
