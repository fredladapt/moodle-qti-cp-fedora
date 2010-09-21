<?php

/**
 * 
 * 
 * @copyright (c) 2010 University of Geneva 
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class transfer_log {
	
	public function message($text, $class = ''){
		global $OUTPUT;
		echo $OUTPUT->notification($text, $class);
	}
	
}

/**
 * 
 * 
 * @copyright (c) 2010 University of Geneva 
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class transfer_log_empty extends transfer_log{
	
	public function message($text, $class= ''){
		return false;
	}
}

