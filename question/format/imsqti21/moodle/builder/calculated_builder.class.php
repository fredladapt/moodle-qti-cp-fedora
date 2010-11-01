<?php

/**
 * Question builder for CALCULATED questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class CalculatedBuilder extends CalculatedBuilderBase{

	static function factory(ImsQtiReader $item, $source_root, $target_root, $category){
		if(!defined('CALCULATED') || count($item->list_interactions())>2  || !self::has_score($item)){
			return null;
		}
		if(!self::is_calculated($item)){
			return null;
		}

		$interactions = $item->list_interactions();
		$main = self::get_main_interaction($item);
		if(count($interactions)==2){
			$second = $interactions[0] == $main ? $interactions[1] : $interactions[0];
			if(strtoupper($second->responseIdentifier) != 'UNIT'){
				return null;
			}
		}
		if(! self::is_numeric_interaction($item, $main)){
			return null;
		}
		return new self($source_root, $target_root, $category);
	}

	public function create_question(){
		$result = parent::create_question();
		$result->qtype = CALCULATED;
		$result->answers = array();
		$result->feedback = array();
		$result->fraction = array();
		$result->tolerance = array();
		$result->tolerancetype = array();
		$result->correctanswerformat = array();
		$result->correctanswerlength = array();

		$result->generalfeedback = '';
		$result->synchronize = false;
		$result->dataset = array();
		$result->length = 1;
		$result->showunits = self::UNIT_HIDE;
		$result->unitpenalty = 0;
		$result->unitsleft = false;
		$result->unitgradingtype = self::QUESTION_GRADE;
		$result->unit = array();
		$result->multiplier = array();
		$result->instructions = '';
		return $result;
	}

	public function build(ImsXmlReader $item){
		$result = $this->create_question();
		$result->name = $item->get_title();
		$result->questiontext =$this->get_question_text($item);
		$result->penalty = $this->get_penalty($item);

		$general_feedbacks = $this->get_general_feedbacks($item);
		$correct_feedbacks = $this->get_correct_feedbacks($item, $general_feedbacks);
		$partiallycorrect_feedbacks = $this->get_partiallycorrect_feedbacks($item, $general_feedbacks);
		$incorrect_feedbacks = $this->get_incorrect_feedbacks($item, $general_feedbacks);

		$result->generalfeedback = implode('<br/>', $general_feedbacks);

		$result->correctfeedback = $this->format_text(implode('<br/>', $correct_feedbacks));
		$result->partiallycorrectfeedback = $this->format_text(implode('<br/>', $partiallycorrect_feedbacks));
		$result->incorrectfeedback = $this->format_text(implode('<br/>', $incorrect_feedbacks));

		$result->dataset = $this->get_datasets($item);
		$result->showunits = $this->get_showunits($item);
		$result->unit = $this->get_units($item);
		$result->multiplier = $this->get_multipliers($item);
		$result->unitpenalty = $this->get_unitpenalty($item);
		$result->unitsleft = $this->get_unitsleft($item);
		$result->unitgradingtype = $this->get_unitgradingtype($item);
		$result->instructions = $this->format_text($this->get_instruction($item));
		$result->defaultgrade = $this->get_maximum_score($item);

		$interaction = $this->get_main_interaction($item);
		$formulas = $this->get_score_formulas($item, $interaction);
		foreach($formulas as $formula){
			$result->answers[] = $this->render_formula($formula);
			$result->feedback[] = $this->format_text($this->get_feedback($item, $interaction, $formula, $general_feedbacks));
			$result->fraction[] = $this->get_fraction($item, $interaction, $formula, $result->defaultgrade);
			$result->tolerance[] = $this->get_tolerance($item, $interaction, $formula);
			$result->tolerancetype[] = $this->get_tolerancetype($item, $interaction, $formula);
			$result->correctanswerformat[] = $this->get_correctanswerformat($item, $interaction, $formula);
			$result->correctanswerlength[] = $this->get_correctanswerlength($item, $interaction, $formula);
		}
		return $result;
	}

}









