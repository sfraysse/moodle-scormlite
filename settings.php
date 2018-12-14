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



defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot.'/mod/scormlite/sharedlib.php');

    // Manual opening of the activity: Use dates / Open / Closed
    $settings->add(new admin_setting_configselect('scormlite/manualopen', get_string('manualopen','scormlite'), get_string('manualopendesc','scormlite'), 1, scormlite_get_manualopen_display_array()));

    // Maximum time
    $settings->add(new admin_setting_configtext('scormlite/maxtime', get_string('maxtime', 'scormlite'), get_string('maxtimedesc','scormlite'), 0, PARAM_INT));

    // Passing score
    $settings->add(new admin_setting_configtext('scormlite/passingscore', get_string('passingscore', 'scormlite'), get_string('passingscoredesc','scormlite'), 50, PARAM_INT));
	
    // Display mode: current window or popup
    $settings->add(new admin_setting_configselect('scormlite/popup', get_string('display','scormlite'), get_string('displaydesc','scormlite'), 0, scormlite_get_popup_display_array()));

    // Chrono
    $settings->add(new admin_setting_configcheckbox('scormlite/displaychrono', get_string('displaychrono', 'scormlite'), get_string('displaychronodesc','scormlite'), 1));
	
    // Maximum number of attempts
    $settings->add(new admin_setting_configselect('scormlite/maxattempt', get_string('maximumattempts', 'scormlite'), '', 1, scormlite_get_attempts_array()));

    // Score to keep when multiple attempts
    $settings->add(new admin_setting_configselect('scormlite/whatgrade', get_string('whatgrade', 'scormlite'), get_string('whatgradedesc', 'scormlite'), 0, scormlite_get_what_grade_array()));

    // Colors
    $jsoncolors = '{"lt":50, "color":"#D53B3B"}, {"lt":65, "color":"#EF7A00"}, {"lt":75, "color":"#FDC200"}, {"lt":101,"color":"#85C440"}';
    $settings->add(new admin_setting_configtext('scormlite/colors', get_string('colors', 'scormlite'), get_string('colorsdesc','scormlite'), $jsoncolors, PARAM_RAW, 100));

    // Reports: display rank
    $settings->add(new admin_setting_configcheckbox('scormlite/displayrank', get_string('displayrank', 'scormlite'), get_string('displayrankdesc','scormlite'), 0));

    // Reports: immediate review access
    $settings->add(new admin_setting_configcheckbox('scormlite/immediate_review', get_string('immediate_review_access', 'scormlite'), get_string('immediate_review_access_help','scormlite'), 0));

    // Reports: display Quetzal statistics
    $settings->add(new admin_setting_configcheckbox('scormlite/quetzal_statistics', get_string('quetzal_statistics_access', 'scormlite'), get_string('quetzal_statistics_access_help','scormlite'), 0));
	
    
    // KD2015 - Version 2.6.3 - Debug functions
    
    // Debug functions: proctec from session timout
    $settings->add(new admin_setting_configcheckbox('scormlite/protecttimeout', get_string('protecttimeout', 'scormlite'), get_string('protecttimeoutdesc','scormlite'), 0));
	
    // Debug functions: proctec from session timout
    $settings->add(new admin_setting_configcheckbox('scormlite/getscormstatus', get_string('debuggetscormstatus', 'scormlite'), get_string('debuggetscormstatusdesc','scormlite'), 0));
	
    // Debug functions: proctec from session timout
    $settings->add(new admin_setting_configcheckbox('scormlite/setscormstatus', get_string('debugsetscormstatus', 'scormlite'), get_string('debugsetscormstatusdesc','scormlite'), 0));
	
    // Debug functions: record error logs
    $settings->add(new admin_setting_configcheckbox('scormlite/recordlogs', get_string('debuglogsrecord', 'scormlite'), get_string('debuglogsrecorddesc','scormlite'), 0));
	
}


