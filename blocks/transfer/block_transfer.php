<?php

include_once $CFG->dirroot . '/lib/debug_util.class.php';

require_once($CFG->dirroot.'/course/transfer/export/lib.php');

if(!function_exists('page')){
	function page(){
		global $PAGE;
		return $PAGE;
	}
}

if(!function_exists('db')){
	function db(){
		global $DB;
		return $DB;
	}
}

/**
 * Provides a block to the course transfer module. The course transfer module is used to import/export
 * various activities and resources together.
 *
 * To make this block visible by default when a new couse is created go to moodle/config.php
 *
 * and add the the following line:
 *
 * 		$CFG->defaultblocks_override = 'participants:activity_modules,calendar_upcoming,recent_activity,transfer';
 *
 * Note that : is used to separate right side blocks from left side blocks.
 *
 * Or add the transfer block to one of the following lines as required
 *
 * 		$CFG->defaultblocks_site = 'site_main_menu,admin,course_list:course_summary,calendar_month';
 * 		$CFG->defaultblocks_social = 'participants,search_forums,calendar_month,calendar_upcoming,social_activities,recent_activity,admin,course_list';
 * 		$CFG->defaultblocks_topics = 'participants,activity_modules,search_forums,admin,course_list:news_items,calendar_upcoming,recent_activity';
 * 		$CFG->defaultblocks_weeks = 'participants,activi
 *
 * See moodle/config-dist.php for further information.
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class block_transfer extends block_list {

	function init() {
		$this->title = get_string('pluginname', __CLASS__);
	}

	/**
	 * Available in Moodle 1.5 and later
	 * This method allows you to control which pages your block can be added to. Page formats are formulated from the full path of the script that is used to display that page. You should return an array with the keys being page format names and the values being booleans (true or false). Your block is only allowed to appear in those formats where the value is true. Example format names are: course-view, site-index (this is an exception, referring front page of the Moodle site), course-format-weeks (referring to a specific course format), mod-quiz (referring to the quiz module) and all (this will be used for those formats you have not explicitly allowed or disallowed).
	 * The full matching rules are:
	 * 1.Prefixes of a format name will match that format name; for example, mod will match all the activity modules. course-view will match any course, regardless of the course format. And finally, site will also match the front page (remember that its full format name is site-index).
	 * 2.The more specialized a format name that matches our page is, the higher precedence it has when deciding if the block will be allowed. For example, mod, mod-quiz and mod-quiz-view all match the quiz view page. But if all three are present, mod-quiz-view will take precedence over the other two because it is a better match.
	 * 3.The character * can be used in place of any word. For example, mod and mod-* are equivalent. At the time of this document's writing, there is no actual reason to utilize this "wildcard matching" feature, but it exists for future usage.
	 * 4.The order that the format names appear does not make any difference.
	 * @see blocks/block_base#applicable_formats()
	 */
	function applicable_formats() {
		return array(
			'site-index ' => false,
			'course' => true,
			//'mod' => true,
		);
	}

	public function course_id(){
		return page()->course->id;
	}

	public function course_module_id(){
		return is_object(page()->cm) ? page()->cm->id : '';
	}

	public function module(){
		if(!is_object(page()->cm)){
			return null;
		}
		return db()->get_record('modules', array('id'=>page()->cm->module), '*', MUST_EXIST);
	}

	public function accept(){
		if($this->course_id() == SITEID){
			return false;
		}
		if(!is_object(page()->cm)){
			return true;
		}

		$export = course_export::factory();
		return $export->accept($this->module());
	}

	function get_content(){
		if(!is_null($this->content)){
			return $this->content;
		}
		if(!$this->accept()){
			$result = new stdClass();
			$result->items = array();
			$result->footer = '';
			return $this->content = $result;
		}

		$course_id = $this->course_id();
		$course_module_id = $this->course_module_id();

		$params = "course_id=$course_id";
		$params .= empty($course_module_id) ? '' : "&course_module_id=$course_module_id";

		global $CFG;
		$export_href = "{$CFG->wwwroot}/course/transfer/export/index.php?$params";
		$import_href = "{$CFG->wwwroot}/course/transfer/import/index.php?$params";

		$result = new stdClass();
		$result->footer = '';
		$result->items[] = '<a href="'.$export_href.'">'. get_string('export', __CLASS__) .'</a>';
		$result->icons[] = '';//<img src="images/icons/1.gif" class="icon" alt="" />';
		$result->items[] = '<a href="'.$import_href.'">'. get_string('import', __CLASS__) .'</a>';
		$result->icons[] = '';//<img src="images/icons/1.gif" class="icon" alt="" />';

		return $this->content = $result;
	}

}


