<?php

/**
 * Question builder for MULTICHOICE questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class MultichoiceBuilder extends QuestionBuilder{

	static function factory($item, $source_root, $target_root, $category){
		if(!defined("MULTICHOICE") || !self::has_score($item)){
			return null;
		}else{
			$count = count($item->list_interactions());
			$main = self::get_main_interaction($item);
			if($count == 1 && $main->is_choiceInteraction()){
				return new self($source_root, $target_root, $category);
			}else{
				return null;
			}
		}
	}

	public function create_question(){
		$result = parent::create_question();
		$result->qtype = MULTICHOICE;
		$result->fraction = array();
		$result->answer = array();
		$result->feedback = array();
		$result->answernumbering = 'none';
		$result->correctfeedback = '';
		$result->partiallycorrectfeedback = '';
		$result->incorrectfeedback = '';
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

		$feedbacks_to_filter_out = array_merge($general_feedbacks, $correct_feedbacks, $partiallycorrect_feedbacks, $incorrect_feedbacks);

		$interaction = self::get_main_interaction($item);
		$result->single = $interaction->maxChoices == 1;
		$result->shuffleanswers = $interaction->shuffle == 'true' || $interaction->shuffle == '';

		$result->defaultgrade = $this->get_maximum_score($item);

		$choices = $interaction->all_simpleChoice();
		foreach($choices as $choice){
			$answer = $choice->identifier;
			$result->answer[] = $this->to_text($choice);
			$result->feedback[] = $this->format_text($this->get_feedback($item, $interaction, $answer, $feedbacks_to_filter_out));
			$result->fraction[] = $this->get_fraction($item, $interaction, $answer, $result->defaultgrade);
		}

		return $result;
	}
}








