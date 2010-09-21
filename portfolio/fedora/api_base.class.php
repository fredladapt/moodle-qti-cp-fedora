<?php

/**
 * Base class for API classes. A Fedora API implements the requirements of a specific Fedora implementation for the Fedora portfolio.
 * For example required metadata, etc.
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class api_base{

	public static function get_name(){
		return 'API Base';
	}

	protected $portfolio = NULL;

	public function __construct($portfolio){
		$this->portfolio = $portfolio;
	}

	public function get_portfolio(){
		return $this->portfolio;
	}

	public function get_fedora(){
		return $this->portfolio->get_fedora();
	}

	public function get_owner(){
		global $USER;
		if(!empty($USER->idnumber)){
			return $USER->idnumber;
		}else{
			return $USER->email;
		}
	}

	/**
	 * Returns the default title. That is the temporary file name. 
	 */
	public function get_default_title(){
		$result = '';
		try{
			//when called from temp file exporter
			$fileid = $this->get_portfolio()->get('exporter')->get('caller')->get('fileid');
			if(!empty($fileid)){
				$fs = get_file_storage();
				$file = $fs->get_file_by_id($fileid);
				$result = $file->get_filename();
				if($pos = strpos($result, '.')){
					$result = substr($result, 0, $pos);
				}
			}
		}catch(Exception $e){
			$result ='';
		}
		return $result;
	}

	public function has_export_config(){
		return true;
	}

	public function get_allowed_export_config(){
		$result = array();
		$meta = new fedora_object_meta();
		foreach($meta as $key=>$value){
			$result[$key] = $key;
		}
		return $result;
	}

	public function export_config_form(&$mform) {
		//do nothing
	}

	public function get_export_summary(){
		$portfolio = $this->get_portfolio();

		$items = $this->get_allowed_export_config();
		$config = array();
		foreach($items as $item){
			if(isset($_POST[$item])){
				$config[$item] = $_POST[$item];
			}
		}
		$portfolio->set_export_config($config);
		$result = array();
		foreach($config as $key=>$value){
			$result[get_string($key, 'portfolio_fedora')] = is_array($value) ? implode(', ', $value) : $value;
		}
		$result = empty($result) ? false : $result;
		return $result;
	}

	public function prepare_package() {
		return true;// we send the files as they are, no prep required
	}

	public function get_interactive_continue_url(){
		return false;
	}

	public function expected_time($callertime) {
		return $callertime; // we trust what the portfolio says
	}

	public function content_to_foxml($content, $meta, $export_config){
		return fedora_content_to_foxml($content, $meta);
	}
	
	public function send_package() {
		$result = array();
		
		$portfolio = $this->get_portfolio();
		$fedora = $this->get_fedora();
		$exportconfig = $portfolio->get('exportconfig');
		
		$files = $portfolio->get('exporter')->get_tempfiles(); 
		foreach($files as $file){
			$meta = new fedora_object_meta();
			$meta->pid = $fedora->get_nextPID();
			$meta->label = isset($exportconfig['title']) ? $exportconfig['title'] : $file->get_filename();
			$meta->mime = $file->get_mimetype();
			$meta->owner = isset($exportconfig['owner']) ? $exportconfig['owner'] : $this->get_owner();

			$content = $file->get_content();

			$foxml = $this->content_to_foxml($content, $meta, $exportconfig);

			$result[] = $fedora->ingest($foxml, $meta->pid, $meta->label, $meta->owner);
		}
		return true;
	}

}















