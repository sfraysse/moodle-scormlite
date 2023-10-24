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
$action  = optional_param('action', '', PARAM_ALPHA);  // 'save', ''

// Useful objects and vars
$cm = get_coursemodule_from_id('scormlite', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record("course", array("id"=>$cm->course), '*', MUST_EXIST);
$activity = $DB->get_record("scormlite", array("id"=>$cm->instance), '*', MUST_EXIST);
$sco = $DB->get_record("scormlite_scoes", array("id"=>$activity->scoid), '*', MUST_EXIST);

$scoclosed = $sco->manualopen == 2 || ($sco->manualopen == 0 && time() > $sco->timeclose);	

//
// Page setup 
//

$context = context_course::instance($course->id);  // KD2014 - 2.6 compliance
require_login($course->id, false, $cm);
require_capability('mod/scormlite:viewotherreport', $context);
$url = new moodle_url('/mod/scormlite/report/report.php', array('id'=>$cmid));
$PAGE->set_url($url);

//
// Save data
//

if ($action == 'save' && $scoclosed) {
	$users = array();
	foreach($_POST as $id => $val) {
		$exp = explode('_', $id);		
		if ($exp[0] == 'user') {
			if ($val == 'on') {
				$users[] = $exp[1];
			}
		}			
	}
	if (!empty($users)) {
		scormlite_delete_attempts($sco->id, $users, $course, $cm, $activity);
	}
}

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

$attemptwhat = $sco->whatgrade;
switch($attemptwhat) {
	case 0 :  // Highest
		echo '<p>'.get_string('highestattemptdesc', 'scormlite').'</p>';
		break;
	case 1 :  // First
		echo '<p>'.get_string('firstattemptdesc', 'scormlite').'</p>';
		break;
	case 2 :  // Last
		echo '<p>'.get_string('lastattemptdesc', 'scormlite').'</p>';
		break;
}

//
// Fetch data
//

$sql = "
	SELECT SST.userid, SST.scoid, U.firstname, U.lastname, U.idnumber, U.picture, U.imagealt, U.email
	FROM {scormlite_scoes_track} SST
	INNER JOIN {scormlite_scoes} SS ON SS.id=SST.scoid
	INNER JOIN {user} U ON U.id=SST.userid
	WHERE SST.element='x.start.time' AND SST.attempt='1' AND SST.scoid=".$sco->id;
$records = $DB->get_records_sql($sql);

// Store data and statistics
$students = array();
$scores = array();
foreach ($records as $record) {
	if (!array_key_exists($record->userid, $students) && !is_guest($context, $record->userid)) {
		// Add student
		$student = new stdClass();
		$student->name = $record->lastname." ".$record->firstname;
		$student->id = $record->userid;
		$student->picture = $record->picture;
		$student->imagealt = $record->imagealt;
		$student->email = $record->email;
		$student->firstname = $record->firstname;
		$student->lastname = $record->lastname;
        
        // KD2014 - 2.6 compliance
		$student->firstnamephonetic = '';
		$student->lastnamephonetic = '';
		$student->middlename = '';
		$student->alternatename = '';
        
		// Add time tracks
		$timetracks = scormlite_get_sco_runtime($sco->id, $student->id);
		$student->start = $timetracks->start;
		$student->finish = $timetracks->finish;
		// Add SCORM tracks
		$scotracks = scormlite_get_tracks($sco->id, $student->id);
		$student->status = $scotracks->status;
		$student->total_time = $scotracks->total_time;
		$student->attempt = $scotracks->attempt;
		$student->attemptnb = $scotracks->attemptnb;
		$student->score = null;
		$student->trainer = has_capability('mod/scormlite:viewotherreport', context_module::instance($cm->id), $student->id);  // KD2014 - 2.6 compliance
		// Score statistics
		if ($scotracks->score_scaled !== '' && ($scotracks->status == 'passed' || $scotracks->status == 'failed')) { // && $scoclosed) {
			$student->score = floatval($scotracks->score_scaled) * 100;
			if (!$student->trainer) {
				$student->scorerank = $student->score; // To calculate the rank
				$scores[] = $student->score;	// To calculate the average
			}
		}
		// Add it
		$students[$record->userid] = $student;
	}
}
// Average score
$avgscore = empty($scores) ? null : array_sum($scores) / count($scores);
if (isset($avgscore)) $avgscore = sprintf("%01.1f", $avgscore);

// Student ranks
scormlite_report_set_users_ranks($students);
uasort($students, 'scormlite_report_compare_users_by_name');

//
// Print table
//

if (empty($students)) {
	echo '<p>'.get_string('noreportdata', 'scormlite').'</p>';
} else {
	require_once($CFG->dirroot.'/lib/tablelib.php');

	// Sort
	//scormlite_report_sort_students_byname($students);

	$config = get_config('scormlite');
	$displayrank = $config->displayrank;
    
    /* KD2015 - Version 2.6.3 - Debug functions */
    $contextmodule = context_module::instance($cm->id);
	$debugget = $config->getscormstatus && has_capability('mod/scormlite:debugget', $contextmodule);
	$debugset = $config->setscormstatus && has_capability('mod/scormlite:debugset', $contextmodule);
    $debug = ($debugget || $debugset);

	// Define table columns
	$columns = array('select', 'picture', 'fullname', 'attempt', 'start', 'time', 'status', 'score');
	if ($displayrank) $columns[] = 'rank';
	if ($debug) $columns[] = 'debug'; /* KD2015 - Version 2.6.3 - Debug functions */
	$headers = array('', '', get_string('learner', 'scormlite'), get_string('attemptcap', 'scormlite'), get_string('started', 'scormlite'), get_string('time', 'scormlite'), get_string('status', 'scormlite'), get_string('score', 'scormlite'));
	if ($displayrank) $headers[] = get_string('rank', 'scormlite');
	if ($debug) $headers[] = get_string('debug', 'scormlite'); /* KD2015 - Version 2.6.3 - Debug functions */
	
	// Define table object
	$table = new flexible_table('mod-scormlite-report');
	$table->define_columns($columns);
	$table->define_headers($headers);
	$table->define_baseurl($url);

	// LMS presentation
	//$table->sheettitle = ''; // workaround to avoid moodle table crash when using exporter
	$row_exporter = new scormlite_table_lms_export_format();
	$table->export_class_instance($row_exporter);
	
	// Styles
	$table->column_class('select', 'select');
	$table->column_class('picture', 'picture');
	$table->column_class('fullname', 'fullname');
	$table->column_class('attempt', 'attempt');
	$table->column_class('start', 'start');
	$table->column_class('time', 'time');
	$table->column_class('status', 'status');
	$table->column_class('score', 'score');
	if ($displayrank) $table->column_class('rank', 'rank');
	if ($debug) $table->column_class('debug', 'debug'); /* KD2015 - Version 2.6.3 - Debug functions */

	// Setup
	$table->setup();

	// Print JS function
	echo '
		<script language="JavaScript">
			function toggle(source) {';
				foreach ($students as $userid => $student) {
					echo '
						checkbox = document.getElementById("user_'.$userid.'");
						checkbox.checked = source.checked;			
					';
				}
	echo '
			}
			function confirmSubmit() {
				var agree=confirm("'.get_string('deleteattempsconfirm', 'scormlite').'");
				if (agree) return true ;
				else return false ;
			}
		</script>
	';
	
	// Fill
	echo '<form name="reportform" action="'.$url.'&action=save" method="post">';
	$table->start_output();
	foreach ($students as $userid => $student) {
		$row = array();	
		// Check
		$row[] = '<input type="checkbox" name="user_'.$student->id.'" id="user_'.$student->id.'"/>';
		// User
		$row[] = $OUTPUT->user_picture($student);	
		$row[] = $student->lastname." ".$student->firstname;
		// Attempt
		$row[] = $student->attempt." / ".$student->attemptnb;
		// Time
		$row[] = userdate($student->start, get_string('strftimedatetimeshort', 'langconfig'));
		$row[] = scormlite_format_duration($student->total_time);
		// Status
		$strstatus = get_string($student->status, 'scormlite');
		$row[] = $strstatus;
		// Score
		$review_link = null;
		$reviewallowed = has_capability('mod/scormlite:reviewothercontent', context_module::instance($cm->id));
		//$reviewallowed = $reviewallowed && scormlite_has_review_access($sco, $student);
		if ($reviewallowed && isset($student->score)) $review_link = scormlite_report_get_link_review($sco->id, $student->id, $url);
		$row[] = array('score' => $student->score, 'link' => $review_link, 'colors' => $sco->colors);
		// Rank
		if ($displayrank) $row[] = $student->rank;
        
        /* KD2015 - Version 2.6.3 - Debug functions */
		if ($debug) {
            $path = $CFG->wwwroot.'/mod/scormlite/report/';
            $row[] = '<a href="'.$path.'debug.php?scoid='.$sco->id.'&userid='.$userid.'" target="_blank">'.get_string('debugopen', 'scormlite').'</a>';
        }
		//
		if ($student->trainer) $row['class'] = "trainer";
		$table->add_data($row);
	}
	// Average row
	$row = array();
	$row[] = '<input type="checkbox" name="all" id="all" onClick="toggle(this)"/>';
	$row[] = '';
	$row[] = get_string("averagescore", "scormlite");
	$row[] = '';
	$row[] = '';
	$row[] = '';
	$row[] = '';
	$row[] = array('score' => $avgscore, 'colors' => $sco->colors);
	if ($displayrank) $row[] = '';
	if ($debug) $row[] = ''; /* KD2015 - Version 2.6.3 - Debug functions */
	$row['class'] = "average";
	$table->add_data($row);
	// The end	
	$table->finish_output();
	
	// Buttons
	echo '<div style="margin-top:20px;">';
	if ($scoclosed) {
		echo '<input type="submit" value="'.get_string("deleteattemps", "scormlite").'" onClick="return confirmSubmit()" class="btn btn-secondary"/>';
	} else {
		echo '<button type="button" onClick="alert(\''.get_string("deleteattempsno", "scormlite").'\')" class="btn btn-secondary">'.get_string("deleteattemps", "scormlite").'</button>';		
	}
	if ($sco->quetzal_statistics == 1) {
		$statsLink = new moodle_url('/mod/scormlite/report/statistics.php', array('id'=>$cmid));
		echo '<a href="'.$statsLink.'" class="btn btn-secondary" style="margin-left:5px;">'.get_string("quetzal_statistics", "scormlite").'</a>';		
	}
	echo '</div>';
	echo '</form>';
}

//
// The end
//

echo $OUTPUT->footer();

?>