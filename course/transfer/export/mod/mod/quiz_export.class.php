<?php

class quiz_export extends mod_export{

	public function export_path(){
		global $CFG;
		return $CFG->dirroot.'/question/format/imsqti21/main.php';
	}

	public function accept($module){
		if(!file_exists($this->export_path())){
			return false;
		}
		return parent::accept($module);
	}

	function export(export_settings $settings){
		$path = $settings->get_path();
		$mod = $settings->get_course_module();
		
		include_once($this->export_path());

		$href = $this->safe_name($mod->name). '.quiz.qti.zip';

		$export = new QtiExport();
		
		$export->add_quiz($mod);
		$export->save("$path/$href");
		$this->add_manifest_entry($settings, $mod->name, $href);	
	}

}