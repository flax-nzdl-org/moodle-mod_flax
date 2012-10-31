<?PHP  // $Id: report.php
/**
 * @author alex.xf.yu@gmail.com
 * 
 * This script reports on one of the followings: 
 * 1. a flax instance (a list of attempts)
 * 2. an attempt on a flax instance
 * 
 * 
 **/

    require_once('../../config.php');
	require_once ($CFG->dirroot.'/mod/flax/lib.php');

    // return a param through GET or POST; otherwise, the supplied default value (0 in this case) is returned.
	$id = optional_param('id', 0, PARAM_INT); // id as of course id
	$fid = optional_param('fid', 0, PARAM_INT); // id in the flax table

	global $DB;
	if ($id) {
		if (! $cm = $DB->get_record('course_modules', array('id'=>$id), '*', MUST_EXIST)) {
			print_error('Course Module ID was incorrect id=$id');
		}
		if (! $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST)) {
			print_error('Course is misconfigured id=$cm->course');
		}

		if (! $flax = $DB->get_record('flax', array('id'=>$cm->instance), '*', MUST_EXIST)) {
			print_error('Flax id is incorrect (id=$cm->instance)');
		}
	} else {
		if (! $flax = $DB->get_record('flax', array('id'=>$fid), '*', MUST_EXIST)) {
			print_error('Course module is incorrect');
		}
		if (! $course = $DB->get_record('course', array('id'=>$flax->course), '*', MUST_EXIST)) {
			print_error('Course is misconfigured');
		}
		if (! $cm = get_coursemodule_from_instance('flax', $flax->id, $course->id)) {
			print_error('Course Module ID was incorrect');
		}
	}
    // get the roles context for this course
    $sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);
    $modulecontext = get_context_instance(CONTEXT_MODULE, $cm->id);

    // set homeurl of couse (for error messages)
    $course_homeurl = "$CFG->wwwroot/course/view.php?id=$course->id";

    require_login($course, false, $cm);

    // get report mode
    if (has_capability('mod/flax:viewreport',$modulecontext)) {
        // normally, $mode='overview' would be resulted.
        $mode = optional_param('mode', 'overview', PARAM_ALPHA);
    } else {
        // ordinary students have no choice
        $mode = 'overview';
    }

    // assemble array of form data
    $formdata = array(
        'mode' => $mode,
        'reportusers'      => has_capability('mod/flax:viewreport',$modulecontext) ? optional_param('reportusers', get_user_preferences('flax_reportusers', 'allusers'), PARAM_ALPHANUM) : 'this',
        'reportattempts'   => optional_param('reportattempts', get_user_preferences('flax_reportattempts', 'all'), PARAM_ALPHA),
        'reportformat'     => optional_param('reportformat', 'htm', PARAM_ALPHA),
        'reportshowlegend' => optional_param('reportshowlegend', get_user_preferences('flax_reportshowlegend', '0'), PARAM_INT),
        'reportencoding'   => optional_param('reportencoding', get_user_preferences('flax_reportencoding', ''), PARAM_ALPHANUM),
        'reportwrapdata'   => optional_param('reportwrapdata', get_user_preferences('flax_reportwrapdata', '1'), PARAM_INT),
    );

    foreach ($formdata as $name=>$value) {
        set_user_preference($name, $value);
    }

/// Start the report

    // print page header. if required
    if ($formdata['reportformat']=='htm') {
        flax_print_report_heading($course, $cm, $flax, $mode);
        if (has_capability('mod/flax:viewreport',$modulecontext)) {

//@TODO: print a set of drop down menu which allows user to select different types of reports
//            flax_print_report_selector($course, $flax, $formdata);
        }
    }

    // delete selected attempts, if any
    if (has_capability('mod/flax:deleteattempt',$modulecontext)) {
        $del = optional_param('del', '', PARAM_ALPHA);
    }

    // check for groups
    if (preg_match('/^group(\d*)$/', $formdata['reportusers'], $matches)) {
        $formdata['reportusers'] = 'group';
        $formdata['reportgroupid'] = 0;
        // validate groupid
        if ($groups = groups_get_all_groups($course->id)) {
            if (isset($groups[$matches[1]])) {
                $formdata['reportgroupid'] = $matches[1];
            }
        }
    }

    $user_ids = '';
    $users = array();

    switch ($formdata['reportusers']) {

        case 'allusers':
            // anyone who has ever attempted this flax
            if ($records = $DB->get_records_select(TABLE_FLAX_RESPONSES, "flax=?", array($flax->id), '', 'id,userid')) {
                foreach ($records as $record) {
                    $users[$record->userid] = 0; // "0" means user is NOT currently allowed to attempt this flax
                }
                unset($records);
            }
            break;

        case 'group':
            // group members
            if ($members = groups_get_members($formdata['reportgroupid'])) {
                foreach ($members as $memberid=>$unused) {
                    $users[$memberid] = 1; // "1" signifies currently recognized participant
                }
            }
            break;

        case 'allparticipants':
            // anyone currently allowed to attempt this flax
            if ($records = get_users_by_capability($modulecontext, 'mod/flax:attempt', 'u.id,u.id', 'u.id')) {
                foreach ($records as $record) {
                    $users[$record->id] = 1; // "1" means user is allowed to do this flax
                }
                unset($records);
            }
            break;

        case 'existingstudents':
            // anyone currently allowed to attempt this flax who is not a teacher
            $teachers = get_users_by_capability($modulecontext, 'mod/flax:viewreport', 'u.id,u.id', 'u.id');
            if ($records = get_users_by_capability($modulecontext, 'mod/flax:attempt', 'u.id,u.id', 'u.id')) {
                foreach ($records as $record) {
                    if (empty($teachers[$record->id])) {
                        $users[$record->id] = 1;
                    }
                }
            }
            break;

        case 'this': // current user only
            $user_ids = $USER->id;
            break;

        default: // specific user selected by teacher
            if (is_numeric($formdata['reportusers'])) {
                $user_ids = $formdata['reportusers'];
            }
    }
    if (empty($user_ids) && count($users)) {
        ksort($users);
        $user_ids = join(',', array_keys($users));
    }
    if (empty($user_ids)) {
        print_heading(get_string('nousersyet'));
        exit;
    }

    // database table and selection conditions
    $table = "{".TABLE_FLAX_RESPONSES."} a";
    $select = "a.flax=? AND a.userid IN ($user_ids)";
    $fields = 'a.*, u.firstname, u.lastname, u.picture';

    $attempts = array();

    if ($select) {
        // add user information to SQL query
        $select .= ' AND a.userid = u.id';
        $table .= ", {user} u";
        $order = "u.lastname";
        // get the attempts (at last!)
        $attempts = $DB->get_records_sql("SELECT $fields FROM $table WHERE $select ORDER BY $order", array($flax->id));
    }

    // stop now if no attempts were found
    if (empty($attempts)) {
        print_heading(get_string('noattemptstoshow','quiz'));
        exit;
    }

    // get grades
    $grades = flax_get_grades($flax, $user_ids);

    // get list of attempts by user and set reference to last attempt in clickreport series
    $users = array();
    foreach ($attempts as $id=>$attempt) {

        $userid = $attempt->userid;

        if (!isset($users[$userid])) {
            $users[$userid]->grade = isset($grades[$userid]) ? $grades[$userid] : '&nbsp;';
            $users[$userid]->attempts = array();
        }
		//how many times has a particular user attempted on a flax instance
        $users[$userid]->attempts[] = &$attempts[$id];

    }

    if ($mode!='overview') {
    	//if not overview report, it's detailed report

		//////////////////////////////////////
		// what we could do, when generating detailed report, is to report user's score etc. in each activity instance in these attempts.

    }

/// Open the selected flax report and display it

    if (! is_readable("report/$mode/report.php")) {
        print_error("Report not known (".clean_text($mode).")", $course_homeurl);
    }

    include("report/default.php");  // Parent class
    include("report/$mode/report.php");

    $report = new flax_report();
	$report->display($flax, $cm, $course, $users, $attempts, $questions, $formdata);
//    if (! $report->display($flax, $cm, $course, $users, $attempts, $questions, $formdata)) {
//        print_error("Error occurred during report processing!", $course_homeurl);
//    }

    if ($formdata['reportformat']=='htm') {
        print_footer($course);
    }
    
    //
    //  Executables end here
    //
    
//////////////////////////////////////////////
/// functions to print the report headings and
/// report selector menus
//////////////////////////////////////////////

function flax_grade_heading($flax, $formdata) {
		return get_string('score', 'flax');
}

/**
 *  print page navigations: sitename->coursename->moduleplural->module->reporttype ...
 */
function flax_print_report_heading(&$course, &$cm, &$flax, &$mode) {
    $strmodulenameplural = get_string("modulenameplural", "flax");
    $strmodulename  = get_string("modulename", "flax");

    $title = format_string($course->shortname) . ": $flax->name";
    $heading = $course->fullname;

    $modulecontext = get_context_instance(CONTEXT_MODULE, $cm->id);
    if (has_capability('mod/flax:viewreport',$modulecontext)) {
        if ($mode=='overview' || $mode=='simplestat' || $mode=='fullstat') {
            $module = "quiz";
        } else {
            $module = "flax";
        }

        $navigation = build_navigation(get_string("report$mode", $module), $cm);
    } else {
        $navigation = build_navigation(get_string("report", "quiz"), $cm);
    }

    $button = update_module_button($cm->id, $course->id, $strmodulename);
    print_header($title, $heading, $navigation, "", "", true, $button, navmenu($course, $cm));

    print_heading($flax->name);
}
/**
 * print report header: Content: Overview All users All attempts Generate report
 * print report data
 */ 
function flax_print_report_selector(&$course, &$flax, &$formdata) {

    global $CFG, $DB;

//    $reports = flax_get_report_names('overview,simplestat,fullstat');
    $reports = flax_get_report_names('overview');//Flax can only do overview, for now.

    print '<form method="post" action="'."$CFG->wwwroot/mod/flax/report.php?fx=$flax->id".'">';
    print '<table cellpadding="2" align="center">';

    $menus = array();

    $menus['mode'] = array();
    foreach ($reports as $name) {
//        if ($name=='overview' || $name=='simplestat' || $name=='fullstat') {
        if ($name=='overview') {
            $module = "quiz";   // standard reports
        } else {
            $module = "flax"; // custom reports
        }
        if ($module) {
            $menus['mode'][$name] = get_string("report$name", $module);
        }
    }

    $menus['reportusers'] = array(
        'allusers' => get_string('allusers', 'flax'),
        'allparticipants' => get_string('allparticipants')
    );

    // groups
    if ($groups = groups_get_all_groups($course->id)) {
        foreach ($groups as $gid => $group) {
            $menus['reportusers']["group$gid"] = get_string('group').': '.format_string($group->name);
        }
    }

    // get users who have ever atetmpted this flax
    $users = $DB->get_records_sql("
        SELECT
            u.id, u.firstname, u.lastname
        FROM
            {user} u,
            {".TABLE_FLAX_RESPONSES."} ha
        WHERE
            u.id = ha.userid AND ha.flax=?
        ORDER BY
            u.lastname
    ", array($flax->id));

    // get context
    $cm = get_coursemodule_from_instance('flax', $flax->id);
    $modulecontext = get_context_instance(CONTEXT_MODULE, $cm->id);

    // get teachers enrolled students
    $teachers = get_users_by_capability($modulecontext, 'mod/flax:viewreport', 'u.id,u.firstname,u.lastname', 'u.lastname');
    $students = get_users_by_capability($modulecontext, 'mod/flax:attempt', 'u.id,u.firstname,u.lastname', 'u.lastname');

    // current students
    if (!empty($students)) {
        $firsttime = true;
        foreach ($students as $user) {
            if (isset($users[$user->id])) {
                if ($firsttime) {
                    $firsttime = false; // so we only do this once
                    $menus['reportusers']['existingstudents'] = get_string('existingstudents');
                    $menus['reportusers'][] = '------';
                }
                $menus['reportusers']["$user->id"] = fullname($user);
                unset($users[$user->id]);
            }
        }
    }
    // others (former students, teachers, admins, course creators)
    if (!empty($users)) {
        $firsttime = true;
        foreach ($users as $user) {
            if ($firsttime) {
                $firsttime = false; // so we only do this once
                $menus['reportusers'][] = '======';
            }
            $menus['reportusers']["$user->id"] = fullname($user);
        }
    }

    $menus['reportattempts'] = array(
        'all' => get_string('attemptsall', 'flax'),
        'best' => get_string('attemptsbest', 'flax'),
        'first' => get_string('attemptsfirst', 'flax'),
        'last' => get_string('attemptslast', 'flax')
    );

    print '<tr><td>';
    // helpbutton function in lib/weblib.php
    helpbutton('reportcontent', get_string('reportcontent', 'flax'), 'flax');
    print '</td><th align="right" scope="col">'.get_string('reportcontent', 'flax').':</th><td colspan="7">';
    foreach ($menus as $name => $options) {
        $value = $formdata[$name];
        print choose_from_menu($options, $name, $value, "", "", 0, true);
    };
    print '<input type="submit" value="'.get_string('reportbutton', 'flax').'" /></td></tr>';

    print '</table>';

    print '<hr size="1" noshade="noshade" />';
    print '</form>'."\n";
}
function flax_get_report_names($names='') {
    // $names : optional list showing required order reports names

    $reports = array();

    // convert $names to an array, if necessary (usually is)
    if (!is_array($names)) {
        $names = explode(',', $names);
    }

    $plugins = get_list_of_plugins('mod/flax/report');
    foreach($names as $name) {
        if (is_numeric($i = array_search($name, $plugins))) {
            $reports[] = $name;
            unset($plugins[$i]);
        }
    }

    // append remaining plugins
    $reports = array_merge($reports, $plugins);

    return $reports;
}
function flax_get_report_users($course, $formdata) {
    $users = array();

    /// Check to see if groups are being used in this module
    $groupmode = groupmode($course, $cm); //TODO: there is no $cm defined!
    $currentgroup = setup_and_print_groups($course, $groupmode, "report.php?id=$cm->id&mode=simple");

    $sort = "u.lastname ASC";

    switch ($formdata['reportusers']) {
        case 'students':
            if ($currentgroup) {
                $users = get_group_students($currentgroup, $sort);
            } else {
                $users = get_course_students($course->id, $sort);
            }
            break;
        case 'all':
            if ($currentgroup) {
                $users = get_group_users($currentgroup, $sort);
            } else {
                $users = get_course_users($course->id, $sort);
            }
            break;
    }

    return $users;
}
/*
function flax_get_records_groupby($function, $fieldnames, $table, $select, $groupby) {
    // $function is an SQL aggregate function (MAX or MIN)

	global $DB;
    $fields = sql_concat_join("'_'", $fieldnames);
    $fields = "$groupby, $function($fields) AS joinedvalues";

    if ($fields) {
        $records = $DB->get_records_sql("SELECT $fields FROM $table WHERE $select GROUP BY $groupby", array());
    }

    if (empty($fields) || empty($records)) {
        $records = array();
    }

    $fieldcount = count($fieldnames);

    foreach ($records as $id=>$record) {
        if (empty($record->joinedvalues)) {
            unset($records[$id]);
        } else {
            $values = explode('_', $record->joinedvalues);

            for ($i=0; $i<$fieldcount; $i++) {
                $fieldname = $fieldnames[$i];
                $records[$id]->$fieldname = $values[$i];
            }
        }
        unset($record->joinedvalues);
    }

    return $records;
}*/
?>
