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

class scormlite_event extends \core\event\base {


    // MUST GET FROM RUNTIME

    // sessionid
    // attempt
    // launchmode

    
    // MUST GET FROM ACTIVITY SETTINGS

    // masteryscore
    // maxtime
    // launchmethod

    
    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'scormlite';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

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

}

