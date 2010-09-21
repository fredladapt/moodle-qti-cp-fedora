<?php

/**
 * @link http://www.switch.ch/
 */

/**
 * Metadata used by the SWITCH collections implementation of Fedora.
 * 
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class SWITCH_object_meta{

	public $title = '';
	public $creator = array();
	public $accessRights = 'private';
	public $rights = 'private';
	public $license = '';
	public $discipline = array();
	public $aaid = '';

	public $contributor = array();
	public $publisher = array();
	public $rightsHolder = '';
	public $tableOfContents = '';
	public $abstract = '';
	public $subject = array();
	public $description = array();
	public $language = array();
	public $educationLevel = array();
	public $instructionalMethod = array();
	public $issued = '';
	public $alternative = array();
	public $type = '';
	public $extent = array();
	public $source = '';
	public $identifier = '';

	public $collections = array();
}

/**
 * Transform binary content and metadata to a FOXML formated file that can be ingested into Fedora.
 * 
 * @param $content binary content 
 * @param $meta standard metadata used by fedora
 * @param $switch additional SWITCH collections metadata
 */
function SWITCH_content_to_foxml($content, fedora_object_meta $meta, SWITCH_object_meta $switch = null){
	$meta->lastModifiedDate = $date = time();

	$w = new FoxmlWriter();
	$o = $w->add_digitalObject($meta->pid);
	$w = $o->add_objectProperties();
	$w->add_state('Active');
	$w->add_label($meta->label, false);
	$w->add_ownerId($meta->owner, false);
	$w->add_createdDate($meta->createdDate);
	$w->add_lastModifiedDate($meta->lastModifiedDate);

	// SWITCH CHOR_DC
	$w = $o->add_datastream('CHOR_DC', 'A', 'X', true);
	$w = $w->add_datastreamVersion('CHOR_DC1.0', 'SWITCH CHOR_DC record for this object', $date, 'text/xml', 'http://www.openarchives.org/OAI/2.0/oai_dc/');
	$w = $w->add_switch_dc();
	foreach($switch as $key=>$value){
		if($key != 'collections' && $key !='rights' && $key !='accessRights' && $key !='owner'){
			$f = "add_dc_$key";
			$w->$f($value);
		}
	}
	if(isset($switch->rights)){
		if($switch->rights == 'institution'){
			$owner = $meta->owner;
			$parts = explode('@', $owner);
			$value = count($parts) > 1 ? $parts[1] : '';
		}else{
			$value = '';
		}
		$w->add_dc_rights($switch->rights, $value);
	}

	if(isset($switch->accessRights)){
		if($switch->accessRights == 'institution'){
			$owner = $meta->owner;
			$parts = explode('@', $owner);
			$value = count($parts) > 1 ? $parts[1] : '';
		}else{
			$value = '';
		}
		$w->add_dc_accessRights($switch->accessRights, $value);
	}

	//RELS-EXT
	$w = $o->add_datastream('RELS-EXT', 'A', 'X', false);
	$w = $w->add_datastreamVersion('RELS-EXT1.0', 'Relationships to other objects', $date, 'application/rdf+xml', 'info:fedora/fedora-system:FedoraRELSExt-1.0');
	$w = $w->add_rels_ext();
	$w = $w->add_rel_description("info:fedora/{$meta->pid}");
	$w->add_hasModel('info:fedora/fedora-system:FedoraObject-3.0');
	$w->add_hasModel('info:fedora/LORmodel:object');
	$w->add_hasModel('info:fedora/LORmodel:collection');
	$collections = $switch->collections;
	$collections = is_array($collections) ? $collections : array();
	$collections = array_merge($meta->collections, $collections);
	foreach($collections as $collection){
		$w->add_rel_isMemberOfCollection($collection);
	}
	//@todo: add pid prefix unige?
	$w->add_oai_itemID($meta->pid);

	//Object
	if($content){
		$w = $o->add_datastream('DS1', 'A', 'M', true);
		$w = $w->add_datastreamVersion('DS11.0', $meta->label, $meta->lastModifiedDate, $meta->mime);
		$w->add_binaryContent($content);
	}

	//@todo: change that
	//$o->save("C:\\Documents and Settings\\lo\\Bureau\\text.xml");
	$result = $o->saveXML();
	return $result;

}
