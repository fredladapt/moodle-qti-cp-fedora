<?php

class course_module_selection_form{

	function display($id){
		global $OUTPUT, $CFG, $COURSE, $OUTPUT;
		$modinfo =& get_fast_modinfo($COURSE);
		$export = course_export::factory();
		$modules = get_course_modules($id);
		$result = '<form method="post">';
		$sections = get_all_sections($id);
		foreach($sections as $section){
			if(!empty($section->sequence)){
				$title = ($section->section+1). ' '. $section->name;
				if(!empty($title)){
					$result .= $OUTPUT->heading($title, 2, 'headingblock header outline');
				}
				$result .=  $OUTPUT->box_start();
				$result .= '<div style="padding-left:15px;" class="section clearfix" >';

				$ids = explode(',', $section->sequence);
				foreach($ids as $id){
					if(isset($modules[$id])){
						$module = $modules[$id];
						$cm = get_coursemodule_from_instance($module->name, $module->instance, $module->course, false, IGNORE_MISSING);
						if($cm){
							if($modinfo->cms && isset($modinfo->cms[$cm->id])){
								$icon = @$modinfo->cms[$cm->id]->icon;
							}
							$img_href = empty($icon) ? '' : $OUTPUT->pix_url($icon);
							$img_href = empty($img_href) ? $CFG->wwwroot . '/mod/' .$module->name . '/pix/icon.gif' : $img_href;
							$img = '<img src="'.$img_href.'"> </img>';

							$module_href = $CFG->wwwroot . "/mod/$module->name/view.php?id=$cm->id";
							$module_a = '<a href="'.$module_href.'">'.$cm->name.'</a> ';
							$name = "export_$id";
							if($export->accept($module)){
								$checked = isset($_POST[$name]) && !empty($_POST[$name]);
								$result .=  '<input type=checkbox name="'.$name.'"'. ($checked?'checked="yes"':'') .'> '.$img.$module_a.'</input><br/>';
							}else{
								$result .=  '<input disabled="true" type=checkbox name="'.$name.'"> '.$img.$cm->name.'</input><br/>';
							}
						}
					}
				}

				$result .= '</div>';
				$result .=  $OUTPUT->box_end();
			}
		}
		$result .= '<input type=submit value="'. get_string('export', 'block_transfer')  .'">';
		$result .= '<input type=hidden name="action" value="export">';
		$result .= '</form>';
		return $result;
	}

	function modules(){
		$result = array();
		foreach($_POST as $key=>$value){
			if(substr($key, 0, 7) == 'export_'){
				$key = ltrim($key, 'export_');
				$result[$key] = $key;
			}
		}
		return $result;
	}

	function is_valid(){
		return (bool)$this->modules();
	}
}