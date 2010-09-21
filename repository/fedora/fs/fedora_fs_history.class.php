<?php

/**
 * Returns objects belonging to a specific user which have been modified between two dates .
 * 
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class fedora_fs_history extends fedora_fs_folder{

	public function __construct($title, $start_date= NULL, $end_date = NULL, $owner = NULL){
		if($title){
			$this->title = $title;
		}
		if($start_date){
			$this->start_date = $start_date;
		}
		if($end_date){
			$this->end_date = $end_date;
		}
		if($owner){
			$this->owner = $owner;
		}
	}
	
	 public function get_thumbnail(){
		global $CFG;
		$default = $CFG->wwwroot . '/repository/fedora/resource/history.png';
		return $this->get(__FUNCTION__, $default);
	}
		
	public function get_end_date(){
		if(isset($this->end_date)){
			return $this->end_date;
		}else{
			return endoftime();
		}
	}

	public function get_start_date(){
		if(isset($this->start_date)){
			return $this->start_date;
		}else{
			return 0;
		}
	}

	public function get_owner(){
		if(isset($this->owner)){
			return $this->owner;
		}else{
			return '';
		}
	}

	public function query($fedora){
		$result = array();
		$start = $this->get_start_date();
		$end = $this->get_end_date();

		$owner = $this->get_owner();
		$objects = self::sparql_find($fedora, '', 0, $start, $end, $owner, self::get_max_results());
		foreach($objects as $object){
			$pid = $object['pid'];
			$label = $object['label'];
			$date = $object['lastmodifieddate'];
			$result[] = new fedora_fs_object($pid, $label, $date);
		}
		$this->sort($result); //fedora sparql order by failing. Should sort at server level when it works.
		return $result;
	}
}

