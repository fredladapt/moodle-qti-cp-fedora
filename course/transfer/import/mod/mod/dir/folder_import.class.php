<?php

/**
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class folder_import extends mod_import{

	public function get_weight(){
		return 1000000;
	}

	public function get_extentions(){
		return array();
	}

	public function accept($settings){
		$path = $settings->get_path();
		return is_dir($path);
	}

	protected function process_import($settings){
		$cid = $settings->get_course_id();
		$path = $settings->get_path();
		$filename = $settings->get_filename();

		$data = new StdClass();
		$data->course = $cid;
		$data->name =  empty($filename) ? basename($path) : trim_extention($filename);
		$data->intro = '<p>'.$data->name.'</p>';
		$data->introformat = FORMAT_HTML;
		$cm = $this->insert($settings, 'folder', $data);

		$context = get_context_instance(CONTEXT_MODULE, $cm->id);
		$this->add_directory_content($path, $context);
		return $data;
	}

}







