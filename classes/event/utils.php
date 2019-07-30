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

trait utils {

    /**
     * Get object ID mapping.
     */
    public static function get_objectid_mapping() {
        return array('db' => 'scormlite', 'restore' => 'scormlite');
    }

    /**
     * Get URL related to the action.
     */
    public function get_url() {
        return new \moodle_url("/mod/$this->objecttable/view.php", array('id' => $this->contextinstanceid));
    }

    /**
     * Convert second to ISO duration.
     */
    protected function iso8601_duration($seconds)
    {
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

