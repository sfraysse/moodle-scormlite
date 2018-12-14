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
require_once('../../config.php');
require_once($CFG->dirroot.'/mod/scormlite/report/reportlib.php');
require_once($CFG->dirroot.'/mod/scormlite/locallib.php');

// Params
$scoid = required_param('scoid', PARAM_INT);            // SCO id
$userid = optional_param('userid',$USER->id,PARAM_INT);	// User id
$attempt = optional_param('attempt', 1, PARAM_INT);     // Attempt

// Objects and vars
$sco = $DB->get_record("scormlite_scoes", array("id"=>$scoid), '*', MUST_EXIST);
$activity = scormlite_get_containeractivity($scoid, $sco->containertype);
$cm = get_coursemodule_from_instance($sco->containertype, $activity->id, 0, false, MUST_EXIST);
$course = $DB->get_record("course", array("id"=>$cm->course), '*', MUST_EXIST);

//
// Page setup
//

$url = new moodle_url('/mod/scormlite/datamodel.php', array('scoid'=>$scoid, 'id'=>$cm->id, 'userid'=>$userid));
$PAGE->set_url($url);

//
// Continue
//

if (confirm_sesskey() && (!empty($scoid))) {

	// Never record information for none current user
	if ($USER->id == $userid) {
		$result = true;
		foreach (data_submitted() as $element => $value) {
			$element = str_replace('__','.',$element);
			if (!in_array($element, array('id', 'scoid', 'sesskey', 'attempt', 'userid'))) {
                
                // SF2018 - Signature change
				$res = scormlite_insert_track($userid, $scoid, $attempt, $element, $value, $sco->containertype);
				
                if (!$res) scormlite_debug_add_log($userid, $scoid, $attempt, 'Error recording \''.$element.'\' ['.$value.']');  
				$result = $res && $result;
			}
		}
	}

	// Check completion and grades
	if ($result) {
		scormlite_check_completion($userid, $activity, $cm, $course, $sco->containertype);
		scormlite_check_grades($userid, $activity, $cm, $course, $sco->containertype);
	}

	// Give a feedback to the client
	if ($result) {
		echo "true\n0";
	} else {
		echo "false\n101";
	}
} else {
    if (!confirm_sesskey()) scormlite_debug_add_log($userid, $scoid, $attempt, 'Session timeout');  /* KD2015 - Version 2.6.3 - Error logs */
	echo "false\n101";
}

