<?php

require_once($CFG->libdir.'/fedora/lib.php');
require_once($CFG->libdir.'/debug_util.class.php');
require_once($CFG->libdir.'/mime/mime_type.php');

/**
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class repository_fedora extends repository {

	public static function get_api_names(){
		$result = array();
		$directory = dirname(__FILE__) .'/api/';
		$files = scandir($directory);
		foreach($files as $file){
			if(strpos($file, $trailer = '.class.php') !== false){
				include_once $directory.$file;
				$class = str_replace($trailer, '', $file);
				$f = array($class, 'get_name');
				if(is_callable($f)){
					$result[$class] = call_user_func($f);
				}
			}
		}
		return $result;
	}

	const NAME = 'repository_fedora';

	/**
	 *
	 * @param int $repositoryid
	 * @param object $context
	 * @param array $options
	 */
	public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()){
		parent::__construct($repositoryid, $context, $options);
	}

	public static function get_instance_option_names() {
		return array( 'base_url',
					  'api',
        			  'client_certificate_file',
        			  'client_certificate_key_file',
        			  'client_certificate_key_password',
        			  'check_target_certificate',
        			  'target_ca_file',
        			  'basic_login',
        			  'basic_password',
        			  'max_results',
		);
	}

	public function instance_config_form($mform){
		$size = 50;
		$mform->addElement('text', $name = 'base_url', get_string('base_url', 'repository_fedora'), array('size' =>$size));
		$mform->addHelpButton($name, $name, 'repository_fedora');

		$mform->addElement('select', $name = 'api', get_string('api', 'repository_fedora'), self::get_api_names());
		$mform->addHelpButton($name, $name, 'repository_fedora');

		$mform->addElement('text', $name = 'client_certificate_file', get_string('client_certificate_file', 'repository_fedora'), array('size' =>$size));
		$mform->addHelpButton($name, $name, 'repository_fedora');

		//$mform->addElement('text', 'client_certificate_key_file', get_string('client_certificate_key_file', 'repository_fedora'), array('size' =>$size));
		$mform->addElement('text', $name = 'client_certificate_key_file', get_string('client_certificate_key_file', 'repository_fedora'), array('size' =>$size));
		$mform->addHelpButton($name, $name, 'repository_fedora');

		$mform->addElement('password', $name = 'client_certificate_key_password', get_string('client_certificate_key_password', 'repository_fedora'), array('size' =>$size));
		$mform->addHelpButton($name, $name, 'repository_fedora');

		$mform->addElement('text', $name = 'check_target_certificate', get_string('check_target_certificate', 'repository_fedora'));
		$mform->addHelpButton($name, $name, 'repository_fedora');

		//moodle bug cannot use checkbox for check_target_certificate
		$mform->addElement('text', $name = 'target_ca_file', get_string('target_ca_file', 'repository_fedora'), array('size' =>$size));
		$mform->addHelpButton($name, $name, 'repository_fedora');

		$mform->addElement('text', $name = 'basic_login', get_string('basic_login', 'repository_fedora'), array('size' =>$size));
		$mform->addHelpButton($name, $name, 'repository_fedora');
		$mform->setDefault($name, 'fedoraAdmin');

		$mform->addElement('password', $name = 'basic_password', get_string('basic_password', 'repository_fedora'), array('size' =>$size));
		$mform->addHelpButton($name, $name, 'repository_fedora');

		$mform->addElement('text', $name = 'max_results', get_string('max_results', 'repository_fedora'), array('size' =>$size));
		$mform->addHelpButton($name, $name, 'repository_fedora');
		$mform->setDefault($name, 250);

	}

	public function get_fedora_config(){
		$keys = self::get_instance_option_names();
		$result = new RestConfig();
		foreach($keys as $key){
			$f = "set_$key";
			if(is_callable(array($result, $f))){
				$result->$f($this->get_option($key));
			}
		}
		return $result;
	}

	public function get_fedora(){
		$result = new FedoraProxy($this->get_fedora_config());
		return $result;
	}

	public function print_search() {
		$api = $this->get_api();
		$result =  $api->print_search($this);
		return $result;
	}

	public function get_api(){
		$class = $this->get_option('api');

		$result = array();
		$directory = dirname(__FILE__) .'/api/';
		$files = scandir($directory);
		$f = "$class.class.php";
		foreach($files as $file){
			if($file == $f){
				include_once $directory.$file;
				return new $class();
			}
		}

		include_once $directory . 'fedora_default_repository_api.class.php';
		return new fedora_default_repository_api();
	}

	/**
	 * Search files in repository
	 * When doing global search, $search_text will be used as
	 * keyword.
	 *
	 * @return mixed, see get_listing()
	 */
	public function search($search_text) {
		$api = $this->get_api();
		return $api->search($this->get_fedora(), $search_text);
	}

	public function global_search(){
		return true;
	}

	public function get_link($url) {
		return $url;
	}
/*
	protected function get_object_datastream($pid){
		$fedora = $this->get_fedora();
		$ds = $fedora->get_object($pid);

		echo $pid . "\n".'  ';
		debug($ds) . '  ';
		die;

		if(!empty($ds)){
			return $ds;
		}

		$list = $fedora->list_datastreams($pid);
		foreach($list as $item){
			$dsid = strtolower($item['dsid']);
			if(	$dsid != strtolower(FedoraProxy::DUBLIN_CORE_DS_NAME) &&
			$dsid != strtolower(FedoraProxy::LOM_DS_NAME)){
				return $item;
			}
		}
		return false;
	}*/

	public function get_file($source, $filename) {
		if($start = strpos($source,'objects/')){
			$source = substr($source, $start, strlen($source)-$start);
			$source = str_replace('objects/', '', $source);
			$source = str_replace('/content', '', $source);
			$source = str_replace('/datastreams/', '?', $source);
			$id = $source;
		}else{
			$id = $source;
		}

		$id = explode('?', $id);
		$pid = reset($id);
		$dsID = count($id)>1 ? $id[1] : '';

		$fedora = $this->get_fedora();
		$content = $fedora->get_datastream_content($pid, $dsID);
		$datastream = $fedora->get_datastream($pid, $dsID);
		if(isset($datastream['datastreamProfile'])){
			$datastream = reset($datastream);
		}
		$ext = isset($datastream['mimetype']) ? $datastream['mimetype'] : '';
		if(empty($ext)){
			$ext = isset($datastream['dsmime']) ? $datastream['dsmime'] : '';
		}
		$ext = mimetype_to_ext($ext);
		$filename .= empty($ext) ? '' : ".$ext";

		$path = $this->prepare_file($filename);
		if($create_file = fopen($path, 'w')){
			fwrite($create_file, $content);
			fclose($create_file);
			chmod($path, 0777);
		}
		return array('path'=>$path, 'url'=>'');
	}

	/**
	 * Print a upload form
	 *
	 * @return array
	 */
	public function print_login() {
		return $this->get_listing();
	}

	public function get_listing($path = '', $page = '') {
		$api = $this->get_api();
		$result = $api->get_listing($this, $this->get_fedora(), $path, $page);
		return $result;
	}

	/**
	 * Define the name of this repository
	 * @return string
	 */
	public function get_name(){
		$result = parent::get_name();
		$result = empty($result) ? get_string('repositoryname', self::NAME) : $result;
		return $result;
	}

}












