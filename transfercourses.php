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
 * File : transfercourses.php
 * Create courses for UFR Droit and enrol teachers.
 */

require_once('../../config.php');
require_once('../../blocks/mytermcourses/lib.php');

require_login();

$contextsystem = context_system::instance();
$PAGE->set_context($contextsystem);
$currenturl = new moodle_url('/local/scriptdroit/transfercourses.php');
$PAGE->set_url($currenturl);

require_capability('local/scriptdroit:manage', $contextsystem);

$originurl = new moodle_url('/local/scriptdroit/scriptmanager.php');

echo $OUTPUT->header();

$roleappuiadmin = $DB->get_record('role', array('shortname' => 'appuiadmin'));

// Récupération de la liste des cours.

$sqldroit = "SELECT * FROM {course_categories} WHERE idnumber LIKE '$CFG->previousyearprefix-1%' AND depth = 4 "
        . "AND idnumber NOT LIKE '$CFG->previousyearprefix-1COMMON' "
        . "AND idnumber NOT LIKE '$CFG->previousyearprefix-12016V146'";

$listdroitoldcategories = $DB->get_records_sql($sqldroit);

foreach ($listdroitoldcategories as $droitoldcategory) {

    $oldvetcodeyear = $droitoldcategory->idnumber;
    $newvetcodeyear = $CFG->yearprefix.substr($oldvetcodeyear, 5);

    if ($DB->record_exists('course_categories', array('idnumber' => $newvetcodeyear))) {

        $droitnewcategory = $DB->get_record('course_categories', array('idnumber' => $newvetcodeyear));

        $listdroitoldcourses = $DB->get_records('course', array('category' => $droitoldcategory->id));

        foreach ($listdroitoldcourses as $droitoldcourse) {

            $oldcoursecode = $droitoldcourse->idnumber;
            $newcoursecode = $CFG->yearprefix.substr($oldcoursecode, 5);
            $newcoursename = $droitoldcourse->fullname;

            $oldshortname = $droitoldcourse->shortname;

            if (substr($oldshortname, 0, 5) != $CFG->previousyearprefix) {

                $droitoldcourse->shortname = $CFG->previousyearprefix."-".$droitoldcourse->shortname;

                $DB->update_record('course', $droitoldcourse);
            }

            if (!$DB->record_exists('course', array('idnumber' => $newcoursecode))) {

                $newcourse = local_scriptdroit_createcourse($newcoursename, $newcoursecode, $droitnewcategory->id);

                $oldcontextid = $DB->get_record('context',
                            array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $droitoldcourse->id))->id;

                $context = $DB->get_record('context',
                        array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $newcourse->id));

                $listoldappuiadmins = $DB->get_records('role_assignments',
                        array('roleid' => $roleappuiadmin->id, 'contextid' => $oldcontextid));

                foreach ($listoldappuiadmins as $oldappuiadmin) {

                    if ($DB->record_exists('user', array('id' => $oldappuiadmin->userid))) {

                        $user = $DB->get_record('user', array('id' => $oldappuiadmin->userid));

                        $contextinstance = context_course::instance($newcourse->id);

                        if (!is_enrolled($contextinstance, $user)) {

                            // L'appui administratif est inscrit au cours.
                            $enrolmethod = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $newcourse->id));
                            $now = time();
                            $roleassignment = new stdClass();
                            $roleassignment->roleid = $roleappuiadmin->id;
                            $roleassignment->contextid = $context->id;
                            $roleassignment->userid = $oldappuiadmin->userid;
                            $roleassignment->timemodified = $now;
                            $roleassignment->modifierid = 0;
                            $DB->insert_record('role_assignments', $roleassignment);

                            $enrolment = new stdClass();
                            $enrolment->enrolid = $enrolmethod->id;
                            $enrolment->userid = $oldappuiadmin->userid;
                            $enrolment->timestart = $now;
                            $enrolment->timecreated = $now;
                            $enrolment->timemodified = $now;
                            $enrolment->modifierid = 0;
                            $DB->insert_record('user_enrolments', $enrolment);
                        }
                    }
                }
            }
        }
    }
}

echo "<a href=$originurl>".get_string('redirect', 'local_scriptdroit')."</a>";
echo $OUTPUT->footer();

function local_scriptdroit_createcourse($coursename, $courseidnumber, $categoryid) {

    $newcourseidnumber = block_mytermcourses_tryidnumber('course', $courseidnumber, 0);
    $courseshortname = block_mytermcourses_tryshortname($coursename, 0);

    $coursedata = new stdClass;
    $coursedata->fullname = $coursename;
    $coursedata->shortname = $courseshortname;
    $coursedata->category = $categoryid;
    $coursedata->idnumber = $newcourseidnumber;
    $coursedata->format = 'topics';

    $newcourse = create_course($coursedata);
    $newcontext = context_course::instance($newcourse->id, MUST_EXIST);

    block_mytermcourses_createsections($newcourse, $newcontext);

    return $newcourse;
}
