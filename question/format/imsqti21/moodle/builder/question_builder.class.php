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

	const MOODLE_QUESTION_DATA = 'MOODLE_QUESTION_DATA';

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
	public static function factory($item, $source_root, $target_root, $category){
		if($result = EssayBuilder::factory($item, $source_root, $target_root, $category)){
			return $result;
		}else if($result = TruefalseBuilder::factory($item, $source_root, $target_root, $category)){
			return $result;
		}else if($result = MatchingBuilder::factory($item, $source_root, $target_root, $category)){
			return $result;
		}else if($result = NumericalBuilder::factory($item, $source_root, $target_root, $category)){
			return $result;
		}else if($result = DescriptionBuilder::factory($item, $source_root, $target_root, $category)){
			return $result;
		}else if($result = CalculatedSimpleBuilder::factory($item, $source_root, $target_root, $category)){
			return $result;
		}else if($result = CalculatedBuilder::factory($item, $source_root, $target_root, $category)){
			return $result;
		}else if($result = CalculatedMultichoiceBuilder::factory($item, $source_root, $target_root, $category)){
			return $result;
		}else if($result = MultichoiceBuilder::factory($item, $source_root, $target_root, $category)){
			return $result;
		}else if($result = ShortanswerBuilder::factory($item, $source_root, $target_root, $category)){
			return $result;
		}else if($result = ClozeBuilder::factory($item, $source_root, $target_root, $category)){
			return $result;
		}
		return null;
	}

	/**
	 * Returns the tool name used to generate qti files.
	 * Mostly used to identify if a file is a reimport.
	 *
	 */
	public static function get_tool_name(){
		return Qti::get_tool_name('moodle');
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
	private $category = '';

	public function __construct($source_root, $target_root, $category){
		$resource_manager = new QtiImportResourceManager($source_root, $target_root);
		$renderer = new QtiPartialRenderer($resource_manager);
		$this->strategy = QtiImportStrategyBase::create_moodle_default_strategy($renderer);
		$this->category = $category;
	}

	public function get_category(){
		return $this->category;
	}

	/**
	 * @return QtiImportStrategy
	 */
	public function get_strategy(){
		return $this->strategy;
	}

	/**
	 * Returns the storage context for the question
	 *
	 */
	function get_context() {
		if($category = $this->get_category()){
			$contextid = $category->contextid;
			$context = get_context_instance_by_id($contextid);
			return $context;
		}else{
			return null;
		}
	}

	function get_data(ImsQtiReader $item){
		$feedbacks = $item->list_modalFeedback();
		foreach($feedbacks as $feedback){
			if($feedback->outcomeIdentifier == self::MOODLE_QUESTION_DATA){
				$result = $feedback->text();
				return $result;
			}
		}
		return '';
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
	 * Build a question from the file.
	 *
	 * @param ImsQtiReader $item
	 */
	public function build($item){
		if($data = $this->get_data($item)){
			$data = unserialize($data);
			return $this->build_moodle($data);
		}else{
			return $this->build_qti($item);
		}
	}

	/**
	 * Build questions using the QTI format. Doing a projection by interpreting the file.
	 *
	 * @param ImsQtiReader $item
	 */
	public function build_qti($item){
		return null;
	}

	/**
	 * Build questions using moodle serialized data. Used for reimport, i.e. from Moodle to Moodle.
	 * Used to process data not supported by QTI and to improve performances.
	 *
	 * @param object $data
	 */
	public function build_moodle($data){
		$result = $this->create_question();
		if(isset($data->name)){
			$result->name =  $data->name;
		}
		if(isset($data->questiontext)){
			$result->questiontext = $data->questiontext;
		}
		if(isset($data->generalfeedback)){
			$result->generalfeedback = $data->generalfeedback;
		}
		if(isset($data->penalty)){
			$result->penalty = $data->penalty;
		}
		if(isset($data->defaultgrade)){
			$result->defaultgrade = $data->defaultgrade;
		}
		if(isset($data->options)){
			if(isset($data->options->correctfeedback)){
				$result->correctfeedback = $this->format_text($data->options->correctfeedback);
			}
			if(isset($data->options->partiallycorrectfeedback)){
				$result->partiallycorrectfeedback = $this->format_text($data->options->partiallycorrectfeedback);
			}
			if(isset($data->options->incorrectfeedback)){
				$result->incorrectfeedback = $this->format_text($data->options->incorrectfeedback);
			}
		}

		return $result;
	}

	protected function create_question($data = null){
		$default = new qformat_default();
		$result = $default->defaultquestion();
		$result->usecase = 0; // Ignore case
		$result->image = ''; // No image
		$result->questiontextformat = FORMAT_HTML; //HTML
		$result->answer = array();
		$result->context = $this->get_context();
		$category = $this->get_category();
		$result->category = $category ? $category->name : '';

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

	public function __call($name, $arguments) {
		$f = array($this->strategy, $name);
		if(is_callable($f)){
			return call_user_func_array($f, $arguments);
		}else{
			throw new Exception('Unknown method: '. $name);
		}
	}

}










