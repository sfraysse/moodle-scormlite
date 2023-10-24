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


defined('MOODLE_INTERNAL') || die();
 
////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////
 
/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */ 
function scormlite_supports($feature) {
	switch($feature) {
		case FEATURE_MOD_ARCHETYPE:				return MOD_ARCHETYPE_OTHER;  // Type of module (resource, activity or assignment)
		case FEATURE_BACKUP_MOODLE2:			return true;  // True if module supports backup/restore of moodle2 format
		case FEATURE_GROUPS:					return false; // True if module supports groups
		case FEATURE_GROUPINGS:					return false; // True if module supports groupings
		case FEATURE_GROUPMEMBERSONLY:			return true;  // True if module supports groupmembersonly
		case FEATURE_SHOW_DESCRIPTION:			return true; // True if module can show description on course main page
		case FEATURE_NO_VIEW_LINK:				return false; // True if module has no 'view' page (like label)
		case FEATURE_MOD_INTRO:					return true;  // True if module supports intro editor
		case FEATURE_COMPLETION_TRACKS_VIEWS:	return true; // True if module has code to track whether somebody viewed it
		case FEATURE_COMPLETION_HAS_RULES:		return false; // True if module has custom completion rules
		case FEATURE_MODEDIT_DEFAULT_COMPLETION:return false; // True if module has default completion
		case FEATURE_GRADE_HAS_GRADE:			return false; // True if module can provide a grade
		// Next ones should be checked
		case FEATURE_GRADE_OUTCOMES:			return false; // True if module supports outcomes
		case FEATURE_ADVANCED_GRADING:			return false; // True if module supports advanced grading methods
		case FEATURE_IDNUMBER:					return false; // True if module supports outcomes
		case FEATURE_COMMENT:					return false; // 
		case FEATURE_RATE:						return false; //  
        case FEATURE_MOD_PURPOSE:             	return MOD_PURPOSE_CONTENT;
		default: return null;
	}
} 
 
/**
 * Get icon mapping for font-awesome.
 * SF2017 - Added for 3.3 compatibility
 */
function mod_scormlite_get_fontawesome_icon_map() {
    return [
        'mod_scormlite:review' => 'fa-search',
    ];
}

/**
 * Saves a new instance of the scormlite into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $data An object from the form in mod_form.php
 * @param mod_scormlite_mod_form $mform
 * @return int The id of the newly inserted scormlite record
 */  
function scormlite_add_instance($data, $mform=null) {
	global $DB, $CFG;
	require_once($CFG->dirroot.'/mod/scormlite/sharedlib.php');
	if (is_array($data->colors)) {
		$data->colors = implode(',', $data->colors);
	}
	$transaction = $DB->start_delegated_transaction();
	{
		$scoid = scormlite_save_sco($data, $mform, $data->coursemodule, 'packagefile');
		$data->scoid = $scoid;
		$data->timemodified = time();
		$data->id = $DB->insert_record('scormlite', $data);
	}
	$DB->commit_delegated_transaction($transaction);
	
    // Grades
    $data->cmid = $data->coursemodule;

	// SF2018 - Get Quetzal manifest
	$data->cmidnumber = uniqid();

    scormlite_update_grades($data);
	
	// Force update
	$cm = $DB->get_record('course_modules', array('id'=>$data->cmid));
	if ($cm) {
		$cm->idnumber = $data->cmidnumber;
		$DB->update_record('course_modules', $cm);
	}

	return $data->id;
}

/**
 * Updates an instance of the scormlite in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $data An object from the form in mod_form.php
 * @param mod_scormlite_mod_form $mform
 * @return boolean Success/Fail
 */
function scormlite_update_instance($data, $mform) {
	global $DB, $CFG;
	require_once($CFG->dirroot.'/mod/scormlite/sharedlib.php');
	if (is_array($data->colors)) {
		$data->colors = implode(',', $data->colors);
	}
	$transaction = $DB->start_delegated_transaction();
	{
		// SF2018 - Get SCO ID
		$scoid = scormlite_save_sco($data, $mform, $data->coursemodule, 'packagefile');

		$data->timemodified = time();
		$data->id = $data->instance;
		$DB->update_record('scormlite', $data);
	}
	$DB->commit_delegated_transaction($transaction);
	
	// Grades
    $data->cmid = $data->coursemodule;

	// SF2018 - Use the idnumber
	$cm = $DB->get_record('course_modules', array('id'=>$data->cmid));
	$data->cmidnumber = $cm->idnumber;

    scormlite_update_grades($data);

	return true;
}

/**
 * Removes an instance of the scormlite from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function scormlite_delete_instance($id) {
	global $DB, $CFG;
	require_once($CFG->dirroot.'/mod/scormlite/sharedlib.php');
	if (!$scormlite = $DB->get_record('scormlite', array('id'=>$id))) {
		return false;
	}
	scormlite_delete_sco($scormlite->scoid);
	$DB->delete_records('scormlite', array('id'=>$id));
	
	// Grades
    scormlite_grade_item_delete($scormlite);
    
	return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function scormlite_user_outline($course, $user, $mod, $scormlite) {
	global $CFG;
	require_once($CFG->dirroot.'/mod/scormlite/report/reportlib.php');
	return scormlite_sco_user_outline($scormlite->scoid, $user->id);
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $scormlite the module instance record
 * @return void, is supposed to echp directly
 */
function scormlite_user_complete($course, $user, $mod, $scormlite) {
	global $CFG;
	require_once($CFG->dirroot.'/mod/scormlite/report/reportlib.php');
	echo scormlite_sco_user_complete($scormlite->scoid, $user->id);
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in scormlite activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function scormlite_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Returns all activity in scormlites since a given time
 *
 * @param array $activities sequentially indexed array of objects
 * @param int $index
 * @param int $timestart
 * @param int $courseid
 * @param int $cmid
 * @param int $userid defaults to 0
 * @param int $groupid defaults to 0
 * @return void adds items into $activities and increases $index
 */
function scormlite_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see scormlite_get_recent_mod_activity()}

 * @return void
 */
function scormlite_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
/*
function scormlite_cron () {
    return true;
}
*/

/**
 * Returns an array of users who are participanting in this scormlite
 *
 * Must return an array of users who are participants for a given instance
 * of scormlite. Must include every user involved in the instance,
 * independient of his role (student, teacher, admin...). The returned
 * objects must contain at least id property.
 * See other modules as example.
 *
 * @param int $scormliteid ID of an instance of this module
 * @return boolean|array false if no participants, array of objects otherwise
 */
function scormlite_get_participants($scormliteid) {
    return false;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function scormlite_get_extra_capabilities() {
    return array();
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Return grade for given user or all users.
 *
 * @global stdClass
 * @global object
 * @param int $scormid id of scorm
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function scormlite_get_user_grades($activity, $userid=0) {
	global $CFG;
	require_once($CFG->dirroot.'/mod/scormlite/scormlitelib.php');
	$grades = array();
	if (empty($userid)) {
		$raws = scormlite_get_grades($activity);
		if (!empty($raws)) {
			foreach ($raws as $userid => $raw) {
	            $grades[$userid] = new stdClass();
	            $grades[$userid]->id         = $userid;
	            $grades[$userid]->userid     = $userid;
	            $grades[$userid]->rawgrade   = $raw;
	        }    		
    	}
    } else {
    	$raw = scormlite_get_grade($userid, $activity);
    	if (isset($raw)) {
    		$grades[$userid] = new stdClass();
	        $grades[$userid]->id 		= $userid;
	        $grades[$userid]->userid 	= $userid;
	        $grades[$userid]->rawgrade 	= $raw;
    	}
    }
    if (empty($grades)) return false;
    return $grades;
}

/**
 * Creates or updates grade item for the give scormlite instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $scormlite instance object with extra cmidnumber and modname property
 * @return void
 */
function scormlite_grade_item_update($activity, $grades=null) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');
    $params = array('itemname'=>$activity->code);
    if (isset($activity->cmidnumber)) {
        $params['idnumber'] = $activity->cmidnumber;
    }
    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademax']  = 100;
    $params['grademin']  = 0;
	
    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }
    $res = grade_update('mod/scormlite', $activity->course, 'mod', 'scormlite', $activity->id, 0, $grades, $params);

	// NNX2016 - Provide the grade "pass"
	if (isset($activity->cmid)) {
		$cm = $DB->get_record('course_modules',array('id'=>$activity->cmid));
		if ($cm) {
			$grade = $DB->get_record('grade_items',array('courseid'=>$activity->course, 'itemmodule'=>$activity->modulename, 'iteminstance'=>$cm->instance));
			if ($grade) {
				$grade->gradepass = $activity->passingscore;
				$DB->update_record('grade_items', $grade);
			}
		}
	}
	
	return $res;
}

/**
 * Update scormlite grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $scormlite instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 */
function scormlite_update_grades($activity, $userid=0, $nullifnone=true) {
	global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');
    if ($grades = scormlite_get_user_grades($activity, $userid)) {
    	scormlite_grade_item_update($activity, $grades);
    } else if ($userid and $nullifnone) {
    	$grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = null;
        scormlite_grade_item_update($activity, $grade);
    } else {
    	scormlite_grade_item_update($activity);
    }
}

/**
 * Delete grade item for given scorm
 *
 * @global stdClass
 * @param object $scorm object
 * @return object grade_item
 */
function scormlite_grade_item_delete($activity) {
	global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
    return grade_update('mod/scormlite', $activity->course, 'mod', 'scormlite', $activity->id, 0, null, array('deleted'=>1));
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function scormlite_get_file_areas($course, $cm, $context) {
	$areas = array();
	$areas['content'] = get_string('areacontent', 'scormlite');
	$areas['package'] = get_string('areapackage', 'scormlite');
	return $areas;
}

/**
 * File browsing support for SCORM file areas
 *
 * @param file_browser $browser
 * @param stdclass $areas
 * @param stdclass $course
 * @param stdclass $cm
 * @param stdclass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return stdclass file_info instance or null if not found
 */
function scormlite_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
	global $CFG;
	$file_info = null;

	if (has_capability('moodle/course:managefiles', $context) && ($filearea === 'content' || $filearea === 'package')) {
		$fs = get_file_storage();
		$filepath = is_null($filepath) ? '/' : $filepath;
		$filename = is_null($filename) ? '.' : $filename;
		$urlbase = $CFG->wwwroot.'/pluginfile.php';

		if ($itemid === null) {
			// itemid is the scoid
			global $DB;
			$scormlite = $DB->get_record('scormlite', array('id' => $cm->instance), 'id,scoid');
			if ($scormlite) {
				$itemid = $scormlite->scoid;
			}
		}

		$storedfile = $fs->get_file($context->id, 'mod_scormlite', $filearea, $itemid, $filepath, $filename);
		if ($storedfile === false && $filepath === '/' && $filename === '.') {
			$storedfile = new virtual_root_file($context->id, 'mod_scormlite', $filearea, null);
		}

		if ($storedfile !== false) {
			if ($filearea === 'content') {
				require_once($CFG->dirroot.'/mod/scormlite/locallib.php');
				$file_info = new scormlite_content_file_info($browser, $context, $storedfile, $urlbase, $areas[$filearea], true, true, false, false);
			} else if ($filearea === 'package') {
				$file_info = new file_info_stored($browser, $context, $storedfile, $urlbase, $areas[$filearea], false, true, false, false);
			}
		}
	}
	return $file_info;
}

/**
 * Serves the files from the scormlite file areas
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return void this should never return to the caller
 */
function scormlite_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
	global $CFG;
	require_once($CFG->dirroot.'/mod/scormlite/sharedlib.php');
	return scormlite_shared_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options, 'scormlite');
}

////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding scormlite nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the scormlite module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function scormlite_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the scormlite settings
 *
 * This function is called when the context for the page is a scormlite module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $scormlitenode {@link navigation_node}
 */
function scormlite_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $scormlitenode=null) {
}

////////////////////////////////////////////////////////////////////////////////
// Reset feature                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the scorm.
 *
 * @param object $mform form passed by reference
 */
function scormlite_reset_course_form_definition(&$mform) {
	$mform->addElement('header', 'scormheader', get_string('modulenameplural', 'scormlite'));
	$mform->addElement('advcheckbox', 'reset_scormlite', get_string('deletealltracks','scormlite'));
}

/**
 * Course reset form defaults.
 *
 * @return array
 */
function scormlite_reset_course_form_defaults($course) {
	return array('reset_scormlite'=>1);
}

/**
 * Removes all grades from gradebook
 *
 * @global stdClass
 * @global object
 * @param int $courseid
 * @param string optional type
 */
function scormlite_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $sql = "SELECT s.*, cm.idnumber as cmidnumber, s.course as courseid
              FROM {scormlite} s, {course_modules} cm, {modules} m
             WHERE m.name='scormlite' AND m.id=cm.module AND cm.instance=s.id AND s.course=?";

    if ($scormlites = $DB->get_records_sql($sql, array($courseid))) {
        foreach ($scormlites as $scormlite) {
            scormlite_grade_item_update($scormlite, 'reset');
        }
    }
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * scorm attempts for course $data->courseid.
 *
 * @global stdClass
 * @global object
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function scormlite_reset_userdata($data) {
	$status = array();
	if (!empty($data->reset_scormlite)) {

		// SCORM Lite Tracks
		$sql = '
			DELETE SST
			FROM {scormlite_scoes_track} SST
			INNER JOIN {scormlite} S ON S.scoid=SST.scoid
			INNER JOIN {course_modules} CM ON CM.instance=S.id
			WHERE CM.course=?';
		global $DB;
		$DB->execute($sql, array($data->courseid));

		// Grades
        if (empty($data->reset_gradebook_grades)) {
            scormlite_reset_gradebook($data->courseid);
		}
		
		// Status
		$status[] = array(
			'component' => get_string('modulenameplural', 'scormlite'),
			'item' => get_string('deletealltracks', 'scormlite'),
			'error' => false);
	}
	return $status;
}

////////////////////////////////////////////////////////////////////////////////
// Additional stuff (not in the template)                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function scormlite_page_type_list($pagetype, $parentcontext, $currentcontext) {
	$module_pagetype = array('mod-scormlite-*'=>get_string('page-mod-scormlite-x', 'scormlite'));
	return $module_pagetype;
}

/**
 * writes overview info for course_overview block - displays upcoming scorm objects that have a due date
 *
 * @param object $type - type of log(aicc,scorm12,scorm13) used as prefix for filename
 * @param array $htmlarray
 * @return mixed
 */
function scormlite_print_overview($courses, &$htmlarray) {
}








