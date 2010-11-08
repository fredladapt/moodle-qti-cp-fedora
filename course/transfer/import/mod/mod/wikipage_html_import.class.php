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
		$result->cachedcontent = $this->get_content($settings, $result);
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

		$cm = $this->get_course_module($settings);
		$this->save_resources($settings, $cm, $result);
		return $result->id ? $result : false;
	}

	/**
	 * Returns the course module record.
	 *
	 * @param import_settings $settings
	 */
	protected function get_course_module(import_settings $settings){
		global $DB;
		$subwiki = $DB->get_record('wiki_subwikis', array('id' => $settings->get_parent_id()));
		$course_id = $settings->get_course_id();
		$wiki_id = $subwiki->wikiid;

		$module = $DB->get_record('modules', array('name' => 'wiki'));
		$module_id = $module->id;
		//todo: check if list instance/modules make uses of module id?
		$result = $DB->get_record('course_modules', array('instance' => $wiki_id, 'course' => $course_id, 'module' => $module_id));
		return $result;
	}

	protected function create(import_settings $settings){
		global $USER;

		$result = new stdClass();
		$result->resources = array();
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
		$result = new stdClass();
		$result->content = '';
		$result->contentformat = 'html';
		$result->version = 0;
		$result->timecreated = time();
		$result->userid = $USER->id;
		return $result;
	}

	protected function get_content(import_settings $settings, $data){
		$result = $this->read($settings, 'description');
		$result = $this->translate($settings, $data, 'attachments', $result);
		return $result;
	}

	/**
	 * Save embeded resources. I.e. images
	 *
	 * @param import_settings $settings
	 * @param object $cm
	 * @param object $data
	 */
	protected function save_resources(import_settings $settings, $cm, $data){
		global $USER;

		$context = get_context_instance(CONTEXT_MODULE, $cm->id);
		$fs = get_file_storage();
		$component = 'mod_wiki';
		foreach($data->resources as $resource){
			$file_record = array(
            	'contextid' => $context->id,
            	'component'  => $component,
            	'filearea'  => $resource['filearea'],
				'itemid'    => $data->subwikiid,
				'filepath'  => '/',
            	'filename'  => $resource['filename'],
				'userid'    => $USER->id
			);
			try{
				$r = $fs->create_file_from_pathname($file_record, $resource['path']);
			}catch(Exception $e){
				//debug($e);
			}
		}
	}
}






?>