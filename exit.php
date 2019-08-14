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

// Params
$backurl = required_param('backurl', PARAM_RAW);
$backurl = urldecode($backurl);

//
// Page setup
//

$url = new moodle_url('/mod/scormlite/exit.php');
$PAGE->set_url($url);

?>

<html>
<head>
	<script type="">
	
		function redirect() {
			setTimeout(function() { 
				top.location.href = '<?php echo $backurl; ?>';
			}, 100);
		}

	</script>
</head> 
<body onload="redirect()">
	<p>
		<?php echo get_string('activityunloading', 'scormlite');?>
	</p>
</body>
</html>
