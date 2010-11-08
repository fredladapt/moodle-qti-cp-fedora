<?php

/**
 * Export a data object as an html page with a .table.html extention.
 * The data object is encoded as an HTML TABLE tag.
 * Encode module's properties inside div tags with a class attribute to allow content's extraction.
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License
 * @author laurent.opprecht@unige.ch
 *
 */
class data_export extends mod_export{

	/**
	 * @var export_settings
	 */
	protected $settings = false;

	function export(export_settings $settings){
		$this->settings = $settings;
		$path = $settings->get_path();
		$mod = $settings->get_course_module();

		$content = $this->format_page($mod);
		$href = $this->safe_name($mod->name).'.table.html';

		$this->export_file_areas($settings);

		$this->add_manifest_entry($settings, $mod->name, $href);
		$this->context = false;
		$this->settings = false;
		return file_put_contents("$path/$href", $content);
	}


	private $_context = false;
	/**
	 * Returns the context used to store files.
	 * On first call construct the result based on $mod.
	 * On subsequent calls return the cached value.
	 *
	 * @param $mod
	 */
	protected function get_context($mod=null){
		if($this->_context){
			return $this->_context;
		}

		global $DB;
		$module = $DB->get_record('modules', array('name'=>'data'),'*', MUST_EXIST);
		$cm = $DB->get_record('course_modules', array('course'=>$mod->course, 'instance'=>$mod->id, 'module'=>$module->id),'*', MUST_EXIST);
		$this->_context = get_context_instance(CONTEXT_MODULE, $cm->id);
		return $this->_context;
	}

	protected function format_page($mod){
		global $DB;

		$this->get_context($mod);//initialize get_context;

		$css = $this->get_main_css();
		$title = $mod->name;
		$description = $mod->intro;
		$description = str_ireplace('<p>', '', $description);
		$description = str_ireplace('</p>', '', $description);

		$data_record = $DB->get_record('data_records', array('dataid'=>$mod->id),'*', MUST_EXIST);
		$data = $DB->get_records('data_content', array('recordid'=>$data_record->id), 'recordid, fieldid');
		$fields = $DB->get_records('data_fields', array('dataid'=>$mod->id));

		$result = "<html><head>$css<title>$title</title></head><body>";
		$result .= $this->format_field($title, 'title');
		$result .= $this->format_field($description, 'description');
		$result .= '<table>';
		$result .= $this->format_header($fields);
		$result .= $this->format_tbody($data, $fields);
		$result .= '</table></body></html>';
		return $result;
	}

	/**
	 * Format the table's header
	 *
	 * @param $fields
	 */
	protected function format_header($fields){
		$result = '<tr>';
		foreach($fields as $field){
			$result .= '<th>' ;
			$f = array($this, 'format_header_' . $field->type);
			if(is_callable($f)){
				$result .= call_user_func($f, $field);
			}else{
				$result .= $this->format_header_default($field);
			}
			$result .= '</th>';
		}
		$result .= '</tr>';
		return $result;
	}

	/**
	 * Format the table's body
	 *
	 * @param unknown_type $data
	 * @param unknown_type $fields
	 */
	protected function format_tbody($data, $fields){
		if(empty($data)){
			return '';
		}

		$result = '<tbody><tr>';
		$recordid = -1;
		foreach($data as $item){
			if($item->recordid != $recordid){
				if($recordid != -1){
					$result .= '</tr><tr>';
				}
				$recordid = $item->recordid;
			}
			$result .= $this->format_cell($item, $fields);
		}

		$result .= '</tr></tbody>';
		return $result;
	}

	/**
	 * Format a table's cell
	 *
	 *
	 * @param unknown_type $item
	 * @param unknown_type $fields
	 */
	protected function format_cell($item, $fields){
		$result = '';
		$result .= '<td>' ;
		$field = $fields[$item->fieldid];
		$f = array($this, 'format_data_' . $field->type);
		if(is_callable($f)){
			$result .= call_user_func($f, $item);
		}else{
			$result .= $item->content;
		}
		$result .= '</td>';

		return $result;
	}

	// FORMAT DATA FIELD -- FORMAT DATA FIELD -- FORMAT DATA FIELD -- FORMAT DATA FIELD -- FORMAT DATA FIELD --

	/**
	 * Format a checkbox field
	 *
	 *
	 * @param unknown_type $field
	 */
	protected function format_data_checkbox($field){
		return $field->content;
	}

	/**
	 * Format a date field
	 *
	 * @param unknown_type $field
	 */
	protected function format_data_date($field){
		return date('j.n.Y', $field->content);
	}

	/**
	 * Format a file field
	 *
	 * @param unknown_type $field
	 */
	protected function format_data_file($content){
		global $DB;

		$fs = get_file_storage();
		if($file = $fs->get_file($this->get_context()->id, 'mod_data', 'content', $content->id, '/', $content->content)) {
			$href = urlencode($file->get_filename());
			$path = $this->settings->get_path() . '/' . $href;

			$file->copy_content_to($path);
			$href = 'href="' . $href . '"';

			$result = "<a $href>{$content->content}</a>";

		}else{
			$result = '';
		}
		return $result;
	}

	/**
	 * Format a menu field
	 *
	 * @param unknown_type $field
	 */
	protected function format_data_menu($field){
		return $field->content;
	}

	/**
	 * Format a multimenu field. I.e. a menu were you can select multiple values.
	 *
	 * @param unknown_type $field
	 */
	protected function format_data_multimenu($field){
		$parts = explode('#', $field->content);
		$options = array();
		foreach($parts as $part){
			if($part){
				$options[] = $part;
			}
		}
		$result = implode('<br/>', $options);
		return $result;
	}

	/**
	 * Format a number field
	 *
	 * @param unknown_type $field
	 */
	protected function format_data_number($field){
		return $field->content;
	}

	/**
	 * Format a radio button field
	 *
	 * @param unknown_type $field
	 */
	protected function format_data_radiobutton($field){
		return $field->content;
	}

	/**
	 * Format a text field
	 *
	 * @param unknown_type $field
	 */
	protected function format_data_text($field){
		return $field->content;
	}

	/**
	 * Format a text area field
	 *
	 * @param unknown_type $field
	 */
	protected function format_data_textarea($field){
		return $field->content;
	}

	/**
	 * Format a url field
	 *
	 * @param unknown_type $field
	 */
	protected function format_data_url($field){
		$href = 'href="' . $field->content . '"';
		$title = $field->content1;
		return "<a $href>$title</a>";
	}

	//FORMAT HEADER FIELD -- FORMAT HEADER FIELD -- FORMAT HEADER FIELD -- FORMAT HEADER FIELD -- FORMAT HEADER FIELD --

	/**
	 * Default format function for header fields
	 *
	 * @param unknown_type $field
	 */
	protected function format_header_default($field){
		$result = '';
		$result .= $this->format_field($field->name, 'title', $field->description);
		$result .= $this->format_field($field->description, 'description', '', false);
		$result .= $this->format_field($field->type, 'type', '', false);
		return $result;
	}

	/**
	 * Format the header of a checkbox field
	 *
	 * @param unknown_type $field
	 */
	protected function format_header_checkbox($field){
		$result = $this->format_header_default($field);

		$options = $field->param1;
		$options = explode("\n", $options);

		$options_list = '<ul>';
		foreach($options as $option){
			$options_list .= "<li>$option</li>";
		}
		$options_list .= '</ul>';

		$result .= $this->format_field($options_list, 'options', '', false);
		return $result;
	}

	/**
	 * Format the header of a date field
	 *
	 * @param $field
	 */
	protected function format_header_date($field){
		return $this->format_header_default($field);
	}

	/**
	 * Format the header of a file field
	 *
	 * @param unknown_type $field
	 */
	protected function format_header_file($field){
		return $this->format_header_default($field);
	}

	/**
	 * Format the header of a menu field
	 *
	 * @param unknown_type $field
	 */
	protected function format_header_menu($field){
		$result = $this->format_header_default($field);

		$options = $field->param1;
		$options = explode("\n", $options);

		$options_list = '<ul>';
		foreach($options as $option){
			$options_list .= "<li>$option</li>";
		}
		$options_list .= '</ul>';

		$result .= $this->format_field($options_list, 'options', '', false);
		return $result;
	}

	/**
	 * Format the header of a multimenu field
	 *
	 * @param unknown_type $field
	 */
	protected function format_header_multimenu($field){
		$result = $this->format_header_default($field);

		$options = $field->param1;
		$options = explode("\n", $options);

		$options_list = '<ul>';
		foreach($options as $option){
			$options_list .= "<li>$option</li>";
		}
		$options_list .= '</ul>';

		$result .= $this->format_field($options_list, 'options', '', false);
		return $result;
	}

	/**
	 * Format the header of a number field
	 *
	 * @param unknown_type $field
	 */
	protected function format_header_number($field){
		return $this->format_header_default($field);
	}

	/**
	 * Format the header of a radiobutton field
	 *
	 * @param unknown_type $field
	 */
	protected function format_header_radiobutton($field){
		$result = $this->format_header_default($field);

		$options = $field->param1;
		$options = explode("\n", $options);

		$options_list = '<ul>';
		foreach($options as $option){
			$options_list .= "<li>$option</li>";
		}
		$options_list .= '</ul>';

		$result .= $this->format_field($options_list, 'options', '', false);
		return $result;
	}

	/**
	 * Format the header of a text field
	 *
	 * @param unknown_type $field
	 */
	protected function format_header_text($field){
		return $this->format_header_default($field);
	}

	/**
	 * Format the header of a textarea field
	 *
	 * @param unknown_type $field
	 */
	protected function format_header_textarea($field){
		return $this->format_header_default($field);
	}

	/**
	 * Format the header of a url field
	 *
	 * @param unknown_type $field
	 */
	protected function format_header_url($field){
		return $this->format_header_default($field);
	}



	/**
	 * Format a field
	 *
	 * @param string $value the field's value to be written
	 * @param string $class the css class/field's name
	 * @param bool $visible true if the field is to be visible, false otherwise
	 */
	protected function format_field($value, $class = '', $title = '', $visible = true){
		$style = $visible ? '' : 'style="display:none"';
		$class = $class ? 'class="' . $class . '"' : '';
		$title = $title ? 'title="' . $title . '"' : '';
		$result = "<div $title $class $style>$value</div>";
		return $result;
	}

}














