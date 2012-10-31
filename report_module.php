<?php // $Id: index.php
/**
 * @author alex.xf.yu@gmail.com

 * This page lists all attempts on a flax exercise
 * Invoked by the link 'Report' under each flax instance in Navigation->Courses->[course name]->[section name]->[exercise instance name]->Report
 *
 **/

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

//parameters passed from flax_extend_navigation
$cmid = required_param('cmid', PARAM_INT);            // course module id

$cm     = get_coursemodule_from_id('flax', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_login($course, false, $cm);
if (isguestuser()) {
    print_error('guestsarenotallowed');
}

	add_to_log($course->id, 'flax', 'report_module.php', "report_module.php?cmid=$cmid", '');
	
	$PAGE->set_pagelayout('incourse');
	$PAGE->set_url('/mod/flax/report_module.php', array('cmid' => $cmid));
	$PAGE->set_title($course->fullname);
	$PAGE->set_heading($course->shortname);
// 	$PAGE->navbar->add(get_string('report', 'flax'));
	$PAGE->requires->css('/mod/flax/styles.css');
	/// Output starts here
	
	echo $OUTPUT->header();
    
	//    $moduelcontext = get_context_instance(CONTEXT_MODULE, $id);
	$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
    require_capability('mod/flax:viewreport', $coursecontext);
	$is_teacher = has_capability('mod/flax:viewallreport', $coursecontext);
	
	if(! $course->showreports && ! $is_teacher) {// check course settings and user capability
		echo $OUTPUT->box(get_string('reporthidden', 'flax'), 'generalbox boxaligncenter', 'intro');
		echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id' => $course->id)));
		echo $OUTPUT->footer();
		die();
	}
	report_mod_activity($cm->id, $course->id);

    // Finish the page
    echo $OUTPUT->footer();
?>
