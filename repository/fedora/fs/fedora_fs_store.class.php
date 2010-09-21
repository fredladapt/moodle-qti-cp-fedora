<?php  

/**
 * A FS - file system - store. Used to put together several FS objects such as queries.
 * 
 * 
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class fedora_fs_store extends fedora_fs_folder{
	
	protected $title = '';
	protected $children = array();
	protected $elements = array();

	public function __construct($title){
		$this->title = $title;
	}
	
	public function aggregate($element){
		$this->elements[] = $element;
	}
	
	public function add($child){
		if(!isset($child->store_id)){
			$child->store_id = uniqid();
		}
		$this->children[$child->store_id] = $child;
	}
	
	public function find($id){
		if(isset($this->children[$id])){
			return $this->children[$id];
		}else{
			foreach($this->children as $child){
				if($result = $child->find($id)){
					return $resutl;
				}
			}
		}
		return false;
	}

	public function query($fedora){
		$result = $this->children;
		foreach($this->elements as $element){
			if($items = $element->query($fedora)){
				$result = array_merge($result, $items);
			}
		}
		return $result;
	}
}
