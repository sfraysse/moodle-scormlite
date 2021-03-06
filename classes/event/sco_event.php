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

abstract class sco_event extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'scormlite_scoes';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Let developers validate their custom data (such as $this->data['other'], contextlevel, etc.).
     * Also used to complete data.
     */
    protected function validate_data() {

        // Get the SCO
        global $DB;
        $sco = $DB->get_record('scormlite_scoes', ['id' => $this->data['objectid']]);

        // Complete other data
        $this->data['other']['masteryscore'] = intval($sco->passingscore);
        $this->data['other']['launchmethod'] = $sco->popup ? 'AnyWindow' : 'OwnWindow';
        if ($sco->maxtime) {
            $this->data['other']['maxtime'] = $this->iso8601_duration($sco->maxtime * 60);
        }
    }

    /**
     * Get object ID mapping.
     */
    public static function get_objectid_mapping() {
        return false;
    }

    /**
     * Convert second to ISO duration.
     */
    protected function iso8601_duration($seconds) {
        $intervals = array('D' => 60 * 60 * 24, 'H' => 60 * 60, 'M' => 60, 'S' => 1);

        $pt = 'PT';
        $result = '';
        foreach ($intervals as $tag => $divisor) {
            $qty = floor($seconds / $divisor);
            if (!$qty && $result == '') {
                continue;
            }

            $seconds -= $qty * $divisor;
            $result  .= "$qty$tag";
        }
        if ($result == '')
            $result = '0S';
        return "$pt$result";
    }


}

