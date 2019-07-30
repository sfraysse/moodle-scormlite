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


header('Content-Type: text/javascript');

// Includes
require_once("../../config.php");
require_once($CFG->dirroot.'/mod/scormlite/report/reportlib.php');
require_once($CFG->dirroot.'/mod/scormlite/locallib.php');

// Params
$id = optional_param('id', '', PARAM_INT);              // Course Module ID
$scoid = required_param('scoid', PARAM_INT);            // SCO ID
$userid = optional_param('userid', $USER->id, PARAM_INT);	// User id
$backurl = optional_param('backurl','',PARAM_RAW);	// Back URL
$attempt = optional_param('attempt', 1, PARAM_INT);     // Attempt

// Objects and vars
$sco = $DB->get_record("scormlite_scoes", array("id"=>$scoid), '*', MUST_EXIST);
$activity = scormlite_get_containeractivity($scoid, $sco->containertype);
$cm = get_coursemodule_from_instance($sco->containertype, $activity->id, 0, false, MUST_EXIST);
$course = $DB->get_record("course", array("id" => $cm->course), '*', MUST_EXIST);

// Check back URL
$backurl = str_replace('&amp;', '&', $backurl);
if (empty($backurl)) $backurl = new moodle_url('/mod/'.$sco->containertype.'/view.php', array('id'=>$cm->id));
else if (strpos($backurl, '?') === false) $backurl .= "?scoid=".$scoid."&userid=".$userid;
else $backurl .= "&scoid=".$scoid."&userid=".$userid;



//
// Page setup
//

$url = new moodle_url('/mod/scormlite/api.php', array('id'=>$id, 'scoid'=>$scoid, 'userid'=>$userid, 'backurl'=>$backurl, 'attempt'=>$attempt));
$PAGE->set_url($url);

//
// Check permissions
//

require_login($cm->course, false, $cm); // Required for global vars init ($COURSE used to find language)


//
// Print the page
//

// Prepare userdata object for the JS

$select = 'SELECT DISTINCT '.$DB->sql_concat('u.id', '\'#\'', 'COALESCE(st.attempt, 0)').' AS uniqueid, ';
$select .= 'st.scoid AS scoid, st.attempt AS attempt, u.id AS userid, u.idnumber, u.firstname, u.lastname, u.picture, u.imagealt, u.email ';
$from = 'FROM {user} u ';
$from .= 'LEFT JOIN {scormlite_scoes_track} st ON st.userid = u.id AND st.scoid = '.$scoid;
$sort = ' ORDER BY u.firstname ASC, u.lastname ASC';
$params = array();

// Fetch the attempts

$attempts = $DB->get_records_sql($select.$from.$sort, $params);

// User data
if (! $user = $DB->get_record('user', array('id'=>$userid))) {
	print_error('invaliduser', scormlite);
}
$userdata = new stdClass();
$userdata->student_id = addslashes_js($user->username);
$userdata->student_name = addslashes_js($user->lastname .', '. $user->firstname);

// Tracking data
if ($usertrack = scormlite_get_tracks($scoid, $userid, $attempt)) {
	// All recorded tracks
	foreach ($usertrack as $key => $value) {
		$userdata->$key = addslashes_js($value);
	}
} else {
	// The only needed track for a new attempt
	$userdata->status = 'notattempted';
}
// Other data (activity settings)

$jsonstring = '{';
// Chrono
if ($sco->displaychrono == 1) $jsonstring .='"showChrono":true';
else $jsonstring .='"showChrono":false';
// Lang
$jsonstring .=', "lang":"'.current_language().'"';
// Colors
$colors = scormlite_get_config_colors($sco->containertype);
$thresholds = scormlite_parse_colors_thresholds($sco->colors);
foreach ($colors as $i => &$color) {
	if ($i >= count($thresholds)) { // should not happen
		$threshold = $thresholds[count($thresholds) - 1];
	} else {
		$threshold = $thresholds[$i];
	}
	$color->lt = $threshold;
}
unset($color);
$jcolors = json_encode($colors);
$jsonstring .= ", \"scoreColors\":$jcolors}";
// Launch data
$userdata->launch_data = $jsonstring;
$userdata->scaled_passing_score = (float)sprintf('%2.2f', $sco->passingscore)/100;
if ($sco->maxtime != 0) { // 0 means "no max time"
	$userdata->max_time_allowed = "PT".$sco->maxtime."M";         // Should check if >60 to format hours !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
}


//
// Logs
//

$sessionid = uuid();
$review = $usertrack && ($usertrack->status == "passed" || $usertrack->status == "failed");

scormlite_trigger_sco_event('attempt_launched', $course, $cm, $activity, $sco, $userid, [
	'sessionid' => $sessionid,
	'attempt' => $attempt,
	'launchmode' => $review ? 'review' : 'normal',
]);


// Include the JS local API
include_once($CFG->dirroot.'/mod/scormlite/datamodels/scorm_13.js.php');

// set the start time of this SCO
scormlite_insert_track($userid, $scoid, $attempt, 'x.start.time', time());


?>


var errorCode = "0"; function underscore(str) { str =
String(str).replace(/.N/g,"."); return str.replace(/\./g,"__"); }

