<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_scormlite\event;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/scormlite/report/reportlib.php');

class result_updated extends \core\event\base {

    use utils;


    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'scormlite';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Let developers validate their custom data (such as $this->data['other'], contextlevel, etc.).
     * Also used to complete data.
     */
    protected function validate_data() {
        global $USER;

        // Get the SCO
        global $DB;
        $scormlite = $DB->get_record('scormlite', ['id' => $this->data['objectid']]);
        $sco = $DB->get_record('scormlite_scoes', ['id' => $scormlite->scoid]);

        // Complete other data
        $this->data['other']['masteryscore'] = $sco->passingscore;
        if ($sco->maxtime) {
            $this->data['other']['maxtime'] = $this->iso8601_duration($sco->maxtime * 60);
        }
        $this->data['other']['scoringmethod'] = ['BestAttempt', 'FirstAttempt', 'LastAttempt'][$sco->whatgrade];
        if ($sco->maxattempt) {
            $this->data['other']['maxattempts'] = $sco->maxattempt;
        }
        $this->data['other']['attemptsnumber'] = scormlite_get_attempt_count($sco->id, $USER->id);
    }

}

