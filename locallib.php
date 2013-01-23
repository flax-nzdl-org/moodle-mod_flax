<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local library file for flax.  These are non-standard functions that are used
 * only by flax.
 *
 * @package    mod
 * @subpackage flax
 * @author alex.xf.yu@gmail.com
 **/

/** Make sure this isn't being directly accessed */
defined('MOODLE_INTERNAL') || die();

/** Include the files that are required by this module */
// require_once($CFG->dirroot . '/mod/flax/lib.php');

// About external flax server
define('REGISTERED_FLAX_SERVER', 'registered_flax_server');
define('FLAX_SERVER_NAME', 'flax_server_name');
define('FLAX_SERVER_PORT', 'flax_server_port');
define('DEFAULT_FLAX_SERVER', 'flax.nzdl.org');
define('DEFAULT_FLAX_PORT', 80);

// Mode of flax exercises
define('YES', 'yes');
define('NO', 'no');
define('GRADED', 'graded');
define('MAX_GRADE', 'maxgrade');

/** Table names of flax module */
define('FLAX_TBL', 'flax');
define('QUESTION_TBL', 'flax_questions');
define('FINISH_TBL', 'flax_user_finish');
define('SUBMISSION_TBL', 'flax_user_submissions');
define('VIEW_TBL', 'flax_user_views');

/** A place holder in $SESSION that holds the question data of a flax exercise to be added/edited. */
define('FLAXCONTENT', 'flaxsessiondata');

define('CONTENT', 'content');
define('ANSWER', 'answer');
define('PARAMKEYS', 'paramvalues');
define('PARAMVALUES', 'paramvalues');
define('EXERCISEURL', 'exerciseurl');
define('ACTIVITYTYPE', 'activitytype');

//The followings must match what's been used in Activity.js and Activity.java
define('MDLSITEID', 'mdlsiteid');
define('MDLSITEURL', 'mdlsiteurl');
define('VIEWID', 'viewid');
define('RECORDID', 'recordid');
define('USERNAME', 'username');
define('USERSCORE', 'userscore');
define('NUMCLOSED', 'numclosed');
define('RESPONSECONTENT', 'responsecontent');
define('COURSEID', 'courseid');
define('COURSEPAGEURL', 'coursepageurl');
define('COMPLETE', 'complete');//USED BY WordGuessing/ImageGuessing
define('EXERCISEURLUPDATE', 'groupexerciseurlupdate');

// define('FLAX_NO',  '0');
// define('FLAX_YES', '1');

// //These must match the ones defined in LLDL.js
define('ID_SEPARATOR', ',');//used in lib.php and view.php
define('TEXT_SEPARATOR', ':;:;:');//used in lib.php and view.php
define('NV_SEPARATOR', ';;');//used in ws_gateway.php
define('ARG_SEPARATOR', ']]');//used in ws_gateway.php

//The followings are used in view.php and attempt.php
define('PARAM_NV_SEPARATOR', ';');
define('PARAM_ARG_SEPARATOR', ']');
define('MDLPARAMS', 'mdlparams');

define('FLAXURL', 'flaxurl');
define('PARAMS', 'params');
define('MODULEPARAMS', 'moduleParams');

define('DESIGN_INTERFACE', '14');
define('LIST_COLL', '1009');
define('MODULE_SITE_REGISTER', '1010');

define('FLAXTYPE_WRAP', 'flaxtype_wrap');
define('COLL_LIST_WRAP', 'coll_list_wrap');
define('ACTIVITY_LIST_WRAP', 'activity_list_wrap');
define('CONTENTSUMMARY_WRAP', 'contentsummary_wrap');
define('DOCUMENT_LIST_WRAP', 'doc_list_wrap');
define('COLL_LIST_ID', 'flax_coll_list_container_id');
define('ACTIVITY_LIST_ID', 'flax_activity_list_container_id');
define('CONTENTSUMMARY_ID', 'contentsummary_id');
define('DOCUMENT_LIST_ID', 'flax_document_list_container_id');
define('PLACE_HOLDER_ID', 'flax_place_holder_id');

//////////////////////////////////////////////////////////////////////////////////////
/// Any other flax functions go here.  Each of them must have a name that starts with flax_
function flax_get_ungraded_callback_info(){
	global $USER, $CFG;
	$callbackinfo =
	PARAM_ARG_SEPARATOR.MDLSITEURL     .PARAM_NV_SEPARATOR.$CFG->wwwroot.
	PARAM_ARG_SEPARATOR.USERNAME      .PARAM_NV_SEPARATOR.$USER->username;//critical to group/pair mode activity
	return $callbackinfo;
}
function flax_get_graded_callback_info($viewid, $mdl_site_id, $user_exercise_score=0, $recordids, array $rawinfo=NULL){
	global $USER, $CFG;
	$callbackinfo =
	                    VIEWID        .PARAM_NV_SEPARATOR.$viewid.
	PARAM_ARG_SEPARATOR.MDLSITEID     .PARAM_NV_SEPARATOR.$mdl_site_id.
	PARAM_ARG_SEPARATOR.MDLSITEURL     .PARAM_NV_SEPARATOR.$CFG->wwwroot.
	PARAM_ARG_SEPARATOR.GRADED        .PARAM_NV_SEPARATOR.YES.
	PARAM_ARG_SEPARATOR.USERNAME      .PARAM_NV_SEPARATOR.$USER->username.//critical to group/pair mode activity
	PARAM_ARG_SEPARATOR.USERSCORE     .PARAM_NV_SEPARATOR.$user_exercise_score.
	PARAM_ARG_SEPARATOR.RECORDID      .PARAM_NV_SEPARATOR.$recordids;
	if($rawinfo){
		foreach($rawinfo as $key=>$value){
			$callbackinfo .= PARAM_ARG_SEPARATOR. $key .PARAM_NV_SEPARATOR. $value;
		}
	}
	return $callbackinfo;
}
/**
 * Is the flax an exercise or a resource?
 * @param int $flax (see flax table in install.xml)
 * @return bool
 */
function flax_is_type_exercise($flax){
	return $flax->flaxtype == 'exercise';
}
/**
 * Is the flax exercise being graded?
 * @param int $flax (see flax table in install.xml)
 * @return bool
 */
function flax_is_graded($flax){
	return $flax->maxgrade > 0;
}
/**
 * Test if a particular question of the flax exercise closed?
 * @param int $closed Must be either no (open) or yes (closed) (see flax_user_finish table in install.xml)
 * @return bool
 */
function flax_is_question_finished($closed){
	return $closed == YES;
}
/**
 * Get user score on a particular exercise from submission table
 * this function is similar to lib/flax_update_grades
 *
 * @param object $flax null means all flaxs
 * @param int $userid 
 * @return void
 */
function flax_read_user_score($flax, $userid) {
	global $CFG, $DB;

	//Note: CANNOT mix the two approaches - ordered parameters and named parameters - in one sql query
	// or 'Mixed types of sql query parameters' exception thrown!
	$params = array($flax->id, $userid);
	$sql = 'SELECT SUM(score) AS userscore
	FROM {'.SUBMISSION_TBL.'}
	WHERE flaxid=? AND userid=?';
	$records = $DB->get_records_sql($sql, $params);
	return reset($records)->userscore;
}
function flax_read_user_grade($courseid, $flaxid, $userid){
	global $CFG;
    require_once("$CFG->libdir/gradelib.php");
	// See gradelib.php for details of what's been returned from grade_get_grades()
	$grades = reset(reset(grade_get_grades($courseid, 'mod', 'flax', $flaxid, $userid)));
	if(! $grades || ! $grades->grades){
		return '0';
	}
	$user_grade = reset($grades->grades);
	//     	print_object($grades);
	if (!($user_grade->grade)) {
		return '0';//'empty grades';// I think we should display '0' instead of 'empty grades'
	} else {
		if($user_grade->str_grade == '-'){//this shouldn't happen - check it anyway
			return '0';
		}
		//     		$grade_info .= reset($grades->items[0]->grades)->str_grade;
		return $user_grade->str_grade;
	}
	
}
/**
 * Returns a stdClass containing: 
 * Array of integers - an array of numeric values that can be used as maximum grades
 * Integer - the default value of maximum grade
 *
 * @return stdClass 
 */
function flax_maxgrades_config() {
	$grades = array();
	$max = 100; $min = 1;
	$grades[0] = get_string('gradeno','flax');
	for ($i=$max; $i>0; $i--) {
		$grades[$i] = $i;
	}
	$cfg = new stdClass();
	$cfg->default = 0;
	$cfg->maxgrades = $grades;
	$cfg->grademax = $max;
	$cfg->grademin = $min;
	return $cfg;
}
/**
 * Test if the open/close are set for a flax exercise, and 
 * if so, if the current time is between the open and close time of a flax exercise
 * @param int $closed Must be either no (open) or yes (closed) (see flax_user_answers table in install.xml)
 * @return bool
 */
function flax_exercise_open_close_check($flax){
	$now = time();
	//Is it open yet?
	if ($flax->timeopen && $flax->timeopen > $now) {
		return false;
	}
	// check that the activity is not closed
	if ($flax->timeclose && $flax->timeclose < $now) {
		return false;
	}
	if(! $flax->timeclose) {//exercise never closes?
		return false;
	}
	return true;
}

function flax_obj2arr($d) {
	if (is_object($d)) {
		// Gets the properties of the given object
		// with get_object_vars function
		$d = get_object_vars($d);
	}

	if (is_array($d)) {
		/*
		 * Return array converted to object
		* Using __FUNCTION__ (Magic constant)
		* for recursive call
		*/
		return array_map(__FUNCTION__, $d);
	}
	else {
		// Return array
		return $d;
	}
}
function flax_arr2obj($d) {
	if (is_array($d)) {
		/*
		 * Return array converted to object
		* Using __FUNCTION__ (Magic constant)
		* for recursive call
		*/
		return (object) array_map(__FUNCTION__, $d);
	}
	else {
		// Return object
		return $d;
	}
}
/**
 * flax wrapper around {@see add_to_log()}
 *
 * @param string $action to be logged
 * @param array $params required - [cid]; optional - [info], [url],[cmid],[userid]
 */
function flax_log($action, $params) {

	if (is_null($action) || is_null($params)) {
		die();
	}
	if(!array_key_exists('info', $params)){
		$params['info'] = 'logging for action: '.$action;
	}
	if(!array_key_exists('url', $params)){
		$params['url'] = '';
	}
	if(!array_key_exists('cmid', $params)){
		$params['cmid'] = 0;
	}
	if(!array_key_exists('userid', $params)){
		$params['userid'] = 0;
	}
	add_to_log($params['cid'], 'flax', $action, $params['url'], $params['info'], $params['cmid'], $params['userid']);
}
function flax_debug_log($msg){
	$contents = PHP_EOL.(string)$msg;
	file_put_contents('./error.log', $contents, FILE_APPEND);
}
/**
 * Print out error message and stop outputting.
 *
 * @param string $message
 */
function flax_warn($message) {
        echo '<div style="text-align: center; font-weight: bold; color: black;">';
        echo '<span style="color: red;">'.get_string('warning', 'flax').':</span> ';
        echo s($message, true);
        echo '</div>';
		return;
}
function starts_with($str, $substr){
	$len = strlen($substr);
	return (substr($str, 0, $len) === $substr);
}
function ends_with($str, $substr){
	$len = strlen($substr);
	$start = $len * -1;//make it negative so searching starts from the end of $str
	return (substr($str, $start) === $substr);
}
function flax_create_element_html($att_array) {
	$el_id = '';
	if($att_array['element_id']){
		$el_id = 'id="'.$att_array['element_id'].'"';
	}
	$html = 
		'<div '.$el_id.' style="'.$att_array['element_style'].'" class="fitem">'.
			'<div class="fitemtitle">'.
				'<div class="fstaticlabel">'.
					'<label>'.$att_array['element_label'].'</label>'.
				'</div>'.
			'</div>'.
			'<div id="'.$att_array['element_content_id'].'" class="felement fstatic">'.
				$att_array['element_content'].
			'</div>'.
		'</div>'.
	    '<br>';
    return $html;
}
function flax_load_js_lib($courseid=null){
	global $CFG, $PAGE, $USER;
	$is_teacher = '';
    $coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);
	if (has_capability('mod/flax:create', $coursecontext)) {
	        // The login user is a teacher
	        $is_teacher = 'teacher';
	} 
	$randnum = rand(0, 10);
// 	$PAGE->requires->yui2_lib(array('utilities','container','button'));
// 	$PAGE->requires->yui2_lib('utilities');
// 	$PAGE->requires->yui2_lib('container');
// 	$PAGE->requires->yui2_lib('button');
	$flax_path = flax_get_server_url().'/greenstone3/interfaces/flax/';
	$flax_js_path = flax_get_server_url().'/greenstone3/interfaces/flax/js/';
	$PAGE->requires->js(new moodle_url($flax_path.'yui/utilities/utilities.js'));
	$PAGE->requires->js(new moodle_url($flax_path.'yui/container/container-min.js'));
	$PAGE->requires->js(new moodle_url($flax_path.'yui/button/button-min.js'));
	$PAGE->requires->js(new moodle_url($flax_js_path.'resources/LLDL-interface-en.js'));
	$PAGE->requires->js(new moodle_url($flax_js_path.'resources/designactivity-interface-en.js'));
	$PAGE->requires->js(new moodle_url($flax_js_path.'core/LLDL.js?i='.$randnum));
   $PAGE->requires->js(new moodle_url($flax_js_path.'core/utility.js?i='.$randnum));
   $PAGE->requires->js(new moodle_url($flax_js_path.'core/BeanObjects.js?i='.$randnum));
   
   $PAGE->requires->js(new moodle_url($flax_js_path.'core/CollectionBuilder.js?i='.$randnum));
	
   $PAGE->requires->css(new moodle_url($flax_path.'style/flax-style.css'));
   $PAGE->requires->css(new moodle_url($flax_path.'style/ListCollections.css'));
   $PAGE->requires->css(new moodle_url($flax_path.'style/DesignInterface.css'));
   $PAGE->requires->css(new moodle_url($flax_path.'style/BuildCollection.css'));

    // Put styles.css after flax-style.css to override any conflicting rules between the two (not working?? find out later TODO)
	$PAGE->requires->css('/mod/flax/styles.css');
	$PAGE->requires->js('/mod/flax/module.js');//it has used some of the stuff in LLDL.js
	$PAGE->requires->js('/mod/flax/design_module.js');
	$PAGE->requires->js_init_call('M.mod_flax.config', 
		array(  $CFG->wwwroot,
				get_string('modulename', 'flax'),
				'dummy str',
				flax_get_server_url(),
				flax_get_mdl_site_id(),
				$USER->username,
				$courseid,
				$is_teacher,
				//Loop thru 'classes' dir and get all available flax activity class names
				implode(',', flax_get_activity_classes()),
				
				array(
				    FLAXTYPE_WRAP,
                    COLL_LIST_WRAP,
                    ACTIVITY_LIST_WRAP,
                    CONTENTSUMMARY_WRAP,
                    DOCUMENT_LIST_WRAP,
				    COLL_LIST_ID,
					ACTIVITY_LIST_ID,
					CONTENTSUMMARY_ID,
					DOCUMENT_LIST_ID,
					PLACE_HOLDER_ID
				)
				
		), true
	);
    // load activity classes which have been impletemented for the module
    $classes = flax_get_activity_classes();
    foreach($classes as $class) {    	
    	$PAGE->requires->js(new moodle_url($flax_js_path.'resources/'.$class.'-interface-en.js?i='.$randnum));
    	$PAGE->requires->js(new moodle_url($flax_js_path.$class.'/Design'.$class.'.js?i='.$randnum));
    	$PAGE->requires->css(new moodle_url($flax_path.'style/Design'.$class.'.css'));
    }
}
/**
 * Loop thru 'classes' dir and get all available flax activity class names
 */
function flax_get_activity_classes() {
	$classes_dir = dirname(__FILE__).'/classes';
	$files_arr = scandir($classes_dir);
	$act_names_arr = array();
	foreach ($files_arr as $fname){
		if(!ends_with($fname, '.class.php') || !starts_with($fname, 'flax_activity_')) continue;
		$activity_name = substr(substr($fname, strlen('flax_activity_')), 0, strlen('.class.php')*-1);
		$act_names_arr[] = $activity_name;
	}
	return $act_names_arr;
}
/**
 * Convert activity type to the class name implemented for the module, eg, ScrambleSentence to flax_activity_ScrambleSentence
 * @param string $activitytype
 */
function flax_get_activity_js_object_name($classname){
	return 'flax_activity_'.$activitytype;
}
/**
 * Convert activity type to the class name implemented for the module, eg, ScrambleSentence to flax_activity_ScrambleSentence
 * @param string $activitytype
 */
function flax_get_activity_class_name($activitytype){
	return 'flax_activity_'.$activitytype;
}
/**
 * Convert activity type to the file name of the class implemented for the module, eg, ScrambleSentence to flax_activity_ScrambleSentence.class.php
 * @param string $activitytype
 */
function flax_get_activity_class_filename($activitytype){
	return 'flax_activity_'.$activitytype.'.class.php';
}
function flax_icon_progress($txt){
	global $OUTPUT;
	$icon_progress = $OUTPUT->pix_icon('i/loading_small', 'Loading').'<span style="margin-left:5px;">'.$txt.'</span>';
	return $icon_progress;
}
function register_site_id(){
   //The following checking makes sure registration is only done once at the first time access
	$cfg = get_config('flax');
	$flax_domain = $cfg->{FLAX_SERVER_NAME};
	$flax_port = $cfg->{FLAX_SERVER_PORT};
	if(property_exists($cfg, REGISTERED_FLAX_SERVER) && $cfg->{REGISTERED_FLAX_SERVER} == $flax_domain.$flax_port){
	   //already registered
	   //debugging('already registered'.$domain.$port);
		return;
	}
	global $CFG, $USER;
	$mdlsiteid=flax_get_mdl_site_id();
	$mdlsiteurl = $CFG->wwwroot;
	$callback = '/mod/flax/submit.php';
	$adminemail = $CFG->supportemail;
	$param = 'a=pr&ro=1&rt=r&o=xml&s=MdlListCollections'.
			'&s1.service='.MODULE_SITE_REGISTER.'&s1.mdlsiteid='.$mdlsiteid.'&s1.mdlsiteurl='.$mdlsiteurl.'&s1.callback='.$callback.'&s1.supportemail='.$CFG->supportemail;
	$register_result = query_flax($param, 'string');
	if($register_result){
		set_config(REGISTERED_FLAX_SERVER, $flax_domain.$flax_port, 'flax');
	}
	return true;
}
/**
 * The id returned from this function is used:
 * 1. register the site with the remote backend FLAX server (in mod_form.php)
 * 2. verification key each time a relayed message (user's response to an exercise, etc.) is sent back from the FLAX server (in view.php)
 *
 * @return string identifier of the site
 */
function flax_get_mdl_site_id() {
	global $CFG;
	return substr($CFG->siteidentifier, 0, 32);
	/**
	 $plain_id = substr($CFG->siteidentifier, 0, 32) + $CFG->wwwroot + '/mod/flax/query_site_info.php';
	 $secret_key = 'flaxnzdlorg';
	 $iv = 'groldznxalf';
	 $cipher = 'rijndael-128';
	 $mode = 'cbc';
	 $td = mcrypt_module_open($cipher, '', $mode, $iv);
	 mcrypt_generic_init($td, $secret_key, $iv);

	 $crypt_id = bin2hex( mcrypt_generic($td, $plain_id) );

	 mcrypt_generic_deinit($td);
	 mcrypt_module_close($td);

	 return $crypt_id;
	 */
}
function query_flax($param, $format=null){
	global $CFG;
	require_once($CFG->libdir . '/filelib.php');//include curl - a wrapper class of the PHP cURL library

    $c = new curl(array());
    $url = flax_get_full_domain();
	$content = $c->post($url, $param);//Note: var_dump($content): string(22573) [displayed in browser]
	if($c->error){
		$msg = '<h2>Oops! '.$c->error.'</h2>'.
		       '<br/><br/><a target="_blank" href="'.$url.'" style="font-weight:bold;">'.$url.'</a>'.
		       '<br/><br/>'.
		       '<div style="font-weight:bold;">Suggestions:</div>'.
		       '<ul>'.
		          '<li>The external FLAX is not running. Try clicking <a target="_blank" href="'.$url.'" style="font-weight:bold;">'.$url.'</a></li>'.
		          '<li>Your Moodle server is protected by a firewall or proxy</li>'.
		          '<li>Your Moodle server needs a VPN connection to access the external FLAX server</li>'.
		       '</ul>';
		notice($msg);
		die();
	}
	if($format && $format == 'xml'){
		$xml = simplexml_load_string($content);
		return $xml;
	}else{
		return $content;
	}
}
function upload_flax($url, $file_param){
	global $CFG;
	require_once($CFG->libdir . '/filelib.php');//include curl class

	$c = new curl(array());
	$response = $c->post($url, $file_param);
	$err = $c->error;
// 	$ch = curl_init();
// 	curl_setopt($ch,CURLOPT_URL, $url);
// 	curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
// 	curl_setopt($ch,CURLOPT_POST, true);
// 	curl_setopt($ch,CURLOPT_HEADER, 0);
// 	curl_setopt($ch,CURLOPT_VERBOSE, 0);
// 	curl_setopt($ch,CURLOPT_POSTFIELDS, $file_param);
	
// 	//execute post
// 	$response = curl_exec($ch);
// 	$err = curl_error($ch);
	
// 	//close connection
// 	curl_close($ch);
	
// 	flax_debug_log('err='.$err);
// 	flax_debug_log('data='.$data);
// 	flax_debug_log('response='.$response);
// 	flax_debug_log('file_param='.print_r($file_param, true));
	
	return $err? $err : $response;		
}
function flax_get_xml_response($ok){
	$type = $ok?'1':'-1';
	$xml_resp = '<?xml version="1.0" encoding="UTF-8"?>
	<response><message type="'.$type.'"/></response>';
	return $xml_resp;
}
/**
 * Used by mod_form of both modules
 */
function query_flax_collections(){
	global $USER;
	$param = 'a=pr&ro=1&rt=r&o=xml&s=MdlListCollections'.
			'&s1.service='.LIST_COLL.
			'&s1.lang=en'.
			'&s1.username='.$USER->username.
			'&s1.mdlsiteid='.flax_get_mdl_site_id();
	return query_flax($param, 'string');
}
function flax_get_full_domain(){
	return flax_get_server_url().'/greenstone3/flax';
}
/**
 * Based on the settings set in settings.php, work out the FLAX server url (domain:port).
 * @return string the FLAX server url
 */
function flax_get_server_url() {
 	$flax_server = get_config('flax', FLAX_SERVER_NAME);
 	$flax_port = get_config('flax', FLAX_SERVER_PORT);

 	if(!$flax_server){
 		$flax_server = DEFAULT_FLAX_SERVER;
 	}
 	if(!$flax_port){
 		$flax_port = DEFAULT_FLAX_PORT;
 	}
	// Check if we need to prefix $flax_server with "http://"
	if(strpos($flax_server, 'http://') === false) {
		$flax_server = 'http://'.$flax_server;
	}
	return $flax_server.':'.$flax_port;
}
/**
 * @return boolean true if $string starts with $substring; false otherwise.
 */
function flax_string_starts_with($string, $substring) {
	return (strncmp($string, $substring, strlen($substring))==0);
}
function create_question_record($flax, $content, $answer, $paramkeys, $paramvalues){
	global $DB;
	$question = new stdClass();
	$question->flaxid = $flax->id;
	$question->{CONTENT} = $content;
	$question->{ANSWER} = $answer;
	$question->{PARAMKEYS} = $paramkeys;
	$question->{PARAMVALUES} = $paramvalues;

	if(!$DB->insert_record(QUESTION_TBL, $question)) {
		flax_log('failed to insert question db tbl', array('cid'=>$flax->course));
	}
}
function create_finish_record($flax, $questionid, $userid){
	global $DB;
	$o = new stdClass();
	$o->flaxid = $flax->id;
	$o->questionid = $questionid;
	$o->userid = $userid;
	$o->finished = 'no';

	if(! $id = $DB->insert_record(FINISH_TBL, $o)) {
		flax_log('failed to insert FINISH db tbl', array('cid'=>$flax->course));
	}
	return $id;
}

/*
 * @param string $id id of the flax table
 */
function flax_delete_aux_tables($id){
	global $DB;
	
	if(! $DB->delete_records(QUESTION_TBL, array('flaxid'=>$id))) {
		error_log("deleting flax_questions table failed");
		return false;
	}
	
	if($DB->record_exists(FINISH_TBL, array('flaxid'=>$id))){
		$DB->delete_records(FINISH_TBL, array('flaxid'=>$id));
	}
	if($DB->record_exists(VIEW_TBL, array('flaxid'=>$id))){
		$DB->delete_records(VIEW_TBL, array('flaxid'=>$id));
	}
	if($DB->record_exists(SUBMISSION_TBL, array('flaxid'=>$id))){
		$DB->delete_records(SUBMISSION_TBL, array('flaxid'=>$id));
	}
	return true;
}
/*
 * @param string $mdlparams parameters seperated by PARAM_ARG_SEPARATOR and PARAM_NV_SEPARATOR
 */
function explode_params($mdlparams){
	$param = array();
	$p_arr = explode(PARAM_ARG_SEPARATOR, $mdlparams);
	foreach($p_arr as $p){
		$nv_arr = explode(PARAM_NV_SEPARATOR, $p);
		$param[$nv_arr[0]] = $nv_arr[1];
	}
	return $param;
}
/**
 * Function used by lib/get_recent_mod_activity and index.php
 * 
 * @param string $flaxid
 * @param string $userid
 * @param string $sincetime
 */
function read_exercise_attempts_from_db($flaxid, $userid='', $sincetime=''){
    global $DB;
    
    $params = array('cminstance'=>$flaxid);        
    if ($userid) {
        $userselect = "AND u.id = :userid";
        $params['userid'] = $userid;
    } else {
        $userselect = "";
    }
    if ($sincetime) {
        $timeselect = "AND a.accesstime > :sincetime";
        $params['sincetime'] = $sincetime;
    } else {
        $timeselect = "";
    }
    
    $userfields = user_picture::fields('u', null, 'userid');
	$sql = "SELECT
	a.id, a.flaxid, a.score, a.submissionids, a.accesstime,
	f.name, f.flaxtype, f.activitytype, f.maxgrade, f.gradeover, f.intro, $userfields
	FROM
	{".VIEW_TBL."} a
	JOIN {flax} f ON f.id = a.flaxid
	JOIN {user} u ON u.id = a.userid
	WHERE
	f.id = :cminstance $userselect $timeselect
	ORDER BY
	a.accesstime ASC";
// 	debugging('sql='.$sql);
	$records = $DB->get_records_sql($sql, $params);
// 	print_object($params);
// 	print_object($records);
	return $records;
}
/**
 * Function used by lib/print_recent_mod_activity
 * 
 * @param stdClass $p
 */
function get_attempt_report_link(stdClass $p){
	global $CFG;
	$params = array('viewid'=>$p->viewid, 'flaxid'=>$p->flaxid, 'cmid'=>$p->cmid, 'fullname'=>$p->fullname, 'userid'=>$p->userid);
	$url = new moodle_url($CFG->wwwroot . '/mod/flax/report_attempt.php', $params);
	$link = html_writer::link($url, $p->text, array('class'=>'name'));
	return $link;
}
/**
 * This function prints out all attempts on a flax instance by a user or all users
 * 
 * Function used by index.php and report_module.php and lib/flax_user_complete
 * 
 * @param string $cmid
 * @param string $courseid
 * @param string $userid null means all users
 */
function report_mod_activity($cmid, $courseid, $userid=''){

	$activities = array();
	$index = 0;
	flax_get_recent_mod_activity($activities, $index, '', $courseid, $cmid, $userid);
	if(!empty($activities)){
		foreach ($activities as $activity) {
			flax_print_recent_mod_activity($activity, $courseid);
		}
	}else{
		global $OUTPUT;
		echo $OUTPUT->container(get_string('noattempts','flax'), 'mod-flax-noattempts-container-class');
	}
}