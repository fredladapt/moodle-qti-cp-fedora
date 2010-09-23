<?php

/**
 * Access Fedora through the RDI - resouce index - API using FS - file system - objects.
 *
 * See the fs folder for a list of file system objects.
 * Note: the resource index service has to be enabled and configured on the Fedora instance for this class to work.
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class rdi_fs_repository_api{

	static function get_name(){
		return 'RDI FS';
	}

	static function get_user_owner_id(){
		return get_fedora_owner_id();
	}

	static function get_store($for){
		static $result = NULL;
		if(!is_null($result)){
			return $result;
		}
		$owner = self::get_user_owner_id();

		$today = today();
		$this_week = this_week();
		$last_week = last_week();
		$two_weeks_ago = last_week(2);
		$three_weeks_ago = last_week(3);

		$result = new fedora_fs_store(get_string('root', 'repository_fedora'));
		$result->add(new fedora_fs_mystuff());
		$result->add($history = new fedora_fs_store(get_string('history', 'repository_fedora')));
		$history->add($today = new fedora_fs_history(get_string('today', 'repository_fedora'), today(), NULL, $owner));
		$history->add($this_week = new fedora_fs_history(get_string('this_week', 'repository_fedora'), $this_week, NULL, $owner));
		$history->add(new fedora_fs_history(get_string('last_week', 'repository_fedora'), $last_week, $this_week, $owner));
		$history->add(new fedora_fs_history(get_string('two_weeks_ago', 'repository_fedora'), $two_weeks_ago, $last_week, $owner));
		$history->add(new fedora_fs_history(get_string('three_weeks_ago', 'repository_fedora'), $three_weeks_ago, $two_weeks_ago, $owner));

		$result->aggregate(new fedora_fs_lastobjects());
		$result->set_max_results($for->get_option('max_results'));

		global $CFG;
		$history->set_thumbnail($CFG->wwwroot . '/lib/fedora/resource/history.png');
		return $result;
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

	static function search($fedora, $text){
		$query = optional_param('query', '');
		$searchLevel = optional_param('searchLevel', '');
		$hitPageSize = optional_param('hitPageSize', 0);
		$offset = max(optional_param('hitPageStart', 0)-1, 0);
		$myStuff = optional_param('hitPageStart', false);
		$owner = $myStuff ? self::get_user_owner_id() : '';
		$sort = optional_param('sortFields', '');

		$search = new fedora_fs_search('', $query, $searchLevel, NULL, NULL, $owner, $hitPageSize, $offset, $sort);

		return self::print_fs($search->query($fedora));
	}

	static function get_listing($for, $fedora, $path = '', $page = ''){
		$store = self::get_store($for);
		$path = empty($path) ? array($store) : unserialize($path);
		$current = empty($path) ? $store : end($path);


		return self::print_fs($current->query($fedora), $path);
	}

	static function print_fs($items, $path = array()){
		$p = array();
		$current_path = array();
		foreach($path as $item){
			$p[] = array(
						'name' => $item->get_title(),
						'path' => $item->get_path($current_path),
			);
			$current_path[] = $item;
		}

		$list = array();
		foreach($items as $item){
			if(!$item->is_system()){
				$list[] = $item->format($path);
			}
		}

		$result = array();
		$result['nologin']  = true;
		$result['nosearch'] = false;
		$result['norefresh'] = false;
		$result['dynload'] = true;
		$result['manage'] = '';
		$result['list'] = $list;
		if($p){
			$result['path'] = $p;
		}
		return $result;
	}

}












