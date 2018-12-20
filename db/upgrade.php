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
 * Upgrade script for the quiz module.
 *
 * @package    block_assessment_information
 * @copyright  2018 Shubhendra Doiphode
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Quiz module upgrade function.
 * @param string $oldversion the version we are upgrading from.
 */
function xmldb_block_assessment_information_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

      if ($oldversion < 2018021202) {

        // Define field gradevisible to be added to block_assessment_information.
        $table = new xmldb_table('block_assessment_information');
        $field = new xmldb_field('gradevisible', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0', 'visible');

        // Conditionally launch add field gradevisible.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Assessment_information savepoint reached.
        upgrade_block_savepoint(true, 2018021202, 'assessment_information');
    }
    return true;
}
