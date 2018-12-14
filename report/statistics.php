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
require_once('../../../config.php');
require_once($CFG->dirroot.'/mod/scormlite/report/reportlib.php');

// Params
$cmid = required_param('id', PARAM_INT); 

// Useful objects and vars
$cm = get_coursemodule_from_id('scormlite', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record("course", array("id"=>$cm->course), '*', MUST_EXIST);
$activity = $DB->get_record("scormlite", array("id"=>$cm->instance), '*', MUST_EXIST);
$sco = $DB->get_record("scormlite_scoes", array("id"=>$activity->scoid), '*', MUST_EXIST);

//
// Page setup 
//

$context = context_course::instance($course->id);  // KD2014 - 2.6 compliance
require_login($course->id, false, $cm);
require_capability('mod/scormlite:viewotherreport', $context);
$url = new moodle_url('/mod/scormlite/report/statistics.php', array('id'=>$cmid));
$PAGE->set_url($url);

//
// Print the page
//

// Start
scormlite_print_header($cm, $activity, $course);

// Tabs
$playurl = "$CFG->wwwroot/mod/scormlite/view.php?id=$cm->id";
$reporturl = "$CFG->wwwroot/mod/scormlite/report/report.php?id=$cm->id";
scormlite_print_tabs($cm, $activity, $playurl, $reporturl, 'report');

// Title and description
scormlite_print_title($cm, $activity);


// ----------- Relevant data ---------

// Print the stats
scormlite_report_print_quetzal_statistics($sco->id);


// Buttons
echo '<div style="margin-top:20px;">';
$reportLink = new moodle_url('/mod/scormlite/report/report.php', array('id'=>$cmid));
echo '<a href="'.$reportLink.'" class="btn btn-default" style="margin-left:5px;">'.get_string("quetzal_statistics_back", "scormlite").'</a>';		
echo '</div>';

echo '</form>';
	

//
// The end
//

echo $OUTPUT->footer();

?>