<?php

/**
 * Export a page object as an html page with a .page.html extention.
 * Encode module's properties inside div tags with a class attribute to allow content's extraction.
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License
 * @author laurent.opprecht@unige.ch
 *
 */
class page_export extends mod_export{

	function export(export_settings $settings){
		$path = $settings->get_path();
		$mod = $settings->get_course_module();

		$content = $this->format_page($mod);
		$href = $this->safe_name($mod->name).'.page.html';

		$this->add_manifest_entry($settings, $mod->name, $href);
		return file_put_contents("$path/$href", $content);
	}

	protected function format_page($mod){
		$css = $this->get_main_css();
		$title = $mod->name;
		$description = $mod->intro;
		$description = str_ireplace('<p>', '', $description);
		$description = str_ireplace('</p>', '', $description);
		$content = $mod->content;

		$mod->displayoptions = unserialize($mod->displayoptions);
		if($mod->displayoptions['printheading']){
			$heading_style = '';
		}else{
			$heading_style = 'style="display:none;"';
		}

		if($mod->displayoptions['printintro']){
			$intro_style = '';
		}else{
			$intro_style = 'style="display:none;"';
		}

		$result = "<html><head>$css<title>$title</title></head><body>";
		$result .= '<div class="title" '. $heading_style . '>'.$title.'</div>';
		$result .= '<div class="description" '. $intro_style .'>'. $description . '</div>';
		$result .= '<div class="content">'. $content . '</div>';
		$result .= '</body></html>';
		return $result;
	}

}