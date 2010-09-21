<?php

/**
 * Represent a Fedora objects. Returns the list of non-system datastreams belonging to this object. 
 * 
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class fedora_fs_object extends fedora_fs_base{

	protected $pid = 0;

	public function __construct($pid, $title, $date){
		$this->pid = $pid;
		$this->title = $title;
		$this->date = $date;
	}

	public function get_thumbnail(){
		global $CFG;
		$default = $CFG->wwwroot . '/repository/fedora/resource/object.png';
		return $this->get(__FUNCTION__, $default);
	}

	public function query($fedora){
		$result = array();
		$pid = $this->pid;
		$config = $fedora->get_config();
		$base_url = rtrim($config->get_base_url(), '/');
		$items = $fedora->list_datastreams($pid);
		foreach($items as $item){
			$dsID = $item['dsid'];
			$title = $item['label'];
			$mime_type = $item['mimeType'];
			$source = "$base_url/objects/$pid/datastreams/$dsID/content";
			$result[] = new fedora_fs_datastream($pid, $dsID, $title, $mime_type, $source);
		}

		return $result;
	}

	public function format($path = array()){
		$title = $this->get_title();
		if($title){
			$result = array(
		        		'title' => $title, 
						'shorttitle' => $title, 
		        		'date'=> $this->get_date(), 
		        		'size'=> $this->get_size(),        
		        		'thumbnail' => $this->get_thumbnail(),
						'children' =>array(),
						'path' => $this->get_path($path),
			);
		}else{
			$result = array();
		}
		return $result;
	}
}

