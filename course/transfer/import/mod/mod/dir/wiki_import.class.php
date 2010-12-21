
<?php

include_once dirname(__FILE__) . '/imscp_manifest_import.class.php';

/**
 * Imports a wiki IMSCP directory as a wiki object.
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class wiki_import extends imscp_manifest_import {

    public function get_weight() {
        return 1;
    }

    public function get_extentions() {
        return array('wiki');
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

    protected function process_import(import_settings $settings) {
        $result = $this->create($settings);
        $result = $this->insert($settings, 'wiki', $result) ? $result : false;

        if (!$result) {
            return false;
        }

        //create subwiki
        global $DB, $USER;
        $subwiki = new object();
        $subwiki->wikiid = $result->id;
        $subwiki->groupid = 0;
        $subwiki->userid = 0; //$USER->id;
        $subwiki->id = $DB->insert_record('wiki_subwikis', $subwiki);
        if (!$subwiki->id) {
            return false;
        }

        //import wikipages
        $this->set_parent_id($subwiki->id);
        $this->import_manifest($settings);
        $this->reset_parent_id();

        //set wiki->firstpagetitle = firstpage->title
        global $DB;
        $pages = $DB->get_records('wiki_pages', array('subwikiid' => $subwiki->id), 'subwikiid ASC');
        if ($pages) {
            $first_page = reset($pages);
            $result->firstpagetitle = $first_page->title;
            $DB->update_record('wiki', $result);
        }

        return $result;
    }

    protected function create(import_settings $settings) {
        $result = new stdClass();
        $result->resources = array();
        $result->course = $settings->get_course_id();
        $result->name = $this->get_title($settings);
        $result->intro = '<p>' . $result->name . '</p>';
        $result->introformat = FORMAT_HTML;
        $result->firstpagetitle = '';
        $result->wikimode = 'collaborative';
        $result->defaultformat = 'html';
        $result->forceformat = true;
        $result->editbegin = 0;
        $result->editend = 0;
        return $result;
    }

}

