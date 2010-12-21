<?php

/**
 * Base class for importing a file.
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class mod_import {

    public static function factory($log) {
        $result = new mod_import_aggregate($log, null);
        $directory = dirname(__FILE__) . '/mod/';
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $path = $directory . $file;
                if (strpos($file, '.class.php') !== false) {
                    include_once($path);
                    $class = str_replace('.class.php', '', $file);
                    $mod = new $class($log, $result);
                    $result->add($mod);
                }
            }
        }
        $result->sort();
        return $result;
    }

    private $log;
    private $parent;
    private $section;

    public function __construct($log, $parent = null) {
        $this->log = $log;
        $this->parent = $parent;
    }

    public function get_parent() {
        return $this->parent;
    }

    public function get_root() {
        if (empty($this->parent)) {
            return $this;
        } else {
            return $this->parent->get_root();
        }
    }

    public function get_name() {
        $result = get_class($this);
        $result = str_replace('_import', '', $result);
        return $result;
    }

    public function get_extentions() {
        $name = $this->get_name();
        if (!empty($name)) {
            $result = array($name);
        } else {
            $result = array();
        }
        return $result;
    }

    public function get_weight() {
        return 0;
    }

    public function get_log() {
        return $this->log;
    }

    /**
     * The filearea used to store files
     */
    public function get_file_filearea() {
        return 'content';
    }

    /**
     * The default item id used to store files.
     */
    public function get_file_itemid() {
        return 0;
    }

    public function accept($settings) {
        $path = $settings->get_path();
        $file_ext = $settings->get_extention();
        $extentions = $this->get_extentions();
        foreach ($extentions as $ext) {
            if ($ext == $file_ext) {
                return true;
            }
        }
        return false;
    }

    public function import($settings) {
        if ($this->accept($settings)) {
            return $this->process_import($settings);
        } else {
            return false;
        }
    }

    protected function process_import($settings) {
        return false;
    }

    /**
     * Create a module object with default values.
     *
     * @param $settings
     */
    protected function create(import_settings $settings) {
        $result = new stdClass();
        $result->name = $this->get_title($settings);
        $result->resources = array();
        return $result;
    }

    /**
     * Returns the object's default title. I.e. the 'filename' without extentions.
     *
     * @param $settings
     */
    protected function get_title(import_settings $settings) {
        $filename = $settings->get_filename();
        $ext = $settings->get_extention();
        $result = str_ireplace(".$ext", '', $filename);
        return $result;
    }

    protected function get_description(import_settings $settings, $data) {
        $result = $this->read($settings, 'description');
        $result = $this->translate($settings, $data, 'intro', $result);
        return $result;
    }

    protected function read(import_settings $settings, $name, $default = '') {
        if ($doc = $settings->get_dom()) {
            $list = $doc->getElementsByTagName('div');
            foreach ($list as $div) {
                if (strtolower($div->getAttribute('class')) == $name) {
                    $result = $this->get_innerhtml($div);
                    $result = str_ireplace('<p>', '', $result);
                    $result = str_ireplace('</p>', '', $result);
                    return $result;
                }
            }
            $list = $doc->getElementsByTagName('body');
            if ($body = $list->length > 0 ? $list->item(0) : NULL) {
                $body = $doc->saveXML($body);
                $body = str_replace('<body>', '', $body);
                $body = str_replace('</body>', '', $body);
            } else {
                $body = '';
            }
        }
        return $default;
    }

    /**
     * Returns the inner html of a node.
     *
     * @param $node
     */
    protected function get_innerhtml($node) {
        $result = '';
        $doc = $node->ownerDocument;
        $children = $node->childNodes;
        foreach ($children as $child) {
            $result .= $doc->saveXml($child);
        }
        return $result;
    }

    protected function message($text, $class = '') {
        $this->log->message($text, $class);
    }

    protected function notify_success($mod_name, $id, $name) {
        global $CFG;
        $text = get_string('import', 'block_transfer') . ': ';
        $href = "$CFG->wwwroot/mod/$mod_name/view.php?id=$id";
        $text .= '<a href="' . $href . '">' . $name . '</a>';
        $this->message($text);
    }

    protected function notify_failure($name) {
        global $CFG;
        $text = get_string('failed_to_import', 'block_transfer') . ': ' . $name;
        $this->message($text, 'error');
    }

    protected function insert($settings, $mod_name, $data) {
        $cid = $settings->get_course_id();

        global $DB;
        $mod = $DB->get_record('modules', array('name' => $mod_name), '*', MUST_EXIST);

        try {
            $time = time();
            $data->timecreated = $time;
            $data->timemodified = $time;
            $data->revision = 1;
            if (empty($data->displayoptions)) {
                $data->displayoptions = serialize(array('printheading' => false, 'printintro' => true));
            }
            if (empty($data->description)) {
                $data->description = $data->name;
            }
            $data->id = $DB->insert_record($mod_name, $data);
            if ($data->id) {
                $instance = $this->insert_module($settings, $mod_name, $data);
            } else {
                throw new Exception('DB error');
            }
            return $instance;
        } catch (Exception $e) {
            return false;
        }
    }

    protected function insert_module($settings, $mod_name, $data) {
        $cid = $settings->get_course_id();

        global $DB;
        $mod = $DB->get_record('modules', array('name' => $mod_name), '*', MUST_EXIST);

        $instance = new StdClass();
        $instance->course = $cid;
        $instance->module = $mod->id;
        $instance->instance = $data->id;
        $instance->section = $settings->get_section_id();
        $instance->visible = true;
        $instance->visibleold = false;
        $instance->idnumber = '';
        $instance->added = time();
        $instance->id = $DB->insert_record('course_modules', $instance);

        if (!empty($instance->id)) {
            $this->save_resources($settings, $instance, $data);
            $section = $settings->get_section();
            $section->sequence .= ',' . $instance->id;
            $DB->update_record('course_sections', $section);
            rebuild_course_cache($cid);
            //$DB->commit_delegated_transaction($transaction);

            $this->notify_success($mod_name, $instance->id, $data->name);
        } else {
            throw new Exception('DB error');
        }
        return $instance;
    }

    /**
     * Save embeded resources. I.e. images
     *
     * @param unknown_type $settings
     * @param unknown_type $cm
     * @param unknown_type $data
     */
    protected function save_resources($settings, $cm, $data) {
        global $USER;

        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        $fs = get_file_storage();
        $component = 'mod_' . $this->get_name();
        foreach ($data->resources as $resource) {
            $file_record = array(
                'contextid' => $context->id,
                'component' => $component,
                'filearea' => $resource['filearea'],
                'itemid' => 0,
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

    protected function extract($path, $delete_file = true) {
        $temp_dir = $this->get_temp_directory(dirname($path));
        $zipper = new zip_packer();
        $result = $zipper->extract_to_pathname($path, $temp_dir);
        if ($delete_file) {
            fulldelete($path);
        }
        return $temp_dir;
    }

    protected function get_temp_directory($root) {
        global $USER;
        $result = $root . '/d' . $USER->id . sha1(time() . uniqid()) . '/';
        return $result;
    }

    protected function add_path($path, $context, $filepath = '/') {
        if (is_dir($path)) {
            return $this->add_directory($path, $context, $filepath);
        } else {
            return $this->add_file($path, $context, $filepath, basename($path));
        }
    }

    protected function add_file($path, $context, $filepath = '/', $filename, $mimetype = '') {
        global $USER;
        $fs = get_file_storage();
        $file_record = array(
            'contextid' => $context->id,
            'component' => 'mod_' . $this->get_name(),
            'filearea' => $this->get_file_filearea(),
            'itemid' => $this->get_file_itemid(),
            'filepath' => $filepath,
            'filename' => $filename,
            'userid' => $USER->id,
            'sortorder' => 1,
            'mimetype' => $mimetype,
        );
        $result = $fs->create_file_from_pathname($file_record, $path);
        return $result;
    }

    protected function add_directory($path, $context, $filepath = '/') {
        global $USER;
        if ($filepath != '/') {
            $fs = get_file_storage();
            $r = $fs->create_directory($context->id, 'mod_' . $this->get_name(), 'content', 0, $filepath, $USER->id);
        }
        $files = scandir($path);
        $files = array_diff($files, array('.', '..'));
        $dir = rtrim($path, '/') . '/';
        $filepath .= basename($path) . '/';
        foreach ($files as $file) {
            $p = $dir . $file;
            if (is_dir($p)) {
                $this->add_directory($p, $context, $filepath);
            } else {
                $this->add_file($p, $context, $filepath, basename($p));
            }
        }
    }

    protected function add_directory_content($path, $context, $filepath = '/') {
        $files = scandir($path);
        $files = array_diff($files, array('.', '..'));
        $dir = rtrim($path, '/') . '/';
        foreach ($files as $file) {
            $p = $dir . $file;
            if (is_dir($p)) {
                $this->add_directory($p, $context, $filepath);
            } else {
                $this->add_file($p, $context, $filepath, basename($p));
            }
        }
    }

    // TRANSLATE RELATIVE PATH

    protected function translate(import_settings $settings, $data, $filearea, $text) {
        $pattern = '/src="[^"]*"/';
        $matches = array();
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $match = reset($match);
            $match = str_replace('src="', '', $match);
            $match = trim($match, '"');
            $replace = $this->translate_path($match);
            $text = str_ireplace('src="' . $match . '"', 'src="' . $replace . '"', $text);
            $name = end(explode('/', $match));
            $file_path = $settings->get_directory() . $match;
            $data->resources[$name] = array('filename' => $name, 'path' => $file_path, 'filearea' => $filearea);
        }
        return $text;
    }

    protected function translate_path($path) {
        if (!$this->is_path_relative($path)) {
            return $path;
        }

        $name = end(explode('/', $path));
        $result = '@@PLUGINFILE@@/' . $name;
        return $result;
    }

    public function is_path_relative($path) {
        return strlen($path) < 5 || strtolower(substr($path, 0, 4)) != 'http';
    }

}

/**
 * Represents an aggregation of several importer. Delegate the calls to the objects
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class mod_import_aggregate extends mod_import {

    private $items = array();

    public function add($item) {
        $this->items[] = $item;
    }

    public function get_items() {
        return $this->items;
    }

    public function sort() {
        object_sort($this->items, 'get_weight');
    }

    public function get_extentions() {
        $result = array();
        $items = $this->get_items();
        foreach ($items as $item) {
            $extentions = $item->get_extentions();
            if (!empty($extentions)) {
                $result = array_merge($result, $extentions);
            }
        }
        return $result;
    }

    public function accept($settings) {
        foreach ($this->items as $item) {
            if ($item->accept($settings)) {
                return true;
            }
        }
    }

    public function import(import_settings $settings) {
        foreach ($this->items as $item) {
            if ($result = $item->import($settings)) {
                return $result;
            }
        }
        $this->notify_failure($settings->get_filename());
        return false;
    }

}

