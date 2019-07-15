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
 * File : scriptmanager.php
 * List all scripts and launch them.
 */

include('../../config.php');
require_once('scriptmanagerform.php');
require_once($CFG->dirroot.'/scriptdroit/classes/task/sortalphabetically.php');

require_login();

$contextsystem = context_system::instance();
$PAGE->set_context($contextsystem);
$currenturl = new moodle_url('/local/scriptdroit/scriptmanager.php');
$PAGE->set_url($currenturl);

require_capability('local/scriptdroit:manage', $contextsystem);

$mform = new scriptdroit_scriptmanager_form();

if ($mform->is_cancelled()) {

    $originurl = new moodle_url('/admin/search.php');
    redirect($originurl);

} else if ($fromform = $mform->get_data()) {

    if ($fromform->scriptchoice == 1) {

        $sort = new sort_alphabetically();
        \core\task\manager::queue_adhoc_task($sort);
    }

} else {

    echo $OUTPUT->header();

    $mform->display();
}

echo $OUTPUT->footer();