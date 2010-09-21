<?php

global $CFG;
require_once $CFG->dirroot . '/mod/imscp/locallib.php';

class imscp_import extends mod_import{

	public function get_weight(){
		return 100;
	}

	public function get_extentions(){
		return array();
	}

	public function get_file_itemid(){
		return 1;
	}
	
	public function accept(import_settings $settings){
		$manifest = $settings->get_manifest_reader();
		$name = $manifest->get_root()->name();
		$location = $manifest->get_root()->get_attribute('xsi:schemaLocation');
		return $name == 'manifest' && strpos($location, 'http://www.imsglobal.org') !== false;
		
		/*
				$root = $doc->documentElement;
				if($root->nodeName == 'manifest'){
					$a = $root->getAttribute('xsi:schemaLocation');
					if(strpos($a, 'http://www.adlnet.org') !== false){
						return new scorm_import($this->get_log(), $this);
					}else if(strpos($a, 'http://www.imsglobal.org') !== false){
						return new imscp_import($this->get_log(), $this);
					}
				}
		$path = $settings->get_path();
		
		return is_dir($path);*/
	}

	protected function process_import($settings){
		$cid = $settings->get_course_id();
		$path = $settings->get_path();
		$filename = $settings->get_filename();
		
		global $DB;
		$data = new StdClass();
		$data->course = $cid;
		$data->name =  empty($filename) ? basename($path) : trim_extention($filename);
		$data->intro = '<p>'.$data->name.'</p>';
		$data->introformat = FORMAT_HTML;
		$data->revision = 1;
		$data->keepold = 1;
		$data->structure = null;
		
		$cm = $this->insert($settings, 'imscp', $data);
		
		$context = get_context_instance(CONTEXT_MODULE, $cm->id);
		$this->add_directory_content($path, $context);

		$data = $DB->get_record('imscp', array('id'=>$data->id), '*', MUST_EXIST);
		$structure = imscp_parse_structure($data, $context);
		
		$data->structure = is_array($structure) ? serialize($structure) : null;
		$DB->update_record('imscp', $data);
		return $data;
	}

}







