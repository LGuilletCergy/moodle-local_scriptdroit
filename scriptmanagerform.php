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
 * Initially developped for :
 * Université de Cergy-Pontoise
 * 33, boulevard du Port
 * 95011 Cergy-Pontoise cedex
 * FRANCE
 *
 * Store scripts executed for the UFR Droit
 *
 * @package   local_scriptdroit
 * @copyright 2019 Laurent Guillet <laurent.guillet@u-cergy.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * File : scriptmanagerform.php
 * Form file.
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

class scriptdroit_scriptmanager_form extends moodleform {

    public function definition() {

        global $CFG;

        $mform = $this->_form;

        $listscripts = array(1 => 'Test', 2 => 'Test 2');
        $mform->addElement('select', 'scriptchoice', get_string('scriptchoice', 'local_scriptdroit'), $listscripts);

        $this->add_action_buttons();
    }

    public function validation($data, $files) {

        return array();
    }
}