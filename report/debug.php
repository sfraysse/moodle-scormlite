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

/* KD2015 - Version 2.6.3 - Debug functions */

// Includes
require_once('../../../config.php');
require_once($CFG->dirroot.'/mod/scormlite/locallib.php');
require_once($CFG->dirroot.'/mod/scormlite/report/reportlib.php');

// Params
$scoid = required_param('scoid', PARAM_INT); 
$userid = required_param('userid', PARAM_INT); 
$action = optional_param('action', '', PARAM_TEXT); 

// Objects and vars
$sco = $DB->get_record("scormlite_scoes", array("id"=>$scoid), '*', MUST_EXIST);
$activity = scormlite_get_containeractivity($scoid, $sco->containertype);
$cm = get_coursemodule_from_instance($sco->containertype, $activity->id, 0, false, MUST_EXIST);

// Permissions 
require_login($cm->course, false, $cm);
$config = get_config('scormlite');
$contextmodule = context_module::instance($cm->id);
$debugget = $config->getscormstatus && has_capability('mod/scormlite:debugget', $contextmodule);
$debugset = $config->setscormstatus && has_capability('mod/scormlite:debugset', $contextmodule);
if (!$debugget && !$debugset) {
    echo 'Access denied!';
    die;
}

// Operation which need a page reload
if ($action == 'clean') {
    scormlite_debug_remove_logs($userid, $scoid);
    $path = $CFG->wwwroot.'/mod/scormlite/report/debug.php?scoid='.$scoid.'&userid='.$userid;
    header('Location: '.$path);
    exit;
}
    
// Page setup  
$url = new moodle_url('/mod/scormlite/report/debug.php', array('scoid'=>$scoid, 'userid'=>$userid));
$PAGE->set_url($url);
$PAGE->set_title(get_string('debug', 'scormlite'));
$PAGE->set_heading(get_string('debug', 'scormlite'));
echo $OUTPUT->header();


// Title and description
scormlite_print_title($cm, $activity);
$userdata = $DB->get_record('user', array('id'=>$userid), \core_user\fields::get_picture_fields());
echo '<h2 class="main">'.$OUTPUT->user_picture($userdata, array('courseid'=>$cm->course)).'<br>'.$userdata->firstname.' '.$userdata->lastname.'</h2>';
echo '<div>&nbsp;</div>';

        
/********************** Logs section ************************/

if ($debugget) {
    
    // Display logs
    
    echo '<h2>'.get_string('debuglogs', 'scormlite').'</h2>';
    $records = scormlite_debug_get_logs($userid, $scoid);
    if (empty($records)) {
        echo '<p>'.get_string('debuglogsnolog', 'scormlite').'</p>';
    } else {
        require_once($CFG->dirroot.'/lib/tablelib.php');
    
        // Define table columns
        $columns = array('timestamp', 'attempt', 'title', 'download');
        $headers = array(get_string('debugtimestamp', 'scormlite'), get_string('debugattempt', 'scormlite'), get_string('debugtitle', 'scormlite'), get_string('debugdata', 'scormlite'));
        
        // Define table object
        $table = new flexible_table('mod-scormlite-report');
        $table->define_columns($columns);
        $table->define_headers($headers);
        $table->define_baseurl($url);
        
        // Styles
        $table->column_class('timestamp', 'timestamp');
        $table->column_class('attempt', 'attempt');
        $table->column_class('title', 'title');
        $table->column_class('download', 'download');
    
        // Setup
        $table->setup();
    
        // Fill
        $table->start_output();
        foreach ($records as $record) {
            $row = array();	
            $row[] = userdate($record->timestamp, get_string('strftimedatetimeshort', 'langconfig'));
            $row[] = $record->attempt;
            $row[] = $record->title;
            $path = $CFG->wwwroot.'/mod/scormlite/report/debug_get.php?scoid='.$scoid.'&userid='.$userid.'&logid='.$record->id;
            $row[] = '<a href="'.$path.'" target="_blank">'.get_string('debuggetscormstatusbutton', 'scormlite').'</a>';
            $table->add_data($row);
        }

        // The end	
        $table->finish_output();
        
        // Operations
        $path = $url.'&action=clean';
        echo '<p><button class="btn btn-primary" onclick="window.location=\''.$path.'\';">'.get_string('debuglogsclean', 'scormlite').'</button></p>';
    }
    echo '<div>&nbsp;</div>';
}


/********************** Download section ************************/

if ($debugget) {
    $path = $CFG->wwwroot.'/mod/scormlite/report/debug_get.php?scoid='.$scoid.'&userid='.$userid;
    echo '<h2>'.get_string('debuggetscormstatus', 'scormlite').'</h2>';
    echo '<p>'.get_string('debuggetscormstatusintro', 'scormlite').'</p>';    
    echo '<button class="btn btn-primary" onclick=window.open("'.$path.'");>'.get_string('debuggetscormstatusbutton', 'scormlite').'</button>';
    echo '<div>&nbsp;</div>';
    echo '<div>&nbsp;</div>';
}


/********************** Upload section ************************/

if ($debugset) {
    echo '<h2>'.get_string('debugsetscormstatus', 'scormlite').'</h2>';
    if (isset($_FILES["debugfile"])) {
        if (!empty($_FILES["debugfile"]["name"]) && !empty($_FILES["debugfile"]["tmp_name"])) {
            if (!empty($_FILES["debugfile"]["error"])) {
                echo '<p>'.$_FILES["debugfile"]["error"].'</p>';
            } else {
                $ext = pathinfo($_FILES["debugfile"]["name"], PATHINFO_EXTENSION);
                if (strcasecmp($ext, 'xml') != 0) {
                    echo '<p>'.get_string('debugseterrorformat', 'scormlite').'</p>';
                } else {
                    $data = debug_import_file($_FILES["debugfile"]["tmp_name"], $_FILES["debugfile"]["name"]);
                    if ($sco->containertype != $data->settings['containertype']) {
                        echo '<p>'.get_string('debugseterrortype', 'scormlite').'</p>';
                    } else {
                        debug_set_settings($scoid, $userid, $data->settings);
                        debug_set_attempts($scoid, $userid, $data->attempts);
                        echo '<p>'.get_string('debugsetdone', 'scormlite').'</p>';                    
                    }
                }
            }
        } else {
            echo '<p>'.get_string('debugseterrorformat', 'scormlite').'</p>';
        }
    }
    echo '<p>'.get_string('debugsetscormstatusintro', 'scormlite').'</p>';
    echo '
    <form enctype="multipart/form-data" action="'.$url.'" method="post">
        <p><input name="debugfile" type="file" /><input type="submit" /></p>
    </form>
    ';
}



// The end
echo $OUTPUT->footer();


/**************************** Functions ******************************/


function debug_set_settings($scoid, $userid, $settings) {
    global $DB;
    $record = $DB->get_record('scormlite_scoes', Array('id'=>$scoid));
    foreach($settings as $key=>$val) {
        if (in_array($key, Array('timeopen', 'timeclose', 'manualopen', 'maxtime', 'passingscore', 'displaychrono', 'colors', 'popup', 'maxattempt', 'whatgrade'))) {
            $record->$key = $val;
        }
    }
    $DB->update_record('scormlite_scoes', $record);
}

function debug_set_attempts($scoid, $userid, $attempts) {
    global $DB;
    $DB->delete_records('scormlite_scoes_track', Array('scoid'=>$scoid, 'userid'=>$userid));
    $attempt_number = 1;
    foreach($attempts as $attempt) {
        foreach($attempt as $track) {
            $record = new stdClass();
            $record->userid = $userid;
            $record->scoid = $scoid;
            $record->attempt = $attempt_number;
            $record->element = $track->name;
            $record->value = $track->value;
            $record->timemodified = $track->timemodified;
            $DB->insert_record('scormlite_scoes_track', $record);
        }
        $attempt_number++;
    }
}

function debug_import_file($tempFile, $fileName) {
    global $USER, $CFG;
    
    // Context & draft ID
    $context = context_user::instance($USER->id);
    $contextid = $context->id;
    $fs = get_file_storage();
    $draftitemid = rand(1, 999999999);
    while ($files = $fs->get_area_files($contextid, 'user', 'draft', $draftitemid)) {
        $draftitemid = rand(1, 999999999);
    }
    
    // Build record
    $record = new stdClass();
    $record->filearea = 'draft';
    $record->component = 'user';
    $record->filepath = '/';
    $record->itemid   = $draftitemid;
    $record->license  = $CFG->sitedefaultlicense;
    $record->author   = '';
    $sourcefield = new stdClass;
    $sourcefield->source = $fileName;
    $record->source = serialize($sourcefield);
    $record->filename = "debug.xml";
    $record->contextid = $context->id;
    $record->userid    = $USER->id;
    
    // Store file
    if ($file = $fs->get_file($contextid, 'user', 'draft', $record->itemid, $record->filepath, $record->filename)) {
        $file->delete();
    }
    $fs->create_file_from_pathname($record, $tempFile);
    
    // Open manifest 
    $xmlfile = $fs->get_file($contextid, 'user', 'draft', $record->itemid, $record->filepath, $record->filename);
    $xmltext = $xmlfile->get_content();
    $xmltext = preg_replace('/&(?!\w{2,6};)/', '&amp;', $xmltext);
    
    // Remove temp files
    $fs->delete_area_files($contextid, 'user', 'draft', $record->itemid);

    // Get data
    $settings = Array();
    $attempts = Array();
    $objXML = new myXml2Array();
    $xml_debugs = $objXML->parse($xmltext);
    foreach ($xml_debugs as $xml_debug) {
        foreach ($xml_debug['children'] as $xml_item) {
            if ($xml_item['name'] == 'SETTINGS') { 
                foreach ($xml_item['children'] as $xml_setting) {
                    $name = strtolower($xml_setting['name']);
                    if (!isset($xml_setting['tagData'])) $settings[$name] = ''; 
                    else $settings[$name] = $xml_setting['tagData']; 
                }
            } else if ($xml_item['name'] == 'ATTEMPT') {
                $number = $xml_item['attrs']['NUMBER'];
                $attempt = Array();
                foreach ($xml_item['children'] as $xml_track) {
                    $track = new stdClass();
                    $track->name = strtolower($xml_track['name']);
                    if (!isset($xml_track['tagData'])) $track->value = ''; 
                    else $track->value = $xml_track['tagData'];
                    $track->timemodified = $xml_track['attrs']['TIMEMODIFIED'];
                    $attempt[] = $track;
                }
                $attempts[$number] = $attempt;
            }
        }
    }
    $res = new stdClass();
    $res->settings = $settings;
    $res->attempts = $attempts;
    return $res;
}


/* Usage
 Grab some XML data, either from a file, URL, etc. however you want. Assume storage in $strYourXML;

 $objXML = new xml2Array();
 $arrOutput = $objXML->parse($strYourXML);
 print_r($arrOutput); //print it out, or do whatever!

*/
class myXml2Array {

    var $arrOutput = array();
    var $resParser;
    var $strXmlData;

    /**
     * Convert a utf-8 string to html entities
     *
     * @param string $str The UTF-8 string
     * @return string
     */
    function utf8_to_entities($str) {
        global $CFG;

        $entities = '';
        $values = array();
        $lookingfor = 1;

        return $str;
    }

    /**
     * Parse an XML text string and create an array tree that rapresent the XML structure
     *
     * @param string $strInputXML The XML string
     * @return array
     */
    function parse($strInputXML) {
        $this->resParser = xml_parser_create ('UTF-8');
        xml_set_object($this->resParser, $this);
        xml_set_element_handler($this->resParser, "tagOpen", "tagClosed");

        xml_set_character_data_handler($this->resParser, "tagData");

        $this->strXmlData = xml_parse($this->resParser, $strInputXML );
        if (!$this->strXmlData) {
            die(sprintf("XML error: %s at line %d",
            xml_error_string(xml_get_error_code($this->resParser)),
            xml_get_current_line_number($this->resParser)));
        }

        xml_parser_free($this->resParser);

        return $this->arrOutput;
    }

    function tagOpen($parser, $name, $attrs) {
        $tag=array("name"=>$name, "attrs"=>$attrs);
        array_push($this->arrOutput, $tag);
    }

    function tagData($parser, $tagData) {
        if (trim($tagData)) {
            if (isset($this->arrOutput[count($this->arrOutput)-1]['tagData'])) {
                $this->arrOutput[count($this->arrOutput)-1]['tagData'] .= $this->utf8_to_entities($tagData);
            } else {
                $this->arrOutput[count($this->arrOutput)-1]['tagData'] = $this->utf8_to_entities($tagData);
            }
        }
    }

    function tagClosed($parser, $name) {
        $this->arrOutput[count($this->arrOutput)-2]['children'][] = $this->arrOutput[count($this->arrOutput)-1];
        array_pop($this->arrOutput);
    }

}


?>




