<?php

class wiki_export extends mod_export{

	function export(export_settings $settings){
		$path = $settings->get_path();
		$mod = $settings->get_course_module();

		$manifest = new ImscpManifestWriter();
		$m = $manifest->add_manifest();
		$organization = $m->add_organizations()->add_organization();
		$resources = $m->add_resources();

		global $DB;
		$subwiki = $DB->get_record('wiki_subwikis', array('wikiid'=>$mod->id));
		$pages = $DB->get_records('wiki_pages', array('subwikiid'=>$subwiki->id));

		$href = $this->safe_name($mod->name).'.wiki/';
		mkdir("$path/$href/");
		foreach($pages as $page){
			$name = $this->safe_name($page->title) . '.wikipage.html';
			$content = $this->format_page($settings, $page);
		 	file_put_contents("$path/$href/$name", $content);
		 	$this->add_submanifest_entry($manifest, $organization, $resources, $page->title, $name);
		}

		$this->export_file_areas($settings, $href);

		$this->add_manifest_entry($settings, $mod->name, $href, 'wiki.imscp');
		$manifest->save("$path/$href/imsmanifest.xml");
		return;
	}

	protected function format_page(export_settings $settings, $page){
		$css = $this->get_main_css();
		$title = $page->title;
		$description = $page->cachedcontent;
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