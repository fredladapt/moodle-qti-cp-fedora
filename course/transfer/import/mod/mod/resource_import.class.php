<?php

class resource_import extends mod_import{

	public function get_extentions(){
		return array('*');
	}

	public function accept($settings){
		return true;
	}

	public function get_weight(){
		return 1000000;
	}

	protected function process_import(import_settings $settings){
		//file may be removed by another import module
		//if the other module import fail the file not be present anymore
		//in this case we return null and leave the import process follows its path
		if(!file_exists($settings->get_path())){
			return null;
		}

		$cid = $settings->get_course_id();
		$path = $settings->get_path();

		$filename = $settings->get_filename();
		$ext = $settings->get_extention();
		$mimetype = ext_to_mimetype($ext);
		if($ext){
			$filename = trim_extention($filename) . '.' . $ext;
		}

		$data = new StdClass();
		$data->course = $cid;
		$data->name =  trim_extention($filename);
		$data->intro = "<p>$filename</p>";
		$data->introformat = FORMAT_HTML;
		$data->tobemigrated  = 0;
		$data->legacyfiles = 0;
		$data->legacyfileslast = null;
		$data->display = 0;
		$data->filterfiles = 0;
		$cm = $this->insert($settings, 'resource', $data);

		$context = get_context_instance(CONTEXT_MODULE, $cm->id);
		$this->add_file($path, $context, '/', $filename, $mimetype);
		return $data;
	}

}







