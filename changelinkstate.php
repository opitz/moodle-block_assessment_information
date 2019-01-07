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
define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');
require_once('../../lib/modinfolib.php');
require_login();

$id = required_param('id',PARAM_INT);
$visible = required_param('state',PARAM_INT);

global $DB,$COURSE;

$resource = new StdClass();
$resource->id = $id;
$resource->visible = $visible;

$result = $DB->update_record('block_assessment_information', $resource);

$sql = 'select itemid from {block_assessment_information} where id ='.$id;
$result= $DB->get_record_sql($sql);
if(isset($result) && $result->itemid != ""){
	$record=  new StdClass();
	$record->id= $result->itemid; // course module id from block_assessment_information
	$record->visible = $visible;
	$record->visibleold = $visible;
	$update = $DB->update_record('course_modules', $record);
	 $completioncache = \cache::make('core', 'coursemodinfo')->purge();
	echo 'State succefully changed';
}