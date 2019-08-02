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

class sco_attempts_reset extends sco_event {

    /**
     * Return localised event name.
     */
    public static function get_name() {
        return get_string('event_attempts_reset', 'scormlite');
    }

    /**
     * Returns description of what happened.
     */
    public function get_description()  {
        return "The attempts of user with id '$this->userid' have been reset for the '{$this->objecttable}' activity with the id '$this->contextinstanceid'.";
    }

    /**
     * Let developers validate their custom data (such as $this->data['other'], contextlevel, etc.).
     * Also used to complete data.
     */
    protected function validate_data() {
    }


}

