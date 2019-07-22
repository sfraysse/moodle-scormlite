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



require_once($CFG->dirroot.'/mod/scormlite/locallib.php');

// 
// Operations on SCOs
//

// Save

function scormlite_save_sco($data, $form, $cmid, $file_fieldname, $multisco = false) {
	global $DB;

	// Don't save if no file
	$filename = $form->get_new_filename($file_fieldname);
	if ($filename === false) return false;

	// Important for external activities
	if (!isset($data->scoid)) $data->scoid = $data->id; 

	// Insert SCO
	if ($data->scoid == 0) $data->id = $DB->insert_record('scormlite_scoes', $data);
	else $data->id = $data->scoid;
	
	// Parse package
	scormlite_parse_package($data, $form, $cmid, $file_fieldname, $multisco);
	
	// Update with colors
	if (is_array($data->colors)) $data->colors = implode(',', $data->colors);
	$DB->update_record('scormlite_scoes', $data);

	// SF2018 - Record Quetzal questions
	scormlite_parse_quetzal($data, $cmid, $data->scoid);

	return $data->id;
}

// Delete

function scormlite_delete_sco($scoid) {
	global $CFG, $DB;
	// Get SCO information
	$scodata = $DB->get_record('scormlite_scoes', array('id'=>$scoid));
	if ($scodata) {

		// SF2018 - Record Quetzal questions
		$DB->delete_records('scormlite_quetzal_questions', array('scoid'=>$scoid));

		// Delete in DB
		$DB->delete_records('scormlite_scoes_track', array('scoid'=>$scoid));
		$DB->delete_records('scormlite_scoes', array('id'=>$scoid));
	}
}

//
// Some lib functions implementations: additional param added ($modulename).
// 


// Plugin file

function scormlite_shared_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array(), $modulename='scormlite') {
	global $CFG;
	if ($context->contextlevel != CONTEXT_MODULE) {
		return false;
	}
	require_login($course, true, $cm);
	$lifetime = isset($CFG->filelifetime) ? $CFG->filelifetime : 86400;
	$scoid = (int)array_shift($args);
	if ($filearea === 'content') {
		$revision = (int)array_shift($args); // prevents caching problems - ignored here
		$relativepath = implode('/', $args);
		$fullpath = "/$context->id/mod_$modulename/content/$scoid/$relativepath";
		// TODO: add any other access restrictions here if needed!
	} else if ($filearea === 'package') {
		if (!has_capability('moodle/course:manageactivities', $context)) {
			return false;
		}
		$lifetime = 0; // no caching here
	} else {
		return false;
	}
	$filename = array_pop($args);
	$filepath = '/' . implode('/', $args);
	if (count($args) > 0) {
		$filepath .= '/';
	}
	$fs = get_file_storage();
	$file = $fs->get_file($context->id, 'mod_'.$modulename, $filearea, $scoid, $filepath, $filename);
	if (! $file || $file->is_directory()) {
		return false;
	}
	// finally send the file
    send_stored_file($file, $lifetime, 0, false, $options);
}

function scormlite_shared_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename, $modulename='scormlite') {
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
			$module = $DB->get_record($modulename, array('id' => $cm->instance), 'id,scoid');
			if ($module) {
				$itemid = $module->scoid;
			}
		}

		$storedfile = $fs->get_file($context->id, 'mod_'.$modulename, $filearea, $itemid, $filepath, $filename);
		if ($storedfile === false && $filepath === '/' && $filename === '.') {
			$storedfile = new virtual_root_file($context->id, 'mod_'.$modulename, $filearea, null);
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

function scormlite_shared_reset_userdata($data, &$status, $modulename='scormlite') {
    $sql = '
        DELETE SST
        FROM {scormlite_scoes_track} SST
        INNER JOIN {'.$modulename.'} S ON S.scoid=SST.scoid
        INNER JOIN {course_modules} CM ON CM.instance=S.id
        WHERE CM.course=?';
    global $DB;
    $DB->execute($sql, array($data->courseid));
    // Status
    $status[] = array(
        'component' => get_string('modulenameplural', $modulename),
        'item' => get_string('deletealltracks', 'scormlite'),
        'error' => false);
}


// 
// Activity settings options
//

// Popup options

function scormlite_get_popup_display_array(){
	return array(0 => get_string('currentwindow', 'scormlite'),
	1 => get_string('popup', 'scormlite'));
}

// Availability options

function scormlite_get_manualopen_display_array($addAuto = false){
	$options = array();
	$options[0] = get_string('manualopendates', 'scormlite');
	$options[1] = get_string('manualopenopen', 'scormlite');
	$options[2] = get_string('manualopenclose', 'scormlite');
	if ($addAuto) $options[3] = get_string('manualopenauto', 'scormlite');
	return $options;
}

// Attempts options

function scormlite_get_attempts_array() {
	$options = array();
	$options[0] = get_string('nolimit', 'scormlite');
	$options[1] = get_string('attempt1', 'scormlite');
    for ($i=2; $i<=6; $i++) {
        $options[$i] = get_string('attemptsx', 'scormlite', $i);
    }
	return $options;
}
function scormlite_get_what_grade_array() {
	$options = array();
	$options[0] = get_string('highestattempt', 'scormlite');
	$options[1] = get_string('firstattempt', 'scormlite');
	$options[2] = get_string('lastattempt', 'scormlite');
	return $options;
}

// Review access options

function scormlite_get_review_access_array()
{
	$options = array();
	$options[0] = get_string('whenclosed', 'scormlite');
	$options[1] = get_string('immediate', 'scormlite');
	$options[2] = get_string('onsuccess', 'scormlite');
	return $options;
}


// 
// Form functions
//

// Add colors settings

function scormlite_form_add_colors(&$form, $pluginname = "scormlite") {
	global $CFG;
	require_once($CFG->dirroot.'/mod/scormlite/report/reportlib.php');
	$form->addElement('header', 'colorszone', get_string('colors', 'scormlite'));
	$colors = scormlite_get_config_colors($pluginname);
	// All colors except the last one
	for ($i=0; $i < count($colors)-1; $i++) {
		$color = $colors[$i];
		$attributes = 'maxlength="3" size="3" style="background-color:'.$color->color.'"';
		$form->addElement('text', "colors[$i]", get_string('scorelessthan', 'scormlite'), $attributes);
		$form->setDefault("colors[$i]", $color->lt);
		$form->setType("colors[$i]", PARAM_INT);
		$form->addRule("colors[$i]", null, 'numeric', null, 'client');
	}
	// The last one
	$color = $colors[$i];
	$attributes = 'disabled maxlength="3" size="3" style="background-color:'.$color->color.'"';
	$form->addElement('text', "colors[$i]", get_string('scoreupto', 'scormlite'), $attributes);
	$form->setDefault("colors[$i]", 100);
	$form->setType("colors[$i]", PARAM_INT);
	$form->addRule("colors[$i]", null, 'numeric', null, 'client');
}

// Pre-process colors

function scormlite_form_process_colors(&$default_values, $pluginname = "scormlite") {	
	global $CFG, $DB;
	require_once($CFG->dirroot.'/mod/scormlite/report/reportlib.php');
	$thresholds = null;
	if (array_key_exists('colors', $default_values) && $default_values['colors'] != null) {
		// Colors set for the activity
		$thresholds = scormlite_parse_colors_thresholds($default_values['colors']);
		unset($default_values['colors']);		
	} else if (array_key_exists('scoid', $default_values) && !empty($default_values['scoid'])) {
		// Colors set for the SCO
		$sco = $DB->get_record("scormlite_scoes", array("id"=>$default_values['scoid']));
		if ($sco) $thresholds = scormlite_parse_colors_thresholds($sco->colors);
	}
	if (isset($thresholds)) {
		foreach ($thresholds as $i => $threshold) {
			$default_values["colors[$i]"] = $threshold;
		}
	}
}

// Check colors validity

function scormlite_form_check_colors($data, &$errors, $pluginname = "scormlite") {
	global $CFG;
	require_once($CFG->dirroot.'/mod/scormlite/report/reportlib.php');
	$colors = scormlite_get_config_colors($pluginname);
	for ($i=0; $i < count($colors); $i++) {
		$value = $data["colors"][$i];
		if ($value < 0 || $value > 101) {
			$errors["colors[$i]"] = get_string('notvalidtresholdscore', 'scormlite');
		}
	}
}







