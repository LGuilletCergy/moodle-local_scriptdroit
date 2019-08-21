<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

include('../../config.php');

require_login();

echo $OUTPUT->header();

echo $OUTPUT->footer();

if (is_siteadmin()) {

    $timestart = 1566399600;
    $idnumberstart = "Y2019-1";

    $sql = "SELECT * FROM {course} WHERE timecreated > $timestart AND idnumber LIKE '$idnumberstart*'";

    $listcourses = $DB->get_records_sql($sql);

    foreach ($listcourses as $course) {

        delete_course($course->id);
    }
}

