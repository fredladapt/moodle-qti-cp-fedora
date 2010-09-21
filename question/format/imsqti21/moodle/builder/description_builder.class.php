<?php

/**
 * Question builder for DESCRIPTION questions.
 * 
 * University of Geneva 
 * @author laurent.opprecht@unige.ch
 *
 */
class DescriptionBuilder extends QuestionBuilder{
	
	static function factory($item, $source_root, $target_root){
		if(!defined("DESCRIPTION")){
			return null;
		}else{
			$count = count($item->list_interactions());
			if($count == 0 ){
				return new self($source_root, $target_root);
			}else{
				return null;
			}
		}
	}
	
	public function __construct($source_root, $target_root){
		parent::__construct($source_root, $target_root);
	}
	
	public function create_question(){
		$result = parent::create_question();
        $result->qtype = DESCRIPTION;
        $result->fraction = 0; 
		$result->defaultgrade = 0;
		$result->feedback = '';
        return $result;
	}
	
	/**
	 * 
	 * @param ImsXmlReader $item
	 */
	public function build(ImsXmlReader $item){
		$result = $this->create_question();
        $result->name = $item->get_title();
		$result->questiontext =$this->get_question_text($item);
        $general_feedbacks = $this->get_general_feedbacks($item);
        $result->generalfeedback = implode('<br/>', $general_feedbacks);
		return $result;
	}
	
}








