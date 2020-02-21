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

/**
 * Define the complete scorm structure for backup, with file and id annotations
 */
class backup_scormlite_activity_structure_step extends backup_activity_structure_step {

	protected function define_structure() {

		// To know if we are including userinfo
		$userinfo = $this->get_setting_value('userinfo');

		// Define each element separated
		$scorm = new backup_nested_element('scormlite', array('id'), array(
			'course', 'name', 'intro', 'introformat', 'code', 'scoid', 'timemodified'));

		$scoes = new backup_nested_element('scoes');
		$sco = new backup_nested_element('sco', array('id'), array(
			'containertype', 'scormtype', 'reference', 'sha1hash', 'md5hash', 'revision', 'timeopen', 'timeclose',
			'manualopen', 'maxtime', 'passingscore', 'displaychrono', 'safeexam', 'colors', 'popup', 'maxattempt', 'whatgrade',
			'lock_attempts_after_success', 'launchfile', 'immediate_review', 'review_access', 'quetzal_statistics'));

		$scotracks = new backup_nested_element('scoes_tracks');
		$scotrack = new backup_nested_element('sco_track', array('id'), array(
			'userid', 'scoid', 'attempt', 'element', 'value', 'timemodified'));

		// SF2108 - Quetzal questions
		$questions = new backup_nested_element('questions');
		$question = new backup_nested_element('question', array('id'), array('manifestid', 'title', 'scoid'));
	

		// Build the tree
		$scorm->add_child($scoes);
		$scoes->add_child($sco);
		if ($userinfo) {
			$sco->add_child($scotracks);
			$scotracks->add_child($scotrack);
		}
		// SF2108 - Quetzal questions
		$sco->add_child($questions);
		$questions->add_child($question);

		// Define sources
		$scorm->set_source_table('scormlite', array('id' => backup::VAR_ACTIVITYID));
		
		$sql = '
			SELECT SS.*
			FROM {scormlite_scoes} SS
			INNER JOIN {scormlite} S ON S.scoid=SS.id
			WHERE S.id=?';
		$sco->set_source_sql($sql, array(backup::VAR_PARENTID));
// 		$sco->set_source_table('scormlite_scoes', array());
		
		if ($userinfo) {
			$scotrack->set_source_table('scormlite_scoes_track', array('scoid' => backup::VAR_PARENTID));
			// Define id annotations
			$scotrack->annotate_ids('user', 'userid');
		}
		// SF2108 - Quetzal questions
		$question->set_source_table('scormlite_quetzal_questions', array('scoid' => backup::VAR_PARENTID));

		// Define file annotations
		$scorm->annotate_files('mod_scormlite', 'intro', null); // This file area hasn't itemid
		$sco->annotate_files('mod_scormlite', 'content', 'id');
		$sco->annotate_files('mod_scormlite', 'package', 'id');

		// Return the root element (scorm), wrapped into standard activity structure
		return $this->prepare_activity_structure($scorm);
	}
}
