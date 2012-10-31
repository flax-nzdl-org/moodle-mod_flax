<?php // $Id: index.php
/**
 * @author alex.xf.yu@gmail.com

 * This page lists all the instances of flax module in a particular course along with their scores.
 * It is invoked by clicking on a flax activity link in the block on the left-hand side of course page.
 *
 * This script utilizes review.php
 **/
    require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
//     require_once('../../course/lib.php');
	require_once ($CFG->dirroot.'/lib/weblib.php');
    require_once ($CFG->dirroot.'/mod/flax/locallib.php');
    require_once ($CFG->dirroot.'/mod/flax/lib.php');

    $id = required_param('id', PARAM_INT);   // course
    if (! $course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST)) {
        print_error('Course ID is incorrect');
    }
    
    require_login($course->id, false);

	add_to_log($course->id, 'flax', 'index.php', "index.php?id=$course->id", '');
	
	$PAGE->set_pagelayout('incourse');
	$PAGE->set_url('/mod/flax/index.php', array('id' => $course->id));
	$PAGE->set_title($course->fullname);
	$PAGE->set_heading($course->shortname);
	$PAGE->navbar->add(get_string('modulenameplural', 'flax'));
	$PAGE->requires->css('/mod/flax/styles.css');

	/// Output starts here	
	echo $OUTPUT->header();
    
	$coursecontext = get_context_instance(CONTEXT_COURSE, $id);
    require_capability('mod/flax:viewreport', $coursecontext);
	$is_teacher = has_capability('mod/flax:viewallreport', $coursecontext);
	
	if(! $course->showreports && ! $is_teacher) {// check course settings and user capability
		echo $OUTPUT->box(get_string('reporthidden', 'flax'), 'generalbox boxaligncenter', 'intro');
		echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id' => $course->id)));
		echo $OUTPUT->footer();
		die();
	}
	
	if(! $flax_instances = get_all_instances_in_course('flax', $course)) {
		echo $OUTPUT->heading(get_string('noflaxes', 'flax'), 2);
		echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id' => $course->id)));
		echo $OUTPUT->footer();
		die();
	}
	
// 	echo $OUTPUT->heading(get_string('modulenameplural', 'flax'), 2);
	foreach ($flax_instances as $instance) {
		$cm = get_coursemodule_from_instance('flax', $instance->id);
		$modulecontext = get_context_instance(CONTEXT_MODULE, $cm->id);
		$permission_view = has_capability('mod/flax:viewreport', $modulecontext);
		$permission_view_all = has_capability('mod/flax:viewallreport', $modulecontext);
		if(!$permission_view) continue;//guest role?
		if(!$permission_view_all){
			//This is a student
			$userid = $USER->id;
		}else{
			$userid = '';
		}

		$modfullname = get_string('modulenameplural','flax');
		$image = "<img src=\"" . $OUTPUT->pix_url('icon', 'flax') . "\" class=\"icon\" alt=\"$modfullname\" />";
		if (empty($instance->visible)) {
			$link = html_writer::link(new moodle_url('/mod/flax/view.php', array('id' => $instance->coursemodule)),
					$instance->name, array('class' => 'dimmed'));
		} else {
			$link = html_writer::link(new moodle_url('/mod/flax/view.php', array('id' => $instance->coursemodule)),
					$instance->name);
		}
		echo "<h4>$image $link</h4>";
		report_mod_activity($cm->id, $course->id, $userid);
	}

    // Finish the page
    echo $OUTPUT->footer();
?>
