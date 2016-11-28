<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Steal\Thief;

use App\Dianfei;
use App\Libraries\Fetcher;
use Validator;
use Cache;
use Mail;
use DB;

class DianfeiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $req){
    	error_reporting(E_ALL); ini_set('display_errors', '1');
        $args = [
        	'user_id' => $req->input('user_id'),
            'area' => $req->input('area'),
            'build' => $req->input('build'),
            'room' => $req->input('room'),
        ];
        // validate inputs
        $validator = Validator::make($args, $this->_rules());
        
        // try get from cache
        $cache_key = $args['area'].':'.$args['build'].':'.$args['room'];
//        if ($cache = Cache::get($cache_key, false)) {
//            return $this->success(unserialize($cache));
//        }

        if ($validator->fails()) {
        	if($args['user_id'] == null){
        		$args['user_id'] = 'U201414564';
        		$cookie_tail = '_setdor_cookie';        
				$data = Thief::get('Dianfei')->setDormitory($args, $cookie_tail);
        	}else{
        		$cookie_tail = '_dianfei_cookie';
        		$data = Thief::get('Dianfei')->getDaifei($args, $cookie_tail);
        	}         	         
        }else{
        	$cookie_tail = '_setdor_cookie';        
			$data = Thief::get('Dianfei')->setDormitory($args, $cookie_tail);	
        }
        
        // fetch from remote and push into cache		
        if ($data != 401) {
            Cache::put($cache_key, serialize($data), 60);
            return $this->success($data);
        }else{
        	return $this->fail('set dor first plz');
        }

        return $this->fail();
    }


//    /**
//     * Bind dianfei notification
//     *
//     * @param  Request $req
//     * @return Respose
//     */
//    public function bind(Request $req)
//    {
//        $args = [
//            'area' => $req->input('area'),
//            'build' => $req->input('build'),
//            'room' => $req->input('room'),
//            'email' => $req->input('email'),
//            'notify' => $req->input('notify'),
//        ];
//
//        // validate inputs
//        $validator = Validator::make($args, array_merge($this->_rules(), [
//            'email' => 'required|email|max:50',
//            'notify' => 'required|numeric|between:10,500',
//        ]));
//
//        if ($validator->fails()) {
//            return $this->fail(402, 'invalid args');
//        }
//
//        // validate inputs
//        $dianfei = Fetcher::get('Dianfei');
//        if ($data = $dianfei->fetch($args))
//        {
//            $tmp_id = substr(md5(str_random(40).time()), 0, 20);
//            // check if email has been bound
//            if (Dianfei::isBind($args['email'])) {
//                return $this->fail(410, 'email has been bound');
//            }
//
//            // put bind info into cache and send confirm mail
//            Cache::put($tmp_id, serialize(['info' => $args, 'data' => $data]), 30);
//            Mail::send('dianfei.emails.bind', [
//                'id' => $tmp_id,
//                'args' => $dianfei->parseBuildInfo($args),
//            ], function($mail) use ($args) {
//                $mail->from('admin@hustonline.net', 'admin');
//                $mail->to($args['email']);
//                $mail->subject('邮箱电费通知确认');
//            });
//            return $this->success();
//        }
//
//        return $this->fail();
//    }
//
//
//    public function confirmBind (Request $req)
//    {
//        $id = $req->input('id');
//        if ($id AND ($cached = unserialize(Cache::get($id))))
//        {
//            $args = $cached['info'];
//            $data = $cached['data'];
//
//            if (Dianfei::isBind($args['email'])) {
//                return view('fail', [
//                    'title' => '邮箱通知确认',
//                    'header' => '设置失败',
//                    'msg' => '此邮箱已经设置过电费通知了！',
//                ]);
//            } elseif (Dianfei::create($args)) {
//                Cache::forget($id);
//                return view('dianfei.bind_success', [
//                    'title' => '邮箱通知确认',
//                    'header' => '设置成功',
//                    'msg' => '成功设置电费通知，以后我们会在电费不足时发送邮件到您的邮箱 :)',
//                    'data' => $data,
//                ]);
//            } else {
//                return view('fail', ['msg' => '发生了未知的错误 :(']);
//            }
//        }
//
//        return view('fail', ['msg' => '邮件已过期']);
//    }
//
//
//    public function unbind (Request $req)
//    {
//        $email = $req->input('email');
//
//        // validate inputs
//        $validator = Validator::make(['email' => $email], [
//            'email' => 'required|email|max:50',
//        ]);
//
//        if ($validator->fails()) {
//            return $this->fail(402, 'invalid args');
//        }
//
//        // check if the row exists
//        $row = Dianfei::isBind($email);
//
//        // if email has not been bound
//        if ( ! $row) {
//            return $this->fail(410, 'email has not been bound');
//        }
//
//        // push data into cache for later unbind confirmation
//        $tmp_id = substr(md5(str_random(40).time()), 0, 20);
//        Cache::put($tmp_id, serialize($row->toArray()), 30);
//
//        // send email for unbind confirmation
//        Mail::send('dianfei.emails.unbind', [
//            'id' => $tmp_id,
//            'args' => $row
//        ], function($mail) use ($email) {
//            $mail->from('admin@hustonline.net', 'admin');
//            $mail->to($email);
//            $mail->subject('取消电费邮件通知');
//        });
//
//        return $this->success();
//    }
//
//
//    public function confirmUnbind (Request $req)
//    {
//        // get unbind info from cache
//        $id = $req->input('id');
//        if ($id AND ($args = unserialize(Cache::get($id))))
//        {
//            // test if this email is bound
//            if ( ! Dianfei::isBind($args['email'])) {
//                return view('fail', [
//                    'title' => '邮箱通知解除',
//                    'header' => '通知解除失败',
//                    'msg' => '还没有设置此邮箱的邮件通知，如果需要接收电费通知邮件，请使用华中大校园通客户端进行设置',
//                ]);
//            }
//            // cancel email notification
//            elseif (Dianfei::unbind($args['email'])) {
//                Cache::forget($id);
//                return view('success', [
//                    'title' => '邮箱通知解除',
//                    'header' => '通知解除成功',
//                    'msg' => '电费邮件通知已解除，如果需要再次接收通知邮件，请使用华中大校园通客户端重新设置',
//                ]);
//            }
//            // unpredictable error
//            else {
//                return view('fail', ['msg' => '发生了未知的错误']);
//            }
//        }
//
//        // if no info found in cache
//        return view('fail', ['msg' => '邮件已过期']);
//    }
//
//
//    public function updateInfo(Request $req)
//    {
//        $args = [
//            'area' => $req->input('area'),
//            'build' => $req->input('build'),
//            'room' => $req->input('room'),
//            'email' => $req->input('email'),
//            'notify' => $req->input('notify'),
//        ];
//
//        // validate inputs
//        $validator = Validator::make($args, array_merge($this->_rules(), [
//            'email' => 'required|email|max:50',
//            'notify' => 'required|numeric|between:10,500',
//        ]));
//
//        if ($validator->fails())
//            return $this->fail(402, 'invalid args');
//
//        // update row
//        $query = $args;
//        unset($query['notify']);
//        if ($row = Dianfei::where($query)->first()) {
//            if ($row->notify != $args['notify']) {
//                $row->notify = $args['notify'];
//                // reset last check time
//                $row->last_check = $row->last_sent = 0;
//                $row->save();
//            }
//
//            return $this->success();
//        } else {
//            return $this->fail(410, 'email has not been bound');
//        }
//    }
//
//
    /**
     * Return main validating rules
     *
     * @return Array rules
     */
    private function _rules ()
    {
        return [
        	'user_id' => 'required|regex:/U\d{9}/Ui',
            'area' => 'required|in:东区,西区,韵苑,紫菘,留学生楼',
            'build' => 'required|numeric|between:1,28',
            'room' => 'required',
        ];
    }
}
