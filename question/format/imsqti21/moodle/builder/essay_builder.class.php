<?php

/**
 * Question builder for ESSAY questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class EssayBuilder extends QuestionBuilder{

	static function factory($item, $source_root, $target_root){
		if(!defined("ESSAY")){
			return null;
		}
		
		$count = count($item->list_interactions());
		$main = self::get_main_interaction($item);
		$has_answers = self::has_answers($item, $main);
		if($count == 1 && $main->is_extendedTextInteraction() && !$has_answers){
			return new self($source_root, $target_root);
		}else{
			return null;
		}
	}


	public function __construct($source_root, $target_root){
		parent::__construct($source_root, $target_root);
	}

	public function create_question(){
		$result = parent::create_question();
		$result->qtype = ESSAY;
		$result->fraction = 0; //essays have no score untill graded by the teacher.
		return $result;
	}

	/**
	 *
	 * @param ImsXmlReader $item
	 */
	public function build($item){
		$result = $this->create_question();
		$result->name = $item->get_title();
		$result->questiontext =$this->get_question_text($item);
		$result->defaultgrade = $this->get_maximum_score($item);
		$result->feedback = '';
		$general_feedbacks = $this->get_general_feedbacks($item);
		$result->generalfeedback = implode('<br/>', $general_feedbacks);
		return $result;
	}

}








