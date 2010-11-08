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

	/**
	 * Returns true if the current class provides export functionnalities for $module.
	 *
	 * @param object $module module to module
	 * @return boolean Returns true if the current class provides export functionalities for $module. False otherwise.
	 */
	public function accept($module){
		return $module->name == $this->get_name();
	}

	function export(export_settings $settings){
		return false;
	}

	protected function export_as_page(export_settings $settings){
		$path = $settings->get_path();
		$mod = $settings->get_course_module();
		$content = $this->format_as_page($settings);

		$name = $this->safe_name($mod->name);
		$mod_name = $this->get_name();
		$href = "$name.{$mod->assignmenttype}.$mod_name.html";
		$this->add_manifest_entry($settings, $mod->name, $href);
		$result = file_put_contents("$path/$href", $content);

		$this->export_file_areas($settings);

		return $result;
	}

	/**
	 * Export standard file ares to the $subdir folder
	 *
	 * @param export_settings $settings
	 * @param string $subdir
	 */
	protected function export_file_areas(export_settings $settings, $subdir = ''){
		$contextid = $settings->get_context()->id;
		$path = $settings->get_path() . $subdir . '/resources/';

		$this->copy_file_area($contextid, $path, 'intro');
		$this->copy_file_area($contextid, $path, 'attachments');
		$this->copy_file_area($contextid, $path, 'entry');
		$this->copy_file_area($contextid, $path, 'content');
		//todo: could call mod_get_file_areas instead of harcoding
	}

	/**
	 * Module data exported as an hidden field when the export format permits it.
	 *
	 * @param export_settings $settings
	 */
	protected function get_data(export_settings $settings){
		$mod = $settings->get_course_module();
		$mod = clone $mod;
		unset($mod->id);
		unset($mod->course_module_id);
		$mod->intro = str_replace('@@PLUGINFILE@@', 'resources', $mod->intro);
		return serialize($mod);
	}


	/**
	 * Copy all files contained in a file area to a directory folder
	 *
	 * @param export_settings $settings
	 * @param string $filearea
	 */
	protected function copy_file_area($contextid, $todir, $filearea = 'intro'){
		global $CFG;
		$component = 'mod_' . $this->get_name();

		if (!file_exists($todir)){
			mkdir($todir, $CFG->directorypermissions, true);
		}

		$fs = get_file_storage();
		$files = $fs->get_area_files($contextid, $component,  $filearea);
		foreach($files as $fi){
			if($fi->get_filename() !== '.'){
				$filepath = $todir. $fi->get_filename();
				$fi->copy_content_to($filepath);
			}
		}
	}

	/**
	 * Format the module to a web page containing the title and description
	 *
	 * @param export_settings $settings
	 */
	protected function format_as_page(export_settings $settings){
		$mod = $settings->get_course_module();
		$css = $this->get_main_css();
		$title = $mod->name;
		$description = $mod->intro;
		$description = str_ireplace('<p>', '', $description);
		$description = str_ireplace('</p>', '', $description);
		$result = "<html><head>$css<title>$title</title></head><body>";
		$result .= '<div class="title">'.$title.'</div>';
		$result .= '<div class="description">'. $description . '</div>';
		if($data = $this->get_data($settings)){
			$result .= '<div class="data" style="display:hidden">' . $data .'</div>';
		}
		$result .= '</body></html>';
		$result = str_replace('@@PLUGINFILE@@', 'resources', $result);
		return $result;
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
			$result = false;
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

