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

class ScoreController extends Controller{ 
	 public function getScore(Request $req){
		$args = [
			'user_id' => $req->input('user_id'),
			'model' => $req->input('model'),
			'term' => $req->input('term'),
		];
		
		//validate
		$validator = Validator::make($args, [
            'user_id' => 'required|regex:/U\d{9}/Ui',
            'model' => 'sometimes|in:detail,outline,english',        
            'term' => 'required_if:model,detail|integer',
        ]);
        
        if ($validator->fails())
            return $this->fail(402, 'invalid args');
        
		if($args['model'] == null)
			$args['model'] = 'outline';
			
		if($args['model'] == 'detail'){
			$json = Thief::get('Score')->getDetail($args);
		}elseif($args['model'] == 'outline'){
			$json = Thief::get('Score')->getOutline($args);
		}elseif($args['model'] == 'english'){
			$json = Thief::get('Score')->getEnglishScore($args);
		}else{
			return $this->fail(500);	
		} 
		$json = ['result' => $json];			
		return $this->success($json);
	 }
}
?>
