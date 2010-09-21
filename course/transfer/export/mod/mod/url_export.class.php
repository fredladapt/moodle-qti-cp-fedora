<?php

class url_export extends mod_export{

	function export(export_settings $settings){
		$path = $settings->get_path();
		$mod = $settings->get_course_module();

		$content = "[InternetShortcut]\n";
		$content .="URL={$mod->externalurl}";
		$name = $this->safe_name($mod->name);
		$href = "$name.url";
		$this->add_manifest_entry($settings, $mod->name, $href, 'glossary.imscp');
		file_put_contents("$path/$href", $content);
	}

}