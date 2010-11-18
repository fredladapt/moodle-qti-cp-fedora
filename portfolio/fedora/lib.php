<?php
require_once($CFG->libdir.'/portfolio/plugin.php');
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/flickrlib.php');
require_once($CFG->libdir.'/fedora/lib.php');
include_once($CFG->libdir.'/debug_util.class.php');
require_once(dirname(__FILE__) . '/api_base.class.php');
require_once dirname(__FILE__) . '/api/fedora_default_portfolio_api.class.php';
require_once dirname(__FILE__) . '/api/SWITCH_portfolio_api.class.php';
require_once dirname(__FILE__) . '/api/UniGe_portfolio_api.class.php';

/**
 * Portfolio plugin to Fedora Common.
 *
 * Access, by default, is NOT granted to students !!! but to teachers.
 * To change that modify permissions in ./db/access.php.
 * Access is controlled by the "supported_formats" function. If access is not granted the function returns an empty array.
 *
 * The current class implements the static portfolio functions.
 * The sending process is preformed by the API class selected during portofolio configuration.
 * Each API corresponds to a specific Fedora implementation with specific requirements - i.e. metadata.
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class portfolio_plugin_fedora extends portfolio_plugin_push_base {

	public static function get_name() {
		return get_string('pluginname', 'portfolio_fedora');
	}

	/**
	 * If your plugin has some admininistrative configuration settings (rather than by the user), override this plugin to return true.
	 * You must also override admin_config_form and get_allowed_config.
	 * You can also override admin_config_validation.
	 */
	public static function has_admin_config(){
		return true;
	}

	/**
	 * Because most of the handling of the getting and setting of admin entered configuration data happens
	 * in the parent class, with no need to be overridden in the subclasses, this method is responsible
	 * for letting the parent class know what fields are allowed.
	 */
	public static function get_allowed_config(){
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
					  'content_access_url',
		);
	}

	/**
	 * Returns an array made of all APIs available in the API directory.
	 * The key is the class name, the value is the API user friendly name.
	 */
	public static function get_api_names(){
		$result = array();
		$directory = dirname(__FILE__) .'/api/';
		$files = scandir($directory);
		foreach($files as $file){
			if($file != '.' && $file != '..'){
				include_once $directory.$file;
				$class = str_replace('.class.php', '', $file);
				$f = array($class, 'get_name');
				if(is_callable($f)){
					$result[$class] = call_user_func($f);
				}
			}
		}
		return $result;
	}

	/**
	 * Returns a configuration object for the Fedora server proxy as defined in the portfolio configuration.
	 */
	public function get_fedora_config(){
		$result = new RestConfig();
		$result->set_base_url($this->get_config('base_url'));
		$result->set_client_certificate_file($this->get_config('client_certificate_file'));
		$result->set_client_certificate_key_file($this->get_config('client_certificate_key_file'));
		$result->set_client_certificate_key_password($this->get_config('client_certificate_key_password'));
		$result->set_check_target_certificate($this->get_config('check_target_certificate'));
		$result->set_target_ca_file($this->get_config('target_ca_file'));
		$result->set_basic_login($this->get_config('basic_login'));
		$result->set_basic_password($this->get_config('basic_password'));
		$result->set_max_results($this->get_config('max_results'));
		return $result;
	}

	/**
	 * Returns a local proxy to teh Fedora server.
	 */
	public function get_fedora(){
		$result = new FedoraProxy($this->get_fedora_config());
		return $result;
	}

	private $_api = false;
	/**
	 * Returns the API selected during portfolio configurion.
	 */
	public function get_api(){
		if($this->_api){
			return $this->_api;
		}
		$class = $this->get_config('api');

		$this->_api = array();
		$directory = dirname(__FILE__) .'/api/';
		$files = scandir($directory);
		$f = "$class.class.php";
		foreach($files as $file){
			if($file == $f){
				include_once $directory.$file;
				return $this->_api = new $class($this);
			}
		}

		include_once $directory . 'fedora_default_repository_api.class.php';
		return $this->_api = new fedora_default_repository_api($this);
	}

	/**
	 * This function can actually be called statically or non statically, depending on whether it's for editing an existing instance of the plugin, or for creating a new one.
	 * It's passed an mform object by reference, as are the other two config form functions, to add elements to it.
	 * If you override this you don't need to handle setting the data of the elements (when editing), that's done in the caller, using get_allowed_config.
	 * You can also override admin_config_validation.
	 */
	public function admin_config_form(&$mform){
		$size = 50;
		$mform->addElement('text', $name = 'base_url', get_string('base_url', 'portfolio_fedora'), array('size' =>$size));
		$mform->addHelpButton($name, $name, 'portfolio_fedora');

		$mform->addElement('text', $name = 'content_access_url', get_string('content_access_url', 'portfolio_fedora'), array('size' =>$size));
		$mform->addHelpButton($name, $name, 'portfolio_fedora');

		$mform->addElement('select', $name = 'api', get_string('api', 'portfolio_fedora'), self::get_api_names());
		$mform->addHelpButton($name, $name, 'portfolio_fedora');

		$mform->addElement('text', $name = 'client_certificate_file', get_string('client_certificate_file', 'portfolio_fedora'), array('size' =>$size));
		$mform->addHelpButton($name, $name, 'portfolio_fedora');

		//$mform->addElement('text', 'client_certificate_key_file', get_string('client_certificate_key_file', 'portfolio_fedora'), array('size' =>$size));
		$mform->addElement('text', $name = 'client_certificate_key_file', get_string('client_certificate_key_file', 'portfolio_fedora'), array('size' =>$size));
		$mform->addHelpButton($name, $name, 'portfolio_fedora');

		$mform->addElement('password', $name = 'client_certificate_key_password', get_string('client_certificate_key_password', 'portfolio_fedora'), array('size' =>$size));
		$mform->addHelpButton($name, $name, 'portfolio_fedora');

		//moodle bug cannot use checkbox for check_target_certificate
		$mform->addElement('text', $name = 'check_target_certificate', get_string('check_target_certificate', 'portfolio_fedora'));
		$mform->addHelpButton($name, $name, 'portfolio_fedora');

		$mform->addElement('text', $name = 'target_ca_file', get_string('target_ca_file', 'portfolio_fedora'), array('size' =>$size));
		$mform->addHelpButton($name, $name, 'portfolio_fedora');

		$mform->addElement('text', $name = 'basic_login', get_string('basic_login', 'portfolio_fedora'), array('size' =>$size));
		$mform->addHelpButton($name, $name, 'portfolio_fedora');
		$mform->setDefault($name, 'fedoraAdmin');

		$mform->addElement('password', $name = 'basic_password', get_string('basic_password', 'portfolio_fedora'), array('size' =>$size));
		$mform->addHelpButton($name, $name, 'portfolio_fedora');

		$mform->addElement('text', $name = 'max_results', get_string('max_results', 'portfolio_fedora'), array('size' =>$size));
		$mform->addHelpButton($name, $name, 'portfolio_fedora');
		$mform->setDefault($name, 250);

	}

	protected function add_help_button($mform, $name, $text){
		static $count = 0;
		$title = get_string('base_url', 'portfolio_fedora');
		$page = 'help_' . $name;
		$show_icon = true;
		$show_title = false;
		$mform->setHelpButton($name, array($page, $title, 'portfolio_fedora', $show_icon, $show_title, $text));
	}

	/**
	 * Returns an array of supported formats if access is granted. Otherwise returns an empty array.
	 */
	public function supported_formats() {
		//check if access is granted to this portfolio.
		global $PAGE;
		if($id = @$PAGE->course->id){
			$context = get_context_instance(CONTEXT_COURSE, $PAGE->course->id);
		}else{
			$context = get_context_instance(CONTEXT_USER);
		}
		if(!has_capability('portfolio/fedora:view', $context)){
			return array();
		}

		//returns supported formats
		return array(
		PORTFOLIO_FORMAT_DOCUMENT,
		PORTFOLIO_FORMAT_PLAINHTML,
		PORTFOLIO_FORMAT_IMAGE,
		PORTFOLIO_FORMAT_TEXT,
		PORTFOLIO_FORMAT_PDF,
		PORTFOLIO_FORMAT_PRESENTATION,
		PORTFOLIO_FORMAT_SPREADSHEET,
		PORTFOLIO_FORMAT_FILE,
		PORTFOLIO_FORMAT_RICHHTML,
		PORTFOLIO_FORMAT_VIDEO
		);
	}

	public function has_export_config(){
		return $this->get_api()->has_export_config();
	}

	public function get_allowed_export_config(){
		return $this->get_api()->get_allowed_export_config();
	}

	/**
	 * if this caller wants any additional config items
	 * they should be defined here.
	 *
	 * @param array $mform moodleform object (passed by reference) to add elements to
	 */
	public function export_config_form(&$mform) {
		$this->get_api()->export_config_form($mform);
	}

	/**
	 * after the user submits their config
	 * they're given a confirm screen
	 * summarising what they've chosen.
	 *
	 * this function should return a table of nice strings => values
	 * of what they've chosen
	 * to be displayed in a table.
	 *
	 * @return array array of config items.
	 */
	public function get_export_summary(){
		return $this->get_api()->get_export_summary();
	}

	public function prepare_package() {
		return $this->get_api()->prepare_package();
	}

	public function get_interactive_continue_url(){
		return $this->get_api()->get_interactive_continue_url();
	}

	public function expected_time($callertime) {
		return $this->get_api()->expected_time($callertime);
	}

	public function send_package() {
		return $this->get_api()->send_package();
	}

	public function cleanup(){
		try{
			//when called from temp file exporter
			$fileid = $this->get('exporter')->get('caller')->get('fileid');
			$fs = get_file_storage();
			$file = $fs->get_file_by_id($fileid);
			if(	$file->get_contextid() == SYSCONTEXTID &&
			$file->get_filearea() =='file_transfer'){
				$file->delete();
			}
		}catch(Exception $e){
			;
		}
	}
}


