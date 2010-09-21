<?php

global $CFG;
require_once $CFG->dirroot . '/question/format/imsqti21/main.php';

class question_import extends mod_import{

	public function get_weight(){
		return 0;
	}

	public function get_extentions(){
		return array('xml');
	}

	public function accept(import_settings $settings){
		$result = $settings->get_reader()->get_root()->is_assessmentItem();
		return $result;
	}

	protected function process_import(import_settings $settings){
		$log = $this->get_log();
		$path = $settings->get_path();
		$filename = $settings->get_filename();
		$filename = trim_extention($filename) . '.xml';
		$course = $settings->get_course();

		try{
			$importer = new QtiImport($log);
			$question = $importer->import($path, $filename, $course);
			$question = is_array($question) ? reset($question) : $question;
		}catch(Exception $e){
			debug($e);
			$question = false;
		}
		if($question){
			$this->notify_question_success($settings->get_course_id(), $question->id, $question->name);
		}else{
			$this->notify_failure(basename($path));
		}
		return $question;
	}

	protected function notify_question_success($cid, $id, $name){
		global $CFG;
		$text = get_string('import', 'block_transfer') . ': ';
		$href = "$CFG->wwwroot/question/preview.php?id=$id&courseid=$cid&continue=1";
		$text .= '<a href="'.$href.'">'. $name . '</a>';
		$this->message($text);
	}

}







