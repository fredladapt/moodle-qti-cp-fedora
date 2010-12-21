<?php

global $CFG;
require_once $CFG->dirroot . '/mod/imscp/locallib.php';

class imscp_manifest_import extends mod_import {

    protected $parent_id = 0;

    public function get_weight() {
        return 10;
    }

    public function get_extentions() {
        return array();
    }

    public function accept(import_settings $settings) {
        if ($settings->get_level() > 1) {
            return false; //i.e. import the first level only import others as imscp
        }
        $manifest = $settings->get_manifest_reader();
        $name = $manifest->get_root()->name();
        $location = $manifest->get_root()->get_attribute('xsi:schemaLocation');
        return $name == 'manifest' && strpos($location, 'http://www.imsglobal.org') !== false;
    }

    protected function get_parent_id() {
        return $this->parent_id;
    }

    protected function set_parent_id($value) {
        $this->parent_id = $value;
    }

    protected function reset_parent_id() {
        $this->parent_id = 0;
    }

    protected function process_import($settings) {
        return $this->import_manifest($settings);
    }

    protected function import_manifest($settings) {
        $manifest = $settings->get_manifest_reader()->get_root();
        if ($result = $this->import_organizations($settings, $manifest->all_organization())) {
            return $result;
        } else {
            return $this->import_resources($settings, $manifest->get_resources()->list_resource());
        }
    }

    protected function import_organizations($settings, $organizations) {
        if (empty($organizations)) {
            return false;
        }
        $organization = reset($organizations);
        return $this->import_organization($settings, $organization);
    }

    protected function import_organization($settings, ImscpManifestReader $org) {
        $result = array();
        $items = $org->list_item();
        foreach ($items as $item) {
            if ($item_result = $this->import_item($settings, $item)) {
                $item_result = is_array($item_result) ? $item_result : array($item_result);
                $result = array_merge($result, $item_result);
            }
        }
        return $result;
    }

    protected function import_item($settings, ImscpManifestReader $item) {
        $result = array();
        $title = $item->get_title()->value();
        $resource = $item->navigate();
        $this->import_resource($settings, $resource, $title);
        $children = $item->list_item();
        foreach ($children as $child) {
            if ($item_result = $this->import_item($settings, $child)) {
                $item_result = is_array($item_result) ? $item_result : array($item_result);
                $result = array_merge($result, $item_result);
            }
        }
        return $result;
    }

    protected function import_resources($settings, $resources) {
        $result = array();
        foreach ($resources as $resource) {
            $import = $this->import_resource($settings, $resource);
            if ($import) {
                $import = is_array($import) ? $import : array($import);
                $result = array_merge($result, $import);
            }
        }
        return $result;
    }

    protected function import_resource(import_settings $settings, ImscpManifestReader $resource, $title = '') {
        $href = $resource->href;
        if (empty($href)) {
            $files = $resource->list_file();
            $file = empty($files) ? false : reset($files);
            if ($file) {
                $href = $file->href;
            }
        }
        $type = $resource->type;
        if (!empty($href)) {
            $title = empty($title) ? basename($href) : $title;
            $dir = $settings->get_path();
            $item_settings = $settings->copy("$dir/$href", $title, get_extention($href), $this->get_parent_id());
            return $this->import_child($item_settings);
        } else {
            return false;
        }
    }

    protected function import_child(import_settings $settings) {
        $result = $this->get_root()->import($settings);
        return $result;
    }

}

