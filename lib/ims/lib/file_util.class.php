<?php

/**
 * Helper class for file management.
 * 
 * @copyright (c) 2010 University of Geneva 
 * 
 * @license GNU General Public License
 * @author laurent.opprecht@unige.ch
 *
 */
class FileUtil {

    public static function ensure_directory($path) {
        global $CFG;
        $path = rtrim($path, '/');
        $is_dir = strpos(basename($path), '.') === false;
        $dir = $is_dir ? $path : dirname($path);
        if (!file_exists($dir)) {
            $result = mkdir($dir, $CFG->directorypermissions, true);
            return $result;
        } else {
            return true;
        }
    }

    public function write($path, $content) {
        if (!$fh = fopen($path, 'w')) {
            $result = false;
        } else {
            $result = (bool) fwrite($fh, $content);
        }
        fclose($fh);
        return $result;
    }

}

?>