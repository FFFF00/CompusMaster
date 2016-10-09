<?php
/*
 * Created on 2016��7��18��
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model{
    protected $dateFormat = 'U';

    protected $guarded = [];
    
    public function users(){
    	return $this->belongsToMany('App\User');
    }
}
?>
