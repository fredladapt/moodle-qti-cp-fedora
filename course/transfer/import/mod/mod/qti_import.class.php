<?php

/**
 * Imports a .qti.zip file. 
 * I.e. an IMS package containing only QTI items with the .qti.zip file.
 * 
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class qti_import extends mod_import {

    public function get_weight() {
        return 1;
    }

    public function get_extentions() {
        return array('qti.zip');
    }

    public function accept(import_settings $settings) {
        $path = $settings->get_path();

        $result = (bool) strpos($path, reset($this->get_extentions()));
        return $result;
    }

    protected function process_import(import_settings $settings) {
        $path = $settings->get_path();
        $filename = $settings->get_filename();
        $filename = trim_extention($filename);

        $dir = $this->extract($path, true);

        $folder_settings = $settings->copy($dir, $filename);

        // forces individual items in the package to be imported
        // not very good
        $folder_settings->reset_level();

        $this->get_root()->import($folder_settings);
        fulldelete($dir);
        return true;
    }

}