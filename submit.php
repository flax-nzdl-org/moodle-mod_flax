<?php  // $Id: submit.php,
/**
 * @author alex.xf.yu@gmail.com
 * 
 * Gateway script that receives and processes information (e.g., user's score on an exercise) about exercises sent back from FLAX server and stores it in db 
 */

$path2thisfile = dirname(__FILE__);
require_once(dirname(dirname($path2thisfile)).'/config.php');
require_once ($CFG->dirroot.'/lib/weblib.php');
require_once($path2thisfile.'/locallib.php');

function flax_exit($courseid, $msg){
	$msg = 'submit.php: '.$msg;
	add_to_log($courseid, 'flax', 'submit', 'submit.php?id='.$courseid, $msg);
	flax_debug_log($msg);
	echo $msg; exit;
}

    $verificationkey = required_param(MDLSITEID, PARAM_ALPHANUM);
    $viewid  = required_param(VIEWID, PARAM_INT);
    $recordid  = required_param(RECORDID, PARAM_INT);
	
    global $DB;
	$id_checks = true;
	
    if (! $view = $DB->get_record(VIEW_TBL, array("id"=>$viewid), '*', MUST_EXIST)) {
        $id_checks = false;
    } 
    if (! $flax = $DB->get_record(FLAX_TBL, array("id"=>$view->flaxid), '*', MUST_EXIST)) {
        $id_checks = false;//print_error("Flax ID is incorrect (attempt id = $attempt->id)");
    }
    if (! $course = $DB->get_record('course', array('id'=>$flax->course), '*', MUST_EXIST)) {
        $id_checks = false;//print_error("Course ID is incorrect (flax id = $flax->id)");
    }
    if (! $cm = get_coursemodule_from_instance("flax", $flax->id, $course->id)) {
        $id_checks = false;//print_error("Course Module ID is incorrect");
    }
	if(!$id_checks) {
		flax_exit($course->id, 'id checks failed');
	}
	
	//Check if the user is allowed to submit answers (defined in access.php)
	if(! has_capability('mod/flax:submit', get_context_instance(CONTEXT_MODULE, $cm->id), $view->userid)){
	    flax_exit($course->id, 'User does not have capability of submitting answers');
	}
	$mdl_site_id = flax_get_mdl_site_id();
    if (strcmp($verificationkey, $mdl_site_id) != 0) {
    	flax_exit($course->id, 'Moodle site id mismatch');
    }

    if (! $record = $DB->get_record(FINISH_TBL, array("id"=>$recordid), '*', MUST_EXIST)) {
        flax_exit($course->id, 'Invalid record id: '.$recordid);
    }
    
    //Is the incoming call about updating the exercise url of a group mode exercise?
    $exercise_url_update = optional_param(EXERCISEURLUPDATE, '', PARAM_RAW);
    if($exercise_url_update){
    	$search = array(NV_SEPARATOR, ARG_SEPARATOR);
    	$replace = array("=", "&");
    	$flax->exerciseurl = str_replace($search, $replace, $exercise_url_update);
    	if(!$DB->update_record('flax', $flax)){
	    	flax_exit($course->id, 'Updating flax exerciseurl failed for: '.$flax->name);
    	}else{
    		flax_exit($course->id, 'update exercise url OK');
    	}
    }
	
	$act_class_name = flax_get_activity_class_name($flax->activitytype);
	require_once('classes/'.flax_get_activity_class_filename($flax->activitytype));
    $activity_instance = new $act_class_name($flax, $cm, $course);
    if (!in_array('flax_interface', class_implements($activity_instance))) {
    	flax_exit($course->id, $act_class_name . ' needs to implement the interface class flax_interface');
    }

    $score  = optional_param(USERSCORE, 0, PARAM_INT);
    $responsecontent = optional_param(RESPONSECONTENT, '', PARAM_RAW);
	
    if(!$activity_instance->process_submission($flax, $record, $view, $score, $responsecontent)){
    	flax_exit($course->id, 'activity instance process_submission returned false');
    }
	echo "OK";
?>