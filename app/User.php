<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

class User extends Model{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
//    protected $fillable = [
//        'name', 'email', 'password',
//    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
//    protected $hidden = [
//        'password', 'remember_token',
//    ];

	protected $dateFormat = 'U';

    protected $guarded = [];
    
    public function courses(){
    	return $this->belongsToMany('App\Course');
    }
    
//    public function roles(){
//    	return $this->belongsToMany('App\Role');
//    }
}
