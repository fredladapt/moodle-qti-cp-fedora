<?php



global $CFG;

require_once dirname(__FILE__) . '/rest/fedora/fedora_proxy.class.php';
require_once dirname(__FILE__) . '/util/debug_util.class.php';
$fedora = new FedoraProxy();
$config = $fedora->get_config();
$base_url = rtrim($config->get_base_url(), '/');
$dsID = $config->get_object_datastream_name();
$objects = $fedora->list_objects();
debug($objects);die;


$list = array();
foreach($objects as $object){
	$title = $object[FedoraProxy::OBJECT_TITLE];
	if(!empty($title)){
		$pid = $object[FedoraProxy::OBJECT_ID];
		$list[] = array(
		        		'title'=>$title, 
		        		'date'=>$object[FedoraProxy::OBJECT_MODIFICATION_DATE], 
		        		'size'=>'1mb', //$object[FedoraProxy::], 
		        		'source'=>$pid,  
		        		'url'=> "$base_url/objects/$pid/datastreams/$dsID/content",       
		        		'thumbnail' => '',
		);
	}
}
 

debug($list);
debug($objects);