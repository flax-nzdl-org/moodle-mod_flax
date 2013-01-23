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
 * Local library class for flax.  These are non-standard functions that are used
 * only by flax, and especially shared by all flax activity classes.
 *
 * @package    mod
 * @subpackage flax
 * @author alex.xf.yu@gmail.com
 **/

/** Make sure this isn't being directly accessed */
defined('MOODLE_INTERNAL') || die();

/** Include the files that are required by this module */
require_once ($CFG->dirroot.'/mod/flax/locallib.php');
/**
 * Abstract class representation of a flax activity.
 * Abstract class to provide core functions to the all flax activity classes
 *
 * This class MUST be extended by all flax activity classes defined in mod/flax/classes/.
 * There are a handful of abstract methods that need to be defined as well as
 * severl methods that can optionally be defined in order to make the activity
 * operate in the desired way
 *
 * Database properties
 * @property int $id The id of this lesson page
 * @property int $lessonid The id of the lesson this page belongs to
 * @property int $prevpageid The id of the page before this one
 * @property int $nextpageid The id of the next page in the page sequence
 * @property int $qtype Identifies the page type of this page
 * @property int $qoption Used to record page type specific options
 * @property int $layout Used to record page specific layout selections
 * @property int $display Used to record page specific display selections
 * @property int $timecreated Timestamp for when the page was created
 * @property int $timemodified Timestamp for when the page was last modified
 * @property string $title The title of this page
 * @property string $contents The rich content shown to describe the page
 * @property int $contentsformat The format of the contents field
 *
 * Calculated properties
 * @property-read array $answers An array of answers for this page
 * @property-read bool $displayinmenublock Toggles display in the left menu block
 * @property-read array $jumps An array containing all the jumps this page uses
 * @property-read lesson $lesson The lesson this page belongs to
 * @property-read int $type The type of the page [question | structure]
 * @property-read typeid The unique identifier for the page type
 * @property-read typestring The string that describes this page type
 *
 * @abstract
 * @author xiao@waikato.ac.nz
 */
abstract class flax_base {

const FLAX_SERVER_NAME = 'flax_server_name';
const FLAX_SERVER_PORT = 'flax_server_port';
const DEFAULT_FLAX_SERVER = 'server.moodleflax.org';
const DEFAULT_FLAX_PORT = 80;

//The following contants are used by view.php and attempt.php
const SCOREBACKURL = 'scorebackurl';
const FLAXID = 'flaxid';
const ATTEMPTID = 'attemptid';
const NODEID = 'nodeid';
const NODEIDS= 'nodeids';
const RESPONSEID = 'responseid';
const RESPONSEIDS = 'responseids';
const USERNAME = 'username';
const USERID= 'userid';
const VERIFICATIONKEY = 'verificationkey';
const NUMTRYLEFT = 'numtryleft';
const NUMTRYALLOW = 'numtryallow';
const MODE= 'mode';
const USERSCORE= 'userscore';
const TOTALSCORE = 'totalscore';
const TIMECLOSE = 'timeclose';
const NUMCLOSED = 'numclosed';
const RESPONSECONTENT = 'responsecontent';
const COURSEID = 'courseid';
const COURSEPAGEURL = 'coursepageurl';

const FLAX_NO =  '0';
const FLAX_YES = '1';

//These must match the ones defined in LLDL.js
const ID_SEPARATOR = ',';//used in lib.php and view.php
const TEXT_SEPARATOR = ':;:;:';//used in lib.php and view.php
const NV_SEPARATOR = ';;';//used in ws_gateway.php
const ARG_SEPARATOR = ']]';//used in ws_gateway.php
const PARAM_NV_SEPARATOR = ';';//used in view.php
const PARAM_ARG_SEPARATOR = ']';//used in view.php
const MODULE_PARAMS = 'moduleParams';//used in view.php

const FLAX_NO_GRADE = 0;

const FLAX_GRADEMETHOD_HIGHEST = 0;
const FLAX_GRADEMETHOD_AVERAGE = 1;
const FLAX_GRADEMETHOD_FIRST =   2;
const FLAX_GRADEMETHOD_LAST =    3;
const FLAX_GRADEMETHOD_ACUMULATIVE = 4;

// const TABLE_FLAX_QUESTIONS = 'flax_questions';
// const TABLE_FLAX_RESPONSES = 'flax_responses';
 
    /**
     * A reference to a flax exercise instance
     * @var flax
     */
    protected $flax = null;
    /**
     * The name of the flax exercise
     * @var string
     */
    protected $name = '';
    /**
     * This sets the activity type of the flax exercise - corresponses to one of the classes defined in /flax/classes/
     * The class names defined in /flax/classes/ are in the fasion: flax_$activitytype
     * @var string
     */
    protected $activitytype = '';

    /** @var stdclass course module record */
    protected $cm;

    /** @var stdclass course record */
    protected $course;

    /** @var stdclass context object */
    protected $context;

    /** @var int workshop instance identifier */
    protected $id;

    /** @var string introduction or description of the activity */
    protected $intro;

    /** @var int format of the {@link $intro} */
    protected $introformat;

    /** @var string instructions for the submission phase */
    protected $instructauthors;

    /** @var int format of the {@link $instructauthors} */
    protected $instructauthorsformat;

    /** @var string instructions for the assessment phase */
    protected $instructreviewers;

    /** @var int format of the {@link $instructreviewers} */
    protected $instructreviewersformat;

    /** @var int timestamp of when the module was modified */
    protected $timemodified;

    /**
     * Initializes the flax API instance using the data from DB
     *
     * Makes deep copy of all passed records properties. Replaces integer $course attribute
     * with a full database record (course should not be stored in instances table anyway).
     *
     * @param stdClass $dbrecord flax instance data from {flax} table
     * @param stdClass $cm       Course module record as returned by {@link get_coursemodule_from_id()}
     * @param stdClass $course   Course record from {course} table
     * @param stdClass $context  The context of the flax instance
     */
    public function __construct(stdclass $dbrecord, stdclass $cm, stdclass $course, stdclass $context=null) {
        foreach ($dbrecord as $field => $value) {
	  if (property_exists(get_class($this), $field)) {
                $this->{$field} = $value;
            }
        }
        $this->cm           = $cm;
        $this->course       = $course;
        if (is_null($context)) {
            $this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $this->context = $context;
        }
    }

    /**
     * Magic property method
     *
     * Attempts to call a set_$key method if one exists otherwise falls back
     * to simply set the property
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value) {
        if (method_exists($this, 'set_'.$key)) {
            $this->{'set_'.$key}($value);
        }
        $this->{$key} = $value;
    }

    /**
     * Magic get method
     *
     * Attempts to call a get_$key method to return the property and ralls over
     * to return the raw property
     *
     * @param str $key
     * @return mixed
     */
    public function __get($key) {
        if (method_exists($this, 'get_'.$key)) {
            return $this->{'get_'.$key}();
        }
        return $this->{$key};
    }

    /**
     * Required by the use of __get() magic method?
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key) {
        if (method_exists($this, 'get_'.$key)) {
            $val = $this->{'get_'.$key}();
            return !empty($val);
        }
        return !empty($this->properties->{$key});
    }
    /**
     * For activity classes that have 1-flax-record vs many-flaxquestions-record in db (eg, ScrambleSentence)
     * 
     * @param unknown_type $flax
     */
    protected function load_grade_records($flax){
    	global $USER, $DB;
    	$grade_record_ids = array();
    	// Get records based on criteria from flax_user_grades table; return in random order (no sorting)
    	$grade_records = $DB->get_records_select(GRADE_TBL, 'flaxid=? AND userid=?', array($flax->id, $USER->id));
//     	print_object($grade_records);
    	if(!$grade_records) {
    		$num_question_closed = 0;
    		// This exercise instance($flax->id) has NOT been attempted before,    	
    		// First, read flax-questions table to get a list of questions.
    		// The ids of the question records are needed in order to insert corresponding records into flax_user_answers
    		$records = $DB->get_records_select(QUESTION_TBL, 'flaxid=?', array($flax->id));
    		$flax_questionids = array_keys($records);
//     		print_object('number of question ids = '.count($flax_questionids));
    		foreach($flax_questionids as $questionid) {
    			$a = $this->create_grade_record($flax, $questionid);
    			$grade_record_ids[] = $a->id;
    		}
    		return array($grade_record_ids, $num_question_closed, 0);
    	}
    	else {
    		// This exercise instance has been attempted before    	
    		
    		$num_question_closed = 0;
    		$total_exercise_score = 0;
    		if(flax_is_graded($flax)){
    			foreach($grade_records as $a){
    				if($a->closed == 'yes') {
    					//If it's a closed record
    					$num_question_closed++;
    				}
    				$total_exercise_score += $a->grade;
    				$grade_record_ids[] = $a->id;
    			}    			
    		}
    		return array($grade_record_ids, $num_question_closed, $total_exercise_score);
    	} //End of else
    }
    protected function update_gradebook($flax, $userid){
    	if($score <= 0){ return; }
    	
    	global $CFG;
    	require_once ($CFG->dirroot.'/mod/flax/lib.php');
    	flax_update_grades($flax, $userid);
    	
    }
    protected function display_exercise_finished(){
      	echo $OUTPUT->notification('<h3 style="text-align:center;">'.get_string('exercisefinished', 'flax').'</h3>', 'notifysuccess');
    }
    protected function view_ungraded_exercise($flax){
    	$callbackinfo = flax_get_ungraded_callback_info();
    	return self::extract_view_params($flax->{FLAXURL}, $callbackinfo);
    }
    protected function view_graded_exercise($flax, $viewid, $mdl_site_id, $user_exercise_score, $recordids, array $rawinfo){
         $callbackinfo = flax_get_graded_callback_info($viewid, $mdl_site_id, $user_exercise_score, $recordids, $rawinfo);
    	 return self::extract_view_params($flax->{FLAXURL}, $callbackinfo);
    }
    /**
     * For activity classes that have 1-flax-record vs 1-flaxquestion-record in db (eg, ContentWordGuessing)
     * 
     * @param
     */
    protected function load_grade_record($flax){
    	
    }
    protected function create_grade_record($flax, $questionid){
    	global $USER, $DB;
    	$grade_record = new stdClass();
    	$grade_record->flaxid = $flax->id;
    	$grade_record->questionid = $questionid;
    	$grade_record->userid = $USER->id;
    	$grade_record->grade = 0;
    	$grade_record->closed = 'no';
    	$id = $DB->insert_record(GRADE_TBL, $grade_record, true);
    	$grade_record->id = $id;
    	return $grade_record;
    }
    protected function create_view_record($flax){
    	global $USER, $DB;
    	$view = new stdClass();
    	$view->flaxid = $flax->id;
    	$view->userid = $USER->id;
    	$view->score = 0;
    	$view->submissionids = '';
    	$view->accesstime = $flax->accesstime;
    	$viewid = $DB->insert_record(VIEW_TBL, $view, true);
    	$view->id = $viewid;
    	return $view;
    }
    protected function update_view_record($view, $submissionid, $score){
    	global $DB;
    	$view->score = $view->score + $score;
    	$view->submissionids .= (empty($view->submissionids)? '':',').$submissionid; 
    	$DB->update_record(VIEW_TBL, $view);
    	return true;
    }
    protected function create_submission_record($flax, $questionid, $view, $score, $responsecontent, $paramkeys='', $paramvalues=''){
    	//error_log('responsecontent='.$responsecontent);
    	global $DB;

    	$o = new stdClass();
    	$o->flaxid = $flax->id;
    	$o->questionid = $questionid;
    	$o->userid = $view->userid;
    	$o->viewid = $view->id;
    	$o->useranswer = $responsecontent;
    	$o->paramkeys = $paramkeys;
    	$o->paramvalues = $paramvalues;
    	$o->score = $score;
    	$o->accesstime = time();
    	$id = $DB->insert_record(SUBMISSION_TBL, $o, true);
    	$o->id = $id;
    	return $o;
    }
    /**
     * Internal function - called by the view function of each subclass to extract parameters from the url-like string $ref
     *
     * @param string $ref reference url of this FLAX instance (the field 'exerciseurl' of the 'flax' table of FLAX mod). This is the information merely related to FLAX part.
     * @param string $module_params additional info about the instance (especially regarding the 'Moodle module' part)
     * @return object an object containing info both about FLAX and info needed for the Moodle module
     */
    
    protected function extract_view_params($ref, $module_params) {    
    	$obj = new stdClass();
    	$params_pos = strpos($ref, "&s1.params=");
    
    	$flax_server = flax_get_server_url();
    	$url = substr($ref, 0, $params_pos);
    	if(flax_string_starts_with($url, '?')) {
    		$url = $flax_server.'/greenstone3/flax'.$url;
    	} else {
    		// Replace the part before '?' in $url with $flax_server.'/greenstone3/flax'
    		$url = $flax_server.'/greenstone3/flax'.substr($url, strpos($url, '?'));
    	}
    	/**
    	if(flax_string_starts_with($url, $flax_server)) {
    		$obj->url = $url;
    
    	} else if(flax_string_starts_with($url, '?')) {
    		$obj->url = $flax_server.'/greenstone3/flax'.$url;
    
    	} else {
    		// Replace the part before '?' in $url with $flax_server.'/greenstone3/flax'
    		$obj->url = $flax_server.'/greenstone3/flax'.substr($url, strpos($url, '?'));
    	}*/
    
        $obj->{FLAXURL} = $url;
    	$obj->{PARAMS} = substr($ref, $params_pos+11);
    	$obj->{MODULEPARAMS} = $module_params;
    	return $obj;
    }
    /**
     * This method is invoked in submit.php
     * @param
     * @return ?
     */
    public function process_submission($flax, $record, $view, $score/*either 1 or 0*/, $responseconent){
    	global $DB, $CFG;
    	
    	$score = clean_param($score, PARAM_INT);
    	$record->finished = YES;
    	if(!$DB->update_record(FINISH_TBL, $record)){
    		error_log('failed to update finish table record');
    	}

    	$sub_record = $this->create_submission_record($flax, $record->questionid, $view, $score, $responseconent);
    	$this->update_view_record($view, $sub_record->id, $score);
    	
    	require_once ($CFG->dirroot.'/mod/flax/lib.php');
    	flax_update_grades($flax, $view->userid);
    	    	
    	return true;
    }    
}