<?php

class imscp_export extends mod_export{

	function export(export_settings $settings){
		$path = $settings->get_path();
		$mod = $settings->get_course_module();
		
		$name = $this->safe_name($mod->name);
		$context = $settings->get_context();
		$component = 'mod_imscp';
		$filearea = 'content';
		$fileinfopath = "/$mod->revision/";
		$href = "$name.imscp.zip";

		$browser = new file_browser();
		$fi = $browser->get_file_info($context, $component, $filearea, null, $fileinfopath);
		$this->file_info_copy_to_pathname($fi, $imspath = "$path/$name/");
		
		$this->archive_directory($imspath, "$path/$href", true);
		$this->add_manifest_entry($settings, $mod->name, $href, 'imscp');		
	}

}