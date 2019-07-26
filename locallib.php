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


require_once($CFG->dirroot.'/mod/scormlite/lib.php');
 
//
// Packaging
//

// Packaging functions override
 
require_once($CFG->libdir.'/filelib.php');

class scormlite_content_file_info extends file_info_stored {
	public function get_parent() {
		if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
			return $this->browser->get_file_info($this->context);
		}
		return parent::get_parent();
	}
	public function get_visible_name() {
		if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
			return $this->topvisiblename;
		}
		return parent::get_visible_name();
	}
	public function is_empty_area() {
		if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
			$fs = get_file_storage();
			$empty = $fs->is_area_empty($this->lf->get_contextid(), $this->lf->get_component(), $this->lf->get_filearea(), false); // Do not take into account the item id which is 0
		} else {
			$empty = false;
		}
		return $empty;
	}
}
/*
class scormlite_package_file_info extends file_info_stored {
    public function get_parent() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }
    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }
}
*/

// Parse SCO package

function scormlite_parse_package(&$sco, $form, $cmid, $file_fieldname, $multisco = false) {
	//if ($multisco == false) $itemid = 0;
	//else $itemid = $sco->id;
	$itemid = $sco->id;
	$filename = $form->get_new_filename($file_fieldname);
	if ($filename !== false) {
		$fs = get_file_storage();
		$context = context_module::instance($cmid);  // KD2014 - 2.6 compliance
		$modulename = 'mod_'.$sco->containertype;
		
		// Upload the new package
		$fs->delete_area_files($context->id, $modulename, 'package', $itemid);
		$res = $form->save_stored_file($file_fieldname, $context->id, $modulename, 'package', $itemid, '/', $filename);
		if ($packagefile = $fs->get_file($context->id, $modulename, 'package', $itemid, '/', $filename)) {
		
			// If the package is the same, do nothing
			$newhash = $packagefile->get_contenthash();
			if ($sco->sha1hash == $newhash) return;
			$fs->delete_area_files($context->id, $modulename, 'content', $itemid);
            
            // Extract files only if it is a ZIP file. Otherwize, copy the file.
			$launchfile = 'index.html';
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if ($ext == 'zip') {
				
                // Extract files
                $packer = get_file_packer('application/zip');
                $packagefile->extract_to_storage($packer, $context->id, $modulename, 'content', $itemid, '/');
				
				// Search index.html and manifest.xml
				$fileIndex = $fs->get_file($context->id, $modulename, 'content', $itemid, '/', 'index.html');
				$fileManifest = $fs->get_file($context->id, $modulename, 'content', $itemid, '/', 'imsmanifest.xml');
				
				// Manifest must be used if it exists
				if ($fileManifest) {
					$launchfile = scormlite_get_launchfile_from_manifest($fileManifest);
					if (!$launchfile) $launchfile = 'index.html';
				}
				
            } else {
        		$form->save_stored_file($file_fieldname, $context->id, $modulename, 'content', $itemid, '/', $filename);                
            }
            
			// Update data for DB
			$sco->launchfile = $launchfile;
			$sco->reference = $filename;
			$sco->revision++;
			$sco->sha1hash = $newhash;
		}
	}
}

function scormlite_get_launchfile_from_manifest($file) {
	$contents = $file->get_content();
    $doc = new DOMDocument();
	if (!$doc->loadXML($contents, LIBXML_NONET)) return false;
    $doc_resources = $doc->getElementsByTagName('resource');
    foreach ($doc_resources as $doc_resource) {
        $href = $doc_resource->attributes->getNamedItem('href');
		if ($href) return $href->value;
    }
	return false;
}


//
// Usefull functions for activity pages
//

// Check permissions to display SCO

function scormlite_check_player_permissions($cm, $sco, $userid, $attempt=1, $backhtml = '', $header = false, $activity = null, $course = null) {
	global $USER, $CFG, $OUTPUT;
	require_login($cm->course, false, $cm);
	$allowed = true;
	// Check activity visibility
	if (!$cm->visible and !has_capability('moodle/course:viewhiddenactivities', context_course::instance($cm->course))) {  // KD2014 - 2.6 compliance
		$allowed = false;
		print_error('activityiscurrentlyhidden');
	}
	// Check user
	if ($USER->id != $userid) {
		if (!has_capability('mod/scormlite:reviewothercontent', context_module::instance($cm->id))) {  // KD2014 - 2.6 compliance
			$allowed = false;
			print_error('notallowed', 'scormlite');
		}
	}
	// Review mode
	require_once($CFG->dirroot.'/mod/scormlite/report/reportlib.php');
	$reviewmode = false;
	$achieved = false;
	if ($trackdata = scormlite_get_tracks($sco->id, $userid, $attempt)) {
		$achieved = ($trackdata->status == 'passed' || $trackdata->status == 'failed');
		// NNX2017 - Don't decide here the rules to be in review mode
		$reviewmode = true;  //$achieved && ($sco->manualopen == 2 || ($sco->manualopen == 0 && time() > $sco->timeclose));
	}
	// Disable access to content by another user if content is not in review mode
	if (!$reviewmode && $userid != $USER->id) {
		$allowed = false;
		print_error('notallowed', 'scormlite');
	}
	// Disable access if acheived but not review
	if (!$reviewmode && $achieved) {
		$allowed = false;
		scormlite_print_error(get_string('accessdenied', 'scormlite'), $backhtml, $header, $cm, $activity, $course);
	}
    // Check attempt

    // SF2018 - Illimited attempts except for students
    $illimitedAccess = has_capability('mod/scormlite:viewotherreport', context_module::instance($cm->id));
	$attemptmax = $sco->maxattempt;
    if ($attemptmax != 0 && $attempt > $attemptmax && !$illimitedAccess) {
		
		$allowed = false;
		print_error('notallowed', 'scormlite');
    }
            
	// Check if scorm closed, and print a message if closed
	list($html, $scormopen) = scormlite_get_availability($cm, $sco, $trackdata); 
	if (!$scormopen && !$reviewmode && !$illimitedAccess) {
		echo $html;
		die;
	}
	return $allowed;
}

// Print error function (not the Moodle error format)

function scormlite_print_error($msg, $backhtml = '', $header = false, $cm = null, $activity = null, $course = null) {
	global $OUTPUT, $CFG;
	if ($header) {
		require_once($CFG->dirroot.'/mod/scormlite/report/reportlib.php');
		scormlite_print_header($cm, $activity, $course);
	}
	echo $OUTPUT->box_start('generalbox mdl-align error');
	echo '<p>'.$msg.'</p>';
	if (!empty($backhtml)) echo $backhtml;
	echo $OUTPUT->box_end();
	if ($header) {
		echo $OUTPUT->footer();
	}
	exit;
}



//
// SCO tracks
//

// Insert track for a SCO

// SF2018 - Signature change
function scormlite_insert_track($userid, $scoid, $attempt, $element, $value, $containertype = 'scormlite') {
    global $DB, $CFG;
    $id = null;
    $track = $DB->get_record('scormlite_scoes_track',array('userid'=>$userid, 'scoid'=>$scoid, 'attempt'=>$attempt, 'element'=>$element));
    if ($track) {
        if ($element != 'x.start.time' ) { //don't update x.start.time - keep the original value.
            $track->value = $value;
            $track->timemodified = time();
            $DB->update_record('scormlite_scoes_track', $track);
            $id = $track->id;

			// SF2018 - Record tracks hook
			scormlite_record_track_hooker($track, $containertype);
        }
    } else {
        $track = new stdClass();
        $track->userid = $userid;
        $track->scoid = $scoid;
        $track->attempt = $attempt;
        $track->element = $element;
        $track->value = $value;
        $track->timemodified = time();
        $id = $DB->insert_record('scormlite_scoes_track', $track);

		// SF2018 - Record tracks hook
		scormlite_record_track_hooker($track, $containertype);
    }
    return $id;
}

// SF2018 - Record tracks hook
// Record track

function scormlite_record_track_hooker($track, $containertype = 'scormlite') {
	global $CFG;
	require_once($CFG->dirroot.'/mod/'.$containertype.'/scormlitelib.php');
	$function = $containertype.'_record_track_hook';
	if (function_exists($function)) call_user_func_array($function, array($track));
}

// Check completion

function scormlite_check_completion($userid, $activity, $cm, $course, $containertype = 'scormlite') {
	global $CFG;
	require_once($CFG->dirroot.'/mod/'.$containertype.'/scormlitelib.php');
	$function = $containertype.'_is_activity_completed';
	$args = array($userid, $activity);
	$completed = call_user_func_array($function, $args);
	if ($completed) {
		require_once($CFG->libdir.'/completionlib.php');
		$completion = new completion_info($course);
		$completion->set_module_viewed($cm, $userid);
	}
}

// Check grades

function scormlite_check_grades($userid, $activity, $cm, $course, $containertype = 'scormlite') {
	global $CFG;
	require_once($CFG->dirroot.'/mod/'.$containertype.'/scormlitelib.php');
	$function = $containertype.'_get_grade';
	$args = array($userid, $activity);
	$grade = call_user_func_array($function, $args);
	if (isset($grade)) {
		require_once($CFG->dirroot.'/mod/'.$containertype.'/lib.php');
		$function = $containertype.'_update_grades';
		$args = array($activity, $userid);
        call_user_func_array($function, $args);
	}
}

//
// Container data
//
 
function scormlite_get_containeractivity($scoid, $scocontainertype = 'scormlite') {
	global $CFG;
	require_once($CFG->dirroot.'/mod/'.$scocontainertype.'/scormlitelib.php');
	$function = $scocontainertype.'_get_activity_from_scoid';
	$args = array($scoid);
	return call_user_func_array($function, $args);
}


//
// Manifest data
//
 
function scormlite_parse_quetzal($sco, $cmid, $update = false) {
    global $CFG, $DB;
	require_once($CFG->dirroot.'/mod/scorm/datamodels/scormlib.php');  // For xml2array

	// Update: delete existing questions
	if ($update) {
		$DB->delete_records('scormlite_quetzal_questions', array('scoid'=>$sco->id));
	}

	// Get XML file
	$context = context_module::instance($cmid);
	$modulename = 'mod_'.$sco->containertype;
    $fs = get_file_storage();
	$manifest = $fs->get_file($context->id, $modulename, 'content', $sco->id, '/', 'quetzal.xml'); 
	if (!$manifest) return;

	// Get XML content
	$xmltext = $manifest->get_content();
    $pattern = '/&(?!\w{2,6};)/';
    $replacement = '&amp;';
    $xmltext = preg_replace($pattern, $replacement, $xmltext);
    $objXML = new xml2Array();
    $xml = $objXML->parse($xmltext);
    
    // Get questions
    $questions = array();
    $xmlquestions = $xml[0]['children'][0];
    if (isset($xmlquestions['children'])) { 
        foreach ($xmlquestions['children'] as $xmlquestion) {
            $question = new stdClass();
            $question->manifestid = $xmlquestion['attrs']['ID'];
            $question->title = $xmlquestion['attrs']['TITLE'];
            $question->scoid = $sco->id;
            array_push($questions, $question);
        }
    }
    
    // Update DB
    foreach ($questions as $question) {
        $DB->insert_record('scormlite_quetzal_questions', $question);
	}
}


//
// Manifest data
//

function scormlite_trigger_event($eventname, $course, $cm, $activity, $userid = null, $other = []) {
	$data = [
		'objectid' => $activity->id,
		'context' => context_module::instance($cm->id),
	];
	if (isset($userid)) {
		$data['relateduserid'] = $userid;
	}
	if (!empty($other)) {
		$data['other'] = $other;
	}
	$eventclass = '\mod_scormlite\event\\' . $eventname;
	$event = $eventclass::create($data);
	$event->add_record_snapshot('course', $course);
	$event->add_record_snapshot('scormlite', $activity);
	$event->add_record_snapshot('course_modules', $cm);
	$event->trigger();
}


