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

/**
 * xAPI transformation of a SCORM Lite event.
 *
 * @package    mod_scormlite
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sco_result_reset extends sco_statement {

    /**
     * Context properties.
     *
     * @var array $contextprops
     */
    protected $contextprops = ['learner'];


    /**
     * Build the Statement.
     *
     * @return array
     */
    protected function statement() {

        return array_replace($this->statement_base(), [
            'actor' => $this->actors->get('user', $this->event->userid),
            'verb' => $this->verbs->get('reset'),
            'object' => $this->statement_object(),
        ]);
    }

    /**
     * Get the context.
     *
     * @return array
     */
    protected function statement_context() {
        $context = $this->base_context('scormlite', true, 'scormlite', 'mod_scormlite');        
        return $this->add_context_props($context);
    }

}
