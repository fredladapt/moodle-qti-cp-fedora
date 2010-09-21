<?php

require_once($CFG->dirroot.'/repository/lib.php');
    
function xmldb_repository_fedora_install(){
    $plugin = new repository_type('fedora', array(), true);
    return (bool)$plugin->create(true);
}
