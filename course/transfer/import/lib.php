<?php

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir.'/mime/mime_type.php');
require_once($CFG->libdir.'/portfolio/caller.php');
require_once($CFG->libdir.'/debug_util.class.php');
require_once($CFG->dirroot.'/mod/resource/locallib.php');
require_once($CFG->dirroot.'/portfolio/fedora/lib.php');
require_once($CFG->libdir.'/formslib.php');
require_once dirname(__FILE__) . '/../util/util.php';
require_once dirname(__FILE__) . '/../util/log.php';
require_once dirname(__FILE__) . '/ui.php';
require_once dirname(__FILE__) . '/mod/lib.php';

/**
 *
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class course_import {

	public static function factory($log, $delete_file = true){
		return $result = new self($log, $delete_file);
	}

	private $log;
	private $mod_import;
	private $delete_file;

	public function __construct($log=null, $delete_file=true){
		$this->log = empty($log) ? new transfer_log_empty() : $log;
		$this->mod_import = mod_import::factory($this->log);
		$this->delete_file = $delete_file;
	}

	public function accept($settings){
		return $this->mod_import->accept($settings);
	}

	public function import(import_settings $settings){
		if(file_exists($settings->get_path())){
			$result = $this->mod_import->import($settings);
		}else{
			$this->message(get_string('nothing_to_import', 'block_transfer'));
			$result = false;
		}
		if($this->delete_file){
			fulldelete($settings->get_path());
		}
		return $result;
	}

	protected function message($text){
		$this->log->message($text);
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
class import_settings{

	private $course_id = 0;
	private $path = '';
	private $filename = '';
	private $section_id;
	private $extention = '';
	private $level = 0;
	private $parent_id = 0;

	public function __construct($course_id, $path, $filename = '', $extention = '', $section_id=0){

		if(empty($section_id)){
			$sections = $DB->get_records('course_sections', array('course'=>$cid), 'section ASC', '*');
			$section_id = reset($sections)->id;
		}
		if(empty($filename)){
			$filename = basename($path);
		}
		if(empty($extention)){
			$extention = get_extention($filename);
			$extention = empty($extention) ? get_extention($path) : $extention;
		}
		$extention = ltrim($extention, '.');

		$this->course_id = $course_id;
		$this->path = $path;
		$this->filename = $filename;
		$this->extention = $extention;
		$this->section_id = $section_id;
	}

	public function get_section_id(){
		$result = $this->section_id;
		return $result;
	}

	public function get_section(){
		static $result = null;
		if(empty($result) && !empty($this->section_id)){
			global $DB;
			$result = $DB->get_record('course_sections', array('id'=>$this->section_id), '*', MUST_EXIST);
		}
		return $result;
	}

	public function get_filename(){
		return $this->filename;
	}

	public function get_extention(){
		return $this->extention;
	}

	public function get_path(){
		return $this->path;
	}

	public function get_course_id(){
		return $this->course_id;
	}

	public function get_course(){
		static $result = null;
		if(empty($result)){
			global $DB;
			$result = $DB->get_record('course', array('id'=>$this->course_id), '*', MUST_EXIST);
		}
		return $result;
	}

	public function get_level(){
		return $this->level;
	}

	public function reset_level(){
		$this->level = 0;
	}

	/**
	 * Used to link children to their parent. For example a blog entry to the blog, etc.
	 */
	public function get_parent_id(){
		return $this->parent_id;
	}

	public function copy($path, $filename='', $extention='', $parent_id = 0){
		if(empty($filename)){
			$filename = basename($path);
		}
		if(empty($extention)){
			$extention = get_extention($filename);
			$extention = empty($extention) ? get_extention($path) : $extention;
		}
		$extention = ltrim($extention, '.');
		$course_id =
		$result = clone($this);
		$result->path = $path;
		$result->filename = $filename;
		$result->extention = $extention;
		$result->level++;
		$result->parent_id = $parent_id;

		return $result;
	}

	public function __clone() {
		$this->reader = NULL;
		$this->manifest_reader = NULL;
		$this->dom = NULL;
	}

	private $reader = NULL;
	/**
	 * @return ImsXmlReader
	 */
	public function get_reader(){
		if(!empty($this->reader)){
			return $this->reader;
		}

		$path = $this->path;
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		if($ext == 'xml'){
			return $this->reader = new ImsXmlReader($path);
		}else{
			return $this->reader = ImsXmlReader::get_empty_reader();
		}
	}

	private $manifest_reader = NULL;
	/**
	 * @return ImsXmlReader
	 */
	public function get_manifest_reader(){
		if(empty($this->manifest_reader)){
			$path = $this->get_path();
			if(is_dir($path)){
				$dir = $path;
				$files = scandir($path);
				$files = array_diff($files, array('.', '..'));
				foreach($files as $file){
					if(strtolower($file) == 'imsmanifest.xml'){
						$path = $dir .'/'. $file;
						return $this->manifest_reader = new ImscpManifestReader($dir .'/'. $file);
					}
				}
			}
			return $this->manifest_reader = ImsXmlReader::get_empty_reader();
		}
		return $this->manifest_reader;
	}

	private $dom = NULL;
	/**
	 * @return DOMDocument
	 */
	public function get_dom(){
		if($this->dom){
			return $this->dom;
		}

		$doc = new DOMDocument();
		if($doc->loadHTMLFile($this->get_path())){
			return $this->dom = $doc;
		}else{
			return NULL;
		}
	}
}

