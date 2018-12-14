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

/* KD2015 - Version 2.6.3 - Debug functions */

// Includes
require_once('../../../config.php');
require_once($CFG->dirroot.'/mod/scormlite/locallib.php');
require_once($CFG->dirroot.'/mod/scormlite/report/reportlib.php');

// Params
$scoid = required_param('scoid', PARAM_INT); 
$userid = required_param('userid', PARAM_INT); 
$logid = optional_param('logid', 0, PARAM_INT); 

// Objects and vars
$sco = $DB->get_record("scormlite_scoes", array("id"=>$scoid), '*', MUST_EXIST);
$activity = scormlite_get_containeractivity($scoid, $sco->containertype);
$cm = get_coursemodule_from_instance($sco->containertype, $activity->id, 0, false, MUST_EXIST);

// Permissions 
require_login($cm->course, false, $cm);
require_capability('mod/scormlite:debugget', context_module::instance($cm->id));
$config = get_config('scormlite');
if (!$config->getscormstatus) {
    echo 'You are not allowed to do that!';
    die;
}
    
// Extract data
$data = scormlite_debug_get_data($userid, $scoid, $logid);

// Display data
header('Content-disposition: attachment; filename="debug.xml"');
header('Content-type: "text/xml"; charset="utf8"');
echo $data;



