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

include('../../config.php');
include('../../blocks/mytermcourses/lib.php');

require_login();

$contextsystem = context_system::instance();
$PAGE->set_context($contextsystem);
$currenturl = new moodle_url('/local/scriptdroit/transfercourses.php');
$PAGE->set_url($currenturl);

require_capability('local/scriptdroit:manage', $contextsystem);

$originurl = new moodle_url('/local/scriptdroit/scriptmanager.php');

echo $OUTPUT->header();

$roleappuiadmin = $DB->get_record('role', array('shortname' => 'appuiadmin'));

$listvets = local_scriptdroit_availablevets();

foreach ($listvets as $vetcode => $vet) {

    $listcourses = $vet->courses;

    $category = $DB->get_record('course_categories', array('idnumber' => $vet->vetcodeyear));

    foreach ($listcourses as $coursecode => $course) {

        $newcourse = local_scriptdroit_createcourse($course->coursename, $course->coursecodeyear, $category->id);

        // Ensuite, récupérer les appuis pédagogiques de l'ancien cours et les inscrire dans le nouveau.

        $oldcoursecode = $CFG->previousyearprefix.substr($course->coursecodeyear, 5);

        $oldcourse = $DB->get_record('course', array('idnumber' => $oldcoursecode));
        $contextid = $DB->get_record('context',
                array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $oldcourse->id))->id;

        $listoldappuiadmins = $DB->get_records('role_assignments',
                array('roleid' => $roleappuiadmin->id,'contextid' => $contextid));

        foreach ($listoldappuiadmins as $oldappuiadmin) {

             // L'appui administratif est inscrit au cours.
            $enrolmethod = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $oldcourse->id));
            $now = time();
            $roleassignment = new stdClass();
            $roleassignment->roleid = $roleappuiadmin->id;
            $roleassignment->contextid = $contextid;
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

echo "<a href=$originurl>".get_string('redirect', 'local_scriptdroit')."</a>";
echo $OUTPUT->footer();

function local_scriptdroit_availablevets() {

    // Regarder comment adapter la fonction à mes besoins

    global $CFG;

    $myvets = array();
    $filename = '/home/referentiel/dokeos_elp_ens.xml';

    if (filesize($filename) > 0) {

        $xmldoc = new DOMDocument();
        $xmldoc->load($filename);
        $xpathvar = new Domxpath($xmldoc);
        $vetquery = '//Structure_diplome[@Libelle_composante_superieure="1 : UFR DROIT"]';
        $xmlvets = $xpathvar->query($vetquery);

        foreach ($xmlvets as $xmlvet) {

            $vetcode = $xmlvet->getAttribute('Etape');

            $myvets[$vetcode] = new stdClass();
            $myvets[$vetcode]->vetcodeyear = "$CFG->yearprefix-$vetcode";
            $myvets[$vetcode]->vetname = $xmlvet->getAttribute('libelle_long_version_etape');
            $myvets[$vetcode]->courses = local_scriptdroit_availablecourses($xmlvet, $myvets[$vetcode]->vetcodeyear);
        }
    }

    return $myvets;
}

function local_scriptdroit_availablecourses($xmlvet, $vetcodeyear) {

    $vetcourses = array();
    $xmlteachers = $xmlvet->childNodes;

    foreach ($xmlteachers as $xmlteacher) {

        if ($xmlteacher->nodeType !== 1 ) {
            continue;
        }

        $xmlcourses = $xmlteacher->childNodes;

        foreach ($xmlcourses as $xmlcourse) {

            if ($xmlcourse->nodeType !== 1) {

                    continue;
            }
            $coursecode = $xmlcourse->getAttribute('element_pedagogique');
            $vetcourses[$coursecode] = new stdClass();
            $vetcourses[$coursecode]->coursecodeyear = "$vetcodeyear-$coursecode";
            $vetcourses[$coursecode]->coursename = $xmlcourse->getAttribute('libelle_long_element_pedagogique');
        }
    }

    return $vetcourses;
}

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

// Normalement, j'utilise la version de mytermcourses
// A supprimer si la version ci-dessus fonctionne bien.
//
//function local_scriptdroit_createsections($newcourse) {
//
//    global $DB;
//
//
//    $sectiontitles = array('DESCRIPTION DU COURS', 'PLAN DE COURS', 'FICHES TD', 'INFORMATIONS GENERALES',
//        'SUPPORT ET NOTES DE COURS');
//
//    $numsectionsoption = new stdClass();
//    $numsectionsoption->courseid = $newcourse->id;
//    $numsectionsoption->format = 'topics';
//    $numsectionsoption->sectionid = 0;
//    $numsectionsoption->name = 'numsections';
//    $numsectionsoption->value = count($sectiontitles);
//    $DB->insert_record('course_format_options', $numsectionsoption);
//
//    $now = time();
//
//    $i = 1;
//
//    foreach ($sectiontitles as $sectiontitle) {
//
//        $coursesection = new stdClass();
//        $coursesection->course = $newcourse->id;
//        $coursesection->section = $i;
//        $coursesection->name = $sectiontitle;
//        $coursesection->summary = '';
//        $coursesection->summaryformat = 1;
//        $coursesection->sequence = '';
//        $coursesection->visible = 1;
//        $coursesection->timemodified = $now;
//        $DB->insert_record('course_sections', $coursesection);
//        $i++;
//    }
//}


