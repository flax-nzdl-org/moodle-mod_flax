<?php  // $Id: lib.php  Exp $
/**
 * @author alex.xf.yu@gmail.com
 * 
 * Library of interface functions and constants for the flax module, of which all names start with flax_
 * 
 * A list of functions that all standard modules are suggested to have according to Moodle development API:
 * 
 * flax_supports
 * flax_add_instance
 * flax_update_instance
 * flax_delete_instance
 * flax_user_outline
 * flax_user_complete
 * flax_get_view_actions
 * flax_get_post_actions
 * flax_print_recent_activity (called when displaying the main page of a course)
 * flax_get_recent_mod_activity
 * flax_print_recent_mod_activity
 * flax_get_participants
 * flax_grade_item_update
 * flax_grade_item_delete
 * flax_update_grades
 * flax_cron
 * flax_extend_navigation
 * flax_extend_settings_navigation
 * flax_get_extra_capabilities
 * flax_scale_used
 * flax_scale_used_anywhere
 * 
 * TODO - the followings remain to be implemented for the flax module
 * 
 * flax_get_fileareas
 * flax_pluginfile
 * 
 *
 * @package    mod
 * @subpackage flax
 * @author xiao@waikato.ac.nz
 **/
//////////////////////////////////
/// CONFIGURATION settings

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/locallib.php');

/**
 * @param object $instance An object from the form in mod_form.php
 * @return int flax instance id of the newly inserted flax table record, or false in case of failure
 **/
function flax_add_instance($flax, $mform=null) {
	global $DB;
	// Some field values of the flax table that were not set in mod_form.php
	$timenow = time();
	$flax->timecreated = $timenow;
	$flax->timemodified = $timenow;
	// if exercise is selected not to be graded - set the maxgrade value to 0 -> this will now ensure that the exercise is considered non-graded
	if ($flax->graded == 'no'){
		$flax->maxgrade = 0;
	}
// 	print_object($flax);return;
	// Ready to insert to db and get the id
	$dbid = $DB->insert_record('flax', $flax);
	if(!$dbid){
		flax_log('failed to insert into flax table', array('cid'=>$flax->course));
		exit;
	}
	
	$flax->id = $dbid;
	flax_grade_item_update($flax);	
	_flax_process_data($flax);
	// Now, with $flax->id available, we can insert table flax_questions
	// These value were set in getActivityParams()/Design[activity_name].js
	// Different activity types may utilize these value holders differently. 
	// For example, for ScrambleSentence, nodeids/nodecontents/nodeanswers must be array of the same size (nodeids are sentence ids);
	// whereas for 'ContentWordGuessing' and 'PredictingWords', nodeids contains only one id which is a FLAX document id
// 	flax_content::process_content($flax, $flax->course);
// 	flax_log('after process_content, add flax instance', array('cid'=>$flax->course));
	return $dbid;
}
function _flax_process_data($flax){
	$paramkeys = explode(TEXT_SEPARATOR, $flax->paramkeys);
	$paramvalues = explode(TEXT_SEPARATOR, $flax->paramvalues);
	$nodecontents = explode(TEXT_SEPARATOR, $flax->activitycontents);
	$nodeanswers = explode(TEXT_SEPARATOR, $flax->activityanswers);
	for($i = 0; $i<max(count($nodecontents), count($nodeanswers)); $i++) {	
		create_question_record($flax, 
						(count($nodecontents)>$i ? $nodecontents[$i] : $flax->activitycontents), 
						(count($nodeanswers)>$i ? $nodeanswers[$i] : $flax->activityanswers), 
						(count($paramkeys)>$i ? $paramkeys[$i] : $flax->paramkeys),
						(count($paramvalues)>$i ? $paramvalues[$i] : $flax->paramvalues));
	}
}
/**
 * Updating a flax instance:
 * 1. if only the fields defined in mod_form.php (exercise name, grading method, etc.) are involved,
 *    then we don't need to touch the records in tables flax_questions and/or flax_responses.
 * 2. if the exercise has been redesigned (via clicking 'Redesign exercise' button), 
 *    update is done by first deleting the existing one and then adding a new instance.
 * 
 * @param object $flax An object from the form in mod_form.php
 * @return boolean Success/Failure
 **/
function flax_update_instance($flax) {
// 	flax_delete_instance($flax->id); return true;
	// This function is called only AFTER either the button 'Save and return to course' or 'Save and display' is clicked
	global $DB;
	$flax->id = $flax->instance;
	$flax->timemodified = time();	
	// Update the instance record
	$DB->update_record('flax', $flax);
	
	// update grade item
	flax_grade_item_update($flax);
	_flax_process_data($flax);
	return true;	
}

/**
 * Given the id of an instance of the module,
 * this function will permanently delete the instance
 * and all its data that may be stored in other tables.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function flax_delete_instance($id) {
	global $DB;
    $flax = $DB->get_record('flax', array('id'=>$id), '*', MUST_EXIST);

    //flax_questions, flax_user_answers, flax_user_attempts, and flax_user_views
    if(!flax_delete_aux_tables($id)){
    	error_log("failed to delete aux tables in delete instance");
    	return false;
    }
    // Remove grade item of the instance
    flax_grade_item_delete($flax);
    // Delete records in the main module talbe 'flax'
    if(!$DB->delete_records('flax', array('id'=>$flax->id))){
    	error_log("failed to delete flax records in delete_instance");
    	return false;
    }

    return true;
}
/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports. Called by /course/user.php
 * in My profile->Activity reports->Outline report
 *
 * @param
 * @return object
 **/
function flax_user_outline($course, $user, $mod, $flax) {

	global $DB, $CFG;
//     require_once("$CFG->libdir/gradelib.php");
    $report = new stdClass();
    if($views = $DB->get_records_select(VIEW_TBL, 'flaxid=? AND userid=?', array($flax->id, $user->id), 'id', 'id,accesstime')){
    	$last_time_accessed = null;
    	foreach ($views as $a) {
    		if($a->accesstime>0){
    			if(is_null($last_time_accessed)) {
    				$last_time_accessed = $a->accesstime;
    			} else if($last_time_accessed < $a->accesstime) {
    				$last_time_accessed = $a->accesstime;
    			}
    		}
    	}
    	$report->info = count($views).' '.get_string('attempts','flax');
//     	$report->time = $last_time_accessed;//whether to show last access time in the outline report
    }else{
    	$report->info = get_string('noattempts','flax');
    }
    if(flax_is_graded($flax)){
		$grade_info = get_string('grade').': ';
		$grade_info .= flax_read_user_grade($course->id, $flax->id, $user->id);
		
//     	// See gradelib.php for details of what's been returned from grade_get_grades()
//     	$grades = grade_get_grades($course->id, 'mod', 'flax', $flax->id, $user->id);    	
// //     	print_object($grades);
//     	if (empty($grades->items[0]->grades)) {
//     		$grade_info .= 'empty grades';
//     	} else {
// //     		$grade_info .= reset($grades->items[0]->grades)->str_grade;
//     		$grade_info .= $grades->items[0]->grades[$user->id]->str_grade;
//     	}

    	$report->info .= '<br />'.$grade_info;
    }else{
    	$report->info .= '<br />'.get_string('notgradedexercise','flax');
    }
 
    return $report;
}
/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of the flax module, for user activity reports.
 * Called by /course/user.php
 * 
 * @return boolean
 **/
function flax_user_complete($course, $user, $mod, $flax) {
	$cm = get_coursemodule_from_instance('flax', $flax->id);
	report_mod_activity($cm->id, $course->id,$user->id);return true;
    
   return true;
}
/**
 * Function used for generating participation report
 * @return array
 */
function flax_get_view_actions() {
    return array('view');
}
/**
 * Function used for generating participation report
 * @return array
 */
function flax_get_post_actions() {
    return array('attempt','review');
}
/**
 * Update grades in central gradebook
 *
 * @param object $flax null means all flaxs
 * @param int $userid specific user only, 0 mean all
 * @return void
 */
function flax_update_grades($flax, $userid='') {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');
    
    //Note: CANNOT mix the two approaches - ordered parameters and named parameters - in one sql query
    // or 'Mixed types of sql query parameters' exception thrown! 
    $whereuser = $userid ? ' AND userid=?' : '';
    $params = array($flax->id, $userid);
    $sql = 'SELECT userid, SUM(score) AS usergrade
    			    FROM {'.SUBMISSION_TBL.'}
    			    WHERE flaxid=?'. $whereuser .
    			    'GROUP BY userid';
    $records = $DB->get_records_sql($sql, $params);
// flax_debug_log('num of records='.count($records));
    foreach ($records as $record) {
    	$grades = new stdclass();
    	$grades->userid = $record->userid;
    	//This is how we calculate user's grade for a particular exercise
    	$grades->rawgrade = grade_floatval($record->usergrade/$flax->gradeover * $flax->maxgrade);
    	flax_grade_item_update($flax, $grades);
    	 
    	// 	_flax_update_user_grade($flax, $userid, $record->usergrade/$flax->gradeover);
    }
}
/**
 * Creates or updates grade items for the give flax instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php. Also used by
 * {@link flax_update_grades()}.
 *
 * @param object $flax object with extra cmidnumber
 * @return object grade_item
 */
function flax_grade_item_update(stdClass $flax, $grades = null) {
	if(!flax_is_graded($flax)) {
		return false;
		//we only put grade items for graded mode activities
	}
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $params = array();
    $params['itemname'] = clean_param($flax->name, PARAM_NOTAGS);   	
    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademax']  = $flax->maxgrade;			// grade specified by user in the modedit screen
    $params['grademin']  = 0;

    $result = grade_update('mod/flax', $flax->course, 'mod', 'flax', $flax->id, 0, $grades, $params);
    return $result;
}
/**
 * Delete grade item for given flax
 *
 * @param object $flax object
 * @return object grade_item
 */
function flax_grade_item_delete($flax) {
	if(!flax_is_graded($flax)) {
		return false;
		//we only put grade items for graded mode activities
	}
	global $CFG;
	require_once($CFG->libdir.'/gradelib.php');
	return grade_update('mod/flax', $flax->course, 'mod', 'flax', $flax->id, 0, $flax, array('deleted'=>1));
}
/**
 * @todo
 * 
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param object $coursemodule
 * @return object info
 */
function flax_get_coursemodule_info($coursemodule) {}
/**
 * @deprecated - to be deleted in 2.2
 * 
 * Must return an array of user ids who are participants for a given instance of flax(ie, who are allowed to access this flax instance).
 *
 * @param string $flaxid id of a flax instance
 * @return array an array of user ids who are participants
 */
function flax_get_participants($flaxid) { return false; }
/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in flax activities and print it out.
 *
 * This function is called when displaying the main page of the course
 *
 * @param object $course course object
 * @param boolean $isteacher flag of user's role
 * @param int $timestart time stamp to start counting activities
 * @return boolean true if there was output, or false is there was none.
 * @uses $CFG
 */
function flax_print_recent_activity($course, $isteacher, $timestart) {

	global $CFG, $DB, $OUTPUT;
	$result = false;

	$records = $DB->get_records_sql("
			SELECT
			f.id AS id,
			f.name AS name,
			COUNT(*) AS num_of_instances
			FROM
			{flax} f
			WHERE
			f.course = ?
			AND f.timecreated > ?
			GROUP BY
			f.id, f.name
			", array($course->id, $timestart));
	// note that PostGreSQL requires f.name in the GROUP BY clause

	if($records) {
		$names = array();
		foreach ($records as $id => $record){
			if ($cm = get_coursemodule_from_instance('flax', $record->id, $course->id)) {
				$context = get_context_instance(CONTEXT_MODULE, $cm->id);

				if (has_capability('mod/flax:viewreport', $context)) {
					$href = "$CFG->wwwroot/mod/flax/view.php?hp=$id";
					$name = '&nbsp;<a href="'.$href.'">'.$record->name.'</a>';
					if ($record->num_of_instances > 1) {
						$name .= " ($record->num_of_instances)";
					}
					$names[] = $name;
				}
			}
		}
		if (count($names) > 0) {
			$OUTPUT->heading(get_string('modulenameplural', 'flax').':');

			if ($CFG->version >= 2005050500) { // Moodle 1.5+
				echo '<div class="head"><div class="name">'.implode('<br />', $names).'</div></div>';
			} else { // Moodle 1.4.x (or less)
				echo '<font size="1">'.implode('<br />', $names).'</font>';
			}
			$result = true;
		}
	}
	return $result;  //  True if anything was printed, otherwise false
}
/**
 * Returns all flax instances in a course since a given time.
 * 
 * Called by /course/recent.php on link "Full report of recent activities" on course page
 */
function flax_get_recent_mod_activity(&$activities, &$index, $sincetime, $courseid, $cmid, $userid=0, $groupid=0) {
    global $CFG, $COURSE, $USER, $DB;

    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = $DB->get_record('course', array('id'=>$courseid));
    }

    $modinfo = get_fast_modinfo($course);

    $cm = $modinfo->get_cm($cmid);
	
    $cm_context      = get_context_instance(CONTEXT_MODULE, $cm->id);
    if(! has_capability('mod/flax:viewreport', $cm_context)){
    	return;
    }

    $records = read_exercise_attempts_from_db($cm->instance, $userid, $sincetime);
    $viewallreport   = has_capability('mod/flax:viewallreport', $cm_context);
    $viewfullnames   = has_capability('moodle/site:viewfullnames', $cm_context);
    
// print_object($USER->id);
// print_object($records);
	if (empty($records)) {
		return;
	}
	foreach ($records as $record) {
		// The properties contained in $record is determined by what was read from db in function read_exercise_attempts_from_db()
// 		var_dump($record->userid);
// 		var_dump($USER->id);
		// own submissions always visible; or user has the capability to view all 
		if ($record->userid === $USER->id || $viewallreport ) {
		
			$activity = new stdClass();
			$activity->type = "flax";
			$activity->flaxtype = $record->flaxtype;
			$activity->cmid = $cm->id;
			$activity->flaxid = $record->flaxid;
			$activity->name = $record->name;
			$activity->sectionnum = $cm->sectionnum;
			$activity->timestamp = $record->accesstime;
			$activity->maxgrade = $record->maxgrade;
			$activity->gradeover = $record->gradeover;
			$activity->intro = $record->intro;
			$activity->viewid = $record->id;
			$activity->score = $record->score;
			$activity->submissionids = $record->submissionids;
			$activity->user = new stdClass();
			$activity->user->id = $record->userid;
			$activity->user->picture = $record->picture;
			$activity->user->firstname = $record->firstname;
			$activity->user->lastname = $record->lastname;
			$activity->user->fullname  = fullname($record, $viewfullnames);
			$activity->user->imagealt = $record->imagealt;
			$activity->user->email = $record->email;
				
			$activities[$index++] = $activity;

			unset($activity);

		}
	} // end foreach
// 	print_object($activities);
}
/**
 * Prints the results of flax_get_recent_mod_activity()
 * 
 * @param stdClass $activity element of the array prepared by flax_get_recent_mod_activity()
 * @param string $courseid
 * @param boolean $detail
 * @param array $modnames
 */
function flax_print_recent_mod_activity($activity, $courseid, $detail=false, $modnames=null) {

	global $CFG, $OUTPUT;
	
// 	print_object($activity);
// 	print_object($modnames);
//     if (!empty($activity->user)) {
//         echo html_writer::tag('div', $OUTPUT->user_picture($activity->user, array('courseid'=>$courseid)),
//                 array('style' => 'padding: 7px;'));
//     }
		
    if ($detail) {
    	echo html_writer::start_tag('h4', array('class'=>'flax_mod_activity'));
    	$url = new moodle_url('/mod/flax/view.php', array('id'=>$activity->cmid));
    	$name = format_string($activity->name);
    	echo html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('icon', $activity->type), 'class'=>'icon', 'alt'=>$name));
    	echo ' ' . $modnames[$activity->type];
    	echo html_writer::link($url, $name, array('class'=>'name', 'style'=>'margin-left: 5px'));
    	echo html_writer::end_tag('h4');
    }

    $table = new html_table();
    $table->attributes = array('class'=>'mod-flax-report-attempt-entry-class');
    $table->align = array ('center', 'center');
    $row = new html_table_row();
    
    if (!empty($activity->user)) {
    	$avatar = new html_table_cell();
    	$avatar->text = $OUTPUT->user_picture($activity->user, array('courseid'=>$courseid));
    	$avatar->attributes = array('class'=>'userpicture');
    	$row->cells[] = $avatar;
    	$username = new html_table_cell();
    	$url = new moodle_url($CFG->wwwroot . '/user/view.php', array('id'=>$activity->user->id, 'course'=>$courseid));
    	$username->text = html_writer::link($url, $activity->user->fullname, array('class'=>'name', 'style'=>'margin-left: 5px'));
    	$row->cells[] = $username;
    }

    $cell4 = new html_table_cell();
    $cell4->text = userdate($activity->timestamp);
	$score = new stdClass();
    $score->attributes = array('class'=>'');
    $row->cells[] = $cell4;
     
    $report = new html_table_cell();
    if(flax_is_type_exercise($activity) == false){
    	$reporttext = get_string('flaxlanguageresource', 'flax');
    }else {
    	if(flax_is_graded($activity)){
	    	if($activity->submissionids){
	    		$p = new stdClass();
	    		$p->fullname = $activity->user->fullname;
	    		$p->accesstime = userdate($activity->timestamp);
	    		$text = get_string('viewreport','flax');
	
	    		$p->cmid = $activity->cmid;
	    		$p->flaxid = $activity->flaxid;
	    		$p->viewid = $activity->viewid;
	    		$p->text = $text;
	    		$p->userid = $activity->user->id;
	    		$reporttext = get_attempt_report_link($p);
	    		
			    $score = new html_table_cell();
			    $score->text = get_string('score','flax').': '.$activity->score;
			    $score->attributes = array('class'=>'grade');
			    $row->cells[] = $score;
			    	
	    	}else{
	    		$reporttext = get_string('nosubmissions', 'flax');
	    	}
	    }else{
	    	$reporttext = get_string('notgradedexercise', 'flax');
	    }
    }
    
    $report->text = $reporttext;
    $row->cells[] = $report;
     
    $table->data[] = $row;

    echo html_writer::table($table);

}
/**
 * Moodle 2.0 specifically instroduced function
 *
 *
 * @uses FEATURE_MOD_ARCHETYPE if not set or set to MOD_ARCHETYPE_OTHER indicates the module should be grouped in the 'Add an activity' dropdown menu; 
 *                             while setting to MOD_ARCHETYPE_RESOURCE makes it appear in the 'Add a resource' menu,
 *                             i.e., all archetypes other than MOD_ARCHETYPE_RESOURCE are considered activity types (see course/lib.php/print_section_add_menus()).
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function flax_supports($feature) {
	switch($feature) {
// 		case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_OTHER;
		case FEATURE_GROUPS:                  return false;
		case FEATURE_GROUPINGS:               return false;
		case FEATURE_GROUPMEMBERSONLY:        return false;

		//suppress the notice of 'Undefined property: stdClass::$intro in /course/modedit.php on line 146
		case FEATURE_MOD_INTRO:               return true;

		case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
		case FEATURE_GRADE_HAS_GRADE:         return true;
		case FEATURE_GRADE_OUTCOMES:          return true;
		case FEATURE_BACKUP_MOODLE2:          return true;

		default: return null;
	}
}
/**
 * Extends the global navigation tree by adding flax nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 * 
 * Strange behaviour by Moodle: only when one of the module instances is once viewed to trigger the calling of this function.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the flax module instance in the navigation block
 * @param object $course The course object returned from the DB
 * @param object $flax The module object returned from the DB (the flax instance object)
 * @param object $cm The course module instance returned from the DB
 */
function flax_extend_navigation(navigation_node $navref, stdclass $course, stdclass $flax, cm_info $cm) {
	global $CFG;
	$allow_view_report = has_capability('mod/flax:viewreport', get_context_instance(CONTEXT_MODULE, $cm->id));
// 	$graded = flax_is_graded($flax->graded);

	if ($allow_view_report) {
		$url = new moodle_url('/mod/flax/report_module.php', array('cmid'=>$cm->id));
		$node = $navref->add(get_string('report', 'flax'), $url, navigation_node::TYPE_SETTING,
                null, null, new pix_icon('i/info', ''));
		$node->mainnavonly = false;//set to false will make the node appear (as a link) in the horizental navigation bar on top of the page
		$node->__wakeup();
		$node->force_open();
	}
}

/**
 * Extends the settings navigation with the FLAX settings, 
 * that is, inside the block "Settings" (above the "Course administration" block) a tree node called "FLAX administration" 
 *
 * This function is called when the context for the page is a flax module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $flaxnode - the node by the name "FLAX administration" in the "Settings" block {@link navigation_node}
 */
function flax_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $flaxnode=null) {
	global $PAGE;

	//$flaxobject = $DB->get_record("flax", array("id" => $PAGE->cm->instance));

// 	if (has_capability('mod/flax:editdimensions', $PAGE->cm->context)) {
// 		$url = new moodle_url('/mod/flax/view.php', array('cmid' => $PAGE->cm->id));
// 		$flaxnode->add('firstnode', $url, settings_navigation::TYPE_SETTING);
// // 	}
// // 	if (has_capability('mod/flax:allocate', $PAGE->cm->context)) {
// 		$url = new moodle_url('/mod/flax/view.php', array('cmid' => $PAGE->cm->id));
// 		$flaxnode->add('allocate', $url, settings_navigation::TYPE_SETTING);
// 	}
}
/**
 * Returns all other caps used in module - in addition to those defined in flax/db/access.php
 */
function flax_get_extra_capabilities() {
	return array('moodle/site:viewfullnames');
}
/**
 * Checks if a scale is being used by an flax
 *
 * This is used by the backup code to decide whether to back up a scale
 * @param $flaxid int
 * @param $scaleid int
 * @return boolean True if the scale is used by the flax
 */
function flax_scale_used($flaxid, $scaleid) {
	return false;
}

/**
 * Checks if scale is being used by any instance of flax
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any flax
 */
function flax_scale_used_anywhere($scaleid) {
	return false;
}
/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return bool true
 */
function flax_cron() {
	return true;
}

/**********************************************
 * 
 ********  API functions end here *************
 *
 **********************************************/


/*********************************************************************
 * 
 ***  Below this line are aux functions (names start with '_flax') ***
 *
 *********************************************************************/
/**
 * Function is used by flax_update_grades and flax_base.class.php/update_gradebook
 * @param stdClass $flax
 * @param string $userid
 * @param int $usergradescore
 */
function _flax_update_user_grade($flax, $userid, $usergradescore) {
	$grades = new stdclass();
	$grades->userid = $userid;
	$grades->rawgrade = $usergradescore;
// 	flax_debug_log($userid.'  '.$usergradescore);
	flax_grade_item_update($flax, $grades);
}
/**
 * Function called in flax/index.php(user=='all'), and overview/report.php (when displaying a report of all participants)
 * 
 * @param int $flaxid id of a flax instance
 * @param int $userid id of the user 
 * @param int $userscore the user's score of the flax instance
 * @return string if the instance has been attempted by the user, returns a hyperlinked text to the activity report, 
 *         or plain text showing 'no attempt'
 */
function _flax_get_overview_report_link_html($flaxid, $userid, $userscore) {
	global $CFG, $DB;

	// Display link (link to $CFG->wwwroot/mod/flax/review.php?fid=xx&uid=xx) to report for single user (student?)
	// The first field should be a unique one such as 'id' since it will be used as a key in the associative array
	$attempts = $DB->get_records_select(VIEW_TBL, 'flaxid=? AND userid=?', array($flaxid, $userid), '', 'id,attempts,closed');
	$attempted = 0; $done_question = 0;
	foreach($attempts as $resp) {
		$attempted += intval($resp->attempts);	
		if($resp->closed == 1) {
			$done_question += 1;
		}
	}           
	
	if($attempted > 0) {
		$param = $flaxid.'&uid='.$userid.'&attempted='.$attempted;
		$param .= '&score='.$userscore;
		
		$param .= '&allquestion='.count($attempts).'&donequestion='.$done_question;
		$display_text = get_string('viewreport','flax').' ('.$attempted.' attempts)';
		$report = '<a href="'.$CFG->wwwroot.'/mod/flax/review.php?fid='.$param.'">'.$display_text.'</a>';
	} else { // Not attempted. No link to the actual report page
		$report = get_string('noattempts', 'flax');
	}	
	return $report;		
}
/**
 * Find out how many users have participated in a particular flax instance
 * For a given instance of flax(ie, whose names are shown in table flax_attempts). Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 * 
 * @param string $flaxid id of a flax instance
 * @return array an array of user ids who are participants
 */
// function _flax_get_users($flaxid) {
//     global $CFG, $DB;
//     return $DB->get_records_sql("
//         SELECT DISTINCT
//             u.id, u.id
//         FROM
//             {user} u,
//             {".VIEW_TBL."} a
//         WHERE
//             u.id = a.userid
//             AND a.flaxid = ?
//     ", array($flaxid));
// }
/**@TODO - See moodle/lib/adminlib.php/upgrade_activity_modules()
 * 
 */
function upgrading() {}
?>