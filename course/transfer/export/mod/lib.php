<?php

/**
 *
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class mod_export{

	public static function factory(){
		$result = new mod_export_aggregate();
		$directory = dirname(__FILE__) .'/mod/';
		$files = scandir($directory);
		foreach($files as $file){
			if($file != '.' && $file != '..'){
				$path = $directory.$file;
				if(strpos($file, '.class.php') !== false){
					include_once($path);
					$class = str_replace('.class.php', '', $file);
					$mod = new $class();
					$result->add($mod);
				}
			}
		}
		return $result;
	}

	public function get_name(){
		$result = get_class($this);
		$result = str_replace('_export', '', $result);
		return $result;
	}

	public function accept($module){
		return $module->name == $this->get_name();
	}

	function export(export_settings $settings){
		return false;
	}

	protected function file_info_copy_to_pathname($fi, $temp, $recursive = true){
		global $CFG;
		if(empty($fi)){
			return ;
		}
		if(!$fi->is_directory()){
			$params = $fi->get_params();
			$filepath = $params['filepath'];
			$path = $temp.$filepath;
			if (!file_exists($path)){
				mkdir($path, $CFG->directorypermissions, true);
			}
			$ext = mimetype_to_ext($fi->get_mimetype());
			$ext = empty($ext) ? get_extention($params['filename']) : $ext;
			$ext = empty($ext) ? 'tmp' : $ext;
			$ext = empty($ext) ? '' : '.' . $ext;
			$path .= '/'. trim_extention($this->safe_name($fi->get_visible_name())) .$ext;
			$fi->copy_to_pathname($path);
			return $path;
		}else{
			$files = $fi->get_children();
			foreach($files as $file){
				if(!$file->is_directory()){
					$result = $this->file_info_copy_to_pathname($file, $temp, $recursive);
				}else if($recursive){
					$result = $this->file_info_copy_to_pathname($file, $temp, $recursive);
				}
			}
			return $result;
		}
	}

	protected function archive_directory($directory_path, $file_path, $delete_directory=false){
		$files = array();
		$directory_path = rtrim($directory_path, '/');
		$entries = scandir($directory_path);
		foreach($entries as $entry){
			if($entry !='.' && $entry !='..'){
				$path = $directory_path . '/'. $entry;
				$files[$entry] = $path;
			}
		}
		$zipper = new zip_packer();
		$result = $zipper->archive_to_pathname($files, $file_path);
		if($delete_directory){
			fulldelete($directory_path);
		}
		return $result;
	}

	protected function safe_name($value){
		return urlencode($value);
	}

	protected function add_manifest_entry(export_settings $settings, $title, $href, $type = 'webcontent'){
		$id = $settings->get_manifest()->get_id_factory()->create_local_id('ID');
		$result = $settings->get_manifest_resources()->add_resource($type, $href, $id);
		$result->add_file($href);
		$settings->get_manifest_organization()->add_item($id)->add_title($title);
		return $result;
	}

	protected function get_main_css(){
		$result = '<style type="text/css">';
		$result .= file_get_contents(dirname(__FILE__).'/../resource/main.css');
		$result .= '';
		$result .= '</style>';
		return $result;
	}

	/*
	 protected function get_module($cid, $mid){
		global $DB;
		$cm = get_coursemodule_from_id('quiz', $mid, $cid, false, MUST_EXIST);
		return $DB->get_record('quiz', array('id'=>$cm->instance), '*', MUST_EXIST);
		}*/

}

/**
 *
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class mod_export_aggregate extends mod_export{

	private $items = array();

	public function add($item){
		$this->items[] = $item;
	}

	public function get_items(){
		return $this->items;
	}

	public function accept($module){
		foreach($this->items as $item){
			if($item->accept($module)){
				return true;
			}
		}
	}

	public function export(export_settings $settings){
		foreach($this->items as $item){
			if($item->accept($settings->get_module())){
				return $item->export($settings);
			}
		}
		return false;
	}
}

