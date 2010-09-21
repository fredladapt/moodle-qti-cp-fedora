<?php

/**
 * Question builder for TRUEFALSE questions.
 * 
 * University of Geneva 
 * @author laurent.opprecht@unige.ch
 *
 */
class TruefalseBuilder extends QuestionBuilder{
	
	static function factory(ImsQtiReader $item, $source_root, $target_root){
		if(!defined("TRUEFALSE") || count($item->list_interactions())>1 || !self::has_score($item)){
			return null;
		}
		$main = self::get_main_interaction($item);
		if(!$main->is_choiceInteraction()){
			return null;
		}
		$choices = $main->list_simpleChoice();
		if(count($choices)!=2){
			return null;
		}
		$t0 = strtolower($choices[0]->value());
		$t1 = strtolower($choices[1]->value());
		$true = strtolower(get_string('true', 'qtype_truefalse'));
		$false = strtolower(get_string('false', 'qtype_truefalse'));
		if(($t0 == $true || $t0 == $false) && ($t1 == $true || $t1 == $false)){
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
        $result->qtype = TRUEFALSE;
        $result->fraction = array(); 
        $result->answer = array(); 
		$result->feedbacktrue = '';
		$result->feedbackfalse = '';
		$result->questiontext='';
		$result->correctanswer=true;
        return $result;
	}
	
	public function build(ImsXmlReader $item){
		$result = $this->create_question();
        $result->name = $item->get_title();
        $result->penalty = $this->get_penalty($item);
        $general_feedbacks = $this->get_general_feedbacks($item);
        $result->generalfeedback = implode('<br/>', $general_feedbacks);
		$result->questiontext =$this->get_question_text($item);
		
		$interaction = self::get_main_interaction($item);
    	$result->defaultgrade = $this->get_maximum_score($item, $interaction);
    	
		$true = strtolower(get_string('true', 'qtype_truefalse'));
    	$choices = $interaction->all_simpleChoice();
    	foreach($choices as $choice){
    		$feedback = $this->get_feedback($item, $interaction, $choice->identifier, $general_feedbacks);
    		$score = $this->get_score($item, $interaction, $choice->identifier);
    		$true_answer = strtolower($choice->value()) === $true;
            $result->correctanswer = $score == 0 ? !$true_answer : $true_answer; 
			if($true_answer){
				$result->feedbacktrue = $feedback;
			}else{
				$result->feedbackfalse = $feedback;
			}
    		
    	}    
    	return $result;
	}
}








