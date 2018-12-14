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

require_once(dirname(__FILE__).'/backup_scormlite_stepslib.php');

/**
 * scorm backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_scormlite_activity_task extends backup_activity_task {

	/**
	 * Define (add) particular settings this activity can have
	 */
	protected function define_my_settings() {
		// No particular settings for this activity
	}

	/**
	 * Define (add) particular steps this activity can have
	 */
	protected function define_my_steps() {
		// SCORM only has one structure step
		$this->add_step(new backup_scormlite_activity_structure_step('scormlite_structure', 'scormlite.xml'));
	}

	/**
	 * Code the transformations to perform in the activity in
	 * order to get transportable (encoded) links
	 */
	static public function encode_content_links($content) {
		global $CFG;

		$base = preg_quote($CFG->wwwroot,"/");

		// Link to the list of scorms
		$search="/(".$base."\/mod\/scormlite\/index.php\?id\=)([0-9]+)/";
		$content= preg_replace($search, '$@SCORMINDEX*$2@$', $content);

		// Link to scorm view by moduleid
		$search="/(".$base."\/mod\/scormlite\/view.php\?id\=)([0-9]+)/";
		$content= preg_replace($search, '$@SCORMVIEWBYID*$2@$', $content);

		return $content;
	}
}
