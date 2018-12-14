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


// Includes
require_once('../../config.php');
require_once($CFG->dirroot.'/mod/scormlite/locallib.php'); 

// Params
$id    = optional_param('id', '', PARAM_INT);           // Course Module id, or
$scoid = required_param('scoid', PARAM_INT);            // SCO id
$userid = optional_param('userid',$USER->id,PARAM_INT);	// User id
$backurl = optional_param('backurl','',PARAM_LOCALURL);	// Back URL
$attempt = optional_param('attempt', 1, PARAM_INT);     // Attempt

// Objects and vars
$sco = $DB->get_record("scormlite_scoes", array("id"=>$scoid), '*', MUST_EXIST);
$activity = scormlite_get_containeractivity($scoid, $sco->containertype);
$cm = get_coursemodule_from_instance($sco->containertype, $activity->id, 0, false, MUST_EXIST);
$delayseconds = 1;  // Delay time before sco launch, used to give time to browser to define API

// Check back URL
if (empty($backurl)) $backurl = new moodle_url('/mod/'.$sco->containertype.'/view.php', array('id'=>$cm->id));

//
// Page setup
//

$url = new moodle_url('/mod/scormlite/loadSCO.php', array('id'=>$cm->id, 'scoid'=>$scoid, 'userid'=>$userid, 'backurl'=>$backurl, 'attempt'=>$attempt));
$PAGE->set_url($url);

//
// Check permissions
//

if ($sco->popup == 0) $backhtml = '<input type="button" class="btn btn-primary" value="'.get_string('continue').'" onClick="top.location.href=\''.$backurl.'\'"/>';
else $backhtml = '<input type="button" class="btn btn-primary" value="'.get_string('continue').'" onClick="window.opener.location.href=\''.$backurl.'\';window.close();"/>';
scormlite_check_player_permissions($cm, $sco, $userid, $attempt, $backhtml);

//
// Print the page
//

$context = context_module::instance($cm->id);  // KD2014 - 2.6 compliance
$launcher = ($sco->launchfile == '' ? 'index.html' : $sco->launchfile);
$result = "$CFG->wwwroot/pluginfile.php/{$context->id}/mod_{$sco->containertype}/content/$sco->id/$sco->revision/$launcher";

// which API are we looking for
$LMS_api = 'API_1484_11';
?>

<html>
<head>
	<title>LoadSCO</title>
	<script type="text/javascript">
        //<![CDATA[
        var myApiHandle = null;
        var myFindAPITries = 0;

        function myGetAPIHandle() {
           myFindAPITries = 0;
           if (myApiHandle == null) {
              myApiHandle = myGetAPI();
           }
           return myApiHandle;
        }

        function myFindAPI(win) {
           while ((win.<?php echo $LMS_api; ?> == null) && (win.parent != null) && (win.parent != win)) {
              myFindAPITries++;
              // Note: 7 is an arbitrary number, but should be more than sufficient
              if (myFindAPITries > 7) {
                 return null;
              }
              win = win.parent;
           }
           return win.<?php echo $LMS_api; ?>;
        }

        // hun for the API - needs to be loaded before we can launch the package
        function myGetAPI() {
           var theAPI = myFindAPI(window);
           if ((theAPI == null) && (window.opener != null) && (typeof(window.opener) != "undefined")) {
              theAPI = myFindAPI(window.opener);
           }
           if (theAPI == null) {
              return null;
           }
           return theAPI;
        }

       function doredirect() {
            if (myGetAPIHandle() != null) {
                location.href = "<?php echo $result ?>";
            } else {
				// SF2017 - Remove pix_url
                //document.body.innerHTML = "<p><?php echo get_string('activityloading', 'scormlite');?> <span id='countdown'><?php echo $delayseconds ?></span> <?php echo get_string('numseconds', 'moodle', '');?>. &nbsp; <img src='<?php echo $OUTPUT->pix_url('wait', 'scormlite') ?>'><p>";
                document.body.innerHTML = "<p><?php echo get_string('activityloading', 'scormlite');?> <span id='countdown'><?php echo $delayseconds ?></span> <?php echo get_string('numseconds', 'moodle', '');?>.<p>";
                var e = document.getElementById("countdown");
                var cSeconds = parseInt(e.innerHTML);
                var timer = setInterval(function() {
                                                if( cSeconds && myGetAPIHandle() == null ) {
                                                    e.innerHTML = --cSeconds;
                                                } else {
                                                    clearInterval(timer);
                                                    document.body.innerHTML = "<p><?php echo get_string('activitypleasewait', 'scormlite');?></p>";
                                                    location = "<?php echo $result ?>";
                                                }
                                            }, 1000);
            }
        }
        //]]>
	</script>
	<meta http-Equiv="cache-control" Content="no-cache">
	<meta http-Equiv="pragma" Content="no-cache">
	<meta http-Equiv="expires" Content="0">
</head> 
<body onload="doredirect();">
	<p>
	<?php echo get_string('activitypleasewait', 'scormlite');?>
	</p>
</body>
</html>
