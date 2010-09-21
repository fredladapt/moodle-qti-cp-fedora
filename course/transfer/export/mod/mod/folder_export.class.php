<?php

class folder_export extends mod_export{

	function export(export_settings $settings){
		$path = $settings->get_path();
		$mod = $settings->get_course_module();
		
		//$name = $mod->name;
		$context = $settings->get_context();
		$href = $this->safe_name($mod->name).'/';
		//$fileinfopath = "/$mod->revision/";

		$browser = new file_browser();
		$fi = $browser->get_file_info($context, 'mod_folder', 'content', null, '/', '.');
		$this->file_info_copy_to_pathname($fi, "$path/$href");
		$this->add_manifest_entry($settings, $mod->name, $href, 'folder');
	}

}