<?php

class zip_import extends mod_import{

	public function get_weight(){
		return 100;
	}

	protected function process_import(import_settings $settings){
		$path = $settings->get_path();
		$filename = $settings->get_filename();
		$filename = trim_extention($filename);
		
		$dir = $this->extract($path, true);
		
		$folder_settings = $settings->copy($dir, $filename);
		$this->get_root()->import($folder_settings);
		fulldelete($dir);
		return true;
	}


}