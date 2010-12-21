<?php

require_once(dirname(__FILE__) . '/lib.php');

if (isset($_POST['callbackclass']) && $_POST['callbackclass'] == 'temp_file_portfolio_caller') {
    return;
}

if (empty($PAGE)) {
    return;
}

require_login();

$course_id = optional_param('course_id', 0, PARAM_INT); // Course Module ID
$action = optional_param('action', '', PARAM_ALPHAEXT);

if (!empty($course_id)) {
    $PAGE->set_course($DB->get_record('course', array('id' => $course_id)));
}

$PAGE->set_url($CFG->wwwroot . '/course/export/index.php', array('course_id' => $course_id));
$title = $PAGE->course->shortname . ':' . get_string('import', 'block_transfer');
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();


$form = new transfer_import_form();
$form->display();

if (!empty($_POST) && $form->is_validated()) {
    $path = $form->save_temp_file('file');
    $filename = $form->get_new_filename('file');
    $ext = mimetype_to_ext($form->get_mimetype('file'));
    $ext = $ext ? $ext : get_extention($filename);
    if ($ext) {
        $filename = trim_extention($filename);
        $filename .= '.' . $ext;
    }

    $section = $form->get_data()->section;

    echo $OUTPUT->heading(get_string('import_result', 'block_transfer'), 2, 'heading_block header outline');
    echo $OUTPUT->box_start();
    $importer = new course_import(new transfer_log());

    
    $importer->import(new import_settings($course_id, $path, $filename, $ext, $section));
    echo $OUTPUT->box_end();
}


echo $OUTPUT->footer();
die;

