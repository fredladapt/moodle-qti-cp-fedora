<?php

/**
 * Upload a moodle file with id contained as a URL parameter i.e.
 *
 *  	http://hostname/moodle/.../file.php?id=xxx
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */

define('NO_DEBUG_DISPLAY', true);
error_reporting(-1);

require_once(dirname(__FILE__). '/../../config.php');
require_once($CFG->dirroot . '/lib/filelib.php');
include_once($CFG->dirroot.'/lib/debug_util.class.php');

require_login();

$id = optional_param('id', 0, PARAM_INT);
$fs = get_file_storage();
$file = $fs->get_file_by_id($id);
$filename = $file->get_filename();
header("Content-disposition: attachment; filename=$filename");
header("Content-type: application/octet-stream");
$file->readfile();
die;
