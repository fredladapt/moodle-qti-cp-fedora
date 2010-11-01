<?php

require_once('main.php');

/**
 * Driver to import questions from the QTI format.
 * Relies on builders for creating the question from the input file.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class QtiImport{

	const IMS_MANIFEST_NAME = 'imsmanifest.xml';

	private $filename = '';
	private $realfilename = '';
	private $course = null;
	private $category = null;
	private $stoponerror = false;
	private $importerrors = 0;

	private $base_temp = '';
	private $resources = array();

	/**
	 * @var Log
	 */
	private $log = null;

	public function __construct($log = null){
		$this->log = empty($log) ? LogEmpty::instance() : $log;
	}

	public function import($filename, $realfilename, $course, $category=null, $stoponerror = false) {
		if(is_null($category)){
			$category = $this->get_default_category($course->id);
		}

		$this->filename = $filename;
		$this->realfilename = $realfilename;
		$this->category = $category;
		$this->course = $course;
		$this->stoponerror = $stoponerror;

		try{
			$result = $this->execute_import($filename, $realfilename, $course, $category, $stoponerror);
			return $result;
		}catch(Exception $e){
			debug($e);
			$this->notify('error', '', $e->getMessage());
			return false;
		}
	}

	public function get_questions($filename, $realfilename){
		$this->notify_lang('parsingquestions');
		$this->filename = $filename;
		$this->realfilename = $realfilename;
		if($this->is_archive($realfilename)){
			if($this->base_temp = MoodleUtil::extract_archive($filename)){
				$result = $this->get_questions_from_directory($this->base_temp);
			}else{
				$result = false;
			}
		}else{
			$result = $this->get_questions_from_file($filename);
		}
		return $result;
	}

	public function get_ressources(){
		return $this->resources;
	}

	public function save_questions($questions, $course, $category, $stoponerror){
		$this->category = $category;
		$this->course = $course;

		$count = 0;
		foreach($questions as $question){
			$this->reset_time_limit();
			if($this->is_category($question)){
				if($c = $this->save_category($question)) {
					$category = $c;
				}
			}else{
				$result = $this->save_question($question, $course, $category);
				$this->print_question($question, ++$count);
				if(!empty($result->notice)){
					$this->notify($result->notice);
				}
				if(!empty($result->error)){
					$this->notify($result->error);
					if($stoponerror){
						return false;
					}
				}
			}
		}
	}

	protected function get_default_category($cid){
		$context = get_context_instance(CONTEXT_COURSE, $cid);
		$result = question_get_default_category($context->id);
		$result = $result ? $result : question_make_default_categories(array($context));
		return $result;
	}

	protected function execute_import($filename, $realfilename, $course, $category, $stoponerror){
		$this->importerrors = 0;

		$questions = $this->get_questions($filename, $realfilename);

		if ($stoponerror && $this->importerrors>0) {
			$this->notify_lang('importparseerror');
			return false;
		}
		$this->notify_lang('importingquestions', '', count($questions));
		$this->save_questions($questions, $course, $category, $stoponerror);
		$this->save_resources();
		$this->cleanup();
		$this->write('<hr/>');
		$this->notify_lang('done');
		$this->notify_lang('checkimportedquestion');
		return $questions;
	}

	protected function get_questions_from_directory($directory){
		$result = array();
		$directory = rtrim($directory, '/') . '/';
		$entries = scandir($directory);
		foreach($entries as $entry){
			$path = $directory . $entry;
			if(is_file($path) && $this->is_question_file($path)){
				if($file_questions = $this->get_questions_from_file($path)){
					//$this->notify($entry);
					$result = array_merge($result, $file_questions);
				}
			}else if(is_dir($path) && $entry != '.' && $entry != '..' ){
				$directory_questions = $this->get_questions_from_directory($path);
				$result = array_merge($result, $directory_questions);
			}
		}
		return $result;
	}

	protected function get_questions_from_file($filename){
		if(!file_exists($filename)){
			return array();
		}
		$result = array();
		$reader = new ImsQtiReader($filename, false);
		$items = $reader->query('/def:assessmentItem');
		foreach($items as $item){
			if($builder = $this->create_builder($item)){
				$question = $builder->build($item);

				if(empty($question)){
					$this->notify_lang('importerror');
					$this->importerrors++;
				}else{
					$result[] = $question;

					$this->resources = array_merge($this->resources, $builder->get_resources());
				}
			}else{
				$this->notify_lang('unknownquestiontype', '', $item->title .' (' . basename($filename) .')');
			}
		}
		return $result;
	}

	protected function is_question_file($path){
		$name = basename($path);
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		if(empty($ext) || $ext != 'xml' || self::IMS_MANIFEST_NAME == $name){
			return false;
		}
		$reader = new ImsQtiReader();
		$reader->load($path);
		return count($reader->query('/def:assessmentItem'))>0;
	}

	protected function is_xml($name){
		$ext = pathinfo($name, PATHINFO_EXTENSION);
		return $ext == 'xml';
	}

	protected function is_archive($name){
		return !$this->is_xml($name);
	}

	protected function is_category($question){
		return $question->qtype=='category';
	}

	protected function save_question($question, $course, $category){
		global $USER, $DB;

		if(!isset($question->course)){
			$question->course = $course->id;
		}

		$question->category = $category->id;
		$question->stamp = make_unique_id_code();  // Set the unique code (not to be changed)

		$question->createdby = $USER->id;
		$question->timecreated = time();

		$question->id = $DB->insert_record("question", $question);

		//save type-specific options
		global $QTYPES;
		$result = $QTYPES[$question->qtype]->save_question_options($question);

		// Give the question a unique version stamp determined by question_hash()
		$DB->set_field('question', 'version', question_hash($question), array('id'=>$question->id));

		return $result;
	}

	protected function save_category($question){
		if($this->is_category($question)) {
			return $this->create_category_path($question->category, '/');
		}else{
			return null;
		}
	}

	protected function cleanup(){
		fulldelete($this->filename);
		fulldelete($this->get_base_temp());
	}

	protected function get_base_url(){
		$file = $this->get_base_file();
		//$result = "/moodle/file.php/{$this->course->id}/$file/";
		$result = '@@PLUGINFILE@@/';
		return $result;
	}

	protected function get_base_file(){
		$result = explode('.', basename($this->realfilename));
		return $result[0];
	}

	protected function get_base_temp(){
		return $this->base_temp;
	}

	protected function get_base_target(){
		$file = $this->get_base_file();
		global $CFG;
		return "{$CFG->dataroot}/{$this->course->id}/$file/";
	}

	protected function get_category(){
		return $this->category;
	}

	protected function create_builder($item){
		return QuestionBuilder::factory($item, $this->get_base_temp(), $this->get_base_url(), $this->get_category());
	}

	protected function save_resources(){
		$files = $this->resources;
		foreach($files as $url => $path){
			$this->save_resource($path);
		}
	}

	protected function save_resource($path){
		try{
			$context = get_context_instance(CONTEXT_COURSE, $this->course->id);
			$contextid = $context->id;
			$from_path = $path;
			$filepath = dirname($path);
			$filepath = str_replace($this->get_base_temp(), '', $filepath);
			$filepath = '/'. $this->get_base_file() .'/'. trim($filepath, '/') .'/' ;
			$filearea = 'questiontext';
			$filename = basename($path);
			$itemid = 0;

			$fs = get_file_storage();
			if($fs->file_exists($contextid, 'question', 'questiontext', $itemid, $filepath, $filename)){
				$fs->delete_area_files($contextid, 'question', $filearea, $itemid);
				$fs->cron();
			}

			$file_record = array(	'contextid'=>$contextid, 'component' => 'question',
								 	'filearea'=>'questiontext', 'itemid'=>$itemid,
	    							'filepath'=>$filepath, 'filename'=>$filename,
	    							'timecreated'=>time(), 'timemodified'=>time());
			$fs->create_file_from_pathname($file_record, $from_path);
		}catch(Exception $e){
			debug($path);
			debug($e);
			die;
			$this->notify_lang('cannotimportfile');
		}
	}

	protected function print_question($question, $count){
		global $CFG;
		$course_id = $this->course->id;
		$question_url = "$CFG->wwwroot/question/preview.php?id=$question->id&courseid=$course_id&continue=1";
		$formatoptions = new stdClass;
		$formatoptions->noclean = true;
		$formatoptions->para = false;
		$format = empty($question->questiontextformat) ? FORMAT_MOODLE : $question->questiontextformat;
		$question_text = format_text($question->questiontext, $format, $formatoptions);
		$this->write('<hr/><p><b><a href="'.$question_url.'" target="_blank">' . $count .".</a></b> $question->name<br/>$question_text</p>");
	}

	protected function reset_time_limit(){
		$max_time = get_cfg_var('max_execution_time');
		set_time_limit($max_time);
	}

	public function __call($name, $args){
		$f = array($this->log, $name);
		if(is_callable($f)){
			return call_user_func_array($f, $args);
		}
	}

}


