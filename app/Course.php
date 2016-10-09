<?php
/*
 * Created on 2016��7��13��
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
namespace App;

use Illuminate\Database\Eloquent\Model;

class Course extends Model{
    protected $dateFormat = 'U';

    protected $guarded = [];
    
    public function users(){
    	return $this->belongsToMany('App\User');
    }
}
?>
