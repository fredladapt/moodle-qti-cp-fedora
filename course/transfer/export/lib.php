<?php

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir.'/mime/mime_type.php');
require_once($CFG->libdir.'/portfolio/caller.php');
require_once($CFG->libdir.'/debug_util.class.php');
require_once($CFG->dirroot.'/mod/resource/locallib.php');
require_once($CFG->dirroot.'/portfolio/fedora/lib.php');
require_once($CFG->libdir. '/ims/main.php');
require_once dirname(__FILE__) . '/../util/util.php';
require_once dirname(__FILE__) . '/mod/lib.php';
require_once dirname(__FILE__) . '/ui/course_module_selection_form.class.php';
require_once dirname(__FILE__) . '/ui/export_page.class.php';

function create_transfer_file_from_pathname($name, $path, $delete_path=true){
	global $USER;
	$fs = get_file_storage();
	$file_record = array(
            'contextid'	=> SYSCONTEXTID,
            'component' => 'file_transfer',
            'filearea'  => 'file_transfer',
			'itemid'    => 0,
	  		'filepath'  => '/'. dirname($path) .'/',
            'filename'  => $name,
			'userid'    => $USER->id
	);

	$result = $fs->create_file_from_pathname($file_record, $path);
	if($delete_path){
		fulldelete($path);
	}
	return $result;
}

function get_course_modules($course_id) {
	global $DB;

	$sql = "SELECT cm.*, md.name
    		FROM {course_modules} cm
                   JOIN {modules} md ON md.id = cm.module
            WHERE cm.course=$course_id;";

	$result = $DB->get_records_sql($sql);
	return $result;
}

/**
 *
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class course_export {

	//@todo:handle whitespaces in filenames?

	/**
	 * @return course_export
	 */
	public static function factory(){
		static $result = null;
		if(!empty($result)){
			return $result;
		}else{
			return $result = new self();
		}
	}

	private $mod_export;

	public function __construct(){
		$this->mod_export = mod_export::factory();
	}

	function accept($module){
		return $this->mod_export->accept($module);
	}

	function export($cid, $modules){
		$temp = $this->get_temp_directory();
		$settings = new export_settings($cid, $temp);
		//$details = $this->get_course_modules($cid);
		foreach($modules as $mid){
			$this->mod_export->export($settings->copy($mid));
		}

		$files = scandir($temp);
		$files = array_diff($files, array('.', '..'));
		if(count($files) == 0){
			$path = false;
		}else if(count($files) == 1 && !is_dir($temp.reset($files))){
			$file = reset($files);
			$current_path = $temp . $file;

			global $USER;
			$file_name = 'f'. md5($USER->id . time());
			$ext = pathinfo($current_path, PATHINFO_EXTENSION);
			$ext = empty($ext) ? '': ".$ext";
			$path = dirname($temp) . '/' . $file_name . $ext;

			rename($current_path, $path);
			fulldelete(dirname($current_path));
		}else{
			$settings->get_manifest()->save($temp.'/imsmanifest.xml');
			$path = $this->get_temp_path() .'.zip';
			$this->archive_directory($temp, $path, true);
		}
		return $path;
	}

	protected function get_temp_directory(){
		global $CFG, $USER;
		$key = 'f'.md5($USER->id . time());
		$result = "{$CFG->dataroot}/temp/upload/{$key}/";
		if (!file_exists($result)){
			mkdir($result, $CFG->directorypermissions, true);
		}
		return $result;
	}

	protected function get_temp_path(){global $CFG, $USER;
	$key = md5($USER->id . time());
	$directory = "{$CFG->dataroot}/temp/upload/";
	$result = "$directory{$key}";
	if (!file_exists($directory)){
		mkdir($directory, $CFG->directorypermissions, true);
	}
	return $result;

	}

	protected function archive_directory($directory_path, $file_path, $delete_directory=false){
		$files = array();
		$directory_path = rtrim($directory_path, '/');
		$entries = scandir($directory_path);
		foreach($entries as $entry){
			if($entry !='.' && $entry !='..'){
				$path = $directory_path . '/'. $entry;
				$files[$entry] = $path;
			}
		}
		$zipper = new zip_packer();
		$result = $zipper->archive_to_pathname($files, $file_path);
		if($delete_directory){
			fulldelete($directory_path);
		}
		return $result;
	}

}

/**
 *
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class export_settings{

	private $course_id = 0;
	private $path = '';
	private $course_module_id = 0;
	private $manifest = null;
	private $manifest_resources = null;
	private $manifest_organization = null;

	public function __construct($course_id, $path, $course_module_id=0){
		$this->course_id = $course_id;
		$this->path = $path;
		$this->course_module_id = $course_module_id;
		$this->manifest = new ImscpManifestWriter();
		$manifest = $this->manifest->add_manifest();
		$this->manifest_organization = $manifest->add_organizations()->add_organization();
		$this->manifest_resources = $manifest->add_resources();
	}

	public function get_course_id(){
		return $this->course_id;
	}

	public function get_course_module_id(){
		return $this->course_module_id;
	}

	public function get_course_module(){
		static $result = null;
		global $DB;
		if(is_null($result) || $result->course_module_id != $this->course_module_id){
			$mod = get_coursemodule_from_id('', $this->course_module_id, $this->course_id, false, MUST_EXIST);
			if($mod){
				$result = $DB->get_record($mod->modname, array('id'=>$mod->instance), '*', MUST_EXIST);
				if($result){
					$result->course_module_id = $this->course_module_id;
					$result->module_name = $mod->modname;
				}else{
					$result = null;
				}
			}else{
				$result = null;
			}
		}
		return $result;
	}

	public function get_context(){
		$result = get_context_instance(CONTEXT_MODULE, $this->get_course_module()->course_module_id);
		return $result;
	}

	public function get_module(){
		static $result = null;
		global $DB;
		if(is_null($result) || $result->id != $this->course_module_id){
			$sql = "SELECT  md.*
    			FROM {course_modules} cm
                JOIN {modules} md ON md.id = cm.module
            	WHERE cm.id={$this->course_module_id};";

			$result = $DB->get_record_sql($sql);
			$result = is_object($result) ? $result : null;
		}
		return $result;
	}

	public function get_path(){
		return $this->path;
	}

	public function copy($course_module_id){
		$result = clone($this);
		$result->course_module_id = $course_module_id;
		return $result;
	}

	/**
	 * @return ImscpManifestWriter
	 */
	public function get_manifest(){
		return $this->manifest;
	}

	public function get_manifest_organization(){
		return $this->manifest_organization;
	}

	public function get_manifest_resources(){
		return $this->manifest_resources;
	}


}

/*
 function accept_course_module($module){
 return 	$module->name == 'imscp' ||
 $module->name == 'resource' ||
 $module->name == 'folder';
 }*/


/**
 *
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class temp_file_portfolio_caller extends portfolio_caller_base {

	public static function display_name(){
		return 'Transfer';
	}

	public static function base_supported_formats(){
		return array(PORTFOLIO_FORMAT_FILE);
	}

	public static function expected_callbackargs() {
		return array('fileid' => true, 'title' => false, 'courseid'=>false);
	}

	protected $fileid = '';
	protected $courseid = '';
	protected $title = '';

	public function load_data() {
	}

	public function prepare_package(){
		$fs = get_file_storage();
		$file = $fs->get_file_by_id($this->fileid);
		return $this->singlefile = $this->get('exporter')->copy_existing_file($file);
	}

	public function check_permissions(){
		return true;
	}

	public function expected_time(){
		return PORTFOLIO_TIME_LOW;
	}

	public function get_navigation(){
		return array(array(), '');
	}

	/**
	 * return a string to put at the header summarising this export
	 * by default, just the display name (usually just 'assignment' or something unhelpful
	 *
	 * @return string
	 */
	public function heading_summary() {
		if(!empty($this->course)){
			return get_string('exportingcontentfrom', 'portfolio', $this->course->fullname);
		}else{
			return parent::heading_summary();
		}
	}

	/**
	 * Return a sha1 of the content being exported - used to detect duplicate exports later.
	 */
	public function get_sha1(){
		$fs = get_file_storage();
		$file = $fs->get_file_by_id($this->fileid);
		//debug($this);
		return sha1($file->get_contenthash());
	}

	public function get_return_url(){
		global $CFG;
		return $CFG->wwwroot . '/course/view.php?id=' . $this->courseid;
	}

	public function get($key) {
		if ($key != 'course') {
			return parent::get($key);
		}

		if(empty($this->course) && !empty($this->courseid)) {
			global $DB;
			$this->course = $DB->get_record('course', array('id' => $this->courseid));
		}
		return $this->course;
	}

}



