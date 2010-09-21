<?php

class object_import extends mod_import{

	public function get_weight(){
		return 1000;
	}
	
	public function get_extentions(){
		return array();
	}
	
	//!!!!!!!!!!!!!!!!!!!!!!!!!!! disabled
	public function accept($settings){
		return false;
		
		$path = $settings->get_path();
		$filename = $settings->get_filename();
		$result = is_dir($path);
		return $result;
	}

	protected function process_import(import_settings $settings){
		$result = array();
		$cid = $settings->get_course_id();
		$path = $settings->get_path();
		$filename = $settings->get_filename();
		$root = $this->get_root();
	
		$dir = $path;
		$files = scandir($path);
		$files = array_diff($files, array('.', '..'));
		foreach($files as $file){
			$file_path = $dir . '/' . $file;
			$file_settings = $settings->copy($file_path);
			if($file_result = $root->import($file_settings)){
				$result[$file_path] = $file_result;	
			}
		}
		return $result;
	}

}







