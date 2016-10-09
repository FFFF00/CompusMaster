<?php
/*
 * Created on 2016Äê7ÔÂ13ÈÕ
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Requests;
use App\Course;
use App\User;

class CourseController extends Controller{
	/**
	 * upload the table to database
	 * 
	 * @return Reponse 
	 */
	public function upload(Request $req){
		
		$json = $req->input('json');
		$array = json_decode($json,true);
		$user_id = $array['user_id'];
		$user = User::find($user_id);
		//validate "is this user exit?"
		if($user == null){
			return $this->fail(404,'user not found');
		}
		
		$table = $array['table'];

		foreach($table as $args){
			//var_dump($args);
			//if this course is not exit, add it to database
			$course = Course::firstOrCreate($args);
			//
			if($user->courses->where('id',$course->id)->first() == null){
				$user->courses()->attach($course->id);
			}				
		}
		return $this->success();
	}
	/**
	 * return tables to front ends
	 * 
	 * @return Response
	 */
	public function queryAll(Request $req){
		$user_id = $req->input('user_id');
		$user = User::find($user_id);
				
		if($user == null){
			return $this->fail(404,'user not found');;
		}else{
			$json['user_id'] = $user->id;
			$i = 0;
			//var_dump((array)$user->courses->first()->getAttributes());
			foreach($user->courses as $course){
				$json['table'][$i] = ((array)($course->getAttributes()));
				$i ++;
			}
			return $this->success($json);
		}
	}
	
	public function queryByInfo(Request $req){
		$coursename = $req->input('coursename');
		$teacher = $req->input('teacher');
		$academy = $req->input('academy');
		
		$classroom = $req->input('classroom');
//		$buliding = null;
//		$classroom = null; 
//		if($buliding_classroom != null){
//			$b_c = explode("-",$buliding_classroom);
//			$buliding = $b_c[0];
//			$classroom = $b_c[1];
//		}
		$arg = array_filter(['coursename'=>$coursename, 'teacher'=>$teacher, 'academy'=>$academy, 'classroom'=>$classroom]);
		$courses = Course::where($arg)->get();
		$json = [];
		$i = 0;
		foreach($courses as $course){
				$json[$i] = ((array)($course->getAttributes()));
				$i ++;
			}
		return $this->success($json);
	}
	
	public function rubCourse(Request $req){
		$user_id = $req->input('user_id');
		$user = User::find($user_id);
		$operate = $req->input('operate');
		//$course = Course::find($req->input('course_id'));
		if($user == null){
			return $this->fail(404,'user not found');;
		}else if($operate == 0){
			$user->courses()->attach($req->input('course_id'));	
		}else if($operate == 1){
			$user->courses()->detach($req->input('course_id'));
		}
		return $this->success();
	}
	
	public function getAcademy(Request $req){
		$courses = Course::all();
		$academys = [];
		$i = 0;
		foreach($courses as $course){
			$academys[$i] = $course->academy;
			$i ++; 
		}
		$academys = array_filter(array_flip(array_flip($academys)));
		$i = 0;
		$json = [];
		foreach($academys as $academy){
			$json[$i] = $academy;
			$i ++; 
		}
		return $this->success($json);
	}
//	protected function _courseAdd($args){
//		$course = new Course();
//		$course->save();
//	}
}

?>
