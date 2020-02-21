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

defined('MOODLE_INTERNAL') || die();


function xmldb_scormlite_upgrade($oldversion) {

    global $CFG, $DB;
    $dbman = $DB->get_manager();

	// SCO table
	if ($oldversion < 2012010500) {
		$scoes = $DB->get_records('scormlite_scoes');
		foreach ($scoes as $sco) {
			$colors = json_decode("[$sco->colors]");
			if (is_object(reset($colors))) {
				$new_colors = array();
				foreach ($colors as $color) {
					$new_colors[] = $color->lt;
				}
				$sco->colors = implode(',', $new_colors);
				$DB->update_record('scormlite_scoes', $sco);
			}
		}
		$jsoncolors = '{"lt":50, "color":"#faa"}, {"lt":65, "color":"#fca"}, {"lt":75, "color":"#ffa"}, {"lt":101,"color":"#afa"}';
		$DB->set_field('config_plugins', 'value', $jsoncolors, array('plugin'=>'scormlite', 'name'=>'colors'));
	}

    // Adding attempt fields to scormlite_scoes table
    if ($oldversion < 2012112901) {
        $table = new xmldb_table('scormlite_scoes');

        $field = new xmldb_field('maxattempt', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', 'popup');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('whatgrade', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'maxattempt');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2012112901, 'scormlite');
    }

    /* KD2015 - Version 2.6.3 - Adding logs table */

    if ($oldversion < 2013110503) {
        
        // Define table assign_user_mapping to be created.
        $table = new xmldb_table('scormlite_logs');
    
        // Adding fields to table assign_user_mapping.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('scoid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('attempt', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timestamp', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table assign_user_mapping.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        $dbman->create_table($table);
    }
    
    /* NNX2017 - Version 3.2 - Adding column 'launchfile' to scormlite_scoes table */

    if ($oldversion < 2016112900) {
        
        $table = new xmldb_table('scormlite_scoes');

        $field = new xmldb_field('launchfile', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, false, null, 'whatgrade');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2016112900, 'scormlite');
    }
    
    /* SF2017 - Version 3.2.1 - Adding column 'immediate_review' to scormlite_scoes table */

    if ($oldversion < 2016112901) {
        
        $table = new xmldb_table('scormlite_scoes');

        $field = new xmldb_field('immediate_review', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('quetzal_statistics', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2016112901, 'scormlite');
    }
    
    /* SF2018 - Version 3.2.2 - Adding table "scormlite_quetzal_questions" */

    if ($oldversion < 2016112902 || $oldversion == 2017051500 || $oldversion == 2017110800) {
        
        // Define table assign_user_mapping to be created.
        $table = new xmldb_table('scormlite_quetzal_questions');
    
        // Adding fields to table assign_user_mapping.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('manifestid', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('scoid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table assign_user_mapping.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        $dbman->create_table($table);
    }
    
    /* Adding column 'review_access' to scormlite_scoes table */
    /* Adding column 'lock_attempts_after_success' to scormlite_scoes table */

    if ($oldversion < 2017110805 || $oldversion == 2018050800) {

        $table = new xmldb_table('scormlite_scoes');

        $field = new xmldb_field('review_access', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('lock_attempts_after_success', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018050801, 'scormlite');
    }
    
    /* Adding column 'safeexam' to scormlite_scoes table */

    if ($oldversion < 2018050806 || $oldversion == 2018112800 || $oldversion == 2018112801) {

        $table = new xmldb_table('scormlite_scoes');

        $field = new xmldb_field('safeexam', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018050806, 'scormlite');
    }
    
	return true;
}

