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
define('TOPIC_ZERO_SECTION','52');

class assessment_information{

	public $courseid;
	private $assignment_tables = array('assign','turnitintool', 'turnitintooltwo');
	private $assessment_tables = array('page');
	public $topic_zero_section = TOPIC_ZERO_SECTION;
	private $db;

	function __construct($courseid,$theme){
		global $DB;
		$this->db = $DB;
		$this->courseid = $courseid;
		//preconfigured resources
		if(empty($this->get_course_resources('assessment'))){
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
			'weight ASC', 'id, type, name, url, section, visible');
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
		$this->get_theme_resource_settings();
	}

	public function create_default_resources(){
		global $CFG;
		$course = $this->db->get_record('course',array('id'=>$this->courseid));
		require_once($CFG->dirroot.'/course/modlib.php');
		foreach ($this->resources as $resource=>$instances) {
			if($module = $this->db->get_record('modules', array('name'=>$resource))){
				for($i=1; $i <= $instances; $i++){
					//prepare default objects
					$data = new StdClass();
					$data->section = TOPIC_ZERO_SECTION;
					$data->visible = '1';
					$data->course = $this->courseid;
					$data->module = $module->id;
    				$data->modulename = $module->name;
    				$data->groupmode = $course->groupmode;
    				$data->groupingid = $course->defaultgroupingid;
				    $data->id = '';
				    $data->instance = '';
				    $data->coursemodule = '';
				    $data->grade = 100;
				    //some dummy stuffs
    				$data->name = "Preconfigured resource $module->name $i";
    				$data->introeditor = array(
    					'text' => 'This is dummy description',
    					'format' => 1
    				);
    				//get defaults
    				$config = get_config($module->name);
    				if($config){
    					foreach ($config as $key => $value) {
    						$data->$key = $value;
    					}
    				}
    				add_moduleinfo($data,$course);					
				}
			}
		}
	}

	private function get_theme_resource_settings(){
		$config = get_config('block_assessment_information');
		$theme_key = 'resources_'.$this->theme;
		if($theme_config = $config->$theme_key){
			$theme_config = str_replace('/s+/', '', $theme_config);
			$resource_settings = preg_split('/;/', $theme_config);
			foreach($resource_settings as $resource){
				if($resource){
					preg_match('/(\w*)=(\d*)/', $resource, $matches);
					if($matches && $matches[1] && $matches[2]){
						$this->resources[trim($matches[1])] = trim($matches[2]);
					}
				}
			}
		}
	}
}