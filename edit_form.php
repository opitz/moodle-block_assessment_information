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
class block_assessment_information_edit_form extends block_edit_form {
	protected function specific_definition($mform) {
		global $PAGE;

		//configure section titles
		$mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

		$mform->addElement('text','config_assessment_header', get_string(
			'config_assessment_header', 'block_assessment_information'
		));
        $mform->setType('config_assessment_header', PARAM_TEXT);
        $mform->addRule('config_assessment_header',null,'required',null,'client');
        $mform->setDefault('config_assessment_header',get_string(
			'default_assessment_header', 'block_assessment_information'
		));

		$mform->addElement('text','config_assignment_header', get_string(
			'config_assignment_header', 'block_assessment_information'
		));
        $mform->setType('config_assignment_header', PARAM_TEXT);
        $mform->addRule('config_assignment_header',null,'required',null,'client');
        $mform->setDefault('config_assignment_header', get_string(
			'default_assignment_header', 'block_assessment_information'
		));

		$editor_options = array('noclean'=>true, 'subdirs'=>true, 'maxfiles'=>-1, 'maxbytes'=>0, 'context'=>$PAGE->context);
		$mform->addElement('editor', 'config_assignment_text',
			get_string('config_assignment_text','block_assessment_information'), null, $editor_options);
		$mform->setType('config_assignment_text', PARAM_RAW);

		$mform->addElement('advcheckbox','config_enable_extra_section', get_string(
			'config_enable_extra_section', 'block_assessment_information'
		));

		$mform->addElement('text','config_extra_section_header', get_string(
			'config_extra_section_header', 'block_assessment_information'
		));
		$mform->setType('config_extra_section_header', PARAM_TEXT);
        $mform->setDefault('config_extra_section_header', get_string(
			'default_extra_section_header', 'block_assessment_information'
		));
		$mform->disabledIf('config_extra_section_header','config_enable_extra_section',
			'notchecked');
		//configure subheadings
		$mform->addElement('header', 'config_subheadings', get_string(
			'configure_subheadings', 'block_assessment_information'));

		//subheadings backgroung color
		$mform->addElement('text', 'config_subheadings_title', 
			get_string('config_subheadings_title', 'block_assessment_information'));
		$mform->setType('config_subheadings_title', PARAM_TEXT);

		//subheading title bg color
		$colors = array(''=>'None');

		$config = get_config('block_assessment_information');
		$allowedcolors = explode(';',$config->allowedcolors);
		foreach ($allowedcolors as $color) {
			$color = trim($color);
			if(preg_match('/^#[a-f0-9]{6}$/i', $color)){
				$colors[$color] = $color;
			}
		}

		$mform->addElement('select', 'config_subheadings_background', get_string(
			'config_subheadings_background','block_assessment_information'),
			$colors
		);
		$mform->setType('config_subheadings_background', PARAM_TEXT);

		//subheadings text
		$mform->addElement('editor', 'config_subheadings_text',
			get_string('config_subheadings_text','block_assessment_information'), null, $editor_options);
		$mform->setType('config_subheadings_text', PARAM_RAW);
	}
}