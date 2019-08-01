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

/**
 * Activity types vocab.
 *
 * @package    mod_scormlite
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scormlite\xapi\vocab;

defined('MOODLE_INTERNAL') || die();

use logstore_trax\src\vocab\activity_types as native_activity_types;

/**
 * Activity types vocab.
 *
 * @package    mod_scormlite
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_types extends native_activity_types {

    /**
     * Vocab items.
     *
     * @var array $items
     */
    protected $items = [

        'scormlite' => [
            'type' => 'http://vocab.xapi.fr/activities/web-content',
            'level' => 'http://vocab.xapi.fr/categories/learning-unit',
            'family' => 'resource',
            'standard' => 'scorm',
        ],

    ];


}
