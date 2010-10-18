<?php

require_once dirname(__FILE__) . '/../lib.php'; //required by callback

class export_page{

	public function create(){
		$result = new self();
		return $result;
	}

	public $course_id 			= 0;
	public $course_module_id 	= 0;
	public $action 				= '';
	public $fileid 				= 0;
	public $filename 			= '';
	public $selection_form 		= NULL;
	public $callbackclass		= '';

	public function __construct(){
		$this->course_id 		= optional_param('course_id', 0, PARAM_INT);
		$this->course_module_id = optional_param('course_module_id', 0, PARAM_INT);
		$this->action 			= optional_param('action', '', PARAM_ALPHAEXT);
		$this->callbackclass 	= optional_param('callbackclass', '', PARAM_ALPHAEXT);
		$this->fileid 			= optional_param('fileid', 0, PARAM_INT);
		$this->selection_form 	= new course_module_selection_form();
	}

	public function page_setup(){
		global $PAGE, $DB, $_SERVER;
		$course_id = $this->course_id;
		$course_module_id = $this->course_module_id;

		$course = $DB->get_record('course', array('id' => $course_id));

		$PAGE->set_course($course);

		$params = array('course_id'=>$course_id);
		if($course_module_id){
			$params['course_module_id'] = $course_module_id;
		}

		$PAGE->set_url($_SERVER['REQUEST_URI'], $params);
		$title = $course->shortname .  ':' . get_string('transfer', 'block_transfer');
		$PAGE->set_title($title);
		$PAGE->set_heading($title);
	}

	public function is_visible(){
		return $this->callbackclass != 'temp_file_portfolio_caller';
	}

	private $_modules_to_export = false;
	public function modules_to_export(){
		if($this->_modules_to_export){
			return $this->_modules_to_export;
		}

		if($this->course_module_id){
			$this->_modules_to_export = array($this->course_module_id);
		}else if($this->action == 'export' && $this->selection_form->is_valid()){
			$this->_modules_to_export = $this->selection_form->modules();
		}else{
			$this->_modules_to_export = array();
		}
		return $this->_modules_to_export;
	}

	public function export(){
		global $PAGE;

		$modules = $this->modules_to_export();
		if(empty($modules)){
			return false;
		}

		$export = course_export::factory();
		if($path = $export->export($this->course_id, $modules)){

			$path = str_replace('\\', '/', $path);
			$ext = pathinfo($path, PATHINFO_EXTENSION);
			$ext = empty($ext) ? '' : ".$ext";

			$this->filename = $name = $PAGE->course->shortname  .'-'. get_string('export', 'block_transfer') .'-' . time() . $ext;
			$file = create_transfer_file_from_pathname($name, $path);
			$this->fileid = $file->get_id();
			return true;
		}else{
			return false;
		}
	}

	public function display_file_result(){
		if(empty($this->fileid)){
			return '';
		}

		global $CFG, $OUTPUT;
		$fileid = $this->fileid;
		$name = $this->filename;
		$course_id = $this->course_id;

		$result = '';
		$result .= '<br/>'. $OUTPUT->box_start();
		$href = "{$CFG->wwwroot}/course/transfer/file.php?id={$fileid}";
		$result .=  '<a href="'. $href . '">'. $name . '</a><br/><br/>';
		$result .=  '<form><input type="hidden" name="fileid" value="'. $fileid .'"></form>';

		$button = new portfolio_add_button();
		$button->set_callback_options('temp_file_portfolio_caller', array('fileid' => $fileid, 'title'=> $name, 'courseid'=>$course_id));
		$result .=  $button->to_html();
		$result .=  $OUTPUT->box_end();
		return $result;
	}

	public function display_course_module_selection(){
		if(empty($this->course_module_id)){
			return $this->selection_form->display($this->course_id);
		}else{
			return '';
		}
	}

	public function display(){
		require_login();
		if(!$this->is_visible()){
			return;
		}
		$this->page_setup();

		global $OUTPUT;
		echo $OUTPUT->header();

		$modules_to_export = $this->modules_to_export();
		$action = $this->action;

		if(empty($modules_to_export) && $action == 'export'){
			echo $this->notify_problem('nothing_to_export');
		}

		if($modules_to_export){
			$success = $this->export();
			if(!$success){
				echo $this->notify_problem('nothing_to_export');
			}
		}

		echo $this->display_file_result();
		echo $this->display_course_module_selection();
		echo $OUTPUT->footer();
	}

	public function notify_problem($message, $class = 'notifyproblem'){
		global $OUTPUT;
		return $OUTPUT->notification(get_string($message, 'block_transfer'), $class);
	}

	public function __get($name){
		static $cache = array();
		if(!isset($cache[$name])){
			$cache[$name] = optional_param($name, '');
		}
		return $cache[$name];
	}


}









