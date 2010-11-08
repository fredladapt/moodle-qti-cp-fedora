<?php

/**
 * Import an html page with a .page.html page extention.
 * That is a page that follows the page format of the page export module.
 * Note that generic html pages will not be imported by this module.
 * The reason being that generic html pages may include links, javascript, css, etc in the page header and that the Page object expects to receive an html subset, not a full page html.
 * Generic html pages are imported by the resouce import module as a resouce - i.e. a document.
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License
 * @author laurent.opprecht@unige.ch
 *
 */
class page_import extends mod_import{

	public function accept($settings){
		$path = $settings->get_path();
		$name = basename($path);
		$extentions = $this->get_extentions();
		foreach($extentions as $ext){
			if(strpos($name, $ext) !== false){
				return true;
			}
		}
		return false;
	}

	public function get_extentions(){
		return array('.page.html', '.page.htm');
	}

	protected function process_import($settings){
		$cid = $settings->get_course_id();
		$path = $settings->get_path();
		$filename = $settings->get_filename();

		$text = file_get_contents($path);

		$data = new StdClass();
		$data->resources = array();
		$data->course = $cid;
		$data->name = $this->read($settings, 'title');
		$data->intro = $this->get_description($settings, $data);
		$data->introformat = FORMAT_HTML;
		$data->content = $this->get_content($settings, $data);
		$data->contentformat= FORMAT_HTML;
		$data->legacyfiles = 0;
		$data->legacyfileslast = null;
		$data->display = 5;
		$data->displayoptions = serialize(array('printheading'=>true, 'printintro'=>true));
		$data->revision = 1;
		return $this->insert($settings, 'page', $data) ? $data : false;
	}

	protected function get_content(import_settings $settings, $data){
		$result = $this->read($settings, 'content');
		$result = $this->translate($settings, $data, 'content', $result);
		return $result;
	}


}