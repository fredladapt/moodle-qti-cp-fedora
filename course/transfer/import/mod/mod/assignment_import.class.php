<?php

/**
 * Import an assignment html file as an Assignment object.
 *
 *
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License
 * @author laurent.opprecht@unige.ch
 *
 */
class assignment_import extends mod_import {

    public function accept($settings) {
        $path = $settings->get_path();
        $name = basename($path);
        $extentions = $this->get_extentions();
        foreach ($extentions as $ext) {
            if (strpos($name, $ext) !== false) {
                return true;
            }
        }
        return false;
    }

    public function get_extentions() {
        return array('.upload.assignment.html', '.upload.assignment.htm', '.uploadsingle.assignment.html', '.uploadsingle.assignment.htm', '.online.assignment.html', '.online.assignment.htm', '.offline.assignment.html', '.offline.assignment.htm');
    }

    protected function process_import($settings) {
        global $CFG, $COURSE;

        $cid = $settings->get_course_id();
        $path = $settings->get_path();
        $filename = $settings->get_filename();

        $parts = explode('.', $path);
        $assignmenttype = $parts[count($parts) - 3];

        $data = new StdClass();
        $data->resources = array();
        $data->course = $cid;
        $data->name = $this->read($settings, 'title');
        $data->intro = $this->get_description($settings, $data);
        $data->introformat = FORMAT_HTML;
        $data->assignmenttype = $assignmenttype;
        $data->preventlate = 0;
        if ($assignmenttype == 'upload') {
            $data->resubmit = 1;
            $data->var1 = 3; //allowmaxfiles
            $data->var2 = 0; //allownotes
            $data->var3 = 0; //hideintro
            $data->var4 = 1; //trackdrafts
            $data->var5 = 0;
            $data->emailteachers = 0;
        } else if ($assignmenttype == 'uploadsingle') {
            $data->resubmit = 0;
            $data->var1 = 0;
            $data->var2 = 0;
            $data->var3 = 0;
            $data->var4 = 0;
            $data->var5 = 0;
            $data->emailteachers = 0;
        } else if ($assignmenttype == 'online') {
            $data->resubmit = 0;
            $data->var1 = 0; //commentinline
            $data->var2 = 0;
            $data->var3 = 0;
            $data->var4 = 0;
            $data->var5 = 0;
            $data->emailteachers = 0;
        } else if ($assignmenttype == 'online') {
            $data->resubmit = 0;
            $data->var1 = 0;
            $data->var2 = 0;
            $data->var3 = 0;
            $data->var4 = 0;
            $data->var5 = 0;
            $data->emailteachers = 0;
        } else {
            $data->resubmit = 0;
            $data->var1 = 0;
            $data->var2 = 0;
            $data->var3 = 0;
            $data->var4 = 0;
            $data->var5 = 0;
            $data->emailteachers = 0;
        }
        $data->maxbytes = max($CFG->maxbytes, $COURSE->maxbytes);

        $data->timedue = time() + 7 * 24 * 3600;
        $data->timeavailable = time();
        $data->grade = 100;
        $data->cmidnumber = '';
        $result = $this->insert($settings, 'assignment', $data) ? $data : false;

        assignment_grade_item_update($result);
        return $result;
    }

}