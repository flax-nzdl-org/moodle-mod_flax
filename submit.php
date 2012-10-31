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
		add_to_log($course->id, 'flax', 'submit', 'submit.php?id=$course->id', 'submit.php: id checks failed');
        die('id checks failed');
	}
	
	//Check if the user is allowed to submit answers (defined in access.php)
	if(! has_capability('mod/flax:submit', get_context_instance(CONTEXT_MODULE, $cm->id), $view->userid)){
		die('User does not have capability to submit answer');
	}
	$mdl_site_id = flax_get_mdl_site_id();
    if (strcmp($verificationkey, $mdl_site_id) != 0) {
    	  add_to_log($course->id, 'flax', 'submit', 'submit.php?id=$course->id', 'submit.php: Moodle site id mismatch: '.$mdl_site_id.'<>'.$verificationkey);
        die('Moodle site id mismatch');
    }

    if (! $record = $DB->get_record(FINISH_TBL, array("id"=>$recordid), '*', MUST_EXIST)) {
        add_to_log($course->id, 'flax', 'submit', 'submit.php?id=$course->id', 'submit.php: Invalid table id:'. $recordid);
        die('Invalid table id: '.$recordid);
    }
    
    $time = time();
	// before doing anything, check the open/close time of the activity
	if ($flax->timeopen && $flax->timeopen > $time) {
		die('Exercise not open yet');
    }
    if ($flax->timeclose && $flax->timeclose < $time) {
    	die('Exercise closed');
    }
    
    
    //Is the incoming call about updating the exercise url of a group mode exercise?
//     $update_exerciseurl  = $_POST[EXERCISEURLUPDATE];
    $exercise_url_update = optional_param(EXERCISEURLUPDATE, '', PARAM_RAW);
    if($exercise_url_update){
    	$search = array(NV_SEPARATOR, ARG_SEPARATOR);
    	$replace = array("=", "&");
    	$flax->exerciseurl = str_replace($search, $replace, $exercise_url_update);
    	if(!$DB->update_record('flax', $flax)){
    		$msg = 'Updating flax exerciseurl failed for: '.$flax->name;
    		error_log($msg);
    		die($msg);
    	}
    	echo 'submit.php: update exerciseurl ok';
    	exit;
    }
    
    $act_class_name = 'flax_'.$flax->activitytype;
    require_once('classes/'.$act_class_name.'.class.php');
    $activity_instance = new $act_class_name($flax, $cm, $course);
    if (!in_array('flax_activity', class_implements($activity_instance))) {
    	$msg = $act_class_name . ' does not implement flax_activity interface';
    	throw new coding_exception($msg);
    	die($msg);
    }

    $score  = optional_param(USERSCORE, 0, PARAM_INT);
    $responsecontent = optional_param(RESPONSECONTENT, '', PARAM_RAW);
    
    add_to_log($course->id, 'flax', 'attempt', 'submit.php?id=$course->id', 'activity attempted='.$act_class_name);
    if(!$activity_instance->process_submission($flax, $record, $view, $score, $responsecontent)){
    	die('Error in activity instance process_submission');
    }else{
    	echo 'submit.php: ok';
    }
?>