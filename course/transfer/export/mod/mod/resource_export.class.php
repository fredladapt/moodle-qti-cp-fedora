<?php

class resource_export extends mod_export{

	function export(export_settings $settings){
		$path = $settings->get_path();
		$mod = $settings->get_course_module();
		$context = $settings->get_context();

		$browser = new file_browser();
		$fi = $browser->get_file_info($context, 'mod_resource', 'content', null);
		$filepath = $this->file_info_copy_to_pathname($fi, $path);
		$href = str_replace($path, '', $filepath);
		$href = ltrim($href, '/');
		$href = ltrim($href, "\\");
		$this->add_manifest_entry($settings, $mod->name, $href);
	}

}