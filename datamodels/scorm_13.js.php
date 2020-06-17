
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

if (isset($userdata->status)) {

	if ($userdata->status == "passed" || $userdata->status == "failed") {
		// Review mode
		$userdata->entry = '';				
		$userdata->mode = 'review';				
		$userdata->credit = 'no-credit';
	} else if ($userdata->status == "notattempted") {
		// Initial launch
		$userdata->entry = 'ab-initio';				
		$userdata->mode = 'normal';
		$userdata->credit = 'credit';
	} else if ($userdata->status == 'completed' && $userdata->exit != 'suspend') {
		// A completed content that is not a test and which enable a restart
		$userdata->entry = '';				
		$userdata->mode = 'normal';				
		$userdata->credit = 'credit';
	} else {
		// An incomplete content: always resume to prevent tests stopped abnormaly
		$userdata->entry = 'resume';				
		$userdata->mode = 'normal';				
		$userdata->credit = 'credit';
	}

}

// Get user data in JS
echo 'var userdata = new Array();';
foreach ($userdata as $key => $value) {
	echo "userdata['".$key."'] = '".$value."';";
}

?>

//
// SCORM 2004 Lite API Implementation
//
function SCORMapi1_3() {

    // Standard Data Type Definition
    var CMIString1000 = '^[\\u0000-\\uFFFF]{0,1000}$';
    var CMIString64000 = '^[\\u0000-\\uFFFF]{0,64000}$';
    var CMITimespan = '^P(\\d+Y)?(\\d+M)?(\\d+D)?(T(((\\d+H)(\\d+M)?(\\d+(\.\\d{1,2})?S)?)|((\\d+M)(\\d+(\.\\d{1,2})?S)?)|((\\d+(\.\\d{1,2})?S))))?$';
    var CMIDecimal = '^-?([0-9]{1,5})(\\.[0-9]{1,18})?$';

    // Vocabulary Data Type Definition
    var CMICStatus = '^completed$|^incomplete$|^not attempted$|^unknown$';
    var CMISStatus = '^passed$|^failed$|^unknown$';
    var CMIExit = '^time-out$|^suspend$|^logout$|^normal$|^$';
	
    // Children lists
    var cmi_children = '_version,completion_status,credit,entry,exit,launch_data,learner_id,learner_name,location,max_time_allowed,mode,progress_measure,scaled_passing_score,score,session_time,success_status,suspend_data,total_time';
    var score_children = 'max,raw,scaled,min';

    // Data ranges
    var scaled_range = '-1#1';
    var progress_range = '0#1';

    // The SCORM 1.3 data model
    var datamodel =  {
        'cmi._children':{'defaultvalue':cmi_children, 'mod':'r'},
        'cmi._version':{'defaultvalue':'1.0', 'mod':'r'},
        'cmi.completion_status':{'defaultvalue':'<?php echo isset($userdata->{'cmi.completion_status'})?$userdata->{'cmi.completion_status'}:'not attempted' ?>', 'format':CMICStatus, 'mod':'rw'},
        'cmi.completion_threshold':{'defaultvalue':<?php echo isset($userdata->threshold)?'\''.$userdata->threshold.'\'':'null' ?>, 'mod':'r'},
        'cmi.credit':{'defaultvalue':'<?php echo $userdata->credit ?>', 'mod':'r'},
        'cmi.entry':{'defaultvalue':'<?php echo $userdata->entry ?>', 'mod':'r'},
        'cmi.exit':{'defaultvalue':<?php echo isset($userdata->exit)?'\''.$userdata->exit.'\'':'null' ?>, 'format':CMIExit, 'mod':'w'},
        'cmi.launch_data':{'defaultvalue':<?php echo isset($userdata->launch_data)?'\''.$userdata->launch_data.'\'':'null' ?>, 'mod':'r'},
        'cmi.learner_id':{'defaultvalue':'<?php echo $userdata->student_id ?>', 'mod':'r'},
        'cmi.learner_name':{'defaultvalue':'<?php echo $userdata->student_name ?>', 'mod':'r'},
        'cmi.location':{'defaultvalue':<?php echo isset($userdata->{'cmi.location'})?'\''.$userdata->{'cmi.location'}.'\'':'null' ?>, 'format':CMIString1000, 'mod':'rw'},
        'cmi.max_time_allowed':{'defaultvalue':<?php echo isset($userdata->max_time_allowed)?'\''.$userdata->max_time_allowed.'\'':'null' ?>, 'mod':'r'},
        'cmi.mode':{'defaultvalue':'<?php echo $userdata->mode ?>', 'mod':'r'},
        'cmi.progress_measure':{'defaultvalue':<?php echo isset($userdata->{'cmi.progess_measure'})?'\''.$userdata->{'cmi.progress_measure'}.'\'':'null' ?>, 'format':CMIDecimal, 'range':progress_range, 'mod':'rw'},
        'cmi.scaled_passing_score':{'defaultvalue':<?php echo isset($userdata->scaled_passing_score)?'\''.$userdata->scaled_passing_score.'\'':'null' ?>, 'format':CMIDecimal, 'range':scaled_range, 'mod':'r'},
        'cmi.score._children':{'defaultvalue':score_children, 'mod':'r'},
        'cmi.score.scaled':{'defaultvalue':<?php echo isset($userdata->{'cmi.score.scaled'})?'\''.$userdata->{'cmi.score.scaled'}.'\'':'null' ?>, 'format':CMIDecimal, 'range':scaled_range, 'mod':'rw'},
        'cmi.score.raw':{'defaultvalue':<?php echo isset($userdata->{'cmi.score.raw'})?'\''.$userdata->{'cmi.score.raw'}.'\'':'null' ?>, 'format':CMIDecimal, 'mod':'rw'},
        'cmi.score.min':{'defaultvalue':<?php echo isset($userdata->{'cmi.score.min'})?'\''.$userdata->{'cmi.score.min'}.'\'':'null' ?>, 'format':CMIDecimal, 'mod':'rw'},
        'cmi.score.max':{'defaultvalue':<?php echo isset($userdata->{'cmi.score.max'})?'\''.$userdata->{'cmi.score.max'}.'\'':'null' ?>, 'format':CMIDecimal, 'mod':'rw'},
        'cmi.session_time':{'format':CMITimespan, 'mod':'w', 'defaultvalue':'PT0H0M0S'},
        'cmi.success_status':{'defaultvalue':'<?php echo isset($userdata->{'cmi.success_status'})?$userdata->{'cmi.success_status'}:'unknown' ?>', 'format':CMISStatus, 'mod':'rw'},
        'cmi.suspend_data':{'defaultvalue':<?php echo isset($userdata->{'cmi.suspend_data'})?'\''.$userdata->{'cmi.suspend_data'}.'\'':'null' ?>, 'format':CMIString64000, 'mod':'rw'},
        'cmi.time_limit_action':{'defaultvalue':<?php echo isset($userdata->timelimitaction)?'\''.$userdata->timelimitaction.'\'':'null' ?>, 'mod':'r'},
        'cmi.total_time':{'defaultvalue':'<?php echo isset($userdata->{'cmi.total_time'})?$userdata->{'cmi.total_time'}:'PT0H0M0S' ?>', 'mod':'r'}
    };
	

	// Custom elements, outside of the datamodel
	var customdata = new Array();
	for (key in userdata) {
		if (key in datamodel) {
			// Nothing to do
			// alert(key + " = " + userdata[key]);
		} else if (key.indexOf(".") != -1 && key.substr(0, 2) != "x.") {
			customdata[key] = userdata[key];
		}
	}

	
    //
    // Datamodel inizialization
    //
	
	var cmi = new Object();
	cmi.score = new Object();

    for (element in datamodel) {
		if ((typeof eval('datamodel["'+element+'"].defaultvalue')) != 'undefined') {
			eval(element+' = datamodel["'+element+'"].defaultvalue;');
		} else {
			eval(element+' = "";');
		}
    }


    //
    // Internal timer initialization
    //
	
    var InitializeTime = false;


    //
    // API Methods definition
    //

    var Initialized = false;
    var Terminated = false;
    var diagnostic = "";
    var errorCode = "0";
	var coockiesAllowed = false;

    function Initialize (param) {
		diagnostic = "";
        if (param == "") {
            if ((!Initialized) && (!Terminated)) {
                var AJAXResult = CheckAccess();
                result = ('true' == AJAXResult) ? 'true' : 'false';
                errorCode = ('true' == result)? '0' : '101'; // General exception for any AJAX fault
                if ('true' == result) {
                    Initialized = true;
                    InitializeTime = new Date().getTime();
					errorCode = "0";
					coockiesAllowed = CheckCoockies();
					//alert("Init. Coockies: "+coockiesAllowed);
					if (coockiesAllowed) {
						localdata = GetLocalData();
						if (localdata != "") {
							alert("<?php echo get_string('recovery', 'scormlite') ?>");
							RestoreLocalData(localdata);
						} else {
							RemoveAllCoockies();
						}
					}
					return "true";
				} else {
					alert("Access denied! You will be redirected to the launching page.");
					<?php if ($sco->popup == 1): ?>
						top.closepopup();
					<?php endif; ?>
					top.location.href = '<?php echo $backurl ?>';
				}
            }
        }
        errorCode = "101";
        return "false";
    }

    function Terminate (param) {
		diagnostic = "";
        if (param == "") {
            if ((Initialized) && (!Terminated)) {
				StoreLocalData(cmi);
                var AJAXResult = StoreData(cmi,true,true);
                result = ('true' == AJAXResult) ? 'true' : 'false';
                errorCode = ('true' == result)? '0' : '101'; // General exception for any AJAX fault
                if ('true' == result) {
                    Initialized = false;
                    Terminated = true;
					RemoveLocalData();
					//alert("Terminate OK");
	
					<?php if ($sco->popup == 1): ?>
						top.closepopup();
					<?php endif; ?>
					top.location.href = '<?php echo $backurl ?>';

                } else {
                    diagnostic = "Failure calling the Terminate remote callback: the server replied with HTTP Status " + AJAXResult;
                }
                return result;
            }
        }
        errorCode = "101";
        return "false";
    }

    function GetValue (element) {
		//alert("GetValue "+element);
        diagnostic = "";
        if ((Initialized) && (!Terminated)) {
            if (element !="") {

                // Check if the element is known in the data model
                if ((typeof eval('datamodel["'+element+'"]')) != "undefined") {

                    // Check if the element can be read
                    if (eval('datamodel["'+element+'"].mod') != 'w') {
					
						// OK to read
						//alert("GetValue OK "+element);
						errorCode = "0";
						return eval(element);
                    }
                } else {
				
					// Element is not in the data model. Check if it is in the custom data array.
					if (element in customdata) {

						// OK to read
						//alert("GetValue OK "+element);
						errorCode = "0";
						return customdata[element];
					}
				}
            }
        }
        errorCode = "101";
        return "";
    }

    function SetValue (element,value) {
		//alert("SetValue "+element+" "+value);
        diagnostic = "";
        if ((Initialized) && (!Terminated)) {
            if (element != "") {

                // Check if the element is known in the data model
                if ((typeof eval('datamodel["'+element+'"]')) != "undefined") {

                    // Check if the element can be written
                    if (eval('datamodel["'+element+'"].mod') != 'r') {

                        // Check if element format matches with its definition
                        expression = new RegExp(eval('datamodel["'+element+'"].format'));
                        value = value+'';
                        var matches = value.match(expression);
                        if ((matches != null) && ((matches.join('').length > 0) || (value.length == 0))) {

							// Check if element is inside the defined range (if a range is defined)
							if ((typeof eval('datamodel["'+element+'"].range')) != "undefined") {
								range = eval('datamodel["'+element+'"].range');
								ranges = range.split('#');
								value = value*1.0;
								if (value >= ranges[0]) {
									if ((ranges[1] == '*') || (value <= ranges[1])) {
									
										// Element is inside the range. OK to write
										eval(element+'=value;');
										//alert("SetValue OK "+element+" "+value);
										errorCode = "0";
										return "true";
									}
								}
							} else {
							
								// No range defined. OK to write
								eval(element+'=value;');
								//alert("SetValue OK "+element+" "+value);
								errorCode = "0";
								return "true";
							}
                        }
                    }
                } else {
				
					// Element is not in the data model. OK to write with no check at all (the content is responsible for bugs).
					customdata[element] = value;
					//alert("SetValue OK "+element+" "+value);
					errorCode = "0";
					return "true";
					
				}
            }
        }
        errorCode = "101";
        return "false";
    }

    function Commit(param) {
		//alert("Commit");
		diagnostic = "";

		if ((Initialized) && (!Terminated)) {	
			if (param && coockiesAllowed) {		
				//alert("Local Commit");
				StoreLocalData(cmi);
				return 'true';
			} else {
				//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! Commit only when coockies do not work. Should add a setting for that!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
				return HTTPCommit(false);
			}				
		}
        errorCode = "101";
        return "false";
    }

    function GetLastError () {
        return errorCode;
    }

    function GetErrorString (param) {
        if (param != "") {
            var errorString = "";
            switch(param) {
                case "0":
                    errorString = "No error";
                break;
                case "101":
                    errorString = "General exception";
                break;
            }
            return errorString;
        } else {
            return "";
        }
    }

    function GetDiagnostic (param) {
		return diagnostic;
    }


    //
    // Internal functions
    //

	// Coockies
	
    function CheckCoockies() {
		var name = "scormlitecheck";
		document.cookie = name+"=test";
		var testdata = GetCoockie(name);
		return (testdata == "test");
    }

	function GetCoockie(name) {
		begin = document.cookie.indexOf(name + "=");
		if (begin >= 0) {
			begin += name.length + 1;
			end = document.cookie.indexOf(";",begin);
			if (end < 0) end = document.cookie.length;
			return unescape(document.cookie.substring(begin,end));
		}
		return "";
	}

    function RemoveAllCoockies() {
	    var cookies = document.cookie.split("scormlite");
	    for (var i = 0; i < cookies.length; i++) {
	        var cookie = cookies[i];
	        var eqPos = cookie.indexOf("=");
	        if (eqPos > 1) {
	        	var name = "scormlite"+cookie.substr(0, eqPos);
	        	//alert("Cookie "+name);
		        document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
	        }
	    }
    }
    
    function RemoveLocalData() {
        if (cmi.mode == 'normal') {
			name = "scormlite<?php p($scoid) ?>u<?php p($userid) ?>";
			document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
		}
    }
	
    function HTTPCommit(updateTime) {
		// alert("HTTP Commit");
		var AJAXResult = StoreData(cmi, updateTime);
		var result = ('true' == AJAXResult) ? 'true' : 'false';
		if (result == 'true') {
			//alert("HTTP Commit OK");
			RemoveLocalData();
			errorCode = '0';
		} else {
			errorCode = '101';
			diagnostic = "Failure calling the Commit remote callback: the server replied with HTTP Status " + AJAXResult;
		}
		return result;
    }
	
    function StoreLocalData(data) {
        var datastring = '';
		
        if (cmi.mode == 'normal') {

			// Collect standard data to store (only cmi)
			datastring += CollectData(data,'cmi');
			
			// Collect custom data to store
			datastring += CollectCustomData(customdata);

			// Add a few params
			datastring = "id=<?php p($id) ?>&scoid=<?php p($scoid) ?>&userid=<?php p($userid) ?>&sesskey=<?php echo sesskey() ?>&sessionid=<?php echo $sessionid ?>"+datastring;

			// Coockie
			name = "scormlite<?php p($scoid) ?>u<?php p($userid) ?>";
			document.cookie = name+"="+datastring;
			
			return datastring;
        }
		return '';
    }
	
	function GetLocalData() {
        if (cmi.mode == 'normal') {
			var name = "scormlite<?php p($scoid) ?>u<?php p($userid) ?>";
			var data = GetCoockie(name);
			return unescape(data);
		}
		return "";
	}

	function RestoreLocalData(data) {
		pairs = data.split("&");

		// Remove the 4 ones 
		pairs.shift();  // id
		pairs.shift();  // scoid
		pairs.shift();  // userid
		pairs.shift();  // sesskey

		for (rg in pairs) {
			pair = pairs[rg];
			elements = pair.split("=");
			name = elements[0];
			index = name.indexOf("__");
			while (index != -1){
				name = name.replace("__", ".");
				index = name.indexOf("__");
			}
			value = decodeURIComponent(elements[1]);
			SetValue(name, value);
		}
		
		// Local time update
		cmi.total_time = AddTime(cmi.total_time, cmi.session_time);
		cmi.session_time = "PT0H0M0S";

		// HTTP commit with record of the total time
		//alert("Restore and record HTTP");
		HTTPCommit(true);		
	}
	
    function CheckAccess() {
		var datastring = "id=<?php p($id) ?>&scoid=<?php p($scoid) ?>&userid=<?php p($userid) ?>&attempt=<?php p($attempt) ?>&sesskey=<?php echo sesskey() ?>&sessionid=<?php echo $sessionid ?>";
		var myRequest = NewHttpReq();
		var result = DoRequest(myRequest,"<?php p($CFG->wwwroot) ?>/mod/scormlite/checkaccess.php",datastring);
		var results = String(result).split('\n');
		errorCode = results[1];
		return results[0];
    }

    function StoreData(data, updateTime, terminate) {

        // Common params
        var datastring = "id=<?php p($id) ?>&scoid=<?php p($scoid) ?>&userid=<?php p($userid) ?>&attempt=<?php p($attempt) ?>&sesskey=<?php echo sesskey() ?>&sessionid=<?php echo $sessionid ?>";
			
        // Total time: updated on terminate and restore.
        if (updateTime) {
            datastring += TotalTime();
        }

        // Terminate.
        if (terminate) {
            datastring += TerminateTime();
        }

        // Curent time: always send
        datastring += CurrentTime();

        if (cmi.mode == 'normal') {
		
			// Collect standard data to store (only cmi)
			datastring += CollectData(data,'cmi');
			
			// Collect custom data to store
			datastring += CollectCustomData(customdata);

        }

        // HTTP request
        //alert("HTTP request");
        var myRequest = NewHttpReq();
        var result = DoRequest(myRequest,"<?php p($CFG->wwwroot) ?>/mod/scormlite/datamodel.php",datastring);
        var results = String(result).split('\n');
                
        // Error code
        errorCode = results[1];
        
        // True vs False
        return results[0];
    }

    function CollectData(data,parent) {
        var datastring = '';
        for (property in data) {
            if (typeof data[property] == 'object') {
			
				// Recursivity to go to the leaf elements
                datastring += CollectData(data[property],parent+'.'+property);
				
            } else {
			    var element = parent+'.'+property;
				
				// Check if the element is inside the data model (if should be)
                if ((typeof eval('datamodel["'+element+'"]')) != "undefined") {
				
					// Check if the element can be read
                    if (eval('datamodel["'+element+'"].mod') != 'r') {
					
                        var elementstring = '&'+underscore(element)+'='+encodeURIComponent(data[property]);
                        if ((typeof eval('datamodel["'+element+'"].defaultvalue')) != "undefined") {
                            if (eval('datamodel["'+element+'"].defaultvalue') != data[property] || eval('typeof(datamodel["'+element+'"].defaultvalue)') != typeof(data[property])) {
                                datastring += elementstring;
                            }
                        } else {
                            datastring += elementstring;
                        }
                    }
                }
            }
        }
        return datastring;
    }

    function CollectCustomData(data) {
        var datastring = '';
		for(element in data) {
            var elementstring = '&'+underscore(element)+'='+encodeURIComponent(data[element]);
        	datastring += elementstring;
		}
        return datastring;
    }

    function AddTime (first, second) {
        var timestring = 'P';
        var matchexpr = /^P((\d+)Y)?((\d+)M)?((\d+)D)?(T((\d+)H)?((\d+)M)?((\d+(\.\d{1,2})?)S)?)?$/;
        var firstarray = first.match(matchexpr);
        var secondarray = second.match(matchexpr);
        if ((firstarray != null) && (secondarray != null)) {
            var firstsecs=0;
            if(parseFloat(firstarray[13],10)>0){ firstsecs=parseFloat(firstarray[13],10); }
            var secondsecs=0;
            if(parseFloat(secondarray[13],10)>0){ secondsecs=parseFloat(secondarray[13],10); }
            var secs = firstsecs+secondsecs;  //Seconds
            var change = Math.floor(secs/60);
            secs = Math.round((secs-(change*60))*100)/100;
            var firstmins=0;
            if(parseInt(firstarray[11],10)>0){ firstmins=parseInt(firstarray[11],10); }
            var secondmins=0;
            if(parseInt(secondarray[11],10)>0){ secondmins=parseInt(secondarray[11],10); }
            var mins = firstmins+secondmins+change;   //Minutes
            change = Math.floor(mins / 60);
            mins = Math.round(mins-(change*60));
            var firsthours=0;
            if(parseInt(firstarray[9],10)>0){ firsthours=parseInt(firstarray[9],10); }
            var secondhours=0;
            if(parseInt(secondarray[9],10)>0){ secondhours=parseInt(secondarray[9],10); }
            var hours = firsthours+secondhours+change; //Hours
            change = Math.floor(hours/24);
            hours = Math.round(hours-(change*24));
            var firstdays=0;
            if(parseInt(firstarray[6],10)>0){ firstdays=parseInt(firstarray[6],10); }
            var seconddays=0;
            if(parseInt(secondarray[6],10)>0){ firstdays=parseInt(secondarray[6],10); }
            var days = Math.round(firstdays+seconddays+change); // Days
            var firstmonths=0;
            if(parseInt(firstarray[4],10)>0){ firstmonths=parseInt(firstarray[4],10); }
            var secondmonths=0;
            if(parseInt(secondarray[4],10)>0){ secondmonths=parseInt(secondarray[4],10); }
            var months = Math.round(firstmonths+secondmonths);
            var firstyears=0;
            if(parseInt(firstarray[2],10)>0){ firstyears=parseInt(firstarray[2],10); }
            var secondyears=0;
            if(parseInt(secondarray[2],10)>0){ secondyears=parseInt(secondarray[2],10); }
            var years = Math.round(firstyears+secondyears);
        }
        if (years > 0) {
            timestring += years + 'Y';
        }
        if (months > 0) {
            timestring += months + 'M';
        }
        if (days > 0) {
            timestring += days + 'D';
        }
        if ((hours > 0) || (mins > 0) || (secs > 0)) {
            timestring += 'T';
            if (hours > 0) {
                timestring += hours + 'H';
            }
            if (mins > 0) {
                timestring += mins + 'M';
            }
            if (secs > 0) {
                timestring += secs + 'S';
            }
        }
        return timestring;
    }

    function TotalTime() {
        var total_time = AddTime(cmi.total_time, cmi.session_time);
        return '&'+underscore('cmi.total_time')+'='+encodeURIComponent(total_time);
    }

    function CurrentTime() {
        var current_time = AddTime(cmi.total_time, cmi.session_time);
        return '&'+underscore('current_time')+'='+encodeURIComponent(current_time);
    }

    function TerminateTime() {
        var seconds = (new Date().getTime() - InitializeTime) / 1000 ;
        var terminate_time = SecondsToTime(seconds);
        return '&'+underscore('terminate_time')+'='+encodeURIComponent(terminate_time);
    }

    function SecondsToTime(ts) {
        var sec = (ts % 60);

        ts -= sec;
        var tmp = (ts % 3600);  //# of seconds in the total # of minutes
        ts -= tmp;              //# of seconds in the total # of hours

        // convert seconds to conform to CMITimespan type (e.g. SS.00)
        sec = Math.round(sec*100)/100;

        var strSec = new String(sec);
        var strWholeSec = strSec;
        var strFractionSec = "";

        if (strSec.indexOf(".") != -1)
        {
            strWholeSec =  strSec.substring(0, strSec.indexOf("."));
            strFractionSec = strSec.substring(strSec.indexOf(".")+1, strSec.length);
        }

        if (strWholeSec.length < 2)
        {
            strWholeSec = "0" + strWholeSec;
        }
        strSec = strWholeSec;

        if (strFractionSec.length)
        {
            strSec = strSec+ "." + strFractionSec;
        }


        if ((ts % 3600) != 0 )
            var hour = 0;
        else var hour = (ts / 3600);
        if ( (tmp % 60) != 0 )
            var min = 0;
        else var min = (tmp / 60);

        if ((new String(hour)).length < 2)
            hour = "0"+hour;
        if ((new String(min)).length < 2)
            min = "0"+min;

        var rtnVal = "PT"+hour+"H"+min+"M"+strSec+"S";

        return rtnVal;
    }


    this.Initialize = Initialize;
    this.Terminate = Terminate;
    this.GetValue = GetValue;
    this.SetValue = SetValue;
    this.Commit = Commit;
    this.GetLastError = GetLastError;
    this.GetErrorString = GetErrorString;
    this.GetDiagnostic = GetDiagnostic;
    this.version = '1.0';
}

var API_1484_11 = new SCORMapi1_3();

