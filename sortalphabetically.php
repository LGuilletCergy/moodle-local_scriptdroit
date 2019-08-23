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
 * File : sortalphabetically.php
 * Sort categories and courses by alphabetical order.
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/lib/coursecatlib.php');
require_once($CFG->dirroot.'/course/lib.php');

global $DB;

require_login();

$contextsystem = context_system::instance();
$PAGE->set_context($contextsystem);
$currenturl = new moodle_url('/local/scriptdroit/sortalphabetically.php');
$PAGE->set_url($currenturl);

require_capability('local/scriptdroit:manage', $contextsystem);

$originurl = new moodle_url('/local/scriptdroit/scriptmanager.php');

echo $OUTPUT->header();

$sqllistcategories = "SELECT * FROM {course_categories} WHERE depth >= 3";

$listcategories = $DB->get_records_sql($sqllistcategories);

foreach ($listcategories as $category) {

    $cattosort = coursecat::get($category->id);
    \core_course\management\helper::action_category_resort_subcategories($cattosort, 'name');
}

echo "<a href=$originurl>".get_string('redirect', 'local_scriptdroit')."</a>";
echo $OUTPUT->footer();