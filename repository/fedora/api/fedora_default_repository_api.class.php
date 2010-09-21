<?php


/**
 * Access Fedora using the standard search Rest API - findObjects.
 * 
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class fedora_default_repository_api{

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

	static function get_name(){
		return 'Fedora';
	}

	/**
	 * @return string
	 */
	static function print_search($for){
		return '';
		
		
		$str = '';
		$str .= '<input type="hidden" name="repo_id" value="'.$for->id.'" />';
		$str .= '<input type="hidden" name="ctx_id" value="'.$for->context->id.'" />';
		$str .= '<input type="hidden" name="seekey" value="'.sesskey().'" />';

		$str .= '<label>'.get_string('query', 'repository_fedora').': </label><br/>';
		$str .= '<input type="text" name="query" style="height:1em; width:25em;"></input><a target="_blank" href="http://lucene.apache.org/java/2_4_1/queryparsersyntax.html"> '. get_string('help') .'</a><br/>';

		$str .= '<table><body>';
		$str .= '</td><tr><td>';
		$str .= '<label>'.get_string('search_level', 'repository_fedora').': </label>';
		$str .= '</td><td>';
		$str .= '<select name="searchLevel"  value="">';
		$str .= '<option value="">'.get_string('best_effort', 'repository_fedora').'</option>';
		$str .= '<option value="1">'.get_string('exact', 'repository_fedora').'</option>';
		$str .= '<option value="2">'.get_string('wildcard', 'repository_fedora').'</option>';
		$str .= '<option value="3">'.get_string('fuzzy', 'repository_fedora').'</option>';
		$str .= '</select><br/>';

		$str .= '</td></tr><tr><td>';
		$str .= '<label>'.get_string('hit_page_size', 'repository_fedora').': </label><br/>';
		$str .= '</td><td>';
		$str .= '<input name="hitPageSize" value="" /><br/>';

		$str .= '</td></tr><tr><td>';
		$str .= '<label>'.get_string('hit_page_start', 'repository_fedora').': </label><br/>';
		$str .= '</td><td>';
		$str .= '<input name="hitPageStart " value="1" /><br/>';

		$str .= '</td></tr><tr><td>';
		$str .= '<label>'.get_string('sort', 'repository_fedora').': </label><br/>';
		$str .= '</td><td>';
		$str .= '<select name="sortFields"  value="">';
		$str .= '<option value="PID,SCORE,false">'.get_string('by_relevance_ascending', 'repository_fedora').'</option>';
		$str .= '<option value="PID,SCORE,true">'.get_string('by_relevance_descending', 'repository_fedora').'</option>';
		$str .= '<option value="fgs.label,STRING,false;PID,SCORE">'.get_string('by_title_ascending', 'repository_fedora').'</option>';
		$str .= '<option value="fgs.label,STRING,true;PID,SCORE">'.get_string('by_title_descending', 'repository_fedora').'</option>';
		$str .= '<option value="fgs.lastModifiedDate,AUTO,false;PID,SCORE">'.get_string('by_date_ascending', 'repository_fedora').'</option>';
		$str .= '<option value="fgs.lastModifiedDate,AUTO,true;PID,SCORE">'.get_string('by_date_descending', 'repository_fedora').'</option>';
		$str .= '<option value="chor_dcterms.creatorSorted,STRING,false;PID,SCORE">'.get_string('by_author_ascending', 'repository_fedora').'</option>';
		$str .= '<option value="chor_dcterms.creatorSorted,STRING,true;PID,SCORE">'.get_string('by_author_descending', 'repository_fedora').'</option>';
		$str .= '</select><br/>';

		$str .= '</td></tr>';
		$str .= '</table></body>';
		//$str .= '<label>'.get_string('keyword', 'repository').': </label><br/><input name="s" value="" /><br/>';
		return $str;
	}

	static function search($fedora, $text){
		return '';
		
		
		$query = isset($_POST['query']) ? $_POST['query'] : '';
		$query = trim($query);
		$query = str_replace("\n", '', $query);

		$searchLevel = isset($_POST['searchLevel']) ? $_POST['searchLevel'] : '';
		$searchLevel = (int)$searchLevel;
		$searchLevel = ($searchLevel != 1 || $searchLevel != 2 || $searchLevel != 3) ? '' : $searchLevel;

		$hitPageSize = isset($_POST['hitPageSize']) ? $_POST['hitPageSize'] : '';
		$hitPageSize = (int)$hitPageSize;
		$hitPageSize = $hitPageSize <= 1 ? '' : $hitPageSize;

		$sort = isset($_POST['sortFields']) ? $_POST['sortFields'] : '';

		if(empty($query)){
			return false;
		}

		$args = array();
		$args['query'] = $query;
		if(!empty($searchLevel)){
			$args['searchLevel'] = $searchLevel;
		}
		if(!empty($hitPageSize)){
			$args['hitPageSize'] = $hitPageSize;
		}
		if(!empty($sort)){
			$args['sortFields'] = $sort;
		}

		global $CFG;
		$result = array();
		$result['nologin']  = true;
		$result['nosearch'] = false;
		$result['norefresh'] = false;
		$result['dynload'] = false;
		try{
			$config = $fedora->get_config();
			$base_url = rtrim($config->get_base_url(), '/');
			$dsID = $config->get_object_datastream_name();
			$objects = $fedora->SWITCH_find($args);

			$list = array();
			foreach($objects as $object){
				$title = $object['dcterms.title'];
				if(!empty($title)){
					$pid = $object['PID'];
					$datastreams = $object['ds.id'];
					$children = array();
					foreach($datastreams as $dsID){
						if(! self::is_system_datastream($dsID)){
							$ds_title = $object["$dsID.LABEL"];
							$children[] = array(
		        						'title'=>$ds_title, 
										'shorttitle' => $ds_title, 
						        		'date'=> 0, 
						        		'size'=> 0, 
										'source'=> "$base_url/objects/$pid/datastreams/$dsID/content",
						        		'thumbnail' => $CFG->wwwroot . '/repository/fedora/resource/datastream.png',
							);
						}
					}

					$list[] = array(
		        		'title'=>$title, 
		        		'date'=>$object['fgs.lastModifiedDate'], 
		        		'size'=>'0', //$object[FedoraProxy::], 
		        		'thumbnail' => $CFG->wwwroot . '/repository/fedora/resource/object.png',
		        		'path' =>  '/' . $title . '^object?' . $pid . '^/',
						'children' =>$children,
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
		global $CFG;
		$ret = array();
		$ret['nologin']  = true;
		$ret['nosearch'] = true;
		$ret['norefresh'] = false;
		$ret['dynload'] = true;
		$ret['manage'] = '';
		//$ret['path'] = array(array('name'=>get_string('institutions', 'repository_fedora'), 'path'=>''));
		$current = '';
		if(!empty($path)){
			$head = '';
			$parts = explode('/', $path);
			foreach($parts as $part) {
				if(!empty($part)) {
					$items = explode('^', $part);
					if(count($items) >= 2){
						$head = rtrim($head, '/') .'/'. $items[0] .'^'. $items[1] .'^/';
						//$ret['path'][] = array('name'=>$items[0], 'path'=>$head);
						$current = $items[1];
					}
				}
			}
		}

		try{
			$config = $fedora->get_config();
			$base_url = rtrim($config->get_base_url(), '/');

			$current = empty($current) ? '' : $current;
			if(strlen($current)>7 && substr($current, 0, 7) == 'object?'){
				$is_object = true;
				$current = str_replace('object?', '', $current);
			}else{
				$is_object = false;
			}
			if(! $is_object){
				$objects = $fedora->list_objects($current);
				foreach($objects as $object){
					$title = $object['label'];
					if(!empty($title)){
						$pid = $object['pid'];
						$list[] = array(
		        		'title'=>$title, 
						'shorttitle' => $title, 
		        		'date'=> 0, 
		        		'size'=> 0, //$object[FedoraProxy::], 
		        		'thumbnail' => $CFG->wwwroot . '/repository/fedora/resource/object.png',
						'author' => isset($object['aaid']) ? $object['aaid']: '',
						'path' => rtrim($path, '/') . '/' . $title . '^object?' .$pid . '^/',
						'children' => array(),

						);
					}
				}
			}else{
				$objects = $fedora->list_datastreams($current);
				foreach($objects as $object){
					$dsID = $object['dsid'];
					if(! self::is_system_datastream($dsID)){
						$title = $object['label'];
						if(!empty($title)){
							$pid = $current;
							$list[] = array(
		        		'title'=>$title, 
						'shorttitle' => $title, 
		        		'date'=> 0, 
		        		'size'=> 0, //$object[FedoraProxy::], 
		        		'source'=> "$base_url/objects/$pid/datastreams/$dsID/content",       
		        		'thumbnail' => $CFG->wwwroot . '/repository/fedora/resource/datastream.png',
							//'author' => $author,
							//'path' => rtrim($path, '/') . '/' . $object['title'] . '^object?' . $object['pid'] . '^/',
							//'children' => array(),

							);
						}
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

}
