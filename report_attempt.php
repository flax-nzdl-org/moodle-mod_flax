<?PHP  // $Id: report.php
/**
 * @author alex.xf.yu@gmail.com
 * 
 * This script prints report of single attempt on an exercise.
 * 
 * Report links are generated at lib/flax_print_recent_mod_activity() when flaxtype=='exercise'
 * 
 **/

    require_once('../../config.php');
	require_once ($CFG->dirroot.'/mod/flax/locallib.php');

	global $DB;
	$cmid   = required_param('cmid', PARAM_INT);            // course module id
	$cm     = get_coursemodule_from_id('flax', $cmid, 0, false, MUST_EXIST);
	$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
	$flaxid   = required_param('flaxid', PARAM_INT);            
	$flax = $DB->get_record('flax', array('id' => $flaxid), '*', MUST_EXIST);
	
    require_login($course->id, false);
    add_to_log($course->id, 'flax', 'index.php', "index.php?id=$course->id", '');
    
    $PAGE->set_pagelayout('report');
    $PAGE->set_url('/mod/flax/report_attempt.php', array('id' => $course->id));
    $PAGE->set_title($course->fullname);
    $PAGE->set_heading($course->shortname);
    $PAGE->navbar->add(get_string('modulenameplural', 'flax'), new moodle_url('/mod/flax/index.php', array('id'=>$course->id)));
    $PAGE->navbar->add(format_string($flax->name), new moodle_url('/mod/flax/view.php', array('id'=>$cm->id)));
    $PAGE->navbar->add(get_string('attemptreport', 'flax'));
    $PAGE->requires->css('/mod/flax/styles.css');
    /// Output starts here
    
    echo $OUTPUT->header();

	$viewid = required_param('viewid', PARAM_INT); // id of table flax_user_views
	$username = required_param('fullname', PARAM_TEXT); // 
	$userid = required_param('userid', PARAM_INT); 
	$modulecontext = get_context_instance(CONTEXT_MODULE, $cm->id);
	$permission_view = has_capability('mod/flax:viewreport', $modulecontext);
	$permission_view_all = has_capability('mod/flax:viewallreport', $modulecontext);
	
	if(!$permission_view){//guest ?
		echo $OUTPUT->notification(get_string('permissionviewreport', 'flax'));
		echo $OUTPUT->footer();
		die();
	}
	if(!$permission_view_all //the logged-in user is not a teacher
			&& $userid != $USER->id){//check if the attempt report really belongs to the logged-in user 
		echo $OUTPUT->notification(get_string('permissionviewreport', 'flax'));
		echo $OUTPUT->footer();
		die();		
	}

    echo $OUTPUT->heading(get_string('attemptreportforuser', 'flax', $username), 2);
    
    $params = array('viewid'=>$viewid, 'userid'=>$userid);
	$sql = "SELECT
	s.id, s.useranswer, s.score, s.userid, s.accesstime, s.paramkeys, s.paramvalues,
	q.content, q.answer
	FROM
	{".SUBMISSION_TBL."} s
	JOIN {".QUESTION_TBL."} q ON q.id = s.questionid
	WHERE
	s.viewid = :viewid AND s.userid = :userid
	ORDER BY
	s.accesstime ASC";
// 	debugging('sql='.$sql);
	$submissions = $DB->get_records_sql($sql, $params);
	
	$flax_activity_type = $flax->activitytype;
	
	$act_class_name = flax_get_activity_class_name($flax->activitytype);
	require_once('classes/'.flax_get_activity_class_filename($flax->activitytype));
	$activity_instance = new $act_class_name($flax, $cm, $course);
	$o = new stdClass();
	$o->submissions = $submissions;
	$o->permission_view = $permission_view;
	$o->permission_view_all = $permission_view_all;
	$activity_instance->print_report($flax, $o);
	
    // Finish the page
    echo $OUTPUT->footer();    
?>
