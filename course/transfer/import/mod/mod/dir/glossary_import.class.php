<?php

include_once dirname(__FILE__) . '/imscp_manifest_import.class.php';

/**
 * Imports a glossary IMSCP directory as a glossary object.
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class glossary_import extends imscp_manifest_import {

    public function get_weight() {
        return 1;
    }

    public function get_extentions() {
        return array('glossary');
    }

    public function accept(import_settings $settings) {
        $directory = $settings->get_path();
        if (strpos($directory, reset($this->get_extentions())) == false) {
            return false;
        }
        $manifest = $settings->get_manifest_reader();
        $name = $manifest->get_root()->name();
        $location = $manifest->get_root()->get_attribute('xsi:schemaLocation');
        return $name == 'manifest' && strpos($location, 'http://www.imsglobal.org') !== false;
    }

    protected function process_import($settings) {
        $result = $this->create($settings);
        $result = $this->insert($settings, 'glossary', $result) ? $result : false;

        if (!$result) {
            return false;
        }

        $this->set_parent_id($result->id);
        $this->import_manifest($settings);
        $this->reset_parent_id();

        return $result;
    }

    protected function create(import_settings $settings) {
        $result = new stdClass();
        $result->resources = array();
        $result->name = $this->get_title($settings);
        $result->intro = '<p>' . $result->name . '</p>';
        $result->introformat = FORMAT_HTML;
        $result->allowduplicatedentries = false;
        $result->displayformat = 'dictionary';
        $result->mainglossary = false;
        $result->showspecial = true;
        $result->showalphabet = true;
        $result->showall = true;
        $result->allowcomments = false;
        $result->allowprintview = true;
        $result->usedynalink = true;
        $result->defaultapproval = true;
        $result->globalglossary = false;
        $result->entbypage = 10;
        $result->editalways = false;
        $result->rsstype = 0;
        $result->rssarticles = 0;
        $result->assessed = false;
        $result->scale = 0;
        return $result;
    }

}

