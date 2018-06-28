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
 * @package    blocks
 * @subpackage assessment_information
 * @copyright  2015 Queen Mary University of London Shubhendra Doiphode
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('TOPIC_ZERO_SECTION') || define('TOPIC_ZERO_SECTION','52');

class block_assessment_information extends block_base
{

    public function init()
    {
        $this->title = get_string('pluginname', 'block_assessment_information');
    }

    public function hide_header(){
        return true;
    }

    public function instance_allow_multiple()
    {
        return false;
    }

    function instance_allow_config() {
        return true;
    }

    public function applicable_formats()
    {
        return array(
            'course-view' => true,
        );
    }

	function has_config() {
        return true;
    }
	/**
	 * get_content moodle internal function that is used to get the content of a block
	 *
	 */
    public function get_content()
    {
        global $CFG, $COURSE, $DB;
        require_once($CFG->dirroot . '/blocks/assessment_information/classlib.php');

        if($this->content !== NULL) {
            return $this->content;
        }

        $section = $DB->get_record('course_sections', array('section'=>TOPIC_ZERO_SECTION, 'course'=>$COURSE->id));
        if($section && $section->visible){
            require_once($CFG->dirroot . '/course/lib.php');
            course_update_section($section->course, $section, array('visible' => 0));
        }

        $assessment_information = new assessment_information($COURSE->id,$this->page->theme->name);
		
		
		// CODE ADDED TO CHANGE ACTIVITY ADDED IN SECTION-52 TO STEALTH STARTS HERE
        $cid = $COURSE->id;
        $sequence = $section->sequence;
	if ($sequence!="") {
        $sql_stealth = "UPDATE {course_modules} SET visible=1, visibleoncoursepage=0 WHERE id in (" . $sequence . ")";

        $DB->execute($sql_stealth);
        
        rebuild_course_cache($cid);
        
        
	}
        // CODE ADDED TO CHANGE ACTIVITY ADDED IN SECTION-52 TO STEALTH STARTS HERE

        $this->content = new stdClass();

        $assessmentrenderer = $this->page->get_renderer('block_assessment_information');
        $assessmentrenderer->block_content($this->content, $this->instance->id, $this->config,
            $assessment_information);

        return $this->content;
    }
}
