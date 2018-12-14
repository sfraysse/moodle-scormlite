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
require_once($CFG->dirroot.'/mod/scormlite/report/reportlib.php'); 

// Params
$scoid = required_param('scoid', PARAM_INT);                // SCO id
$userid = optional_param('userid', $USER->id, PARAM_INT);	// User id
$backurl = optional_param('backurl', '', PARAM_LOCALURL);	// Back URL
$attempt = optional_param('attempt', 1, PARAM_INT);     // Attempt

// Objects and vars
$sco = $DB->get_record("scormlite_scoes", array("id"=>$scoid), '*', MUST_EXIST);
$activity = scormlite_get_containeractivity($scoid, $sco->containertype);
$cm = get_coursemodule_from_instance($sco->containertype, $activity->id, 0, false, MUST_EXIST);
$course = $DB->get_record("course", array("id"=>$cm->course), '*', MUST_EXIST);

// Check back URL
if (empty($backurl)) $backurl = new moodle_url('/mod/'.$sco->containertype.'/view.php', array('id'=>$cm->id));


//
// Page setup
//

$url = new moodle_url('/mod/scormlite/player.php', array('scoid'=>$scoid, 'userid'=>$userid, 'attempt'=>$attempt, 'backurl'=>$backurl));
$PAGE->set_url($url);

//
// Check permissions
//

$backhtml = '<input type="button" class="btn btn-primary" value="'.get_string('continue').'" onClick="location.href=\''.$backurl.'\'"/>';
scormlite_check_player_permissions($cm, $sco, $userid, $attempt, $backhtml, true, $activity, $course);

//
// Print the page
//

// Start
scormlite_print_header($cm, $activity, $course);

// Useful strings
$scoidstr = '&amp;scoid='.$scoid;
$scoidpop = '&scoid='.$scoid;
$strexit = get_string('exitactivity','scormlite');
$stractivity = get_string('exitcontent','scormlite');

// NNX2017 - Encode redirect URL
$backurl = urlencode($backurl);

?>


<script type="text/javascript" src="request.js"></script>
<script type="text/javascript" src="api.php?id=<?php echo $cm->id.$scoidstr ?>&userid=<?php echo $userid ?>&attempt=<?php echo $attempt ?>&backurl=<?php echo $backurl ?>"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot; ?>/mod/scormlite/rd.js"></script>

<div id="scormpage">
	<div id="scormbox" class="no-toc" style="width: 100%">
		<div id="scormobject" class="scorm-right">
			<noscript><p>Javascript is required!</p></noscript>
			<?php
			if ($sco->popup == 0) {
				// Load the SCO in the main window
				echo "<script type=\"text/javascript\">scormlite_resize('100%', '100%');</script>\n";
				$fullurl = "loadSCO.php?id=".$cm->id.$scoidstr."&userid=".$userid."&attempt=".$attempt."&backurl=".$backurl;

				// SF2018 - Add a close button
				$unload_url = (new moodle_url('/mod/scormlite/empty.php'))->out();
				echo "<button onclick=\"document.getElementById('scoframe1').src = '".$unload_url."';\" class='btn btn-primary btn-sm' style='margin-bottom:10px;'>".get_string('manualopenclose', 'scormlite')."</button>\n";
				
				echo "<iframe id=\"scoframe1\" class=\"scoframe\" name=\"scoframe1\" src=\"{$fullurl}\"></iframe>\n";
			} else {
				// Load the SCO in a popup window
				?>
				<script type="text/javascript">
					//<![CDATA[
						var scormlitePopup;
						function openpopup(url,options) {
							fullurl = "<?php echo $CFG->wwwroot.'/mod/scormlite/' ?>" + url;
							scormlitePopup = window.open(fullurl,'scormlite',options);
							scormlitePopup.resizeTo(screen.width, screen.height);
							scormlitePopup.focus();
							return scormlitePopup;
						}
						function closepopup() {
							scormlitePopup.close();
						}
						url = "loadSCO.php?id=<?php echo $cm->id.$scoidpop ?>&userid=<?php echo $userid ?>&attempt=<?php echo $attempt ?>&backurl=<?php echo $backurl ?>";
						openpopup(url, "type=fullWindow,fullscreen,left=0,top=0,scrollbars=yes");   // Add here the popup settings

                        // KD2015-SL01 - Moved here to solve a popup error
						scormlite_resize('100%', '100%');                        
                        
					//]]>
				</script>

				<?php
				echo $OUTPUT->box(get_string('popupmessage', 'scormlite'), 'generalbox boxaligncenter boxwidthwide', 'intro');
			}
			?>

			<?php
            /* KD2015 - Version 2.6.3 - Proctec from session timout */
            $config = get_config('scormlite');
            if ($config->protecttimeout == 1) {
            ?>
                <iframe id="protecttimeout" name="protecttimeout" src="protect_timeout.php" style="width: 0;height: 0; border: none;"></iframe>
				<script type="text/javascript">
                    setInterval(function(){ document.getElementById('protecttimeout').contentWindow.location.reload(); }, 60000);
				</script>
            <?php } ?>

		</div>
	</div>
</div>

<?php
echo $OUTPUT->footer();
?>



