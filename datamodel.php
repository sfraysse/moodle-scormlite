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
$sessionid = required_param('sessionid', PARAM_RAW);    // Session id
$scoid = required_param('scoid', PARAM_INT);            // SCO id
$userid = optional_param('userid',$USER->id,PARAM_INT);	// User id
$attempt = optional_param('attempt', 1, PARAM_INT);     // Attempt

// Objects and vars
$sco = $DB->get_record("scormlite_scoes", array("id"=>$scoid), '*', MUST_EXIST);
$activity = scormlite_get_containeractivity($scoid, $sco->containertype);
$cm = get_coursemodule_from_instance($sco->containertype, $activity->id, 0, false, MUST_EXIST);
$course = $DB->get_record("course", array("id"=>$cm->course), '*', MUST_EXIST);

// Include hooks
$hookFile = $CFG->dirroot . '/mod/' . $sco->containertype . '/hooks.php';
if (file_exists($hookFile)) require_once($hookFile);

//
// Page setup
//

$url = new moodle_url('/mod/scormlite/datamodel.php', array('scoid'=>$scoid, 'id'=>$cm->id, 'userid'=>$userid));
$PAGE->set_url($url);

//
// Continue
//

if (confirm_sesskey() && (!empty($scoid))) {

	// Logs
	$usertrack = scormlite_get_tracks($scoid, $userid, $attempt);
	$review = $usertrack && ($usertrack->status == "passed" || $usertrack->status == "failed");
	$eventdata = [
		'sessionid' => $sessionid,
		'attempt' => $attempt,
		'launchmode' => $review ? 'review' : 'normal',
	];

	// Error management
	$result = true;

	// New status
	$terminated = false;
	$completed = false;
	$passed = false;
	$failed = false;
	$rawscored = false;
	$scaledscored = false;

	// Values
	$currenttime = false;
	$terminatetime = false;
	$rawscore = false;
	$scaledscore = false;
	
	// Parse submitted data
	foreach (data_submitted() as $element => $value) {
		$element = str_replace('__','.',$element);

		// SCORM elements: never record information for none current user
		if ($USER->id == $userid 
			&& !in_array($element, array('id', 'scoid', 'sesskey', 'attempt', 'userid', 'current_time', 'terminate_time'))) {

			// Record the element
			$res = scormlite_insert_track($userid, $scoid, $attempt, $element, $value, $sco->containertype);
			if ($res) {

				// Completion status
				if ($element == 'cmi.completion_status' && $value == 'completed') {
					$completed = $attempt > $usertrack->attempt
						|| ($attempt == $usertrack->attempt && $usertrack->completion_status != 'completed');
				}

				// Passed status
				if ($element == 'cmi.success_status' && $value == 'passed') {
					$passed = $attempt > $usertrack->attempt
						|| ($attempt == $usertrack->attempt && $usertrack->success_status != 'passed');
				}

				// Failed status
				if ($element == 'cmi.success_status' && $value == 'failed') {
					$failed = $attempt > $usertrack->attempt
						|| ($attempt == $usertrack->attempt && $usertrack->success_status != 'failed');
				}

				// Raw scored
				if ($element == 'cmi.score.raw') {
					$rawscored = $attempt > $usertrack->attempt
						|| ($attempt == $usertrack->attempt && $usertrack->score_raw != $value);
				}

				// Scaled scored
				if ($element == 'cmi.score.scaled') {
					$scaledscored =  $attempt > $usertrack->attempt
						|| ($attempt == $usertrack->attempt && $usertrack->score_scaled != $value);
				}

			} else {

				// Log the error
				scormlite_debug_add_log($userid, $scoid, $attempt, 'Error recording \'' . $element . '\' [' . $value . ']');  
			}

			// Global result
			$result = $res && $result;
		}

		// Non-SCORM elements
		if ($element == 'current_time') {
			$currenttime = $value;
		} else if ($element == 'terminate_time') {
			$terminated = true;
			$terminatetime = $value;
		}
	}
	if ($result) {
	
		// Logs

		// Passed or failed log
		if ($passed || $failed) {
			$usertrack = scormlite_get_tracks($scoid, $userid, $attempt);
			$successdata = $eventdata;
			$successdata['score_raw'] = "$usertrack->score_raw";
			$successdata['score_scaled'] = "$usertrack->score_scaled";
			$successdata['score_min'] = 0;
			$successdata['score_max'] = 100;
			$successdata['duration'] = $currenttime;
		}
		if ($passed) {

			// Passed log
			scormlite_trigger_sco_event('attempt_passed', $course, $cm, $activity, $sco, $userid, $successdata);

		} else if ($failed) {

			// Failed log
			scormlite_trigger_sco_event('attempt_failed', $course, $cm, $activity, $sco, $userid, $successdata);

		} else if ($completed) {

			// Completed log
			$eventdata['duration'] = $currenttime;
			scormlite_trigger_sco_event('attempt_completed', $course, $cm, $activity, $sco, $userid, $eventdata);
		}

		// Terminated log
		if ($terminated) {
			$eventdata['duration'] = $terminatetime;
			scormlite_trigger_sco_event('attempt_terminated', $course, $cm, $activity, $sco, $userid, $eventdata);
		}

		// Best track changed
		if ($passed || $failed) {
			$besttrack = scormlite_get_tracks($scoid, $userid);
			if ($besttrack->attempt == $attempt) {
				$successdata['attempt'] = $attempt;
				$successdata['success'] = $passed;
				unset($successdata['sessionid']);
				scormlite_trigger_scormlite_event('result_updated', $course, $cm, $activity, $successdata);
			}
		}

		// Check completion
		if ($completed) {
			scormlite_check_completion($userid, $activity, $cm, $course, $sco->containertype);
		}

		// Check grading
		if ($rawscored || $scaledscored) {
			scormlite_check_grades($userid, $activity, $cm, $course, $sco->containertype);
		}

		// Hooks

		// Completion hook
		if ($completed) {
			$function = $sco->containertype . '_hook_completion';
			if (function_exists($function)) $function($cm, $activity, $sco, $userid, $attempt);
		}
	}

	// Give a feedback to the client
	if ($result) {
		echo "true\n0";
	} else {
		echo "false\n101";
	}

} else {

	// Log the error
	if (!confirm_sesskey()) scormlite_debug_add_log($userid, $scoid, $attempt, 'Session timeout');

	// Give a feedback to the client
	echo "false\n101";
}

