<?php

class glossary_export extends mod_export{

	function export(export_settings $settings){
		$path = $settings->get_path();
		$mod = $settings->get_course_module();


		$manifest = new ImscpManifestWriter();
		$m = $manifest->add_manifest();
		$organization = $m->add_organizations()->add_organization();
		$resources = $m->add_resources();

		global $DB;
		$entries = $DB->get_records('glossary_entries', array('glossaryid'=>$mod->id));

		$href = $this->safe_name($mod->name).'.glossary/';
		mkdir("$path/$href/");
		foreach($entries as $entry){
			$name = $this->safe_name($entry->concept) . '.glossaryitem.html';
			$content = $this->format_entry($entry);
			file_put_contents("$path/$href/$name", $content);
			$this->add_submanifest_entry($manifest, $organization, $resources, $entry->concept, $name);
		}

		$this->export_file_areas($settings, $href);

		$manifest->save("$path/$href/imsmanifest.xml");
		$this->add_manifest_entry($settings, $mod->name, $href, 'glossary.imscp');
	}

	protected function format_entry($entry){
		$css = $this->get_main_css();
		$title = $entry->concept;
		$description = $entry->definition;
		$description = str_ireplace('<p>', '', $description);
		$description = str_ireplace('</p>', '', $description);
		$result = "<html><head>$css<title>$title</title></head><body>";
		$result .= '<div class="title">'.$title.'</div>';
		$result .= '<div class="description">'. $description . '</div>';
		$result .= '</body></html>';
		$result = str_replace('@@PLUGINFILE@@', 'resources', $result);
		return $result;
	}

	protected function add_submanifest_entry($manifest, $organization, $resources, $title, $href, $type = 'webcontent'){
		$id = $manifest->get_id_factory()->create_local_id('ID');
		$result = $resources->add_resource($type, $href, $id);
		$result->add_file($href);
		$organization->add_item($id)->add_title($title);
		return $result;
	}

}