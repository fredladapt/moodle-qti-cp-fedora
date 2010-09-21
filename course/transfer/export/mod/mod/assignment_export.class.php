<?php

/**
 * Export Assignement modules to html format.
 * Encode the basic properties in the file with class attributes to allow reimport.
 *
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License
 * @author laurent.opprecht@unige.ch
 *
 */
class assignment_export extends mod_export{

	function export(export_settings $settings){
		$path = $settings->get_path();
		$mod = $settings->get_course_module();
		$content = $this->format_page($mod);

		$name = $this->safe_name($mod->name);
		$href = "$name.{$mod->assignmenttype}.assignment.html";
		$this->add_manifest_entry($settings, $mod->name, $href);
		return file_put_contents("$path/$href", $content);
	}

	protected function format_page($mod){
		$css = $this->get_main_css();
		$title = $mod->name;
		$description = $mod->intro;
		$description = str_ireplace('<p>', '', $description);
		$description = str_ireplace('</p>', '', $description);
		$result = "<html><head>$css<title>$title</title></head><body>";
		$result .= '<div class="title">'.$title.'</div>';
		$result .= '<div class="description">'. $description . '</div>';
		$result .= '</body></html>';
		return $result;
	}

}