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
 * Define all the restore steps that will be used by the restore_scormlite_activity_task.
 * 
 * Structure step to restore one scormlite activity
 */
class restore_scormlite_activity_structure_step extends restore_activity_structure_step {

	protected function define_structure() {

		$paths = array();
		$userinfo = $this->get_setting_value('userinfo');

		$paths[] = new restore_path_element('scormlite', '/activity/scormlite');
		
		$paths[] = new restore_path_element('scormlite_sco', '/activity/scormlite/scoes/sco');
		if ($userinfo) {
			$paths[] = new restore_path_element('scormlite_sco_track', '/activity/scormlite/scoes/sco/scoes_tracks/sco_track');
		}
		// SF2018 - Quetzal questions
		$paths[] = new restore_path_element('question', '/activity/scormlite/scoes/sco/questions/question');

		// Return the paths wrapped into standard activity structure
		return $this->prepare_activity_structure($paths);
	}

	protected function process_scormlite($data) {
		global $DB;

		$data = (object)$data;
		$data->course = $this->get_courseid();
		$data->timemodified = $this->apply_date_offset($data->timemodified);

		// insert the scormlite record
		$newitemid = $DB->insert_record('scormlite', $data);
		// immediately after inserting "activity" record, call this
		$this->apply_activity_instance($newitemid);
	}

	protected function process_scormlite_sco($data) {
		global $DB;

		$data = (object)$data;

		$oldid = $data->id;
		$data->timeopen = $this->apply_date_offset($data->timeopen);
		$data->timeclose = $this->apply_date_offset($data->timeclose);

		$scormid = $this->elementsnewid['scormlite'];
		$newitemid = $DB->insert_record('scormlite_scoes', $data);
		$DB->execute("UPDATE {scormlite} SET scoid=$newitemid WHERE id=$scormid");
		$this->set_mapping('scormlite_sco', $oldid, $newitemid, true);
	}

	protected function process_scormlite_sco_track($data) {
		global $DB;

		$data = (object)$data;
		$oldid = $data->id;
		$data->scoid = $this->get_new_parentid('scormlite_sco');
		$data->userid = $this->get_mappingid('user', $data->userid);
		$data->timemodified = $this->apply_date_offset($data->timemodified);

		$newitemid = $DB->insert_record('scormlite_scoes_track', $data);
		// No need to save this mapping as far as nothing depend on it
		// (child paths, file areas nor links decoder)
	}

	// SF2018 - Quetzal questions
	protected function process_question($data) {
		global $DB;

		$data = (object)$data;
		$data->scoid = $this->get_new_parentid('scormlite_sco');
        
        // Save in DB
		$newitemid = $DB->insert_record('scormlite_quetzal_questions', $data);
        
        // Mapping
		// No need to save this mapping as far as nothing depend on it
		// (child paths, file areas nor links decoder)
	}

	protected function after_execute() {
		// Add scormlite related files, no need to match by itemname (just internally handled context)
		$this->add_related_files('mod_scormlite', 'intro', null);
		$this->add_related_files('mod_scormlite', 'content', 'scormlite_sco');
		$this->add_related_files('mod_scormlite', 'package', 'scormlite_sco');
	}
}
