<?php

class rdi_repository_api{

	static function get_name(){
		return 'RDI';
	}

	static function get_user_owner_id(){
		return get_fedora_owner_id();
	}

	/**
	 * Show the search screen
	 * https://collection.switch.ch/LOREST/objects/find?query=searchTerm&searchLevel=1&hitPageSize=hitPageSize&hitPageStart=hitPageStart&sortFields=sortOrder
	 *
	 * query (requried): String. The word or phrase you are looking for. See Lucene Site for more information. http://lucene.apache.org/java/2_4_1/queryparsersyntax.html
	 *
	 * searchLevel (optional): Integer.
	 * 		1 = exact search for the searchTerm.
	 * 		2 = adds a wildcard (*) at the end of the searchTerm.
	 * 		3 = adds a Tilde(~) for Fuzzy search at the end of the searchTerm.
	 * If no searchLevel is given, Collection search best effort, which meens it tries the next higher searchLevel if no result is found on the lower.
	 *
	 * hitPageSize (optional): How many results should be returned per Page. Default is 1000
	 * hitPageStart (optional): At wich result the results shhould start displaing.
	 * sortFields (optional): Sort order.
	 * 		Default ist by relevance (PID,SCORE,false).
	 * 		Title (fgs.label,STRING,false;PID,SCORE)
	 * 		Date (fgs.lastModifiedDate,AUTO,true;PID,SCORE)
	 * 		Author (chor_dcterms.creatorSorted,STRING,false;PID,SCORE).
	 * To change the sort direction, toggle the false to true and vice versa.
	 * @return string
	 */
	static function print_search($for){
		$str = '';
		$str .= '<input type="hidden" name="repo_id" value="'.$for->id.'" />';
		$str .= '<input type="hidden" name="ctx_id" value="'.$for->context->id.'" />';
		$str .= '<input type="hidden" name="seekey" value="'.sesskey().'" />';

		$str .= '<label>'.get_string('query', 'repository_fedora').': </label><br/>';
		$str .= '<input type="text" name="query" style="height:1em; width:25em;"></input><br/>';

		$str .= '<table><body>';
		$str .= '</td><tr><td>';
		$str .= '<label>'.get_string('search_level', 'repository_fedora').': </label>';
		$str .= '</td><td>';
		$str .= '<select name="searchLevel">';
		//$str .= '<option value="">'.get_string('best_effort', 'repository_fedora').'</option>';
		$str .= '<option value="1">'.get_string('exact', 'repository_fedora').'</option>';
		$str .= '<option value="2" selected="selected">'.get_string('fuzzy', 'repository_fedora').'</option>';
		$str .= '<option value="3">'.get_string('regex', 'repository_fedora').'</option>';
		$str .= '</select><br/>';

		$str .= '</td></tr><tr><td>';
		$str .= '<label>'.get_string('hit_page_size', 'repository_fedora').': </label><br/>';
		$str .= '</td><td>';
		$str .= '<input name="hitPageSize" value="'. $for->get_option('max_results') .'" /><br/>';

		$str .= '</td></tr><tr><td>';
		$str .= '<label>'.get_string('hit_page_start', 'repository_fedora').': </label><br/>';
		$str .= '</td><td>';
		$str .= '<input name="hitPageStart" value="1" /><br/>';

		$str .= '</td></tr><tr><td>';
		$str .= '<label>'.get_string('sort', 'repository_fedora').': </label><br/>';
		$str .= '</td><td>';
		$str .= '<select name="sortFields"  value="">';
		$str .= '<option value="ASC(?label)">'.get_string('by_title_ascending', 'repository_fedora').'</option>';
		$str .= '<option value="DESC(?label)">'.get_string('by_title_descending', 'repository_fedora').'</option>';
		$str .= '<option value="ASC(?lastModifiedDate)">'.get_string('by_date_ascending', 'repository_fedora').'</option>';
		$str .= '<option value="DESC(?lastModifiedDate)">'.get_string('by_date_descending', 'repository_fedora').'</option>';
		//$str .= '<option value="chor_dcterms.creatorSorted,STRING,false;PID,SCORE">'.get_string('by_author_ascending', 'repository_fedora').'</option>';
		//$str .= '<option value="chor_dcterms.creatorSorted,STRING,true;PID,SCORE">'.get_string('by_author_descending', 'repository_fedora').'</option>';
		$str .= '</select><br/>';

		$str .= '</td></tr><tr><td>';
		$str .= '<label>'.get_string('my_stuff', 'repository_fedora').': </label><br/>';
		$str .= '</td><td>';
		$str .= '<input type="checkbox" name="myStuff" checked="true" /><br/>';

		$str .= '</td></tr>';
		$str .= '</table></body>';
		//$str .= '<label>'.get_string('keyword', 'repository').': </label><br/><input name="s" value="" /><br/>';
		return $str;
	}

	static function sparql_query($search='', $searchLevel='', $owner='', $hitPageSize=0, $offset=0, $sort=''){
		$search = trim($search);

		$hitPageSize = (int)$hitPageSize;
		$hitPageSize = $hitPageSize <= 1 ? 0 : $hitPageSize;

		$offset = (int)$offset;
		$offset = $offset <= 0 ? 0 : $offset;

		$result[] = 'select ?pid ?label ?lastModifiedDate ?ownerId from <#ri>';
		$result[] = 'where{';
		$result[] = '?pid <fedora-model:hasModel> <info:fedora/fedora-system:FedoraObject-3.0>';
		//$result[] = '.';
		//$result[] = '?pid $link $target';
		$result[] = '.';
		$result[] = '?pid <fedora-view:lastModifiedDate> ?lastModifiedDate';
		//$result[] = 'FILTER(?lastModifiedDate> "2010-07-10T00:00:00.00Z"^^xsd:dateTime)';
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
		if($hitPageSize>1){
			$result[] = "LIMIT $hitPageSize";
		}
		if($offset>0){
			$result[] = "OFFSET $offset";
		}

		$result = implode(' ', $result);
		return $result;
	}

	static function sparql_find($fedora, $query='', $searchLevel='', $owner='', $hitPageSize=0, $offset=0, $sort=''){
		$query = self::sparql_query($query, $searchLevel, $owner, $hitPageSize, $offset, $sort);
		$items = $fedora->ri_search($query, '', 'tuples', 'Sparql', 'Sparql');

		foreach($items as &$item){
			$pid = self::trim_namespaces($item['pid']['@uri']);
			$item['pid'] = $pid;
		}
		return $items;
	}

	static function trim_namespaces($name){
		$result = $name;
		$result = str_replace('info:fedora/', '', $result);
		return $result;
	}

	static function search($fedora, $text){
		$query = isset($_POST['query']) ? $_POST['query'] : '';
		$searchLevel = isset($_POST['searchLevel']) ? $_POST['searchLevel'] : '';
		$hitPageSize = isset($_POST['hitPageSize']) ? $_POST['hitPageSize'] : '';
		$offset = isset($_POST['hitPageStart']) ? ((int)$_POST['hitPageStart']) - 1: 0;

		$myStuff = isset($_POST['myStuff']) ? $_POST['myStuff'] : false;
		if($myStuff){
			$owner = self::get_user_owner_id();
		}else{
			$owner = '';
		}

		$sort = isset($_POST['sortFields']) ? $_POST['sortFields'] : '';

		global $CFG;
		$result = array();
		$result['nologin']  = true;
		$result['nosearch'] = false;
		$result['norefresh'] = false;
		$result['dynload'] = true;
		try{
			$objects = self::sparql_find($fedora, $query, $searchLevel, $owner, $hitPageSize, $offset, $sort);

			$list = array();
			foreach($objects as $object){
				//debug($object);
				$title = $object['label'];
				if(!empty($title)){
					$pid = $object['pid'];
					$list[] = array(
		        		'title'=>$title, 
		        		'date'=>$object['lastmodifieddate'], 
		        		'size'=>'0', 
		        		'thumbnail' => $CFG->wwwroot . '/repository/fedora/resource/object.png',
		        		'path' =>  '/' . $title . '^object?' . $pid . '^/',
						'children' =>array(),
					);
				}
			}
		}
		catch(Exception $e){
			echo $e->getMessage();
			die;
			throw $e;
		}

		$result['list'] = $list;
		return $result;


	}

	static function get_listing($for, $fedora, $path = '', $page = ''){
		if(!empty($path)){
			$path = trim($path, '/');
			$parts = explode('^', $path);
			$ids = count($parts)>1 ? $parts[1] : '';
			$ids = explode('?', $ids);
			$is_object = $ids[0] == 'object';
			$pid = count($ids) > 1 ? $ids[1] : '';
		}else{
			$is_object = false;
			$pid = '';
		}

		global $CFG;
		$ret = array();
		$ret['nologin']  = true;
		$ret['nosearch'] = false;
		$ret['norefresh'] = false;
		$ret['dynload'] = true;
		$ret['manage'] = '';

		try{
			if($is_object){
				$config = $fedora->get_config();
				$base_url = rtrim($config->get_base_url(), '/');
				$objects = $fedora->list_datastreams($pid);
				foreach($objects as $object){
					$dsID = $object['dsid'];
					if(! self::is_system_datastream($dsID)){
						$title = $object['label'];
						if(!empty($title)){
							$list[] = array(
		        		'title'=>$title, 
						'shorttitle' => $title, 
		        		'date'=> 0, 
		        		'size'=> 0,  
		        		'source'=> "$base_url/objects/$pid/datastreams/$dsID/content",       
		        		'thumbnail' => $CFG->wwwroot . '/repository/fedora/resource/datastream.png',
							);
						}
					}
				}
			}else{
				$owner = self::get_user_owner_id();
				$max = $for->get_option('max_results');
				$objects = self::sparql_find($fedora, '', 0, $owner, $max);
				$list = array();
				foreach($objects as $object){
					if($item = self::get_listing_object($object['pid'], $object['label'], $object['lastmodifieddate'])){
						$list[] = $item;
					}
				}
			}

		}
		catch(Exception $e){
			echo $e->getMessage();
			die;
			throw $e;
		}

		$ret['list'] = $list;
		return $ret;
	}

	public static function is_system_datastream($ds){
		$datastreams = self::get_system_datastreams();
		foreach($datastreams as $datastream){
			if(strtolower($datastream) == strtolower($ds)){
				return true;
			}
		}
		return false;
	}

	public static function get_system_datastreams(){
		return array('DC', 'CHOR_DC', 'RELS-EXT', 'RELS-INT', 'AUDIT', 'THUMBNAIL');
	}

	static function get_listing_object($pid, $title, $date){
		global $CFG;
		if(!empty($title)){
			$result = array(
		        		'title'=>$title, 
		        		'date'=>$date, 
		        		'size'=>'0', 
		        		'thumbnail' => $CFG->wwwroot . '/repository/fedora/resource/object.png',
		        		'path' =>  '/' . $title . '^object?' . $pid . '^/',
						'children' =>array(),
			);
		}else{
			$result = false;
		}
		return $result;
	}

}












