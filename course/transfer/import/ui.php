<?php

require_once(dirname(__FILE__) . '/lib.php');

class transfer_import_form extends moodleform {

    function definition() {
        $mform = & $this->_form;
        $mform->addElement('header', 'content_header', get_string('content', 'block_transfer'));
        $mform->addElement('hidden', 'course_id');
        $course_id = optional_param('course_id', 0, PARAM_INT);
        $mform->setDefault('course_id', $course_id);
        $mform->addElement('filepicker', 'file', get_string('file', 'block_transfer'));
        $mform->setDefault('file', 0);
        $mform->addRule("file", '', 'required');

        $sections = get_all_sections($course_id);
        $items = array();
        foreach ($sections as $section) {
            $items[$section->id] = empty($section->name) ? $section->section : $section->name;
        }
        $mform->addElement('select', 'section', get_string('section', 'block_transfer'), $items);
        $mform->addRule('section', '', 'required');

        $mform->addElement('static', '<br/>');
        $mform->addElement('submit', 'submitbutton', get_string('import', 'block_transfer'));
    }

    function get_mimetype($elname) {
        global $USER;

        if (!$this->is_submitted() or !$this->is_validated()) {
            return false;
        }

        $element = $this->_form->getElement($elname);

        if ($element instanceof MoodleQuickForm_filepicker || $element instanceof MoodleQuickForm_filemanager) {
            $values = $this->_form->exportValues($elname);
            if (empty($values[$elname])) {
                return false;
            }
            $draftid = $values[$elname];
            $fs = get_file_storage();
            $context = get_context_instance(CONTEXT_USER, $USER->id);
            if (!$files = $fs->get_area_files($context->id, 'user', 'draft', $draftid, 'id DESC', false)) {
                return false;
            }
            $file = reset($files);

            return $file->get_mimetype();
        }

        return false;
    }

    /**
     * Returns a temporary file, do not forget to delete after not needed any more.
     *
     * @param string $elname
     * @return string or false
     */
    function save_temp_file($elname) {
        if (!$this->get_new_filename($elname)) {
            return false;
        }
        if (!$dir = make_upload_directory('temp/forms')) {
            return false;
        }
        if (!$tempfile = tempnam($dir, 'tempup_')) {
            return false;
        }
        if (!$this->save_file($elname, $tempfile, true)) {
            // something went wrong
            @unlink($tempfile);
            return false;
        }

        return $tempfile;
    }

}