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

use logstore_trax\src\utils;

/**
 * xAPI transformation of a SCORM Lite event.
 *
 * @package    mod_scormlite
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait statement_utils {

    /**
     * Get the result.
     *
     * @param bool $success Success value
     * @return array
     */
    protected function statement_result($success = null, $result = []) {
        if (isset($success)) {
            $result['success'] = $success;
        }
        return $this->add_result_props($result);
    }

    /**
     * Add the result props.
     *
     * @param array $result result
     * @return array
     */
    protected function add_result_props(&$result) {

        if (isset($this->resultprops)) {
            foreach($this->resultprops as $prop) {

                switch ($prop) {

                    case 'completion':
                        $result['completion'] = true;
                        break;

                    case 'score':
                        $result['score'] = [
                            'min' => $this->eventother->score_min,
                            'max' => $this->eventother->score_max,
                            'raw' => $this->eventother->score_raw,
                            'scaled' => $this->eventother->score_scaled,
                        ];
                        break;

                    case 'duration':
                        $result['duration'] = $this->eventother->duration;
                        break;
                }
            }
        }
        return $result;
    }

    /**
     * Add the context props.
     *
     * @param array $context context
     * @return array
     */
    protected function add_context_props(&$context) {

        if (isset($this->contextprops)) {
            foreach($this->contextprops as $prop) {

                switch ($prop) {

                    case 'launchmode':
                        $context['extensions']['https://w3id.org/xapi/cmi5/context/extensions/launchmode'] 
                            = $this->eventother->launchmode;
                        break;

                    case 'launchmethod':
                        $context['extensions']['http://vocab.xapi.fr/extensions/launch-method']
                            = $this->eventother->launchmethod;
                        break;

                    case 'masteryscore':
                        $context['extensions']['https://w3id.org/xapi/cmi5/context/extensions/masteryscore']
                            = $this->eventother->masteryscore;
                        break;

                    case 'scoringmethod':
                        $context['extensions']['http://vocab.xapi.fr/extensions/scoring-method']
                            = $this->eventother->scoringmethod;
                        break;

                    case 'maxtime':
                        if (isset($this->eventother->maxtime)) {
                            $context['extensions']['http://vocab.xapi.fr/extensions/max-time']
                                = $this->eventother->maxtime;
                        }
                        break;

                    case 'maxattempts':
                        $context['extensions']['http://vocab.xapi.fr/extensions/max-attempts']
                            = $this->eventother->maxattempts;
                        break;

                    case 'attemptsnumber':
                        $context['extensions']['http://vocab.xapi.fr/extensions/attempts-number']
                            = $this->eventother->attemptsnumber;
                        break;

                    case 'learner':
                        if ($this->event->userid != $this->event->relateduserid) {
                            $context['extensions']['http://vocab.xapi.fr/extensions/learner']
                                = $this->actors->get('user', $this->event->relateduserid);
                        }
                        break;
                }
            }
        }
        return $context;
    }

}
