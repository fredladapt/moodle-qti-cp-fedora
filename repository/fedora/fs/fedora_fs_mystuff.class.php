<?php

/**
 * Returns object belonging to the current user.
 * 
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class fedora_fs_mystuff extends fedora_fs_folder{

	public function __construct(){
	}

	static function get_user_owner_id(){
		return get_fedora_owner_id(); 
	}
	
	public function get_title(){
		return get_string('mystuff', 'repository_fedora');
	}

	public function query($fedora){
		$result = array();

		$owner = self::get_user_owner_id();
		$objects = self::sparql_find($fedora, '', 0, NULL, NULL, $owner, self::$max_results);
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

