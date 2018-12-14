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

// 
// KD2015 - Version 2.6.3 - Error logs functions
//

// Record a log

function scormlite_debug_add_log($userid, $scoid, $attempt, $title) {
    global $DB;
	$config = get_config('scormlite');
    if (!$config->recordlogs) return;
    $record = new stdClass();
    $record->scoid = $scoid;
    $record->userid = $userid;
    $record->attempt = $attempt;
    $record->title = $title;
    $record->data = scormlite_debug_get_data($userid, $scoid);
    $record->timestamp = time();
    $id = $DB->insert_record('scormlite_logs', $record);
    return $id;
}

function scormlite_debug_get_logs($userid, $scoid) {
    global $DB;
	$config = get_config('scormlite');
    $records = $DB->get_records('scormlite_logs', Array('scoid'=>$scoid, 'userid'=>$userid), 'timestamp');
    return $records;
}

function scormlite_debug_remove_logs($userid = null, $scoid = null) {
    global $DB;
    $cond = Array();
    if ($scoid) $cond['scoid'] = $scoid;
    if ($userid) $cond['userid'] = $userid;
    $DB->delete_records('scormlite_logs', $cond);
}

function scormlite_debug_get_data($userid, $scoid, $logid = 0) {
    global $DB;
    
    // Get data from logs
    if ($logid > 0) {
        $record = $DB->get_record('scormlite_logs', Array('id'=>$logid));
        if ($record) return $record->data;
        else return '';
    }
    
    // Get current data
    $data = '<debug module="scormlite" version="2.6.3">'."\n";
    
    // Attempts
    $attempts = scormlite_get_attempt_count($scoid, $userid);
    for ($i=1; $i<=$attempts; $i++) {
        $tracks = $DB->get_records('scormlite_scoes_track', array('userid'=>$userid, 'scoid'=>$scoid, 'attempt'=>$i));
        $data .= "\t".'<attempt number="'.$i.'">'."\n";
        foreach ($tracks as $track) {
            $data .= "\t\t".'<'.$track->element.' timemodified="'.$track->timemodified.'"><![CDATA['.$track->value.']]></'.$track->element.'>'."\n";
        }    
        $data .= "\t".'</attempt>'."\n";
    }
    
    // SCO settings
    $settings = $DB->get_record('scormlite_scoes', array('id'=>$scoid));
    $data .= "\t".'<settings>'."\n";
    foreach ($settings as $key=>$val) {
        $data .= "\t\t".'<'.$key.'><![CDATA['.$val.']]></'.$key.'>'."\n";
    }    
    $data .= "\t".'</settings>'."\n";
    
    $data .= '</debug>';
    return $data;
}


// 
// Print functions
//

// Print HTML basics

function scormlite_print_header_html($activity, $bodyid = null, $bodyclass = null) {
	global $CFG;
    if (empty($activity->code)) $pagetitle = format_string($activity->name);
	else $pagetitle = format_string('['.$activity->code.'] '.$activity->name);
	$encoding = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
	$body = '<body';
	if (isset($bodyid)) $body .= ' id="'.$bodyid.'"';
	if (isset($bodyclass)) $body .= ' class="'.$bodyclass.'"';
	$body .= '>';
	$cssbegin = '<style type="text/css">';
	$cssend = '</style>';
	$cssstandard = file_get_contents($CFG->dirroot.'/mod/scormlite/report/export.css');
	echo '<html><head>'.$encoding.'<title>'.$pagetitle.'</title>';
	echo $cssbegin.$cssstandard.$cssend;
	echo '</head>'.$body.'<div id="region-main">';
	return $pagetitle;
}

function scormlite_print_footer_html() {
	echo '</div></body></html>';
}

// Print heading

function scormlite_print_header($cm, $activity, $course) {
	global $PAGE, $OUTPUT;
	if (empty($activity->code)) $pagetitle = format_string($activity->name);
    else $pagetitle = format_string('['.$activity->code.'] '.$activity->name);
	$PAGE->set_title(strip_tags($pagetitle));
	$PAGE->set_heading($course->fullname);
	echo $OUTPUT->header();
	return $pagetitle;
}

// Print tabs

function scormlite_print_tabs($cm, $activity, $playurl, $reporturl, $defaulttab) {
    $contextmodule = context_module::instance($cm->id);  // KD2014 - 2.6 compliance
	if (has_capability('mod/scormlite:viewotherreport', $contextmodule)) {
		$row  = array();
		$row[] = new tabobject('play', $playurl, get_string('tabplay', 'scormlite'));
		$row[] = new tabobject('report', $reporturl, get_string('tabreport', 'scormlite'));
		$tabs = array();
		$tabs[] = $row;
		print_tabs($tabs, $defaulttab);
	}
}

// Print title
 
function scormlite_print_title($cm, $activity, $link = null) {
    if (empty($activity->code)) $pagetitle = format_string($activity->name);
    else $pagetitle = format_string('['.$activity->code.'] '.$activity->name);
    if ($link) $pagetitle .= ' '.$link;
	echo '<h2 class="main">'.$pagetitle.'</h2>';
}

// Print description

function scormlite_print_description($cm, $activity) {
	global $OUTPUT;
	if ($activity->intro != "") {
		echo $OUTPUT->box(format_module_intro('scormlite', $activity, $cm->id), 'generalbox', 'intro');
	}
}

// Print groupings

function scormlite_print_grouping($groupingid, $link = null) {
	global $DB;
    
    // KD2015-31 - End of "group members only" option
	// $grouping = $DB->get_record('groupings', array('id'=>$groupingid), 'id,name', MUST_EXIST);
	$grouping = $DB->get_record('groups', array('id'=>$groupingid), 'id,name', MUST_EXIST);

	$groupingtitle = get_string('groupresults', 'scormlite', $grouping->name);
	if ($link) $groupingtitle .= ' '.$link;
	echo '<h3 class="mdl-align grouping">'.$groupingtitle.'</h3>';
}

function scormlite_print_activity_grouping($cm, $activity, $link = null) {
	if (intval($cm->groupingid) != null) scormlite_print_grouping($cm->groupingid, $link);
}

function scormlite_print_usergroup_box($courseid, $groupings = null, $groupingid = null, $userid = null, $strtitle = '', $prestr1 = '', $prestr2 = '', $poststr = '', $select = true, $display = true) {
	global $OUTPUT, $DB;
	// If one grouping, select it
	if (count($groupings) == 1) {
		$keys = array_keys($groupings);
		$groupingid = $keys[0];
	}
	// Stop here if no display
	if ($display == false) return $groupingid;
	// Stop here if no groupings were found
	if (empty($groupings)) {
		echo $OUTPUT->box_start('generalbox mdl-align');
		if (!empty($userid)) {
			$user = $DB->get_record('user', array('id'=>$userid), '*', MUST_EXIST);
			$fullname = fullname($user);
			echo '<p>'.get_string('nousergroupingdata', 'scormlite', $fullname).'</p>';
		} else {
			echo '<p>'.get_string('nogroupingdata', 'scormlite').'</p>';		
		}
		echo $OUTPUT->box_end();
		echo $OUTPUT->footer();
		exit;
	} 
	// Title box
	echo $OUTPUT->box_start('generalbox mdl-align');
	echo $strtitle;
	if (count($groupings) == 1 || $select == false) {
		echo $prestr1.get_string('groupresults', 'scormlite', $groupings[$groupingid]->name).$poststr;
	} else {
		scormlite_print_groupings_selectform($courseid, $groupings, $groupingid, $userid, $prestr2, $poststr);
	}
	if (empty($groupingid)) {
		echo '<p>'.get_string('selectgrouping', 'scormlite').'</p>';
	}
	echo $OUTPUT->box_end();
	if (empty($groupingid)) {
		echo $OUTPUT->footer();
		exit;
	}
	return $groupingid;
}

function scormlite_print_groupings_selectform($courseid, $groupings, $groupingid = null, $userid = null, $prestr = '', $poststr = '') {
	// Combo
	$select_grouping_html = '<select name="groupingid" onchange="document.getElementById(\'choose_grouping_form\').submit()">';
	if (empty($groupingid)) $select_grouping_html .=  '<option value="" selected="selected">'.get_string("select", "scormlite").'</option>';
	foreach ($groupings as $grouping) {
		$select_grouping_html .= "<option value='$grouping->id'";
		if ($groupingid == $grouping->id) $select_grouping_html .= 'selected="selected"';
		$select_grouping_html .= ">$grouping->name</option>";
	}
	$select_grouping_html .= '</select>';
	$select_grouping_html = $prestr.$select_grouping_html.$poststr;
	// Form
	$select_grouping_html = '
		<form method="GET" id="choose_grouping_form" autocomplete="off">
			'.$select_grouping_html.'
			<input name="courseid" type="hidden" value="'.$courseid.'" />
			<input name="userid" type="hidden" value="'.$userid.'" />
			<input id="submit_grouping" class="btn btn-default" type="submit" />
		</form>
		<script>document.getElementById("submit_grouping").style.display="none"</script>';
	echo $select_grouping_html;
}

function scormlite_check_user_grouping($courseid, $userid, $groupingid, $activitytype = 'scormlite') {
	global $OUTPUT;
	$usergroupings = scormlite_report_get_user_groupings($courseid, $userid, $activitytype);
	if (!array_key_exists($groupingid, $usergroupings)) {
		echo $OUTPUT->box_start('generalbox mdl-align');
		echo '<p>'.get_string('notallowed', 'scormlite').'</p>';
		echo $OUTPUT->box_end();
		echo $OUTPUT->footer();
		exit;
	}
}

// Print personal profile
	
function scormlite_print_myprofile($cm) {
	$res = scormlite_get_myprofile($cm);
	echo $res[0];
	return $res[1];
}
function scormlite_get_myprofile($cm) {
	if (isloggedin() && !isguestuser()) {
		$html = '';
		global $DB, $OUTPUT, $CFG, $USER;
		$userid = $USER->id;
		$userdata = $DB->get_record('user', array('id'=>$userid), user_picture::fields());
		$html .= '<div class="myprofile">'."\n";
		$html .= $OUTPUT->user_picture($userdata, array('courseid'=>$cm->course));
		$html .= "<a href=\"$CFG->wwwroot/user/view.php?id=$userid&amp;course=$cm->course\">".
					"$userdata->firstname $userdata->lastname</a>";
		$html .= '</div>'."\n";
		return array($html, $userdata);
	}
	return array('', null);
}

// Print personal status

function scormlite_print_mystatus($cm, $sco, $showattempts = null, $showkeptattempt = true, $percentScore = true) {
	$res = scormlite_get_mystatus($cm, $sco, $showattempts, $showkeptattempt, $percentScore);
	echo $res[0];
	return $res[1];
}
function scormlite_get_mystatus($cm, $sco, $showattempts = null, $showkeptattempt = true, $percentScore = true) {
	global $CFG, $USER, $OUTPUT;
	$userid = $USER->id;
    $attempt = null;
    $attemptnumber = scormlite_get_attempt_count($sco->id, $userid);
    $attemptmax = $sco->maxattempt;
    $attemptwhat = $sco->whatgrade;
    $html = '<div class="status">'."\n";

    // Max attempts number
    if ($attemptmax == 0) {
        // Illimited number of attempts
        $html .= '<p>'."\n";
        $html .= get_string('noattemptsallowed', 'scormlite').':&nbsp;<span class="value">'.get_string('nolimit', 'scormlite').'</span>';
        $html .= '</p>'."\n";
    } else {
        // Limited number of attempts
        $html .= '<p>'."\n";
        $html .= get_string('noattemptsallowed', 'scormlite').':&nbsp;<span class="value">'.$attemptmax.'</span>';
        $html .= '</p>'."\n";
    }
    
    // No existing attempt
    if ($attemptnumber == 0) {
        $trackdata = new stdClass();
		$trackdata->status = 'notattempted';
        $html .= '<p>'."\n";
        $html .= get_string('noattemptsmade', 'scormlite').':&nbsp;<span class="value">0</span>';
        $html .= '</p>'."\n";
        $html .= '</div>'."\n";
        if (!$showattempts) {
            $html = '';
        }
    	return array($html, $trackdata);        
    }
    
    // Check completion of last attempt (suspended ?)
    $trackdata = scormlite_get_tracks($sco->id, $userid, $attemptnumber);

    if ($trackdata->completion_status != "completed") {

        // Made attempts
        $html .= '<p>'."\n";
        $html .= get_string('noattemptsmade', 'scormlite').':&nbsp;<span class="value">'.($attemptnumber-1).'</span>';
        $html .= '</p>'."\n";

        // Last attempt suspended
        $attempt = $attemptnumber;
    } else {
        // Made attempts
        $html .= '<p>'."\n";
        $html .= get_string('noattemptsmade', 'scormlite').':&nbsp;<span class="value">'.$attemptnumber.'</span>';
        $html .= '</p>'."\n";
    
        // Kept attempt
        if ($showkeptattempt) {
            $html .= '<p>'."\n";
            $html .= get_string('whatgrade', 'scormlite').':&nbsp;<span class="value">';
            switch($attemptwhat) {
                case 0 :  // Highest
                    $attempt = scormlite_get_highest_attempt($sco->id, $userid);
                    if (!isset($attempt)) $attempt = $attemptnumber; // If no score
                    $html .= get_string('highestattempt', 'scormlite').' ('.get_string('attempt', 'scormlite').' '.$attempt.')';
                    break;
                case 1 :  // First
                    $attempt = 1;
                    $html .= get_string('firstattempt', 'scormlite');
                    break;
                case 2 :  // Last
                    $attempt = $attemptnumber;
                    $html .= get_string('lastattempt', 'scormlite');
                    break;
            }
            $html .= '</span></p>'."\n";
        }
        if (!isset($attempt)) $attempt = $attemptnumber;  // Happens if not $showkeptattempt
    }
    if ($showattempts) {
        $html .= '<p>&nbsp;</p>'."\n";
    } else {
        $html = '<div class="status">'."\n";
    }
    
    if (has_capability('mod/scormlite:viewmyreport', context_module::instance($cm->id))) {  // KD2014 - 2.6 compliance    
        // Get data from attempt
        $trackdata = scormlite_get_tracks($sco->id, $userid, $attempt);

        //    Status
        $html .= '<p class="scormlite-status">'."\n";
        $strstatus = get_string($trackdata->status, 'scormlite');
        $html .= get_string('status', 'scormlite').':&nbsp;<span class="value">'.$strstatus.'</span>';
        $html .= '</p>'."\n";
        //    Time
        $html .= '<p class="scormlite-time">'."\n";
        $html .= get_string('time', 'scormlite').':&nbsp;<span class="value">'.scormlite_format_duration($trackdata->total_time).'</span>';
        $html .= '</p>'."\n";
        //    Score
        if ($trackdata->score_raw !== '') {
            $html .= '<p class="scormlite-score">'."\n";
            $html .= get_string('score', 'scormlite').':&nbsp;<span class="value">'.$trackdata->score_raw.($percentScore ? '%' : '').'</span>';
            $html .= '</p>'."\n";
        }
        //    Timestamps
        $timetracks = scormlite_get_sco_runtime($sco->id, $userid, $attempt);
        if (!empty($timetracks->start)) {
            $html .= '<p class="scormlite-start">'."\n";
            $html .= get_string('started', 'scormlite').':&nbsp;<span class="value">'.userdate($timetracks->start, get_string('strftimedatetimeshort', 'langconfig')).'</span>';
            $html .= '</p>'."\n";
        }
        if (!empty($timetracks->finish)) {
            $html .= '<p class="scormlite-finish">'."\n";
            $html .= get_string('last', 'scormlite').':&nbsp;<span class="value">'.userdate($timetracks->finish, get_string('strftimedatetimeshort', 'langconfig')).'</span>';
            $html .= '</p>'."\n";
        }
        $html .= '</div>'."\n";
    }
	return array($html, $trackdata);
}

// Print availability (or nothing if already available)

function scormlite_print_availability($cm, $sco, $trackdata, $available = true) {
	$res = scormlite_get_availability($cm, $sco, $trackdata, $available);
	echo $res[0];
	return $res[1];
}
function scormlite_get_availability($cm, $sco, $trackdata, $available = true) {
	$html = '';
	$achieved = $trackdata && ($trackdata->status == 'passed' || $trackdata->status == 'failed');
	$reviewmode = $achieved && ($sco->manualopen == 2 || ($sco->manualopen == 0 && time() > $sco->timeclose));
	$scormopen = true;
	if ($sco->manualopen == 3) {
		// Check auto open
		if (!$available) {
			$scormopen = false;
			if (!$reviewmode) { // No message if review mode is available
				$html .= '<div class="availability">'."\n";
				$html .= get_string("notautoopen", "scormlite");
				$html .= '</div>'."\n";
			}
		}
	} else if ($sco->manualopen == 2) {
		// Check if manually closed
		$scormopen = false;
		if (!$reviewmode) { // No message if review mode is available
			$html .= '<div class="availability">'."\n";
			$html .= get_string("notopen", "scormlite");
			$html .= '</div>'."\n";
		}
	} else if ($sco->manualopen == 0) {
		// Check dates
		$timenow = time();
		if (!empty($sco->timeopen) && $sco->timeopen > $timenow) {
			$scormopen = false;
			if (!$reviewmode) { // No message if review mode is available
				$html .= '<div class="availability">'."\n";
				$html .= get_string("notopenyet", "scormlite", userdate($sco->timeopen));
				$html .= '</div>'."\n";
			}
		}
		if ($scormopen && !empty($sco->timeclose) && $timenow > $sco->timeclose) {
			$scormopen = false;
			if (!$reviewmode) { // No message if review mode is available
				$html .= '<div class="availability">'."\n";
				$html .= get_string("expired", "scormlite", userdate($sco->timeclose));
				$html .= '</div>'."\n";
			}
		}
	}
	return array($html, $scormopen);
}

// Print possible actions (or nothing if not available)

function scormlite_print_myactions($cm, $sco, $trackdata, $scormopen = true, $backurl = null) {
	$res = scormlite_get_myactions($cm, $sco, $trackdata, $scormopen, $backurl);
	echo $res[0];
	return $res[1];
}
function scormlite_get_myactions($cm, $sco, $trackdata, $scormopen = true, $backurl = null, $actionDiv = true) {
    global $USER;
	$html = '';
	$userid = $USER->id;
	$playerurl = new moodle_url('/mod/scormlite/player.php', array('scoid' => $sco->id, 'backurl'=>$backurl));
	$achieved = ($trackdata->status == 'passed' || $trackdata->status == 'failed');
	$reviewModeOnly = $achieved && ($sco->manualopen == 2 || ($sco->manualopen == 0 && time() > $sco->timeclose));
	$reviewMode = $achieved && $sco->immediate_review == 1;
    $attemptnumber = scormlite_get_attempt_count($sco->id, $userid);
	$action = '';
	if ($achieved) {
		if (!isloggedin() || isguestuser()) {
			
			// Can be played by guests
			$action = 'start';
            $playerurl .= '&attempt=1';
			$html .= '<a href="'.$playerurl.'" class="btn btn-primary" role="button">'.get_string("start", "scormlite").'</a>';
			
		} else if ($reviewModeOnly && has_capability('mod/scormlite:reviewmycontent', context_module::instance($cm->id))) {   // KD2014 - 2.6 compliance
			
			// Can be reviewed
			$action = 'review';
            $attempt = scormlite_get_relevant_attempt($sco->id, $userid);
            $playerurl .= '&attempt='.$attempt;
			$html .= '<a href="'.$playerurl.'" class="btn btn-primary" role="button">'.get_string("review", "scormlite").'</a>';
			
        } else {
			
			// Review mode
			if ($reviewMode && has_capability('mod/scormlite:reviewmycontent', context_module::instance($cm->id))) {
				// Can be reviewed
				$action = 'review';
				$attempt = scormlite_get_relevant_attempt($sco->id, $userid);
				$playerurl .= '&attempt='.$attempt;
				$html .= '<a href="'.$playerurl.'" class="btn btn-primary" role="button">'.get_string("review", "scormlite").'</a>&nbsp;&nbsp;&nbsp;';
			}

            // Start a new attempt
            $attemptmax = $sco->maxattempt;
            if ($attemptmax == 0 || ($attemptnumber < $attemptmax)) {
                // Can start new attempt
                $action = 'newattempt';
                $playerurl .= '&attempt='.($attemptnumber+1);
				$html .= '<a href="'.$playerurl.'" class="btn btn-primary" role="button">'.get_string("newattempt", "scormlite").'</a>';
            }
		}
	} else if ($trackdata->status == 'notattempted' && $scormopen) {
		// Can start
		$action = 'start';
        if ($attemptnumber == 0) $playerurl .= '&attempt=1';
        else $playerurl .= '&attempt='.$attemptnumber;
		$html .= '<a href="'.$playerurl.'" class="btn btn-primary" role="button">'.get_string("start", "scormlite").'</a>';
	} else if ($trackdata->status == 'completed' && $trackdata->exit != 'suspend' && $scormopen) {
		// Can restart
		$action = 'restart';
        $playerurl .= '&attempt='.$attemptnumber;
		$html .= '<a href="'.$playerurl.'" class="btn btn-primary" role="button">'.get_string("restart", "scormlite").'</a>';
	} else if ($scormopen) {
		// Can resume, except if the activity is closed
		$action = 'resume';
        $playerurl .= '&attempt='.$attemptnumber;
		$html .= '<a href="'.$playerurl.'" class="btn btn-primary" role="button">'.get_string("resume", "scormlite").'</a>';
	}
	if ($actionDiv) $html = '<div class="actions">'.$html.'</div>'."\n";
	return array($html, $action);
}

// Print the report export buttons

function scormlite_print_exportbuttons($formats, $class = 'mdl-align exportcommands') { 
	echo '<div class="'.$class.'">';  /* KD2015 - Version 2.6.3 - More flexible layout */
	foreach($formats as $format=>$url) {
		if ($format == 'html' || $format == 'csv' || $format == 'xls') {
			$exporturl = new moodle_url($url, array('format'=>$format));
			echo '<input type="button" class="btn btn-default" value="'.get_string('export'.$format, 'scormlite').'" onClick="window.open(\''.$exporturl.'\');"/>';			
			echo '&nbsp;&nbsp;&nbsp;';
  		} else {
			$exporturl = new moodle_url($url, array('format'=>'xls'));
			echo '<input type="button" class="btn btn-default" value="'.get_string('exportbook'.$format, 'assessmentpath').'" onClick="window.open(\''.$exporturl.'\');"/>';			
			echo '&nbsp;&nbsp;&nbsp;';
		}
	}
	echo '</div>';
}



// 
// Get data structures
//

// Get groupings

function scormlite_report_get_course_groupings($courseid, $activitytype = "scormlite") {
	global $DB;

    // KD2015-31 - End of "group members only" option
    /*
	$sql = "
		SELECT DISTINCT(GI.id), GI.name
		FROM {groupings} GI
		INNER JOIN {course_modules} CM ON CM.groupingid=GI.id
		INNER JOIN {modules} M ON M.id=CM.module
		WHERE CM.course=$courseid AND M.name='$activitytype'";
	*/
	$sql = "
		SELECT DISTINCT(G.id), G.name
		FROM {groups} G
		WHERE G.courseid=$courseid";
        
	$res = $DB->get_records_sql($sql);
	return $res;
}

function scormlite_report_get_user_groupings($courseid, $userid, $activitytype = "scormlite") {
	global $DB;

    // KD2015-31 - End of "group members only" option
    /*
	$res = array();
	global $DB, $CFG;
	// User groupings (id=>grouplist)
	require_once($CFG->libdir.'/grouplib.php');
	$usergroupings = groups_get_user_groups($courseid, $userid);
	// Activity groupings (id=>name)
	$activitygroupings = scormlite_report_get_course_groupings($courseid, $activitytype);
	// Grouping records
	foreach ($usergroupings as $groupingid => $grouplist) {
		if (!empty($groupingid)) {  // Reject 0, which may happen
			if (array_key_exists($groupingid, $activitygroupings)) { // Keep it
				$res[$groupingid] = $activitygroupings[$groupingid];
			}
		}
	}
	*/
	$sql = "
		SELECT DISTINCT(G.id), G.name
		FROM {groups} G
		INNER JOIN {groups_members} GM ON GM.groupid=G.id
		WHERE GM.userid=$userid AND G.courseid=$courseid";
	$res = $DB->get_records_sql($sql);

	return $res;
}

// KD2015-31 - End of "group members only" option
// Get the groups found in the access conditions of an activity

function scormlite_report_get_activity_group($cmid) {
    $groups = scormlite_report_get_activity_groups($cmid);
    if (!empty($groups)) return $groups[0];
    return null;
}
function scormlite_report_get_activity_groups($cmid) {
	global $DB;
    $availability_record = $DB->get_record('course_modules', array('id' => $cmid));
    $groups = array();
    if (!isset($availability_record->availability)) return $groups;
    $json = json_decode($availability_record->availability);
    if (!is_object($json)) return $groups;
    $groupids = array();
    scormlite_report_get_activity_groups_collect($groupids, $json);
    foreach ($groupids as $groupid) {
        $groups[] = $DB->get_record('groups', array('id'=>$groupid));
    }
	return $groups;
}
function scormlite_report_get_activity_groups_collect(&$groupids, $json) {
    if (isset($json->c)) {
        foreach ($json->c as $comp) {
            scormlite_report_get_activity_groups_collect($groupids, $comp);
        }
    } else if (isset($json->type) && $json->type == 'group') {
        $groupids[] = $json->id;
    }
}


// Get users

function scormlite_report_populate_users(&$users, $courseid, $groupingid) {
	global $DB;

    // KD2015-31 - End of "group members only" option
    /*
	$sql = "
		SELECT U.id, U.firstname, U.lastname, U.idnumber, U.picture, U.imagealt, U.email, U.firstnamephonetic, U.lastnamephonetic, U.middlename, U.alternatename
		FROM {user} U
		INNER JOIN {groups_members} GM ON GM.userid=U.id
		INNER JOIN {groups} G ON G.id=GM.groupid
		INNER JOIN {groupings_groups} GIG ON GIG.groupid=G.id
		INNER JOIN {groupings} GI ON GI.id=GIG.groupingid
		WHERE GI.id=$groupingid
	";
	*/
    if (is_null($groupingid)) return array();
    /* Alternative (params must be added)
        // Get users enrolled in the course
        $courseid = $course->id;
        $context = context_course::instance($courseid, MUST_EXIST);
        $records = get_enrolled_users($context);
        // Keep only users who have access to this activity
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($cm->id);
        $availability = new \core_availability\info_module($cm);
        $records = $availability->filter_user_list($records);
    */
	$sql = "
		SELECT U.id, U.firstname, U.lastname, U.idnumber, U.picture, U.imagealt, U.email, U.firstnamephonetic, U.lastnamephonetic, U.middlename, U.alternatename
		FROM {user} U
		INNER JOIN {groups_members} GM ON GM.userid=U.id
		INNER JOIN {groups} G ON G.id=GM.groupid
		WHERE G.id=$groupingid
	";

    
	$context = context_course::instance($courseid, MUST_EXIST);
	$records = $DB->get_recordset_sql($sql);
	$userids = array();
	foreach ($records as $record) {
		if (!array_key_exists($record->id, $users)) {
			$user = new stdClass();
			$user->name = $record->lastname." ".$record->firstname;
			$user->id = $record->id;
			$user->picture = $record->picture;
			$user->imagealt = $record->imagealt;
			$user->email = $record->email;
			$user->firstname = $record->firstname;
			$user->lastname = $record->lastname;

            // KD2014 - 2.6 compliance
            $user->firstnamephonetic = $record->firstnamephonetic;
            $user->lastnamephonetic = $record->lastnamephonetic;
            $user->middlename = $record->middlename;
            $user->alternatename = $record->alternatename;
        
			$user->trainer = has_capability('mod/scormlite:viewotherreport', $context, $user->id);
			$users[$record->id] = $user;
			$userids[] = $user->id;
		}
	}
	uasort($users, 'scormlite_report_compare_users_by_name');
	return $userids;
}
function scormlite_report_populate_users_by_tracks(&$users, $courseid, $scoid) {
    $sql = "
	SELECT SST.userid, SST.scoid, U.firstname, U.lastname, U.idnumber, U.picture, U.imagealt, U.email
	FROM {scormlite_scoes_track} SST
	INNER JOIN {user} U ON U.id=SST.userid
	WHERE SST.element='x.start.time' AND SST.attempt='1' AND SST.scoid=".$scoid;
    return scormlite_report_populate_users_by_sql($users, $courseid, $sql);
}

function scormlite_report_populate_users_by_sql(&$users, $courseid, $sql) {
	global $DB;
	$context = context_course::instance($courseid, MUST_EXIST);
    $records = $DB->get_records_sql($sql);
	$userids = array();
    foreach ($records as $record) {
        if (!array_key_exists($record->userid, $users) && !is_guest($context, $record->userid)) {
            $user = new stdClass();
            $user->name = $record->lastname." ".$record->firstname;
            $user->id = $record->userid;
            $user->picture = $record->picture;
            $user->imagealt = $record->imagealt;
            $user->email = $record->email;
            $user->firstname = $record->firstname;
            $user->lastname = $record->lastname;
			$user->trainer = has_capability('mod/scormlite:viewotherreport', $context, $user->id);
			$users[$record->userid] = $user;
			$userids[] = $user->id;
        }
    }
    uasort($users, 'scormlite_report_compare_users_by_name');
	return $userids;
}
function scormlite_report_compare_users_by_name($user_record1, $user_record2) {
	if ($user_record1->name == $user_record2->name) return 0;
	return $user_record1->name < $user_record2->name ? -1 : 1;
}

// Get activities

function scormlite_report_populate_activities(&$activities, $courseid, $groupingid, $activitytype = "scormtype") {
    
	global $DB;
    
    // KD2015-31 - End of "group members only" option
    /*
	$sql = "
		SELECT A.id, A.code, A.name, A.colors, CM.id AS cmid
		FROM {assessmentpath} A
		INNER JOIN {course_modules} CM ON CM.instance=A.id AND CM.course=A.course
		INNER JOIN {modules} M ON M.id=CM.module
		INNER JOIN {groupings} GI ON GI.id=CM.groupingid
		WHERE A.course=$courseid AND M.name='$activitytype' AND GI.id=$groupingid
	";
	*/
	$sql = "
		SELECT A.id, A.code, A.name, A.colors, CM.id AS cmid
		FROM {assessmentpath} A
		INNER JOIN {course_modules} CM ON CM.instance=A.id AND CM.course=A.course
		INNER JOIN {modules} M ON M.id=CM.module
		WHERE A.course=$courseid AND M.name='$activitytype'
	";
	$records_all = $DB->get_records_sql($sql);
    // Keep only activities associated with group $groupingid
    $records = array();
	foreach ($records_all as $record_all) {
        $grouping = scormlite_report_get_activity_group($record_all->cmid);
        if (!is_null($grouping) && $grouping->id == $groupingid) $records[] = $record_all;
    }
    
	$scoids = array();
	foreach ($records as $record) {
		if (!array_key_exists($record->id, $activities)) {
			$activity = new stdClass();
			$activity->id = $record->id;
			$activity->code = $record->code;
			$activity->title = $record->name;
			$activity->colors = $record->colors;
			$activity->cmid = $record->cmid;
			$activities[$record->id] = $activity;
		}
	}
	return $scoids;
}


// 
// Quetzal reports
//

// Print Quetzal statistics

function scormlite_report_print_quetzal_statistics($scoid) {
	global $OUTPUT, $DB;
	
	// Build questions stats
	$questions = scormlite_report_get_quetzal_questions($scoid);
	if (empty($questions)) {
		echo '<p>'.get_string('quetzal_no_manifest', 'scormlite').'</p>';
		return;
	}

	// Build stats (based on a single attempt per user)
	$tracks = scormlite_get_tracks($scoid);
	scormlite_report_populate_quetzal_stats($tracks, $questions);
	
	// Display stats
	scormlite_report_print_quetzal_statistics_table($questions);
	
	// Display legend
	scormlite_report_print_quetzal_statistics_legend();
}

function scormlite_report_print_quetzal_statistics_table($questions) {

	// Prepare table data
	$rows = array();
	$i = 1;
	foreach($questions as $id => $question) {
		$cells = array();
		
		// Title
		$title = $i.'. '.$question->title;
		$cells[] = (object)array('content'=>$title, 'class'=>'statistics-question-title');
		
		// Width
		$correct = $question->stats->correct->percent;
		$incorrect = $question->stats->incorrect->percent;
		$noanswer = $question->stats->noanswer->percent;
		if ($correct + $incorrect + $noanswer != 100) {
			if ($correct != 0) $correct = 100 - $incorrect - $noanswer;
			else if ($incorrect != 0) $incorrect = 100 - $correct - $noanswer;
			else $noanswer = 100 - $correct - $incorrect;
		}
		
		// Bar
		$barHtml = '<div class="chart-bar">';
		$barHtml .= '<div style="width:'.$correct.'%;" class="stack-bar success">'.$correct.'%</div>';
		$barHtml .= '<div style="width:'.$incorrect.'%;" class="stack-bar danger">'.$incorrect.'%</div>';
		$barHtml .= '<div style="width:'.$noanswer.'%;" class="stack-bar neutral">'.$noanswer.'%</div>';
		$barHtml .= '</div>';
		$cells[] = (object)array('content'=>$barHtml, 'class'=>'statistics-question-bar');
		
		// Increment
		$rows[] = (object)array('cells'=>$cells);
		$i++;
	}
	
	// Table
	echo scormlite_report_get_table($rows);
}

function scormlite_report_print_quetzal_statistics_legend() {

	// Prepare table data
	$rows = array();

	// Correct answers
	$cells = array();
	$cells[] = (object)array('content'=>'', 'class'=>'statistics-legend-color success');
	$cells[] = (object)array('content'=>get_string('quetzal_correct_answer', 'scormlite'), 'class'=>'statistics-legend-label');
	$rows[] = (object)array('cells'=>$cells);

	// Wrong answers
	$cells = array();
	$cells[] = (object)array('content'=>'', 'class'=>'statistics-legend-color danger');
	$cells[] = (object)array('content'=>get_string('quetzal_wrong_answer', 'scormlite'), 'class'=>'statistics-legend-label');
	$rows[] = (object)array('cells'=>$cells);

	// No answers
	$cells = array();
	$cells[] = (object)array('content'=>'', 'class'=>'statistics-legend-color neutral');
	$cells[] = (object)array('content'=>get_string('quetzal_no_answer', 'scormlite'), 'class'=>'statistics-legend-label');
	$rows[] = (object)array('cells'=>$cells);
	
	// Table
	echo '<h5 style="margin-top:20px;">'.get_string('legend', 'scormlite').'</h5>';
	echo scormlite_report_get_table($rows);
}

// Populate Quetzal stats

function scormlite_report_populate_quetzal_stats($tracks, &$questions) {

	// Prepare data
	$count = 0;
	foreach($questions as $id => &$question) {
		$question->stats = (object)['correct' => (object)['count' => 0], 'incorrect' => (object)['count' => 0], 'noanswer' => (object)['count' => 0]];
	}

	// Parse tracks
	foreach($tracks as $userId => $track) {

		// Keep only completed attempts, with an answer
		if ($track->completion_status != 'completed') continue;
		$answers = scormlite_report_get_quetzal_answer($track->suspend_data);
		if (!$answers) continue;

		// Parse answers
		$count++;
		foreach($questions as $id => &$question) {
			if (!isset($answers[$id])) {
				$question->stats->noanswer->count++;
				continue;
			}
			$answer = $answers[$id];
			if (!isset($answer->s) || !isset($answer->a) || !isset($answer->st) || $answer->st != 'attempt') {
				$question->stats->noanswer->count++;
			} else if ($answer->s > 0) {
				$question->stats->correct->count++;
			} else {
				$question->stats->incorrect->count++;
			}
		}
	}

	// Add percents
	foreach ($questions as $id => &$question) {
		$question->stats->noanswer->percent = intval($question->stats->noanswer->count * 100.0 / $count);
		$question->stats->correct->percent = intval($question->stats->correct->count * 100.0 / $count);
		$question->stats->incorrect->percent = intval($question->stats->incorrect->count * 100.0 / $count);
	}
}

// Get Quetzal answer

function scormlite_report_get_quetzal_answer($suspend) {
	$suspend = '{'.str_replace("\t", '"', $suspend).'}';
	$json = json_decode($suspend);
	if (!$json) return false;
	if (!isset($json->assmnt)) return false;
	$json = (array)$json->assmnt;
	if (!isset($json['#'])) return false;
	return (array)$json['#'];
}

// Get Quetzal questions

function scormlite_report_get_quetzal_questions($scoid) {
    global $DB;
    $records = $DB->get_records('scormlite_quetzal_questions', array('scoid'=>$scoid));
    $questions = array();
    foreach($records as $record) {
        $question = new stdClass();
        $question->title = $record->title;
        $questions[$record->manifestid] = $question;
    }
    return $questions;
}


// 
// Useful functions to build reports
//

// Get an HTML table

function scormlite_report_get_table($rows, $head = null, $collapsible = false, $collapseId = null) {
	$res = '<table class="table scormlite-table">';
	if (isset($head)) {
		if (isset($head->class)) $res .= '<thead class="'.$head->class.'"><tr>';
		else $res .= '<thead><tr>';
		foreach($head->cells as $cell) {
			$class = '';
			if (isset($cell->class)) $class = ' class="scormlite-cell-'.$cell->class.'"';
			$res .= '<th'.$class.'>'.$cell->content.'</th>';
		}
		$res .= '</tr></thead>';
	}
	if ($collapsible) $res .= '<tbody class="collapse" id="table-body-'.$collapseId.'">';
	else $res .= '<tbody>';
	foreach($rows as $row) {
		$res .= '<tr>';
		foreach($row->cells as $cell) {
			$class = '';
			if (isset($cell->class)) $class = ' class="scormlite-cell-'.$cell->class.'"';
			$res .= '<td'.$class.'>'.$cell->content.'</td>';
		}
		$res .= '</tr>';
	}
	$res .= '</tbody></table>';
	return $res;
}

// Set user ranks

function scormlite_report_set_users_ranks(&$users) {
	uasort($users, 'scormlite_report_compare_users_by_score');
	$rank = 1;
	$lastuser = null;
	foreach ($users as &$user) {
		if (isset($user->scorerank)) {
			if (isset($lastuser) && $lastuser->scorerank > $user->scorerank) {
				$rank += 1;
			}
			$user->rank = $rank;
			$lastuser = $user;
		} else {
			$user->rank = null;
		}
	}
}
function scormlite_report_compare_users_by_score($user_record1, $user_record2) {
	if (!isset($user_record2->scorerank)) return -1;
	if (!isset($user_record1->scorerank)) return 1;
	if ($user_record1->scorerank == $user_record2->scorerank) return 0;
	return $user_record1->scorerank > $user_record2->scorerank ? -1 : 1;
}

// Get link to review a content

function scormlite_report_get_link_review($scoid, $userid, $backurl) {
	global $CFG, $OUTPUT;
    $attempt = scormlite_get_relevant_attempt($scoid, $userid);
    if ($attempt == 0) return '';
	$reviewurl = new moodle_url('/mod/scormlite/player.php', array('scoid'=>$scoid, 'userid'=>$userid, 'attempt'=>$attempt, 'backurl'=>$backurl));
	$strreview = get_string('review', 'scormlite');
	
	// SF2017 - Remove pix_url
	//$reviewlink = '<a title="'.$strreview.'" href="'.$reviewurl.'"><img src="'.$OUTPUT->pix_url('i/search') . '" class="icon" alt="'.$strreview.'" /></a>';
	$reviewlink = '<a title="'.$strreview.'" href="'.$reviewurl.'">'.$OUTPUT->pix_icon('review', $strreview, 'mod_scormlite').'</a>';
	
	return $reviewlink;
}

// 
// Score colors
//

require_once($CFG->dirroot.'/lib/tablelib.php');

// NNX2016 - Added to ensure compatibility since these functions are not in the original file anymore (Moodle 3.1)
require_once($CFG->dirroot.'/mod/scormlite/report/tablelib.php');

class scormlite_table_lms_export_format extends table_default_export_format_parent {

	private $config_colors;

	public function __construct($plugin_name = "scormlite") {
		$this->config_colors = scormlite_get_config_colors($plugin_name);
	}
	
	public function add_data($row) {
		global $OUTPUT;
		// Class
		$class = '';
		if (array_key_exists('class', $row)) {
			$class = $row['class']." ";
			unset($row['class']);
		}
		static $oddeven = 1;
		$oddeven = $oddeven ? 0 : 1;
		$class .= "r".$oddeven;
		// Start row
		echo html_writer::start_tag('tr', array('class' => $class));
		$colbyindex = array_flip($this->table->columns);
		foreach ($row as $index => $data) {
			$column = $colbyindex[$index];
			$style = '';
			if (is_array($data)) {
				$cell = $data['score'];
				if (isset($data['score']) && array_key_exists('colors', $data) && !empty($data['colors'])) {
					$color = $this->get_score_color($data['colors'], $data['score']);
					$style = ';background-color:'.$color;
				}
				if (array_key_exists('link', $data) && !empty($data['link'])) {
					$cell .= '<br/>'.$data['link'];
				}
			} else {
				$cell = $data;
			}
			// Cell
			echo html_writer::tag('td', $cell, array(
				'class' => 'cell c' . $index . $this->table->column_class[$column],
				'style' => $this->table->make_styles_string($this->table->column_style[$column]).$style));
		}
		echo html_writer::end_tag('tr');
	}
	public function start_table($title) {
		$this->table->start_html();
	}
	public function output_headers($headers) {
		$this->table->print_headers();
	}
	public function finish_table() {
		$this->table->finish_html();
	}
	public function finish_document() {
	}

	// Private
	
	private function get_score_color($colors, $score) {
		$thresholds = scormlite_parse_colors_thresholds($colors);
		foreach ($thresholds as $i => $threshold) {
			if (floatval($score) < floatval($threshold)) break;
		}
		if ($i >= count($this->config_colors)) $i = count($this->config_colors) - 1;
		return $this->config_colors[$i]->color;
	}
}

class scormlite_table_html_export_format extends scormlite_table_lms_export_format {

    function add_data($row) {
		if (array_key_exists('class', $row)) {
			if ($row['class'] == 'trainer') {
				return true;
			}
		}
		return parent::add_data($row);
	}	
}

class scormlite_table_xls_export_format extends table_excel_export_format {

	private $config_colors;
	private $item_colors;

	public function __construct($plugin_name = "scormlite") {
		$this->start_document('export');
		$this->config_colors = scormlite_get_config_colors($plugin_name);
		// Format
		$this->formatheaders->set_border(1);
	}

    // KD2014 - Compliance with Moodle 2.5. Overrides parent function.
    function add_seperator() {
        $this->rownum++;
    }

    function start_worksheet($code, $titles, $colwidth = array(), $colnumber = null) {
		if (empty($colwidth)) $colwidth = array(30, 10);
		if (empty($colnumber)) $colnumber = 2;
		else $colnumber = max($colnumber-1, 2);
        $this->rownum=0;
        $this->worksheet = $this->workbook->add_worksheet($code);
        
        // KD2014 - Compliance with Moodle 2.5. PHPExcel used.
		$this->worksheet->set_column(0, 0, $colwidth[0]);
		$this->worksheet->set_column(1, 20, $colwidth[1]);
		//$this->worksheet->pear_excel_worksheet->setColumn(0, 0, $colwidth[0]);
		//$this->worksheet->pear_excel_worksheet->setColumn(1, 20, $colwidth[1]);
        
		// Titles
		foreach($titles as $index=>$title) {
			$this->add_sheet_title($title, $index, $colnumber);			
		}
		$this->add_seperator();
		$this->add_seperator();
    }

    function start_table($sheettitle) {
		// Keep it to override the parent
    }

    function output_headers($headers) {
        $colnum = 0;
        foreach ($headers as $item) {
            $this->worksheet->write_string($this->rownum,$colnum,$item,$this->formatheaders);
            $colnum++;
        }
        $this->rownum++;
    }

    function add_data($row) {
		$isaverage = false;
		if (array_key_exists('class', $row)) {
			if ($row['class'] == 'trainer') {
				return true;
			} else if ($row['class'] == 'average') {
				$isaverage = true;
			}
		}
        $colnum = 0;
        foreach ($row as $key => $item) {
			if (is_numeric($key)) {
				$format = $this->workbook->add_format();
				$format->set_border(1);
				if (is_array($item)) {
					$val = strval($item['score']).'%';
					if (isset($item['score']) && array_key_exists('colors', $item) && !empty($item['colors'])) {
						$color = $this->get_score_color($item['colors'], $item['score']);
						$this->item_colors = $item['colors'];
						$format->set_bg_color($color);  // KD2014 - Compliance with Moodle 2.5
						$format->set_align('center');
					}
				} else {
					$val = strval($item);
					if ($colnum > 0) $format->set_align('center');
					if ($isaverage == true) {
						$format->set_align('right');
						$format->set_border(0);
						$format->set_bold(1);
					}
				}
				$this->worksheet->write($this->rownum,$colnum,$val,$format);
				$colnum++;
			}
        }
        $this->rownum++;
        return true;
    }
	
    function finish_table() {
		parent::finish_table();
		$this->add_seperator();
    }

    function add_break() {
 		$this->add_seperator();
		$this->add_seperator();
	}
	
    function add_legend() {
		$this->add_seperator();
		// Legend title
		$format = $this->workbook->add_format();
		$format->set_align('right');
		$this->worksheet->write($this->rownum,0,get_string('legend', 'scormlite'),$format);
		unset($format);
		// Colors
		$i = 0;
		foreach ($this->config_colors as $color) {
			$format = $this->workbook->add_format();
			$color = $this->get_color($i);
			$format->set_bg_color($color);  // KD2014 - Compliance with Moodle 2.5
			$format->set_align('center');
			$format->set_border(1);
			// Score val
			$score = '';
			if (isset($this->item_colors)) {
				$thresholds = scormlite_parse_colors_thresholds($this->item_colors);
				if ($i == count($this->config_colors)-1) {
					$score = '<=100%';					
				} else {
					$score = '<'.strval($thresholds[$i]).'%';					
				}
			}
			
			$this->worksheet->write($this->rownum,1,$score,$format);
	        $this->rownum++;
			unset($format);
			$i++;
		}
		$this->add_seperator();
    }

	function add_section_title($title) {
		$format = $this->workbook->add_format();
		$format->set_bg_color('#cccccc');  // KD2014 - Compliance with Moodle 2.5
		$format->set_border(1);
		$format->set_bold(1);
		$format->set_align('center');
		$format->set_align('vcenter');		

        // KD2014 - Compliance with Moodle 2.5. PHPExcel used.
		$this->worksheet->merge_cells($this->rownum, 0, $this->rownum+1, 0); 
		//$this->worksheet->pear_excel_worksheet->setMerge($this->rownum, 0, $this->rownum+1, 0);
        
        $this->worksheet->write_string($this->rownum,0,$title,$format);
        $this->worksheet->write_string($this->rownum+1,0,'',$format);
        $this->rownum++;		
        $this->rownum++;		
	}
	
	function add_comment($comment) {
		$this->add_seperator();
		$format = $this->workbook->add_format();
		$format->set_bold(1);
        
        // KD2014 - Compliance with Moodle 2.5. PHPExcel used.
		$this->worksheet->merge_cells($this->rownum, 0, $this->rownum, 6); 
		//$this->worksheet->pear_excel_worksheet->setMerge($this->rownum, 0, $this->rownum, 6);
        
		$this->worksheet->write_string($this->rownum,0,get_string('comments', 'assessmentpath'),$format);
	    $this->rownum++;
		$format = $this->workbook->add_format();
		$format->set_align('vcenter');		
		// Count the number of lines (approximatively) and add one more line (for security)
		$format->set_text_wrap();
		$lines = explode(PHP_EOL, $comment);
		$linenb = 0;
		foreach ($lines as $line) {
			$sublinesnb = floor(strlen($line) / 85)+1;
			$linenb += $sublinesnb;
		}
        
        // KD2014 - Compliance with Moodle 2.5. PHPExcel used.
		$this->worksheet->merge_cells($this->rownum, 0, $this->rownum+$linenb, 6); 
		//$this->worksheet->pear_excel_worksheet->setMerge($this->rownum, 0, $this->rownum+$linenb, 6);
        
		$comment = str_replace(chr(13), '', $comment);
        $this->worksheet->write_string($this->rownum,0,$comment,$format);
        $this->rownum += $linenb;		
	}
	
	// Private
	
    private function add_sheet_title($title, $index, $colnumber) {
		$format = $this->workbook->add_format();
		$format->set_bg_color('#cccccc');  // KD2014 - Compliance with Moodle 2.5
		$format->set_align('center');
		if ($index == 0) {
			$format->set_size(11);
			$format->set_italic(1);
		} else if ($index == 1) {
			$format->set_size(16);
			$format->set_bold(1);			
		} else if ($index == 2) {
			$format->set_size(13);
			$format->set_bold(1);			
		} else if ($index == 3) {
			$format->set_size(11);
		}
        
        // KD2014 - Compliance with Moodle 2.5. PHPExcel used.
		$this->worksheet->merge_cells($this->rownum, 0, $this->rownum, $colnumber); 
		//$this->worksheet->pear_excel_worksheet->setMerge($this->rownum, 0, $this->rownum, $colnumber);
        
        $this->worksheet->write_string($this->rownum,0,$title,$format);
        $this->rownum++;
    }

	private function get_score_color($colors, $score) {
		$thresholds = scormlite_parse_colors_thresholds($colors);
		foreach ($thresholds as $i => $threshold) {
			if (floatval($score) < floatval($threshold)) break;
		}
		if ($i >= count($this->config_colors)) $i = count($this->config_colors) - 1;
		// return $this->config_colors[$i]->color;
		return $this->get_color($i);
	}
	
    // KD2014 - Color num values replaced by hexa vals for Moodle 2.5 compliance
	private function get_color($i) {
		if ($i==0) return "#D53B3B";
		else if ($i==1) return "#EF7A00";
		else if ($i==2) return "#FDC200";
		else if ($i==3) return "#85C440";
	}
}

class scormlite_table_csv_export_format extends table_csv_export_format {

	protected $seperator = "semicolon";
	
	public function __construct($plugin_name = "scormlite") {
        $this->myexporter = new csv_export_writer($this->seperator, '"', $this->mimetype);
		$this->start_document('export');
	}
	
    function add_data($row) {
		if (array_key_exists('class', $row)) {
			if ($row['class'] == 'trainer') {
				return true;
			}
		}
        foreach ($row as $key => $data) {
        	if (is_array($data)) {
				$row[$key] = $data['score'];
			}
        }
		return parent::add_data($row);
	}
}

function scormlite_parse_colors_thresholds($thresholds_as_string) {
	if (is_array($thresholds_as_string)) {
		// In case the colors are not a string, but a JSON struct
		$thresholds = array();
		foreach($thresholds_as_string as $threshold) {
			$thresholds[] = $threshold->lt;
		}
	} else {
		// If colors are a string
		$thresholds = explode(',', $thresholds_as_string);
	}
	sort($thresholds);
	return $thresholds;
}

function scormlite_get_config_colors($plugin_name = "scormlite") {
	$jcolors = get_config($plugin_name, 'colors');
	//assert($jcolors != false);
	$colors = json_decode("[$jcolors]");
	usort($colors, 'scormlite_compare_colors');
	return $colors;
}
function scormlite_compare_colors($c1, $c2) {
	return $c1->lt > $c2->lt;
}

// 
// Report functions for the module lib
//

// User outline: used in cours+user activity report

function scormlite_sco_user_outline($scoid, $userid) {
	$info = new stdClass();
	$info->time = 0;
	$trackdata = scormlite_get_tracks($scoid, $userid);
	if ($trackdata) {
		$info->info = get_string($trackdata->status, 'scormlite');
		$timetracks = scormlite_get_sco_runtime($scoid, $userid);
		if (!empty($timetracks->finish)) {
			$info->time = $timetracks->finish;
		}
	} else {
		$info->info = get_string('notattempted', 'scormlite');
	}
	return $info;
}

// User complete: used in cours+user activity report

function scormlite_sco_user_complete($scoid, $userid)	{
	$trackdata = scormlite_get_tracks($scoid, $userid);
	if ($trackdata) {
		$strinfo = get_string('status', 'scormlite').': '.get_string($trackdata->status, 'scormlite');
		$strinfo .= ', '.get_string('time', 'scormlite').': '.scormlite_format_duration($trackdata->total_time);
		if ($trackdata->score_raw !== '') {
			$strinfo .= ', '.get_string('score', 'scormlite').': '.$trackdata->score_raw.'%';
		}
		$timetracks = scormlite_get_sco_runtime($scoid, $userid);
		if (!empty($timetracks->start)) {
			$strinfo .= ', '.get_string('started', 'scormlite').': '.userdate($timetracks->start, get_string('strftimedatetimeshort', 'langconfig'));
		}
		if (!empty($timetracks->finish)) {
			$strinfo .= ', '.get_string('last', 'scormlite').': '.userdate($timetracks->finish, get_string('strftimedatetimeshort', 'langconfig'));
		}
	} else {
		$strinfo = get_string('notattempted', 'scormlite');
	}
	return $strinfo;
}

// 
// Data format
//

// Transform SCORM time format to a reading format

function scormlite_format_duration($duration) {
	// fetch date/time strings
	$stryears = get_string('years');
	$strmonths = get_string('nummonths');
	$strdays = get_string('days');
	$strhours = get_string('hours');
	$strminutes = get_string('minutes');
	$strseconds = get_string('seconds');
	if ($duration[0] == 'P') {
		// if timestamp starts with 'P' - it's a SCORM 2004 format
		// this regexp discards empty sections, takes Month/Minute ambiguity into consideration,
		// and outputs filled sections, discarding leading zeroes and any format literals
		// also saves the only zero before seconds decimals (if there are any) and discards decimals if they are zero
		$pattern = array( '#([A-Z])0+Y#', '#([A-Z])0+M#', '#([A-Z])0+D#', '#P(|\d+Y)0*(\d+)M#', '#0*(\d+)Y#', '#0*(\d+)D#', '#P#',
                          '#([A-Z])0+H#', '#([A-Z])[0.]+S#', '#\.0+S#', '#T(|\d+H)0*(\d+)M#', '#0*(\d+)H#', '#0+\.(\d+)S#', '#0*([\d.]+)S#', '#T#' );
		$replace = array( '$1', '$1', '$1', '$1$2 '.$strmonths.' ', '$1 '.$stryears.' ', '$1 '.$strdays.' ', '',
                          '$1', '$1', 'S', '$1$2 '.$strminutes.' ', '$1 '.$strhours.' ', '0.$1 '.$strseconds, '$1 '.$strseconds, '');
	} else {
		// else we have SCORM 1.2 format there
		// first convert the timestamp to some SCORM 2004-like format for conveniency
		$duration = preg_replace('#^(\d+):(\d+):([\d.]+)$#', 'T$1H$2M$3S', $duration);
		// then convert in the same way as SCORM 2004
		$pattern = array( '#T0+H#', '#([A-Z])0+M#', '#([A-Z])[0.]+S#', '#\.0+S#', '#0*(\d+)H#', '#0*(\d+)M#', '#0+\.(\d+)S#', '#0*([\d.]+)S#', '#T#' );
		$replace = array( 'T', '$1', '$1', 'S', '$1 '.$strhours.' ', '$1 '.$strminutes.' ', '0.$1 '.$strseconds, '$1 '.$strseconds, '' );
	}
	$result = preg_replace($pattern, $replace, $duration);
	return $result;
}

function scormlite_format_duration_for_csv($duration) {  // SCORM 2004 only
    $pattern = array( '#([A-Z])0+Y#', '#([A-Z])0+M#', '#([A-Z])0+D#', '#P(|\d+Y)0*(\d+)M#', '#0*(\d+)Y#', '#0*(\d+)D#', '#P#',
                      '#([A-Z])0+H#', '#([A-Z])[0.]+S#', '#\.0+S#', '#T(|\d+H)0*(\d+)M#', '#0*(\d+)H#', '#0+\.(\d+)S#', '#0*([\d.]+)S#', '#T#' );
    $replace = array( '$1', '$1', '$1', '$1$2Y', '$1M', '$1D ', '',
                      '$1', '$1', 'S', '$1$2m', '$1h', '0.$1s', '$1s', '');
	$result = preg_replace($pattern, $replace, $duration);
	return $result;
}

function scormlite_format_duration_from_ms_for_csv($duration) {
    $duration = $duration / 1000;
    $hours = intval($duration / 3600);
    $rest = $duration % 3600;
    $minutes = intval($rest / 60);
    $seconds = $rest % 60;
    $time = '';
    if ($hours > 0) $time .= $hours.'h';
    if ($minutes > 0) $time .= $minutes.'m';
    if ($seconds > 0) $time .= $seconds.'s';
    return $time;
}


// 
// Track data extract
//

// Get SCO tracks for a user and attempt

function scormlite_get_tracks($scoid, $userid=null, $attempt=null) {
	global $CFG, $DB;
	if (empty($userid)) {
		if ($alltracks = $DB->get_records('scormlite_scoes_track', array('scoid'=>$scoid),'element ASC')) {
			$userstracks = array();
			foreach($alltracks as $tracks) {
				if (!array_key_exists($tracks->userid, $userstracks)) {
					$userstracks[$tracks->userid] = array();
				}
				$userstracks[$tracks->userid][] = $tracks;
			}
			$res = array();
			foreach($userstracks as $userid => $tracks) {
				$res[$userid] = scormlite_get_track($tracks, $scoid, $userid, $attempt);
			}
			return $res;
		} else {
			return false;
		}
	} else {
		if ($tracks = $DB->get_records('scormlite_scoes_track', array('userid'=>$userid, 'scoid'=>$scoid),'element ASC')) {
			return scormlite_get_track($tracks, $scoid, $userid, $attempt);
		} else {
			return false;
		}
	} 
}
	
function scormlite_get_track($tracks, $scoid, $userid, $attempt=null) {
    if (!isset($attempt)) {
        $attempt = scormlite_get_relevant_attempt($scoid, $userid);
        if ($attempt == 0) return null;
    }
	// Create the usertrack object
	$usertrack = new stdClass();
	// IDs
	$usertrack->userid = $userid;
	$usertrack->scoid = $scoid;
	// Pre-sets
	$usertrack->attempt = $attempt;
	$usertrack->attemptnb = scormlite_get_attempt_count($scoid, $userid);
	$usertrack->session_time = '00:00:00';
	$usertrack->total_time = '00:00:00';
	$usertrack->score_raw = '';
	$usertrack->score_scaled = '';
	$usertrack->status = 'notattempted';  // We keep it only for compatibility with legacy code
	$usertrack->completion_status = 'notattempted';
	$usertrack->success_status = 'unknown';
	$usertrack->suspend_data = '';
	$usertrack->exit = '';
	// Collect all defined elements
	$usertrack->timemodified = 0;
	foreach ($tracks as $track) {
        if ($track->attempt == $attempt) {
            $element = $track->element;
            $usertrack->{$element} = $track->value;
            switch ($element) {
                case 'cmi.success_status':
                    $usertrack->success_status = $track->value;
                    break;
                case 'cmi.completion_status':
                    $usertrack->completion_status = $track->value;
                    break;
                case 'cmi.session_time':
                    $usertrack->session_time = $track->value;
                    break;
                case 'cmi.total_time':
                    $usertrack->total_time = $track->value;
                    break;
                case 'cmi.score.raw':
                    $usertrack->score_raw = (float) sprintf('%2.2f', $track->value);
                    break;
                case 'cmi.score.scaled':
                    $usertrack->score_scaled = (float) sprintf('%1.4f', $track->value);
                    break;
                case 'cmi.suspend_data':
                    $usertrack->suspend_data = $track->value;
                    break;
                case 'cmi.exit':
                    $usertrack->exit = $track->value;
                    break;
            }
            if (isset($track->timemodified) && ($track->timemodified > $usertrack->timemodified)) {
                $usertrack->timemodified = $track->timemodified;
            }
        }
	}
	// Status
	if ($usertrack->success_status == 'passed' || $usertrack->success_status == 'failed') {
		$usertrack->status = $usertrack->success_status;
	} else {
		$usertrack->status = $usertrack->completion_status;
	}
	// Score
	if ($usertrack->score_scaled !== '') {	// Scaled in priority
		$usertrack->score_raw = (float)sprintf('%2.2f', $usertrack->score_scaled) * 100;
	} else if ($usertrack->score_raw !== '') {
		$usertrack->score_scaled = (float)sprintf('%1.4f', $usertrack->score_raw) / 100;   // Supposing that min=0 and max=100 (to be improved) !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	}
	// The end
	if (is_array($usertrack)) {
		ksort($usertrack);
	}
	return $usertrack;
}

function scormlite_get_sco_runtime($scoid, $userid, $attempt=null) {
	global $DB;
    if (!isset($attempt)) {
        $attempt = scormlite_get_relevant_attempt($scoid, $userid);
        if ($attempt == 0) return null;
    }
	$timedata = new stdClass();
	$sql = "userid=$userid AND scoid=$scoid AND attempt=$attempt";
	$tracks = $DB->get_records_select('scormlite_scoes_track',"$sql ORDER BY timemodified ASC");
	if ($tracks) {
		$tracks = array_values($tracks);
	}
	if ($tracks) {
		$timedata->start = $tracks[0]->timemodified;
	} else {
		$timedata->start = false;
	}
	if ($tracks && $track = array_pop($tracks)) {
		$timedata->finish = $track->timemodified;
	} else {
		$timedata->finish = $timedata->start;
	}
	return $timedata;
}

function scormlite_get_attempt_count($scoid, $userid) {
    global $DB;
    $element = 'x.start.time';  // Return number of started attempts
    $attempts = $DB->get_records_select('scormlite_scoes_track', "element=? AND userid=? AND scoid=?", array($element, $userid, $scoid), 'attempt');
    if (!empty($attempts)) {
        return count($attempts);
    } else {
        return 0;
    }
}

function scormlite_get_relevant_attempt($scoid, $userid) {
    // Returns the more relevant completed attempt.
    // If no completed attempt, return 1 if the attempt has started
    // Else returns 0
    // If the average mode is used, return the number of completed attempts

    global $DB;    
    $sco = $DB->get_record("scormlite_scoes", array("id"=>$scoid), '*', MUST_EXIST);
    $attemptnumber = scormlite_get_attempt_count($sco->id, $userid);
    $attemptwhat = $sco->whatgrade;
    
    // Check if no attempt or a single attempt
    if ($attemptnumber < 2) return $attemptnumber;
   
    // Check if the last attempt has been completed
    $trackdata = scormlite_get_tracks($sco->id, $userid, $attemptnumber);
    if ($trackdata->completion_status != "completed") {
        $attemptnumber--;
    }
    switch($attemptwhat) {
        case 0 :  // Highest
		
			// SF2018 - Return the last attempt when there is no best attempt
			$attempt =  scormlite_get_highest_attempt($sco->id, $userid);
			if (is_null($attempt)) return $attemptnumber;
			else return $attempt; 

            break;
        case 1 :  // First
            return 1;
            break;
        case 2 :  // Last
            return $attemptnumber;
            break;
    }
}

// SF2018 - Get attempt by scaled or raw
function scormlite_get_highest_attempt($scoid, $userid) {
	$res = scormlite_get_highest_attempt_by_element($scoid, $userid, 'cmi.score.scaled');
	if (is_null($res)) $res = scormlite_get_highest_attempt_by_element($scoid, $userid, 'cmi.score.raw');
	return $res;
}
function scormlite_get_highest_attempt_by_element($scoid, $userid, $element) {

    global $DB;
    $best = null;
    $score = 0;
	
	// SF2018 - Get attempt by scaled or raw
	//$element = 'cmi.score.scaled';  
	
    $attempts = $DB->get_records_select('scormlite_scoes_track', "element=? AND userid=? AND scoid=?", array($element, $userid, $scoid), 'attempt');
    if (!empty($attempts)) {
        foreach ($attempts as $attempt) {
            if ($attempt->value >= $score) {
                $best = $attempt->attempt;
                $score = $attempt->value;
            }
        }
        return $best;
    } else {
        return null;
    }
}

function scormlite_delete_attempts($scoid, $userids) {
	global $DB;
    foreach ($userids as $userid) {
        $DB->delete_records('scormlite_scoes_track', array('scoid'=>$scoid, 'userid'=>$userid));
	}
}







