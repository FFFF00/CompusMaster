<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;

use Illuminate\Redis\Database;
use Illuminate\Support\Facades\Redis;

use App\User;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
   // protected $redirectTo = '/';

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware($this->guestMiddleware(), ['except' => 'logout']);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
        	'user_id' => 'required|regex:/^[U,u][0-9]{9}$/',
            'username' => 'required|max:255',
            'img_url' => 'required|max:255|',
            'password' => 'required|min:6',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        return User::firstOrCreate([
        	'id' => $data['user_id'],
            'username' => $data['username'],
            'img_url' => $data['img_url'],
            'password' => bcrypt($data['password']),
        ]);
    }
    /**
     * 
     * 
     * @return userinfo
     */
    public function login(Request $req){
    	//if user don't exit
        $user = User::find($req->user_id);
    	if($user == null){
    		return $this->fail(500,"this user doesn't exit");
    	}
    	//var_dump(password_verify($req->password, $user->password));
    	if(password_verify($req->password, $user->password)){
    		// create UUID as token
    		$uniqid = uniqid();
    		// put token into redis
    		Redis::set($uniqid,$req->user_id);
    		$array = $user->find($req->user_id)->getAttributes();
    		$array['token'] = $uniqid; 
    		unset($array['password']);
    		return $this->success($array);
    		//return $uniqid;	
    	}
    	return $this->fail(500,'wrong password');
    }
    public function logout(Request $req){
    	$token = $req->token;
    	Redis::delete($token);
    	return $this->success();
    }
    /**
     * register after new users have logined hub system
     * 
     *@return Response 
     */
    public function register(Request $req){
    	if($this->validator($req->all())->fails())   
            return $this->fail(402, 'invalid args');
    	if(User::find($req->user_id) != null)
    		return $this->fail(500, 'user have already registered');
    	$this->create($req->all()); 
    	return $this->success();  	
    }
}




