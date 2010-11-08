<?php

/**
 * Export Assignement modules to html format.
 * Encode the basic properties in the file with class attributes to allow reimport.
 *
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License
 * @author laurent.opprecht@unige.ch
 *
 */
class assignment_export extends mod_export{

	function export(export_settings $settings){
		return $this->export_as_page($settings);
	}

}