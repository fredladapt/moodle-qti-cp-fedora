<?php

global $CFG;
require_once $CFG->dirroot . '/question/format/imsqti21/main.php';

class quiz_import extends mod_import{

	public function get_weight(){
		return 0;
	}

	public function get_extentions(){
		return array('xml');
	}

	public function accept(import_settings $settings){
		$result = $settings->get_reader()->get_root()->is_assessmentTest();
		return $result;
	}

	protected function process_import(import_settings $settings){
		$cid = $settings->get_course_id();
		$path = $settings->get_path();
		$filename = $settings->get_filename();
		$reader = $settings->get_reader();
		$items = $reader->all_assessmentItemRef();
		$dir = dirname($path);
		$maxgrade = 0;
		$questions = array();
		foreach($items as $item){
			$question_path = $dir . '/' . $item->href;
			$question_settings = $settings->copy($question_path);
			if($question = $this->get_root()->import($question_settings)){
				$grade = $item->get_weight()->value;
				$grade = empty($grade) ? 1 : (float)$grade;
				$question->grade = $grade;
				$questions[] = $question;
				$maxgrade += $grade;
			}
		}

		$data = new StdClass();
		$data->resources = array();
		$data->course = $cid;
		$data->name =  $reader->get_root()->title;
		$data->intro = "<p>$data->name</p>";
		$data->introformat = FORMAT_HTML;
		$data->questions = '';
		$data->quizpassword = '';
		$data->feedbackboundarycount = -1;
		$data->feedbacktext = array();
		$data->grade = $maxgrade;
		$data->sumgrades = $maxgrade;
		$data->timeopen = 0;
		$data->timeclose = 0;
		$data->questionsperpage = 1;
		$data->responsesclosed = true;
		$data->scoreclosed = true;
		$data->feedbackclosed = true;
		$data->answersclosed = true;
		$data->solutionsclosed = true;
		$data->generalfeedbackclosed = true;
		$data->overallfeedbackclosed = true;

		quiz_process_options($data);
		$cm = $this->insert($settings, 'quiz', $data);
		$data->coursemodule = $cm->module;

		global $DB;
		foreach($questions as $question){
			$q_data = new StdClass();
			$q_data->quiz = $data->id;
			$q_data->question = $question->id;
			$q_data->grade = $question->grade;
			$q_data->id = $DB->insert_record('quiz_question_instances', $q_data);
			if($q_data->id){
				$data->questions .= empty($data->questions) ? '' : ',';
				$data->questions .= $question->id . ',0';
				//i.e. put question on page 0
			}
		}

		$data->feedbacktext = array( 0=> $this->format_text(''));
		$DB->update_record('quiz', $data);
		quiz_after_add_or_update($data);

		return $cm;
	}

	protected function notify_question_success($cid, $id, $name){
		global $CFG;
		$text = get_string('import', 'block_transfer') . ': ';
		$href = "$CFG->wwwroot/question/preview.php?id=$id&courseid=$cid&continue=1";
		$text .= '<a href="'.$href.'">'. $name . '</a>';
		$this->message($text);
	}

	/**
	 * Return a formatted text entry ready to be processed
	 *
	 * @param string $text text
	 * @param int $format text's format
	 * @param int $itemid existing item id (null for new entries)
	 */
	protected function format_text($text, $format = FORMAT_HTML, $itemid = null){
		return array( 	'text' => $text,
	       				'format' => FORMAT_HTML,
	        			'itemid' => null);
	}
}







