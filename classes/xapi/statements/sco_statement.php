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
 * xAPI transformation of a SCORM Lite event.
 *
 * @package    mod_scormlite
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scormlite\xapi\statements;

defined('MOODLE_INTERNAL') || die();

use logstore_trax\src\statements\base_statement;
use logstore_trax\src\utils\module_context;

/**
 * xAPI transformation of a SCORM Lite event.
 *
 * @package    mod_scormlite
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class sco_statement extends base_statement {

    use module_context, statement_utils;


    /**
     * Get the base Statement.
     *
     * @return array
     */
    protected function statement_base() {
        return [
            'context' => $this->statement_context(),
            'timestamp' => date('c', $this->event->timecreated),
        ];
    }

    /**
     * Get the context.
     *
     * @return array
     */
    abstract protected function statement_context();

    /**
     * Get the object.
     *
     * @return array
     */
    protected function statement_object() {
        global $DB;
        $cm = $DB->get_record('course_modules', ['id' => $this->event->contextinstanceid], '*', MUST_EXIST);
        return $this->activities->get('scormlite', $cm->instance, true, 'module', 'scormlite', 'mod_scormlite');
    }


}
