<?php

/**
 * Base class for folder FS objects. That is objects that returns other FS objects.
 * 
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class fedora_fs_folder extends fedora_fs_base{
/*
	public static function format_datetime($timestamp){
		if(empty($timestamp)){
			return $timestamp;
		}
		
		return '"'. date('Y-m-d', $timestamp) . 'T' . date('H:i:s', $timestamp). '.00Z"^^xsd:dateTime';
	}
	
	public static function trim_namespaces($name){
		$result = $name;
		$result = str_replace('info:fedora/', '', $result);
		return $result;
	}

	public static function sparql_query($search='', $searchLevel='', $start_date = NULL, $end_date = NULL, $owner='', $hitPageSize=0, $offset=0, $sort=''){
		$search = trim($search);
		
		$hitPageSize = (int)$hitPageSize;
		$hitPageSize = $hitPageSize < 1 ? 0 : $hitPageSize;
		$hitPageSize = empty($hitPageSize) ? self::get_max_results() : $hitPageSize;

		$offset = (int)$offset;
		$offset = $offset <= 0 ? 0 : $offset;

		$result[] = 'select ?pid ?label ?lastModifiedDate ?ownerId from <#ri>';
		$result[] = 'where{';
		$result[] = '?pid <fedora-model:hasModel> <info:fedora/fedora-system:FedoraObject-3.0>';
		//$result[] = '.';
		//$result[] = '?pid $link $target';
		$result[] = '.';
		$result[] = '?pid <fedora-view:lastModifiedDate> ?lastModifiedDate';
		if($start_date){
			$result[] = 'FILTER(?lastModifiedDate>='. self::format_datetime($start_date) .')';
		}
		if(is_numeric($end_date) && $end_date<time()){
			$result[] = 'FILTER(?lastModifiedDate<='. self::format_datetime($end_date) .')';
		}
		$result[] = '.';
		$result[] = '?pid <fedora-model:label> ?label';
		if(!empty($search)){
			if($searchLevel==1){
				$result[] = 'FILTER ?label ="'.$search.'")';
			}else if($searchLevel==2){
				$pattern = ".*$search.*";
				$result[] = 'FILTER regex(?label , "'.$pattern.'", "i")';
			}else if($searchLevel==3){
				$result[] = 'FILTER regex(?label , "'.$search.'", "i")';
			}else{
				$pattern = ".*$search.*";
				$result[] = 'FILTER regex(?label , "'.$pattern.'", "i")';
			}
		}
		$result[] = '.';
		$result[] = '?pid <fedora-model:ownerId> ?ownerId ';
		if(!empty($owner)){
			$result[] = '.';
			$result[] = "?pid <fedora-model:ownerId> '".$owner."'";
		}
		$result[] = '}';
		if(!empty($sort)){
			$result[] = 'ORDER BY ASC(?lastModifiedDate)';
		}
		if($hitPageSize>0){
			$result[] = "LIMIT $hitPageSize";
		}
		if($offset>0){
			$result[] = "OFFSET $offset";
		}

		$result = implode(' ', $result);
		//debug(htmlentities($result));die;
		return $result;
	}
*/

	static function sparql_find($fedora, $search='', $searchLevel='', $start_date = NULL, $end_date = NULL, $owner='', $hitPageSize=0, $offset=0, $sort=''){
		$query = new fedora_fs_sparql_query();
		$query->search = $search;
		$query->searchLevel = $searchLevel;
		$query->start_date = $start_date;
		$query->end_date = $end_date;
		$query->owner = $owner;
		$query->hitPageSize = $hitPageSize;
		$query->offset = $offset;
		$query->sort = $sort;
		return $query->query($fedora);
	}

	public function query($fedora){
		return array();
	}

	public function get_thumbnail(){
		global $CFG;
		$default = $CFG->wwwroot . '/repository/fedora/resource/folder.png';
		return $this->get(__FUNCTION__, $default);
	}
	
	public function format($path = array()){
		$result = array();
		$title = $this->get_title();
		$source = $this->get_source();
		$date = $this->get_date();
		$size = $this->get_size();
		$thumbnail = $this->get_thumbnail();
		if(!empty($title)){
			$result = array(
		        		'title' => $title, 
						'shorttitle' => $title, 
		        		'date'=> $date, 
		        		'size'=> $size,        
		        		'thumbnail' => $thumbnail,
						'children' =>array(),
						'path' => $this->get_path($path),
			);
		}
		return $result;
	}

	public function sort(&$items){
		usort($items, array($this, 'compare'));
		return $items;
	}

	protected function compare($left, $right){
		$wa = strtolower($left->get_date());
		$wb = strtolower($right->get_date());
		if ($wa == $wb) {
			$result =  0;
		}else{
			$result = ($wa > $wb) ? -1 : 1;
		}
		//echo($wa  .' : '. $wb . ' : '. $result); echo "<br/>\n";
		return $result;
	}
}

