<?php

global $CFG;
include_once $CFG->dirroot . '/mod/data/lib.php';

/**
 * Import a data page as a data module.
 * That is a page that follows the format of the data export module.
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License
 * @author laurent.opprecht@unige.ch
 *
 */
class data_import extends mod_import{

	/**
	 * Returns true if object can import $settings. False otherwise.
	 *
	 * @see course/transfer/import/mod/mod_import::accept()
	 */
	public function accept($settings){
		$path = $settings->get_path();
		$name = basename($path);
		$extentions = $this->get_extentions();
		foreach($extentions as $ext){
			if(strpos($name, $ext) !== false){
				return true;
			}
		}
		return false;
	}

	public function get_extentions(){
		return array('.table.html', '.table.htm');
	}

	protected function process_import($settings){
		$cid = $settings->get_course_id();
		$path = $settings->get_path();
		$filename = $settings->get_filename();

		$text = file_get_contents($path);

		$data = new StdClass();
		$data->course = $cid;
		$data->name = $this->read($settings->get_dom(), 'title');
		$data->intro = $this->read($settings->get_dom(), 'description');
		$data->introformat = FORMAT_HTML;
		$data->comments = 0;
		$data->timeavailablefrom = 0;
		$data->timeavailableto = 0;
		$data->timeviewfrom = 0;
		$data->timeviewto = 0;
		$data->requiredentries = 0;
		$data->requiredentriestoview = 0;
		$data->maxentries = 0;
		$data->rssarticles = 0;
		$data->singletemplate = null;
		$data->listtemplate = null;
		$data->listtemplateheader = '';
		$data->listtemplatefooter = '';
		$data->addtemplate = null;
		$data->rsstemplate = null;
		$data->rsstitletemplate = null;
		$data->csstemplate = null;
		$data->jstemplate = null;
		$data->asearchtemplate = null;
		$data->approval = 0;
		$data->scale = 0;
		$data->assessed = 0;
		$data->assesstimestart = $time = time();
		$data->assesstimefinish = $time;
		$data->defaultsort = 0;
		$data->defaultsortdir = 0;
		$data->editany = 0;
		$data->notification = 0;
		$result = $this->insert($settings, 'data', $data) ? $data : false;
		if(empty($result)){
			return false;
		}
		$this->get_context($data); //initialize  context;

		$fields = $this->process_header($settings, $data);
		if(empty($fields)){
			return false;
		}
		$this->process_body($settings, $data, $fields);

		data_generate_default_template($data, 'singletemplate');
		data_generate_default_template($data, 'listtemplate');
		data_generate_default_template($data, 'addtemplate');
		data_generate_default_template($data, 'asearchtemplate');           //Template for advanced searches.
		data_generate_default_template($data, 'rsstemplate');
		return $result;
	}

	// HEADER -- HEADER -- HEADER -- HEADER -- HEADER -- HEADER -- HEADER -- HEADER -- HEADER -- HEADER -- HEADER --

	/**
	 * Process table's header. I.e. the definition of the table
	 *
	 * @param $settings
	 * @param $data
	 * @return array of imported fields.
	 */
	protected function process_header(import_settings $settings, $data){
		$result = array();
		$doc = $settings->get_dom();
		$rows = $doc->getElementsByTagName('tr');
		$head = $rows->item(0);
		$cols = $head->getElementsByTagName('th');
		foreach($cols as $col){
			$name = $this->read($col, 'title');
			$description = $this->read($col, 'description');
			$type = $this->read($col, 'type');
			$options = $this->read_list($col, 'options');
			$f = array($this, 'process_header_' . $type);
			if(is_callable($f)){
				$field = call_user_func($f, $settings, $data, $name, $description, $options);
				$result[] = $field;
			}else{
				$field = $this->process_header_default($settings, $type, $name, $description, $options);
				$result[] = $field;
			}
		}
		return $result;
	}


	/**
	 * Default procedure for importing a header - i.e. a field's declaration.
	 *
	 * @param import_settings $settings
	 * @param object $data
	 * @param string $type
	 * @param string $name
	 * @param string $description
	 * @param array $options
	 */
	protected function process_header_default(import_settings $settings, $data, $type, $name, $description, $options){
		global $DB;

		$result = new object();
		$result->dataid = $data->id;
		$result->type = $type;
		$result->name = $name;
		$result->description = $description;
		$result->param1 = is_array($options) ? implode("\n", $options) : $options;
		$result->param2 = null;
		$result->param3 = null;
		$result->param4 = null;
		$result->param5 = null;
		$result->param6 = null;
		$result->param7 = null;
		$result->param8 = null;
		$result->param9 = null;
		$result->param10 = null;

		$result->id = $DB->insert_record('data_fields', $result);
		return $result;
	}

	/**
	 * Imports a checkbox header - i.e. a field's declaration.
	 *
	 * @param import_settings $settings
	 * @param string $type
	 * @param string $name
	 * @param string $description
	 * @param array $options
	 */
	protected function process_header_checkbox(import_settings $settings, $data, $name, $description, $options){
		$result = $this->process_header_default($settings, $data, end(explode('_', __FUNCTION__)), $name, $description, $options);
		return $result;
	}

	/**
	 * Imports a date header - i.e. a field's declaration.
	 *
	 * @param import_settings $settings
	 * @param string $type
	 * @param string $name
	 * @param string $description
	 * @param array $options
	 */
	protected function process_header_date(import_settings $settings, $data, $name, $description, $options){
		$result = $this->process_header_default($settings, $data, end(explode('_', __FUNCTION__)), $name, $description, $options);
		return $result;
	}

	/**
	 * Imports a file header - i.e. a field's declaration.
	 *
	 * @param import_settings $settings
	 * @param string $type
	 * @param string $name
	 * @param string $description
	 * @param array $options
	 */
	protected function process_header_file(import_settings $settings, $data, $name, $description, $options){
		global $CFG, $COURSE;
		$result = $this->process_header_default($settings, $data, end(explode('_', __FUNCTION__)), $name, $description, $options);
		$result->param2 = max($CFG->maxbytes, $COURSE->maxbytes);

		global $DB;
		$DB->update_record('data_fields', $result);
		return $result;
	}

	/**
	 * Imports a menu header - i.e. a field's declaration.
	 *
	 * @param import_settings $settings
	 * @param string $type
	 * @param string $name
	 * @param string $description
	 * @param array $options
	 */
	protected function process_header_menu(import_settings $settings, $data, $name, $description, $options){
		$result = $this->process_header_default($settings, $data, end(explode('_', __FUNCTION__)), $name, $description, $options);
		return $result;
	}

	/**
	 * Imports a multimenu header - i.e. a field's declaration.
	 *
	 * @param import_settings $settings
	 * @param string $type
	 * @param string $name
	 * @param string $description
	 * @param array $options
	 */
	protected function process_header_multimenu(import_settings $settings, $data, $name, $description, $options){
		$result = $this->process_header_default($settings, $data, end(explode('_', __FUNCTION__)), $name, $description, $options);
		return $result;
	}

	/**
	 * Imports a number header - i.e. a field's declaration.
	 *
	 * @param import_settings $settings
	 * @param string $type
	 * @param string $name
	 * @param string $description
	 * @param array $options
	 */
	protected function process_header_number(import_settings $settings, $data, $name, $description, $options){
		$result = $this->process_header_default($settings, $data, end(explode('_', __FUNCTION__)), $name, $description, $options);
		return $result;
	}

	/**
	 * Imports a radiobutton header - i.e. a field's declaration.
	 *
	 * @param import_settings $settings
	 * @param string $type
	 * @param string $name
	 * @param string $description
	 * @param array $options
	 */
	protected function process_header_radiobutton(import_settings $settings, $data, $name, $description, $options){
		$result = $this->process_header_default($settings, $data, end(explode('_', __FUNCTION__)), $name, $description, $options);
		return $result;
	}

	/**
	 * Imports a text header - i.e. a field's declaration.
	 *
	 * @param import_settings $settings
	 * @param string $type
	 * @param string $name
	 * @param string $description
	 * @param array $options
	 */
	protected function process_header_text(import_settings $settings, $data, $name, $description, $options){
		$result = $this->process_header_default($settings, $data, end(explode('_', __FUNCTION__)), $name, $description, $autolink = true);
		return $result;
	}

	/**
	 * Imports a textarea header - i.e. a field's declaration.
	 *
	 * @param import_settings $settings
	 * @param string $type
	 * @param string $name
	 * @param string $description
	 * @param array $options
	 */
	protected function process_header_textarea(import_settings $settings, $data, $name, $description, $options){
		$result = $this->process_header_default($settings, $data, end(explode('_', __FUNCTION__)), $name, $description, $options);
		$result->param2 = 60;
		$result->param3 = 35;
		$result->param4 = 1;

		global $DB;
		$DB->update_record('data_fields', $result);
		return $result;
	}

	/**
	 * Imports a url header - i.e. a field's declaration.
	 *
	 * @param import_settings $settings
	 * @param string $type
	 * @param string $name
	 * @param string $description
	 * @param array $options
	 */
	protected function process_header_url(import_settings $settings, $data, $name, $description, $options){
		$result = $this->process_header_default($settings, $data, end(explode('_', __FUNCTION__)), $name, $description, $autolink = true);
		return $result;
	}

	// PROCESS BODY -- PROCESS BODY -- PROCESS BODY -- PROCESS BODY -- PROCESS BODY -- PROCESS BODY -- PROCESS BODY --

	/**
	 * Process and import the body - i.e. content - of the table.
	 * Parses the table and calls the specialized import method based on the field's type.
	 *
	 * @param import_settings $settings
	 * @param object $data the module's DB record
	 * @param array $fields fields' definition
	 */
	protected function process_body(import_settings $settings, $data, $fields){
		$doc = $settings->get_dom();
		$list = $doc->getElementsByTagName('tbody');
		$body = $list->item(0);
		$rows = $body->getElementsByTagName('tr');
		foreach($rows as $row){
			$data_record = $this->get_data_record($data); //do not create datarecord if there are no rows to import
			$index = 0;
			$cells = $row->getElementsByTagName('td');
			foreach($cells as $cell){
				$field = $fields[$index];
				$type = $field->type;
				$f = array($this, 'process_data_' . $type);
				if(is_callable($f)){
					call_user_func($f, $settings, $field, $data_record, $cell);
				}else{
					$this->process_data_default($settings, $field, $data_record, $cell);
				}

				$index++;
			}
		}
	}

	private $_data_record = false;
	/**
	 * On first call creates as data_record record used to link data object with its content.
	 * On subsequent calls returns the previously created record.
	 *
	 * @param object $data
	 */
	protected function get_data_record($data){
		if($this->_data_record){
			return $this->_data_record;
		}

		global $USER, $DB;

		$this->_data_record = new object();
		$this->_data_record->id = null;
		$this->_data_record->userid = $USER->id;
		$this->_data_record->groupid = 0;
		$this->_data_record->dataid = $data->id;
		$this->_data_record->timecreated = $time = time();
		$this->_data_record->timemodified = $time;
		$this->_data_record->approved = true;
		$this->_data_record->id = $DB->insert_record('data_records', $this->_data_record);
		return $this->_data_record->id ? $this->_data_record : false;
	}

	/**
	 * Default import method for table's cell containing data.
	 *
	 * @param import_settings $settings import settings
	 * @param object $field field's definition
	 * @param object $data_record
	 * @param DOMElement|string $value value to store. Either a string or a DOMElement node.
	 * @param string $content1 additional content
	 * @param string $content2 additional content
	 * @param string $content3 additional content
	 * @param string $content4 additional content
	 */
	protected function process_data_default(import_settings $settings, $field, $data_record, $value, $content1 = null, $content2 = null, $content3 = null, $content4 = null){
		global $DB;

		$result = new object();
		$result->fieldid = $field->id;
		$result->recordid = $data_record->id;
		$result->content = $value instanceof DOMElement ? $this->get_innerhtml($value) : $value;
		$result->content1 = $content1;
		$result->content2 = $content2;
		$result->content3 = $content3;
		$result->content4 = $content4;

		$result->id = $DB->insert_record('data_content', $result);
		return $result->id ? $result : false;
	}

	/**
	 * Method for importing a checkbox data cell.
	 *
	 * @param import_settings $settings import settings
	 * @param object $field field's definition
	 * @param object $data_record
	 * @param DOMElement|string $value value to store. Either a string or a DOMElement node.
	 */
	protected function process_data_checkbox(import_settings $settings, $field, $data_record, $value){
		$result = $this->process_data_default($settings, $field, $data_record, $value);
		return $result;
	}

	/**
	 * Method for importing a date data cell.
	 *
	 * @param import_settings $settings import settings
	 * @param object $field field's definition
	 * @param object $data_record
	 * @param DOMElement|string $value value to store. Either a string or a DOMElement node.
	 */
	protected function process_data_date(import_settings $settings, $field, $data_record, $value){
		$value = $this->get_innerhtml($value);
		$parts = explode('.', $value);
		$d = $parts[0];
		$m = $parts[1];
		$y = $parts[2];
		$value = mktime(0, 0, 0, $m, $d, $y);
		$result = $this->process_data_default($settings, $field, $data_record, $value);
		return $result;
	}

	/**
	 * Method for importing a file data cell.
	 *
	 * @param import_settings $settings import settings
	 * @param object $field field's definition
	 * @param object $data_record
	 * @param DOMElement|string $value value to store. Either a string or a DOMElement node.
	 */
	protected function process_data_file(import_settings $settings, $field, $data_record, $value){
		$list = $value->getElementsByTagName('a');
		$a = $list->item(0);
		$href = $a->getAttribute('href');
		$text = $this->get_innerhtml($a);

		$result = $this->process_data_default($settings, $field, $data_record, $text);

		$file_record = new object();
		$file_record->contextid = $this->get_context()->id;
		$file_record->component = 'mod_data';
		$file_record->filearea = 'content';
		$file_record->itemid = $result->id;
		$file_record->filepath =  '/';
		$file_record->filename = $result->content;
		$fs = get_file_storage();
		$fs->create_file_from_pathname($file_record, dirname($settings->get_path()) .'/' . $href);

		return $result;
	}

	/**
	 * Method for importing a menu data cell.
	 *
	 * @param import_settings $settings import settings
	 * @param object $field field's definition
	 * @param object $data_record
	 * @param DOMElement|string $value value to store. Either a string or a DOMElement node.
	 */
	protected function process_data_menu(import_settings $settings, $field, $data_record, $value){
		$result = $this->process_data_default($settings, $field, $data_record, $value);
		return $result;
	}

	/**
	 * Method for importing a multimenu data cell.
	 *
	 * @param import_settings $settings import settings
	 * @param object $field field's definition
	 * @param object $data_record
	 * @param DOMElement|string $value value to store. Either a string or a DOMElement node.
	 */
	protected function process_data_multimenu(import_settings $settings, $field, $data_record, $value){
		$value = $this->get_innerhtml($value);
		$parts = explode('<br/>', $value);

		$items = array();
		$options = explode("\n", $field->param1);
		foreach($options as $option){
			$items[$option] = '#';
		}
		foreach($parts as $part){
			$items[$part] = $part;
		}
		$value = implode('', $items);

		$result = $this->process_data_default($settings, $field, $data_record, $value);
		return $result;
	}

	/**
	 * Method for importing a number data cell.
	 *
	 * @param import_settings $settings import settings
	 * @param object $field field's definition
	 * @param object $data_record
	 * @param DOMElement|string $value value to store. Either a string or a DOMElement node.
	 */
	protected function process_data_number(import_settings $settings, $field, $data_record, $value){
		$result = $this->process_data_default($settings, $field, $data_record, $value);
		return $result;
	}

	/**
	 * Method for importing a radiobutton data cell.
	 *
	 * @param import_settings $settings import settings
	 * @param object $field field's definition
	 * @param object $data_record
	 * @param DOMElement|string $value value to store. Either a string or a DOMElement node.
	 */
	protected function process_data_radiobutton(import_settings $settings, $field, $data_record, $value){
		$result = $this->process_data_default($settings, $field, $data_record, $value);
		return $result;
	}

	/**
	 * Method for importing a text data cell.
	 *
	 * @param import_settings $settings import settings
	 * @param object $field field's definition
	 * @param object $data_record
	 * @param DOMElement|string $value value to store. Either a string or a DOMElement node.
	 */
	protected function process_data_text(import_settings $settings, $field, $data_record, $value){
		$result = $this->process_data_default($settings, $field, $data_record, $value);
		return $result;
	}

	/**
	 * Method for importing a textarea data cell.
	 *
	 * @param import_settings $settings import settings
	 * @param object $field field's definition
	 * @param object $data_record
	 * @param DOMElement|string $value value to store. Either a string or a DOMElement node.
	 */
	protected function process_data_textarea(import_settings $settings, $field, $data_record, $value){
		$result = $this->process_data_default($settings, $field, $data_record, $value, true);
		return $result;
	}

	/**
	 * Method for importing a url data cell.
	 *
	 * @param import_settings $settings import settings
	 * @param object $field field's definition
	 * @param object $data_record
	 * @param DOMElement|string $value value to store. Either a string or a DOMElement node.
	 */
	protected function process_data_url(import_settings $settings, $field, $data_record, $value){
		$list = $value->getElementsByTagName('a');
		$a = $list->item(0);
		$href = $a->getAttribute('href');
		$text = $this->get_innerhtml($a);
		$result = $this->process_data_default($settings, $field, $data_record, $href, $text);
		return $result;
	}

	// UTIL -- UTIL -- UTIL -- UTIL -- UTIL -- UTIL -- UTIL -- UTIL -- UTIL -- UTIL -- UTIL -- UTIL -- UTIL -- UTIL -- UTIL --

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

	/**
	 * Fetch first child of $el with class name equals to $name and returns all li in an array.
	 *
	 * @param DOMElement $el
	 * @param string $name
	 */
	protected function read_list($el, $name){
		$result = array();
		$list = $el->getElementsByTagName('div');
		foreach($list as $div){
			if(strtolower($div->getAttribute('class')) == $name){
				$lis = $el->getElementsByTagName('li');
				foreach($lis as $li){
					$result[] = trim(html_entity_decode($this->get_innerhtml($li)));
				}
			}
		}
		return $result;
	}

	/**
	 * Fetch first child of $el with class name equals to $name and returns its inner html.
	 * Returns $default if no child is found.
	 *
	 * @param DOMElement $el
	 * @param string $name
	 * @param string $default
	 */
	protected function read($el, $name, $default = ''){
		$list = $el->getElementsByTagName('div');
		foreach($list as $div){
			if(strtolower($div->getAttribute('class')) == $name){
				$result = $this->get_innerhtml($div);
				$result = str_ireplace('<p>', '', $result);
				$result = str_ireplace('</p>', '', $result);
				return $result;
			}
		}
		return $default;
	}
}