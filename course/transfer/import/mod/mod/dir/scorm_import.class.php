<?php

global $CFG;
require_once $CFG->dirroot . '/mod/scorm/lib.php';
require_once $CFG->dirroot . '/mod/scorm/locallib.php';

class scorm_import extends mod_import {

    public function get_weight() {
        return 10;
    }

    public function get_extentions() {
        return array();
    }

    public function accept(import_settings $settings) {
        $manifest = $settings->get_manifest_reader();
        $name = $manifest->get_root()->name();
        $location = $manifest->get_root()->get_attribute('xsi:schemaLocation');
        return $name == 'manifest' && strpos($location, 'http://www.adlnet.org') !== false;
    }

    protected function process_import($settings) {
        $cid = $settings->get_course_id();
        $path = $settings->get_path();
        $filename = $settings->get_filename();

        $cfg_scorm = get_config('scorm');
        $data = new stdClass();
        $data->resources = array();
        $data->name = empty($filename) ? basename($path) : trim_extention($filename);
        $data->intro = $data->name;
        $data->scormtype = SCORM_TYPE_LOCAL;
        $data->timeopen = 0;
        $data->timeclose = 0;
        $data->grademethod = GRADEHIGHEST;
        $data->maxgrade = $cfg_scorm->maxgrade;
        $data->maxattempt = $cfg_scorm->maxattempts;
        $data->whatgrade = $cfg_scorm->whatgrade;
        $data->displayattemptstatus = $cfg_scorm->displayattemptstatus;
        $data->forcecompleted = $cfg_scorm->forcecompleted;
        $data->forcenewattempt = $cfg_scorm->forcenewattempt;
        $data->lastattemptlock = $cfg_scorm->lastattemptlock;
        $data->width = $cfg_scorm->framewidth;
        $data->height = $cfg_scorm->frameheight;
        $data->popup = $cfg_scorm->popup;
        $data->skipview = $cfg_scorm->skipview;
        $data->hidebrowse = $cfg_scorm->hidebrowse;
        $data->displaycoursestructure = $cfg_scorm->displaycoursestructure;
        $data->hidetoc = $cfg_scorm->hidetoc;
        $data->hidenav = $cfg_scorm->hidenav;
        $data->auto = $cfg_scorm->auto;
        $data->updatefreq = $cfg_scorm->updatefreq;

        $cm = $this->insert($settings, 'scorm', $data);

        $context = get_context_instance(CONTEXT_MODULE, $cm->id);

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_scorm', 'package');

        $files = array();
        $entries = scandir($path);
        $entries = array_diff($entries, array('.', '..'));
        foreach ($entries as $entry) {
            $files[$entry] = $path . '/' . $entry;
        }


        $zipper = new zip_packer();
        $result = $zipper->archive_to_storage($files, $context->id, 'mod_scorm', 'package', 0, '/', $filename);
        global $DB;
        $data = $DB->get_record('scorm', array('id' => $data->id), '*', MUST_EXIST);
        $data->reference = $filename;
        $data->course = $cid;
        $data->cmidnumber = $cm->instance;
        $data->cmid = $cm->id;

        scorm_parse($data, true);
        scorm_grade_item_update($data);

        return $data;
    }

}

