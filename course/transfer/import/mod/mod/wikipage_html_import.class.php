<?php

/**
 * Import wiki page html files as wiki page entry objects.
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License
 * @author laurent.opprecht@unige.ch
 *
 */
class wikipage_html_import extends mod_import{

	public function get_extentions(){
		return array('wikipage.html');
	}

	public function accept($settings){
		$path = $settings->get_path();
		$name = basename($path);
		$result = strpos($name, reset($this->get_extentions())) !== false;
		return $result;
	}

	protected function process_import(import_settings $settings){
		global $DB;
		$result = $this->create($settings);
		$result->cachedcontent = $this->get_description($settings);
		$result->id = $DB->insert_record('wiki_pages', $result);
		
		//initial empty version
		$version = $this->create_version();
		$version->pageid = $result->id;
		$version->id = $DB->insert_record('wiki_versions', $version);
		
		//version with content
		$version = $this->create_version();
		$version->pageid = $result->id;
		$version->content = $result->cachedcontent;
		$version->version = 1;
		$version->id = $DB->insert_record('wiki_versions', $version);
		
		return $result->id ? $result : false;
	}

	protected function create(import_settings $settings){
		global $USER;

		$result = new object();
		$result->subwikiid = $settings->get_parent_id();
		$result->title = $this->get_title($settings);
		$result->cachedcontent = '';
		$result->timecreated = $result->timemodified = $result->timerendered = time();
		$result->userid = $USER->id;
		$result->pageviews = 1;
		$result->readonly = false;
		
		return $result;
	}
	
	protected function create_version(){
		global $USER;
		$result = new object();
		$result->content = '';
		$result->contentformat = 'html';
		$result->version = 0;
		$result->timecreated = time();
		$result->userid = $USER->id;
		return $result;
	}

	protected function get_description(import_settings $settings, $default = ''){
		if($doc = $settings->get_dom()){
			$list = $doc->getElementsByTagName('div');
			foreach($list as $div){
				if(strtolower($div->getAttribute('class')) == 'description'){
					$result = $this->get_innerhtml($div);
					$result = str_ireplace('<p>', '', $result);
					$result = str_ireplace('</p>', '', $result);
					return $result;
				}
			}
			$list = $doc->getElementsByTagName('body');
			if($body = $list->length>0 ? $list->item(0) : NULL){
				$body = $doc->saveXML($body);
				$body = str_replace('<body>', '', $body);
				$body = str_replace('</body>', '', $body);
			}else{
				$body = '';
			}
		}
		return $default;
	}
}






?>