<?php

/**
 * Import glossary item html files as glossary entry objects.
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License
 * @author laurent.opprecht@unige.ch
 *
 */
class glossaryitem_html_import extends mod_import {

    public function get_extentions() {
        return array('glossaryitem.html');
    }

    public function accept($settings) {
        $path = $settings->get_path();
        $name = basename($path);
        $result = strpos($name, reset($this->get_extentions())) !== false;
        return $result;
    }

    protected function process_import(import_settings $settings) {
        global $DB;
        $result = $this->create($settings);
        $result->definition = '<p>' . $this->get_definition($settings, $result) . '</p>';
        $result->id = $DB->insert_record('glossary_entries', $result);

        $cm = $this->get_course_module($settings);
        $this->save_resources($settings, $cm, $result);

        return $result->id ? $result : false;
    }

    /**
     * Returns the course module record.
     *
     * @param import_settings $settings
     */
    protected function get_course_module(import_settings $settings) {
        global $DB;
        $course_id = $settings->get_course_id();
        $glossary_id = $settings->get_parent_id();

        $module = $DB->get_record('modules', array('name' => 'glossary'));
        $module_id = $module->id;
        $result = $DB->get_record('course_modules', array('instance' => $glossary_id, 'course' => $course_id, 'module' => $module_id));
        return $result;
    }

    protected function create(import_settings $settings) {
        global $USER, $CFG;

        $result = new stdClass();
        $result->resources = array();
        $result->glossaryid = $settings->get_parent_id();
        $result->userid = $USER->id;
        $result->concept = $this->get_title($settings);
        $result->definition = '';
        $result->definitionformat = FORMAT_HTML;
        $result->definitiontrust = 0;
        $result->attachment = '';
        $result->timecreated = $result->timemodified = time();
        $result->teacherentry = true;
        $result->sourceglossaryid = 0;
        $result->usedynalink = $CFG->glossary_linkentries;
        $result->casesensitive = false;
        $result->fullmatch = $CFG->glossary_fullmatch;
        $result->approved = true;
        return $result;
    }

    protected function get_definition(import_settings $settings, $data) {
        $result = $this->read($settings, 'description');
        $result = $this->translate($settings, $data, 'entry', $result);
        return $result;
    }

    /**
     * Save embeded resources. I.e. images
     *
     * @param import_settings $settings
     * @param object $cm
     * @param object $data
     */
    protected function save_resources(import_settings $settings, $cm, $data) {
        global $USER;

        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        $fs = get_file_storage();
        $component = 'mod_glossary';
        foreach ($data->resources as $resource) {
            $file_record = array(
                'contextid' => $context->id,
                'component' => $component,
                'filearea' => $resource['filearea'],
                'itemid' => $data->id,
                'filepath' => '/',
                'filename' => $resource['filename'],
                'userid' => $USER->id
            );
            try {
                $r = $fs->create_file_from_pathname($file_record, $resource['path']);
            } catch (Exception $e) {
                //debug($e);
            }
        }
    }

}

?>