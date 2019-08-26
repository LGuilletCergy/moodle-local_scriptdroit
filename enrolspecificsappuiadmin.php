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
 * File : enrolspecificsappuiadmin.php
 * Enrol specifics appuiadmins into courses without appuiadmins
 */

require_once('../../config.php');

require_login();
$contextsystem = context_system::instance();
$PAGE->set_context($contextsystem);
$currenturl = new moodle_url('/local/scriptdroit/enrolspecificsappuiadmin.php');
$PAGE->set_url($currenturl);

global $DB;

$originurl = new moodle_url('/local/scriptdroit/scriptmanager.php');

echo $OUTPUT->header();

$roleappuiadmin = $DB->get_record('role', array('shortname' => 'appuiadmin'));

$sqldroit = "SELECT * FROM {course} WHERE idnumber LIKE '$CFG->yearprefix-1%'";

$listcourses = $DB->get_records_sql($sqldroit);

foreach ($listcourses as $course) {

    $contextcourse = $DB->get_record('context', array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $course->id));

    print_object($roleappuiadmin);
    print_object($contextcourse);

    if (!$DB->record_exists('role_assignments',
            array('roleid' => $roleappuiadmin->id, 'contextid' => $contextcourse->id))) {

        print_object("Test");

        $user1 = $DB->get_record('user', array('username' => 'fangard', 'idnumber' => 6277));

        if (!is_enrolled($contextcourse, $user1)) {

            // L'appui administratif est inscrit au cours.
            $enrolmethod = $DB->get_record('enrol', array('enrol' => 'manual',
                'courseid' => $course->id));

            $now = time();
            $roleassignment = new stdClass();
            $roleassignment->roleid = $roleappuiadmin->id;
            $roleassignment->contextid = $contextcourse->id;
            $roleassignment->userid = $user1->id;
            $roleassignment->timemodified = $now;
            $roleassignment->modifierid = 0;
            $DB->insert_record('role_assignments', $roleassignment);

            $enrolment = new stdClass();
            $enrolment->enrolid = $enrolmethod->id;
            $enrolment->userid = $user1->id;
            $enrolment->timestart = $now;
            $enrolment->timecreated = $now;
            $enrolment->timemodified = $now;
            $enrolment->modifierid = 0;
            $DB->insert_record('user_enrolments', $enrolment);
        }

        $user2 = $DB->get_record('user', array('username' => 'gancel', 'idnumber' => 5351));

        if (!is_enrolled($contextcourse, $user2)) {

            // L'appui administratif est inscrit au cours.
            $enrolmethod = $DB->get_record('enrol', array('enrol' => 'manual',
                'courseid' => $course->id));

            $now = time();
            $roleassignment = new stdClass();
            $roleassignment->roleid = $roleappuiadmin->id;
            $roleassignment->contextid = $contextcourse->id;
            $roleassignment->userid = $user2->id;
            $roleassignment->timemodified = $now;
            $roleassignment->modifierid = 0;
            $DB->insert_record('role_assignments', $roleassignment);

            $enrolment = new stdClass();
            $enrolment->enrolid = $enrolmethod->id;
            $enrolment->userid = $user2->id;
            $enrolment->timestart = $now;
            $enrolment->timecreated = $now;
            $enrolment->timemodified = $now;
            $enrolment->modifierid = 0;
            $DB->insert_record('user_enrolments', $enrolment);
        }
    }
}

echo "<a href=$originurl>".get_string('redirect', 'local_scriptdroit')."</a>";
echo $OUTPUT->footer();