<?php


if(! function_exists('request')){

	function request($name, $default = ''){
		return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
	}

}

if(! function_exists('post')){

	function post($name, $default = ''){
		return isset($_POST[$name]) ? $_POST[$name] : $default;
	}
}

if(! function_exists('has_extention')){

	function has_extention($path){
		$path = str_replace('\\', '/', $path);
		$parts = explode('/', $path);
		$filename = array_pop($parts);
		return strpos($filename, '.') !== false;
	}
}

if(! function_exists('trim_extention')){

	function trim_extention($path){
		$parts = explode('.', $path);
		if(count($parts)>1){
			$ext = array_pop($parts);
			$result = str_replace(".$ext", '', $path);
		}else{
			$result = $path;
		}
		return $result;
	}

}


if(! function_exists('get_extention')){

	function get_extention($path){
		$parts = explode('.', $path);
		if(count($parts)>1){
			$result = array_pop($parts);
		}else{
			$result = '';
		}
		return $result;
	}

}

if(! function_exists('object_sort')){

	function object_sort(&$objects, $name, $is_function = true){
		object_sort_class::factory($name, $is_function)->sort($objects);
	}

	class object_sort_class{

		public static function factory($name, $is_function){
			return new self($name, $is_function);
		}


		private $name = '';
		private $is_function = true;

		public function __construct($name, $is_function){
			$this->name = $name;
			$this->is_function = $is_function;
		}

		public function sort(&$objects){
			usort($objects, array($this, 'compare'));
		}

		public function __invoke(&$objects){
			$this->sort($objects);
		}

		protected function compare($left, $right){
			$name = $this->name;
			if($this->is_function){
				$wa = $left->$name();
				$wb = $right->$name();
			}else{
				$wa = $left->$name;
				$wb = $right->$name;

			}
			if ($wa == $wb) {
				return 0;
			}else{
				return ($wa < $wb) ? -1 : 1;
			}
		}
	}

}
