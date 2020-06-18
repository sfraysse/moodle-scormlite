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

function scormlite_safeexam_required($scormlite) {
    global $CFG;

    if (!isset($scormlite->safeexam)) {
        return false;
    }

    return $scormlite->safeexam;
}

function scormlite_safeexam_check($scormlite) {
    if (!scormlite_safeexam_required($scormlite)) {
        return true;
    }
    return strpos($_SERVER['HTTP_USER_AGENT'], 'SEB') !== false;
}

function scormlite_safeexam_warning($scormlite) {
    if (scormlite_safeexam_check($scormlite)) {
        return '';
    }
    return get_string('safeexam_warning', 'scormlite');
}

function scormlite_safeexam_secure_layout($scormlite) {
    global $PAGE;
    if (scormlite_safeexam_required($scormlite)) {
        $PAGE->set_cacheable(false);
        $PAGE->set_popup_notification_allowed(false);
        $PAGE->set_pagelayout('secure');
    }
}
