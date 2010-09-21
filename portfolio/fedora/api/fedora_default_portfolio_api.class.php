<?php

/**
 * Default API implementation for Fedora. Requests object's name and owner.
 * 
 * @copyright (c) 2010 University of Geneva 
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class fedora_default_portfolio_api extends api_base{
	
	public static function get_name(){
		return 'Fedora';
	}

	public function export_config_form(&$mform) {
		$size = 50;

		$mform->addElement('header','content_header', get_string('content', 'portfolio_fedora'));
		$mform->addElement('text', 'label', get_string('label', 'portfolio_fedora'), array('size' =>$size));
		$title = $this->get_default_title();
		$mform->setDefault('label', $title);
		$mform->addRule('label', get_string('field_required', 'portfolio_fedora'), 'required');

		//$mform->addElement('textarea', 'description', get_string('summary', 'portfolio_fedora'), array('cols' =>$size-2, 'rows'=>3));

		$mform->addElement('header', 'collaborator_header', get_string('collaborator', 'portfolio_fedora'));

		$mform->addElement('text', 'owner', get_string('owner', 'portfolio_fedora'), array('size' =>$size, 'readonly'=>'readonly'));
		$mform->setDefault('owner', $this->get_owner());
		$mform->addRule('owner', get_string('field_required', 'portfolio_fedora'), 'required');
	}

	public function get_export_summary(){
		$items = $this->get_allowed_export_config();
		$config = array();
		foreach($items as $item){
			if(isset($_POST[$item])){
				$config[$item] = $_POST[$item];
			}
		}		
		$portfolio = $this->get_portfolio();
		$portfolio->set_export_config($config);
		$result = array();
		foreach($config as $key=>$value){
			$result[get_string($key, 'portfolio_fedora')] = is_array($value) ? implode(', ', $value) : $value;
		}
		$result = empty($result) ? false : $result;
		return $result;
	}

	public function content_to_foxml($content, $meta, $export_config){
		return fedora_content_to_foxml($content, $meta);
	}
	
	public function get_allowed_export_config(){
		$result = array();
		$meta = new fedora_object_meta();
		foreach($meta as $key=>$value){
			$result[$key] = $key;
		}
		return $result;
	}

}