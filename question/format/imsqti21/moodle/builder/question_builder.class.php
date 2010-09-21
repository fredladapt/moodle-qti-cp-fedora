<?php

/**
 * Base class for all question builders. Builders are responsible to construct a moodle question object.
 * Relies on the import strategies to extract values from the QTI file and on the QTI renderer
 * to render the question's parts. 
 * 
 * University of Geneva 
 * @author laurent.opprecht@unige.ch
 *
 */
class QuestionBuilder{

	public static function is_calculated(ImsXmlReader $item){
		if(!$item->has_templateDeclaration()){
			return false;
		}
		$templates = $item->list_templateDeclaration();
		foreach($templates as $template){
			$base_type = $template->baseType;
			if($base_type != Qti::BASETYPE_FLOAT && $base_type != Qti::BASETYPE_INTEGER){
				return false;
			}
		}
		return true;
	} 
	
	/**
	 * @param ImsQtiReader $item
	 * @return QuestionBuilder
	 */
	static function factory($item, $source_root, $target_root){
		if($result = EssayBuilder::factory($item, $source_root, $target_root)){
			return $result;
		}else if($result = TruefalseBuilder::factory($item, $source_root, $target_root)){
			return $result;
		}else if($result = MatchingBuilder::factory($item, $source_root, $target_root)){
			return $result;
		}else if($result = NumericalBuilder::factory($item, $source_root, $target_root)){
			return $result;
		}else if($result = DescriptionBuilder::factory($item, $source_root, $target_root)){
			return $result;
		}else if($result = CalculatedSimpleBuilder::factory($item, $source_root, $target_root)){
			return $result;
		}else if($result = CalculatedBuilder::factory($item, $source_root, $target_root)){
			return $result;
		}else if($result = CalculatedMultichoiceBuilder::factory($item, $source_root, $target_root)){
			return $result;
		}else if($result = MultichoiceBuilder::factory($item, $source_root, $target_root)){
			return $result;
		}else if($result = ShortanswerBuilder::factory($item, $source_root, $target_root)){
			return $result;
		}else if($result = ClozeBuilder::factory($item, $source_root, $target_root)){
			return $result;
		}
		return null;
	}

	/**
	 * 
	 * @param ImsQtiReader $item
	 * @return ImsQtiReader
	 */
	static function get_main_interaction($item){
		return QtiImportStrategyBase::get_main_interaction($item);
	}
	
	static function has_score($item){
		return QtiImportStrategyBase::has_score($item);
	}
	
	static function has_answers($item){
		return QtiImportStrategyBase::has_answers($item);
	}
	
	static function is_numeric_interaction($item, $interaction){
		return QtiImportStrategyBase::is_numeric_interaction($item, $interaction);
	}

	/**
	 * 
	 * @var QtiImportStrategy
	 */
	private $strategy = null;

	public function __construct($source_root, $target_root){
		$resource_manager = new QtiImportResourceManager($source_root, $target_root);
		$renderer = new QtiPartialRenderer($resource_manager);
		$this->strategy = QtiImportStrategyBase::create_moodle_default_strategy($renderer);
	}

	/**
	 * @return QtiImportStrategy
	 */
	public function get_strategy(){
		return $this->strategy;
	}
	
	/**
	 * @return QtiResourceManager
	 */
	public function get_resource_manager(){
		return $this->strategy->get_renderer()->get_resource_manager();
	}

	public function get_resources(){
		return $this->get_resource_manager()->get_resources();
	}
	
	/**
	 * 
	 * @param ImsQtiReader $item
	 */
	public function build($item){
		return null;
	}
	
	protected function create_question(){
        $default = new qformat_default();
        $result = $default->defaultquestion();
        $result->usecase = 0; // Ignore case
        $result->image = ''; // No image
        $result->questiontextformat = 1; //HTML
        $result->answer = array(); 
        return $result;
	}
	
	protected function get_feedback(ImsQtiReader $item, ImsQtiReader $interaction, $answer, $filter_out){
		$result =  $this->get_feedbacks($item, $interaction, $answer, $filter_out);
		$result =  implode('<br/>', $result);
		return $result;
	}
	
	protected function get_instruction(ImsQtiReader $item, $role = Qti::VIEW_ALL){
		$result = $this->get_rubricBlock($item, $role);
		$result = implode('<br/>', $result);
		return $result;
	}

	protected function get_fraction($item, $interaction, $answer, $default_grade){
		$default_grade = empty($default_grade) ? 1 : $default_grade;
		$score = $this->get_score($item, $interaction, $answer);
		$result = MoodleUtil::round_to_nearest_grade($score/$default_grade);
		return $result;
	}
	
	public function __call($name, $arguments) {
		$f = array($this->strategy, $name);
		if(is_callable($f)){
			return call_user_func_array($f, $arguments);
		}else{
			throw new Exception('Unknown method: '. $name);
		}
	}

}



	//strategy
	
	/**
	 * 
	 * @param ImsQtiReader $element
	 */
	/*protected function to_html($item){
		return $this->strategy->to_html($item);
	}
	
	/*protected function to_text($item){
		return $this->strategy->to_text($item);
	}
	
	/**
	 * 
	 * @param ImsQtiReader $item
	 */
	/*protected function get_score_default($item){
		return $this->strategy->get_score_default($item);
	}*/
	
	/**
	 * 
	 * @param ImsQtiReader $item
	 */
	/*protected function get_question_text($item){
		return $this->strategy->get_question_text($item);
	}*/
	
	/**
	 * 
	 * @param ImsQtiReader $item
	 * @return array
	 */
	/*protected function list_outcome($item, $include_feedback_outcome = false){
		return $this->strategy->list_outcome($item, $include_feedback_outcome);
	}*/
	
	/**
	 * 
	 * @param ImsQtiReader $item
	 * @return ImsQtiReader
	 */
	/*protected function get_score_outcome($item){
		return $this->strategy->get_score_outcome($item);
	}
	
	protected function get_main_response($item){
		return $this->strategy->get_main_response($item);
	}
	
	/*protected function get_correct_responses(ImsQtiReader $item, ImsQtiReader $interaction){
		return $this->strategy->get_correct_responses($item, $interaction);
	}*/
	
	/*protected function get_score(ImsQtiReader $item, ImsQtiReader $interaction, $answer, $outcome_id = ''){
		return $this->strategy->get_score($item, $interaction, $answer, $outcome_id);
	}*/

	/*protected function get_maximum_score(ImsQtiReader $item, ImsQtiReader $interaction = null){
		return $this->strategy->get_maximum_score($item, $interaction);
	}*/

	/*protected function get_penalty(ImsQtiReader $item){
		return $this->strategy->get_penalty($item);
	}*/
	
	/*protected function get_general_feedbacks(ImsQtiReader $item){
		return $this->strategy->get_general_feedbacks($item);
	}	*/
	



	/*
	protected function get_response(ImsQtiReader $item, ImsQtiReader $interaction){
		return $this->strategy->get_response($item, $interaction);
	}*/












