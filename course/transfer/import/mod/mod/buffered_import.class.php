<?php

class buffered_import extends mod_import{

	private $cache = array();
	private $current_key = '';

	public function get_extentions(){
		return array('*');
	}

	public function accept($settings){
		return $this->current_key != $this->get_key($settings);
	}

	public function get_weight(){
		return -1000000;
	}

	protected function process_import($settings){
		$key = $this->get_key($settings);
		if(isset($this->cache[$key])){
			return $this->cache[$key];
		}

		$result = false;
		try{
			$this->current_key = $key;
			$result = $this->get_root()->import($settings);
			$this->cache[$key] = $result;
			$this->current_key = '';
		}catch(Exception $e){
			$this->current_key = '';
			throw $e;
		}
		return $result;
	}

	protected function get_key($settings){
		$result = $settings->get_path();
		$result = str_replace("\\", '/', $result);
		$result = str_replace('//', '/', $result);
		$result = str_replace('/./', '/', $result);
		$result = str_replace('/', DIRECTORY_SEPARATOR, $result);
		return $result;
	}

}







