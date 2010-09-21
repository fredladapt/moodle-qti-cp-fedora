<?php

/**
 * Import glossary item html files as glossary entry objects.
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License
 * @author laurent.opprecht@unige.ch
 *
 */
class glossaryitem_html_import extends mod_import{

	public function get_extentions(){
		return array('glossaryitem.html');
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
		$result->definition = '<p>' . $this->get_description($settings) . '</p>';
		$result->id = $DB->insert_record('glossary_entries', $result);
		return $result->id ? $result : false;
	}

	protected function create(import_settings $settings){
		global $USER, $CFG;

		$result = new stdClass();
		$result->glossaryid = $settings->get_parent_id();
		$result->userid = $USER->id;
		$result->concept = $this->get_title($settings);
		$result->definition = '';
		$result->definitionformat = FORMAT_HTML;
		$result->definitiontrust = 0;
		$result->attachment = '';
		$result->timecreated = $result->timemodified = time();
		$result->teacherentry = true;
		$result->sourceglossaryid = 0;
		$result->usedynalink = $CFG->glossary_linkentries;
		$result->casesensitive = false;
		$result->fullmatch = $CFG->glossary_fullmatch;
		$result->approved = true;
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
					$result = str_ireplace('<p/>', '', $result);
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