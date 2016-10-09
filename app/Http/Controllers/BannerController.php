<?php
/*
 * Created on 2016年7月18日
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Requests;
use App\Banner;

class BannerController extends Controller{
	/**
	 * 
	 * 
	 * @return Response
	 */
	public function upload(Request $req){
		$data['name'] = $req->input('name');
		$data['linked_url'] = $req->input('linked_url');
		$data['img_url'] = $req->input('img_url');
		//if banner_img exit
		if($req->hasFile('banner_img')){
			$file = $req->file('banner_img');
			//get the postfix of file
			$filename = $file->getClientOriginalName();
			$filename_arr = explode(".",$filename);
			$postfix = '.'.end($filename_arr);
			//baseurl for visiting sources, basepath for storing sources
			$basepath = str_replace( '\\' , '/' , realpath(dirname(__FILE__).'/../../..'));
			$baseurl = $req->url().'/../..';
		
			if($file->isValid()){
				$img_path = $basepath.'/public/uploads/banner_img/'.$data['name'];
				if(is_dir($img_path)){
					return 'name exit';
				} 
				mkdir($img_path);
				$file->move($img_path, md5($data['name']).$postfix);
				$data['img_url'] = $baseurl.'/uploads/banner_img/'.$data['name'].'/'.md5($data['name']).$postfix;
			}
		}
		$this->create($data);
		return $this->success();
	}
	
	protected function create(array $data)
    {
        return Banner::firstOrCreate([
        	'name' => $data['name'],
            'linked_url' => $data['linked_url'],
            'img_url' => $data['img_url'],
        ]);
    }
    
    public function deleteById(Request $req){
    	$id = $req->input('id');
    	$banner = Banner::find($id);
    	$name = $banner->name;
    	
    	$basepath = str_replace( '\\' , '/' , realpath(dirname(__FILE__).'/../../..'));
    	$img_path = $basepath.'/public/uploads/banner_img/'.$name;
    	
    	$this->deldir($img_path);
    	$banner->delete();
    	return $this->succcess();
    }
    
    protected function deldir($dir) {
  	//先删除目录下的文件：
  		$dh = opendir($dir);
 		while ($file = readdir($dh)) {
 		   	if($file != "." && $file != "..") {
   				$fullpath = $dir."/".$file;
      			if(!is_dir($fullpath)) {
       				unlink($fullpath);
      			}else{
    	  			deldir($fullpath);
	  	  		}
    		}
  		}
  		closedir($dh);
	  //删除当前文件夹：
  		if(rmdir($dir)) {
    		return true;
  		}else{
    		return false;
  		}
	}
    
	public function getAllBanners(){
		$json = [];
		$banners = Banner::All();
		
		$i = 0;
		foreach($banners as $banner){
			$array = $banner->getAttributes();
			$json[$i] = $array;
			$i ++	;
		}
		return $this->success($json);
	}
}
?>
