<?php

class url_import extends mod_import {

    protected function process_import($settings) {
        $cid = $settings->get_course_id();
        $path = $settings->get_path();
        $filename = $settings->get_filename();

        $text = file_get_contents($path);


        $pattern = '/URL=.*$/m';
        $matches = array();
        preg_match($pattern, $text, $matches);
        $url = reset($matches);
        $url = str_ireplace('URL=', '', $url);

        $data = new StdClass();
        $data->resources = array();
        $data->course = $cid;
        $data->name = str_replace('.url', '', $filename);
        $data->intro = '';
        $data->introformat = RESOURCELIB_DISPLAY_EMBED;
        $data->externalurl = $url;
        $data->parameters = serialize(array());
        $data->displayoptions = serialize(array('printheading' => false, 'printintro' => true));
        return $this->insert($settings, 'url', $data) ? $data : false;
    }

}