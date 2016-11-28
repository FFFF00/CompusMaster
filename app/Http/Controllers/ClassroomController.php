<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Steal\Thief;

use Validator;
use Fetcher;
use Cache;
use Bcmath;
use Redis;

use App\Classroom;
use App\RoomCourse;

class ClassroomController extends Controller
{
	//已经弃用
	public function login(Request $req){
		$args = [
			'user_id' => $req->input('user_id'),
			'password' => $req->input('password'),
		];		
		$cookie = Thief::get('Login')->s_login($args);
		if($cookie == null){
			return $this->fail();
		}
		return $this->success($cookie); 
	}
	
	private function _loginByArgs($args){
								
		$cookie = Thief::get('Login')->s_login($args);
		if($cookie == null){
			return $this->fail();
		}
		return $this->success($cookie); 
	}
	
	public function getFreeroom(Request $req){
		$args = [
			'user_id' => $req->input('user_id'),
            'buildingCode' => $req->input('buildingCode'),
            'borrowDate' => $req->input('borrowDate'),
            'section' => $req->input('section'),
        ];
        // validate input data
        $validator = Validator::make($args, [
            'user_id' => 'required|regex:/U\d*/Ui',
            'buildingCode' => 'required|regex:/\W\w{4},[\xe0-\xef][\x80-\xbf]*\w?\W/i',
            'borrowDate' => 'required|regex:/\W\d{4}-\d{2}-\d{2}\W/Ui',
            'section' => 'sometimes|regex:/\W\d*-\d*[\xe0-\xef][\x80-\xbf]{1}\W/Ui',
        ]);
        
        if ($validator->fails()){
        	$date = date("Y-m-d");	        			
        	if(!Cache::get($date.'_storageFreeroom'))
        		$this->_storageFreeroom();
        				
        	$json = $this->_getFreeroomFromCache($args);        	
        }else{
        	$json = $this->_getFreeroomFromWXY($args);            	
        }
        return $this->success($json);         
	}
	
	private function _getFreeroomFromWXY($args){		
            
        if(!Redis::get($args['user_id'].'_cookie'))
        	return $this->fail(401, 'this user has not logged in');
        	
        $buildingCodeArr = array("'C050,西五楼'","'C120,西十二楼S'","'C121,西十二楼N'",
 								 "'D050,东五楼'","'D091,东九楼A'","'D092,东九楼B'",
  								 "'D093,东九楼C'","'D094,东九楼D'","'D120,东十二楼'");
        $SP_buildingCode = array("'D090,东九楼'","'C122,西十二楼'","'0000,所有教学楼'");
		//if buildingCode is a special one 
		if(in_array($args['buildingCode'], $SP_buildingCode)){
			if($args['buildingCode'] === "'C122,西十二楼'"){
				$begin = 1; $end = 2;
			}
			if($args['buildingCode'] === "'D090,东九楼'"){
				$begin = 4; $end = 7;
			}
			if($args['buildingCode'] === "'0000,所有教学楼'"){
				$begin = 0; $end = 8;
			}
			//get buildingCode from array, visit recourse a few times 	
			$json = array();
			for($i = $begin; $i <= $end; $i ++){
				$args['buildingCode'] = $buildingCodeArr[$i];
				$j = $this->_autoSection($args);
				
				array_push($json, $j);
			}	
		}else{
			$json = $this->_autoSection($args);
		}		
		$json = ['json' => $json];
		//return $this->success($json);
		return $json;
	}
	
	private function _pinyingAnalysis($json){
		$arr = array();
		$data = json_decode($json,1);
		$arr['section'] = $data['section'];
//		$arr['borrowDate'] = $data['borrowDate'];
		$arr['buildingCode'] = $data['buildingCode'];
		$arr['buildingList'] = array();
		foreach($data['dataList'] as $d){
			array_push($arr['buildingList'], $d['JSMC']);
		}
		return $arr;
	}
	
	private function _autoSection($args){
		if($args['section'] == null){
			$json = array();
			for($i = 1; $i < 12; $i += 2){
				$args['section'] = "'".$i.'-'.($i + 1).'节'."'";
				$j = $this->_getResultJson($args);    
				
				$j = $this->_pinyingAnalysis($j);
				array_push($json, $j);
			}
		}else{
			$json = $this->_getResultJson($args);
			$json = $this->_pinyingAnalysis($json);
		}
		return $json;
	}
	
	private function _getResultJson($args){
		$json = '';
		//get content from HUST weixiaoyuan
		$content = Thief::get('Freeroom')->getFreeroom($args);		
		//
		preg_match('/var json=(.*);/iU', $content, $data);
		$json = substr($data[1], 1, -1);
		
		if($json == '' || $json == null){
			$json = 'no data';
		}
		return $json;
	}	
	
	private function _storageFreeroom(){
		$data = date("Y-m-d");
		
		$login_args = [
			'user_id' => 'U201414564',
			'password' => '110017',
		];
				
		$freeroom_args = [
			'user_id' => 'U201414564',
            'buildingCode' => "'0000,所有教学楼'",
            'borrowDate' => "'".$data."'",
            'section' => null,
        ];
        
        $this->_loginByArgs($login_args);
        $json = $this->_getFreeroomFromWXY($freeroom_args);  
        $json = $json['json'];
        foreach($json as $js){
        	foreach($js as $j){
        		Cache::put($data.$j['buildingCode'].$j['section'].'_storageFreeroom', $j, 24 * 60);	
        	}        		
        }              
        Cache::put($data.'_storageFreeroom', $json, 24 * 60);
        return ;
	}
	
	private function _getFreeroomFromCache($args = null){
		$date = date("Y-m-d");
		//$data = json_decode($data ,1);
		if($args == null){
			$data = Cache::get($date.'_storageFreeroom');
			return $data;	
		}
		$args['buildingCode'] = substr($args['buildingCode'], 1, 4);

		$buildingCodeArr = array("C050","C120","C121",
 								 "D050","D091","D092",
  								 "D093","D094","D120");
        $SP_buildingCode = array("D090","C122","0000");
		//if buildingCode is a special one 
		if(in_array($args['buildingCode'], $SP_buildingCode)){
			if($args['buildingCode'] === "C122"){
				$begin = 1; $end = 2;
			}
			if($args['buildingCode'] === "D090"){
				$begin = 4; $end = 7;
			}
			if($args['buildingCode'] === "0000"){
				$begin = 0; $end = 8;
			}
			//get buildingCode from array, visit recourse a few times 	
			$json = array();
			for($i = $begin; $i <= $end; $i ++){
				$args['buildingCode'] = $buildingCodeArr[$i];
				$j = $this->_getFreeroomFromCacheAutoSection($args, $date);				
				array_push($json, $j);
			}
		}else{
			$json = $this->_getFreeroomFromCacheAutoSection($args, $date);
		}
		$json = ['json' => $json];
		return $json;
	}
	
	private function _getFreeroomFromCacheAutoSection($args, $date){
		$json = array();
		
		if($args['section'] == null){
			for($i = 1; $i < 12; $i += 2){
				$args['section'] = $i.'-'.($i + 1).'节';
				$j = Cache::get($date.$args['buildingCode'].$args['section'].'_storageFreeroom'); 					
				array_push($json, $j);
			}
			return $json;
		}
		$args['section'] = trim($args['section'], "'");
		$j = Cache::get($date.$args['buildingCode'].$args['section'].'_storageFreeroom'); 					
		array_push($json, $j);		
		return $json;
	}
	
	
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $req)
    {
        // package query args for the next actions
        $args = [
            'date' => $req->input('date'),
            'build' => $req->input('build'),
            'period' => $req->input('period'),
            'room' => $req->input('room'),
        ];

        // validate input data
        $validator = Validator::make($args, [
            'date' => 'required|date',
            'build' => 'required|in:D9,D12,D5,X5,X12',
            'period' => 'required_without:room|in:AM,PM,NIG,WHOLE,ALL',
            'room' => 'required_without:period|min:3|regex:/[a-z0-9;]+/i',
        ]);

        if ($validator->fails())
            return $this->fail(402, 'invalid args');

        $data = [];
        // get courses in the day of the specific classroom
        if ($args['room']) {
            $data = $this->_roomCourse($args);
        }
        // get all available zixi classrooms in the specific day
        elseif ($args['period']) {
            // fetch all
            if ($args['period'] === 'ALL') {
                foreach (['AM', 'PM', 'NIG', 'WHOLE'] as $k) {
                    $args['period'] = $k;
                    $data = array_merge($data, $this->_periodRooms($args));
                }
            }
            // fetch classrooms in the specific time period
            else {
                $data = $this->_periodRooms($args);
            }
        }

        return $data ? $this->success($data) : $this->fail();
    }

    private function _roomCourse($args)
    {
        $courses = [];
        $args['rooms'] = explode(';', trim($args['room'], ';'));
        $roomcourse = new RoomCourse;

        // try to get valid recent data from database
        foreach ($args['rooms'] as $k => $room) {
            $saved = $roomcourse->get(array_merge($args, ['room' => $room]));

            // if the recent saved data found, append to the
            // result and delete from room list to be fetched
            if ($saved AND $saved['updated_at'] > strtotime('-1 day')) {
                $courses[$room] = $saved['data'];
                unset($args['rooms'][$k]);
            }
        }

        // try fetch classroom course data from remote
        if ($args['rooms'] AND
            $fetched_rooms = Fetcher::get('Classroom')->roomcourse($args)
        ) {
            // save the fetched data to database
            foreach ($fetched_rooms as $room => $data) {
                $roomcourse->store(array_merge($args, ['room' => $room]), $data);
            }

            $courses = $courses + $fetched_rooms;
        }

        return $courses;
    }


    private function _periodRooms($args)
    {
        $rooms = null;
        $classroom = new Classroom;

        // get classrooms from database or fetch from remote
        $row = $classroom->get($args);
        if ($row AND ($row['updated_at'] > strtotime('-1 day'))) {
            $rooms = [$args['period'] => $row['data']];
        } elseif ($data = Fetcher::get('Classroom')->classroom($args)) {
            $classroom->store($args, $data);
            $rooms = [$args['period'] => $data];
        }

        return $rooms;
    }
}
