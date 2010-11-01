<?php

/**
 * Question builder for CALCULATEDSIMPLE questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class CalculatedSimpleBuilder extends CalculatedBuilder{

	static function factory(ImsQtiReader $item, $source_root, $target_root, $category){
		$accept = defined('CALCULATEDSIMPLE' || !self::has_score($item)) &&
				  !is_null(CalculatedBuilder::factory($item, $source_root, $target_root)) &&
				  $item->toolName == self::get_tool_name() &&
				  $item->toolVersion >= Qti::get_tool_version() &&
				  $item->label == CALCULATEDSIMPLE;
		if($accept){
			return new self($source_root, $target_root, $category);
		}else{
			return null;
		}
	}

	public function create_question(){
		$result = parent::create_question();
        $result->qtype = CALCULATEDSIMPLE;
        return $result;
	}



}
















