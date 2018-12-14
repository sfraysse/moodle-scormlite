<?php

/* * *************************************************************
 *  This script has been developed for Moodle - http://moodle.org/
 *
 *  You can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
  *
 * ************************************************************* */


// Includes
require_once("../../config.php");

// Params
$id   = required_param('id', PARAM_INT);          // Course module ID
$userid = optional_param('userid', null, PARAM_INT); // Graded user ID (optional)
$itemnumber = optional_param('itemnumber', null, PARAM_INT); // Item number, may be != 0 for activities that allow more than one grade per user

// Useful objects and vars
$cm = get_coursemodule_from_id('scormlite', $id, 0, false, MUST_EXIST);
$course = $DB->get_record("course", array("id"=>$cm->course), '*', MUST_EXIST);
$activity = $DB->get_record("scormlite", array("id"=>$cm->instance), '*', MUST_EXIST);

// Permissions
require_login($course->id, false, $cm);

// Redirections
if (has_capability('mod/scormlite:viewotherreport', context_module::instance($cm->id))) {  // KD2014 - 2.6 compliance
    redirect('report/report.php?id='.$cm->id);
} else {
    redirect('view.php?id='.$cm->id);
}
