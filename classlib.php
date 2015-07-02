<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version details.
 *
 * @package    blocks
 * @subpackage assessment_information
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 // No direct script access.
defined('MOODLE_INTERNAL') || die();
defined('TOPIC_ZERO_SECTION') || define('TOPIC_ZERO_SECTION','52');

class assessment_information{

	public $courseid;
	private $assignment_tables = array('assign','turnitintool', 'turnitintooltwo', 'quiz', 'workshop', 'lesson');
	private $assessment_tables = array('page');
	public $topic_zero_section = TOPIC_ZERO_SECTION;
	private $db;

	function __construct($courseid,$theme){
		global $DB;
		$this->db = $DB;
		$this->courseid = $courseid;
		//preconfigured resources
		if(!$this->topiczero_section_resources()){
			$preconfigured_resources = new preconfigured_resources($this->courseid,$theme);
			$preconfigured_resources->create_default_resources();
		}
		//sync assignments
		$this->sync_assignmnets();
		$this->sync_assessments();
		$this->delete_missing_resources();
	}

	private function sync_assignmnets(){
		$dbman = $this->db->get_manager();
		//check diff between course assignments
		foreach ($this->assignment_tables as $table) {
			if($dbman->table_exists($table)){
				$moduleid = $this->db->get_field('modules','id',array('name'=>$table));
				$result = $this->db->get_records_sql("
					SELECT
						cm.id as 'itemid',
						a.course as courseid,
						? as 'type',
						'".$table."' as 'mtable',
						a.name,
						CONCAT('/mod/', '$table', '/view.php', ?, 'id=', cm.id) as url,
						? as 'section'
					FROM {".$table."} as a
						JOIN {course_modules} as cm ON
							a.course = cm.course and cm.module = $moduleid and
							a.id = cm.instance
						LEFT JOIN {block_assessment_information} as ai ON 
							a.course = ai.courseid and ai.mtable = ? 
							and cm.id = ai.itemid
					WHERE a.course = ? and ai.id is NULL
				", array('modassignment', '?', 'assignment', $table, $this->courseid));
				if($result){
					$this->db->insert_records('block_assessment_information',$result);
				}
			}
		}
	}

	public function sync_assessments(){
		$sectionid = $this->db->get_field('course_sections','id',array(
			'course' => $this->courseid,
			'section' => $this->topic_zero_section
		));
		$resources = $this->db->get_records_sql("
			SELECT
				cm.id as 'itemid',
				cm.course as courseid,
				? as 'type',
				m.name as 'mtable',
				CONCAT('/mod/', m.name, '/view.php', ?, 'id=', cm.id) as url,
				? as 'section',
				cm.instance
			FROM {course_modules} as cm
				JOIN {modules} as m ON
					m.id = cm.module
				LEFT JOIN {block_assessment_information} as ai ON 
					ai.courseid = cm.course and ai.mtable = m.name 
					and ai.itemid = cm.id
			WHERE cm.course = ? and ai.id is NULL and cm.section = ?
		", array('modpage', '?', 'assessment', $this->courseid, $sectionid));
		
		if($resources){
			foreach($resources as $key=>$resource){
				$name = $this->db->get_field(
					$resource->mtable,
					'name',
					array('id'=>$resource->instance)
				);
				$resources[$key]->name = $name;
				unset($resources[$key]->instance);
			}
			$this->db->insert_records('block_assessment_information',$resources);
		}
	}

	public function delete_missing_resources(){
		$missingresources = $this->db->get_records_sql("
			SELECT ai.id
			FROM {block_assessment_information} as ai
			JOIN mdl_modules as m
				ON ai.mtable = m.name
			LEFT JOIN mdl_course_modules as cm
			    ON cm.module = m.id AND cm.id = ai.itemid AND cm.course = ai.courseid
			WHERE ai.courseid = ? AND cm.id is NULL
		", array($this->courseid));
		if($missingresources){
			$res_ids = implode(', ',array_keys($missingresources));
			$this->db->delete_records_select('block_assessment_information',"id IN ($res_ids)");
		}
	}

	public function get_course_resources($section, $visible = 1){
		$result = $this->db->get_records_select('block_assessment_information',
			'section = ? AND courseid = ? AND visible >= ?',
			array(
				$section,$this->courseid,$visible
			), 
			'weight ASC', 'id, type, name, url, section, visible, mtable, itemid');
		return $result;
	}

	public function topiczero_section_resources(){
		$sectionid = $this->db->get_field('course_sections','id',array(
			'course' => $this->courseid,
			'section' => $this->topic_zero_section
		));
		$result = $this->db->count_records('course_modules',array(
			'course' => $this->courseid,
			'section' => $sectionid
		));
		return $result;
	}
}

class preconfigured_resources {
	public $theme;
	public $courseid;
	private $db;
	private $resources= array();

	function __construct($courseid, $theme){
		$this->theme = $theme;
		$this->courseid = $courseid;
		global $DB;
		$this->db = $DB;
	}
	/*
		This function creates the defaults resourses by duplicating the modules in topic_zero_section
		to the target course i.e. the course which is currently being viewed.
	*/
	public function create_default_resources(){
		$targetcourse = $this->db->get_record('course', array('id' => $this->courseid));
		$premixcourseconfig = 'pre_mix_course_'.$this->theme;
		$premixcourseid = get_config('block_assessment_information', $premixcourseconfig);
		if($premixcourseid){
			$premixcourse = $this->db->get_record('course', array('id' => $premixcourseid));
			$premixcoursemodules = get_fast_modinfo($premixcourse);
			$sectionid = $this->db->get_field('course_sections', 'id', array(
				'course' => $premixcourseid,
				'section' => TOPIC_ZERO_SECTION
			));
			$premixresources = $this->db->get_records('course_modules', array(
				'course'=>$premixcourseid,
				'section'=> $sectionid
			));
			foreach ($premixresources as $resource) {
				$cm = $premixcoursemodules->cms[$resource->id];
				$newcmid = $this->duplicate_premix_module($targetcourse,$cm);
			}
		}
	}
	/*
	This function duplicates the module to the target course specified. The code is taken for 
	course/lib.php and modified so that the modules can be duplicated across courses
	*/
	private function duplicate_premix_module($course, $cm) {
	    global $CFG, $DB, $USER;
	    require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
	    require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
	    require_once($CFG->libdir . '/filelib.php');

	    $a          = new stdClass();
	    $a->modtype = get_string('modulename', $cm->modname);
	    $a->modname = format_string($cm->name);

	    if (!plugin_supports('mod', $cm->modname, FEATURE_BACKUP_MOODLE2)) {
	        throw new moodle_exception('duplicatenosupport', 'error', '', $a);
	    }

	    // Backup the activity.

	    $bc = new backup_controller(backup::TYPE_1ACTIVITY, $cm->id, backup::FORMAT_MOODLE,
	            backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id);

	    $backupid       = $bc->get_backupid();
	    $backupbasepath = $bc->get_plan()->get_basepath();

	    $bc->execute_plan();

	    $bc->destroy();

	    // Restore the backup immediately.

	    $rc = new restore_controller($backupid, $course->id,
	            backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id, backup::TARGET_CURRENT_ADDING);

	    $cmcontext = context_module::instance($cm->id);
	    if (!$rc->execute_precheck()) {
	        $precheckresults = $rc->get_precheck_results();
	        if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
	            if (empty($CFG->keeptempdirectoriesonbackup)) {
	                fulldelete($backupbasepath);
	            }
	        }
	    }

	    $rc->execute_plan();

	    // Now a bit hacky part follows - we try to get the cmid of the newly
	    // restored copy of the module.
	    $newcmid = null;
	    $tasks = $rc->get_plan()->get_tasks();
	    foreach ($tasks as $task) {
	        if (is_subclass_of($task, 'restore_activity_task')) {
	            if ($task->get_old_contextid() == $cmcontext->id) {
	                $newcmid = $task->get_moduleid();
	                break;
	            }
	        }
	    }

	    // Move the new cmid to the topic zero section of the target course
	    if ($newcmid) {
	        $info = get_fast_modinfo($course);
	        $newcm = $info->get_cm($newcmid);
	        $section = $DB->get_record('course_sections', array('section' => TOPIC_ZERO_SECTION, 'course' => $course->id));
	        moveto_module($newcm, $section);

	        // Trigger course module created event. We can trigger the event only if we know the newcmid.
	        $event = \core\event\course_module_created::create_from_cm($newcm);
	        $event->trigger();
	    }
	    rebuild_course_cache($course->id);

	    $rc->destroy();

	    if (empty($CFG->keeptempdirectoriesonbackup)) {
	        fulldelete($backupbasepath);
	    }

	    return isset($newcm) ? $newcm : null;
	}
}