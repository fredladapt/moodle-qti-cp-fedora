<?php

/**
 * Returns the last N modified objects belonging to the current user 
 * 
 * @copyright (c) 2010 University of Geneva 
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class fedora_fs_lastobjects extends fedora_fs_folder{

	static function get_user_owner_id(){
		return get_fedora_owner_id();
	}
	
	protected $limit = NULL;
	
	public function __construct($limit = 15){
		$this->limit = $limit;
	}

	public function get_title(){
		return get_string('lastobjects', 'repository_fedora');
	}

	public function format(){
		$result = 'select $pid $modified $label $ownerId from <#ri> ';
		$result .= 'where( ';
		$result .= '$pid <fedora-model:hasModel> <info:fedora/fedora-system:FedoraObject-3.0> ';
		$result .= 'and $pid <fedora-view:lastModifiedDate> $modified ';
		$result .= 'and $pid <fedora-model:label> $label ';
		$result .= 'and $pid <fedora-model:ownerId> $ownerId ';
		$result .= 'and $pid <fedora-model:ownerId> \''. $this->get_user_owner_id() . '\' ';
		$result .= ')minus(' ;
		$result .= '$pid <fedora-rels-ext:isCollection> \'true\' ';
		$result .= ') ';
		$result .= 'order by $modified desc ';
		$result .= "limit {$this->limit} ";
		return $result;
	}

	public function query($fedora){
		$result = array();

		$owner = self::get_user_owner_id();
		$query = $this->format();
		$objects = $fedora->ri_search($query, '', 'tuples', 'iTql', 'Sparql');
		foreach($objects as $object){
			$pid = str_replace('info:fedora/', '', $object['pid']['@uri']);
			$label = $object['label'];
			$date = $object['modified'];
			$result[] = new fedora_fs_object($pid, $label, $date);
		}
		return $result;
	}
}

