<?php

/**
 * 
 * Question builder for MULTIANSWER/Cloze questions.
 * 
 * University of Geneva 
 * @author laurent.opprecht@unige.ch
 *
 */
class ClozeBuilder extends QuestionBuilder{
	
	static function factory($item, $source_root, $target_root){
		if(!defined("MULTIANSWER") || $item->has_templateDeclaration() || !self::has_score($item)){
			return null;
		}else{
			$interactions = $item->list_interactions();
			foreach($interactions as $interaction){
				if(!($interaction->is_inlineChoiceInteraction() ||
					$interaction->is_choiceInteraction() ||
			  	 	$interaction->is_textEntryInteraction() || //$main->is_hottextInteraction()
			   		$interaction->is_gapMatchInteraction())){
			   			return null;
			   		}
				
			}
			return new self($source_root, $target_root);
		}
	}
	
	public function __construct($source_root, $target_root){
		parent::__construct($source_root, $target_root);
	}
	
	public function create_question(){
		$result = parent::create_question();
        $result->qtype = MULTIANSWER;
        return $result;
	}
	
	public function build(ImsXmlReader $item){
    	$text = $this->to_cloze($item);
		$result = qtype_multianswer_extract_question($text);
        $result->questiontextformat = 1; //HTML
        $general_feedbacks = $this->get_general_feedbacks($item);
        $result->generalfeedback = implode('<br/>', $general_feedbacks);
        $result->name = $item->get_title();
        $result->penalty = $this->get_penalty($item);
    	return $result;
	}
	
	protected function to_cloze($item){
		$cloze_renderer = new ClozeRenderer($this->get_strategy(), $item);
		$result = $cloze_renderer->render($item->get_itemBody());
		return $result;
	}
}















