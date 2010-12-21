<?php

/**
 * 
 * Aggregate importer contained in the /dir/ directory. 
 * Delegate import to the first that accept the call.
 * 
 * @copyright (c) 2010 University of Geneva 
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class dir_import extends mod_import_aggregate {

    public function __construct($log, $parent = null) {
        parent::__construct($log, $parent);
        $directory = dirname(__FILE__) . '/dir/';
        $files = scandir($directory);
        $files = array_diff($files, array('.', '..'));
        foreach ($files as $file) {
            $path = $directory . $file;
            if (strpos($file, '.class.php') !== false) {
                include_once($path);
                $class = str_replace('.class.php', '', $file);
                $mod = new $class($log, $this);
                $this->add($mod);
            }
        }
        $this->sort();
    }

    public function accept($settings) {
        return is_dir($settings->get_path());
    }

    public function import(import_settings $settings) {
        if ($this->accept($settings)) {
            return parent::import($settings);
        } else {
            return false;
        }
    }

}

