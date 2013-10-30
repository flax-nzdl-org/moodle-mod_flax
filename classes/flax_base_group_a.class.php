<?php

//  file is part of Moodle - http://moodle.org/
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
 * @package    mod
 * @subpackage flax
 * @author alex.xf.yu@gmail.com & mjl61@waikato.ac.nz
 **/

/** Make sure this isn't being directly accessed */
defined('MOODLE_INTERNAL') || die();

require_once ($CFG->dirroot.'/mod/flax/locallib.php');
require_once('classes/flax_base.class.php');
require_once('classes/flax_interface.class.php');

/**
 * flax_base_group_a class - extends the flax_base class with group of general methods that can apply to any activity
 */
class flax_base_group_a extends flax_base {
	
	/**
     * When an activity is viewed (i.e. page loaded) by user
     */
    public function view($flax){
    	global $CFG, $COURSE, $USER, $DB;
    	$view = parent::create_view_record($flax);

    	if(! flax_is_graded($flax)){
    		return parent::view_ungraded_exercise($flax);    		
    	}
   	
    	$finish_records = $DB->get_records_select(FINISH_TBL, 'flaxid=? AND userid=?', array($flax->id, $USER->id));
    	$record_ids = array();
    	$num_question_closed = 0;
    	$all_questions_finished = true;
    	$user_exercise_score = flax_read_user_score($flax, $USER->id);
    	
    	if(! $finish_records){
    		$all_questions_finished = false;
    		// This exercise instance($flax->id) has NOT been attempted before,    	
    		// First, read flax-questions table to get a list of questions.
    		// The ids of the question records are needed in order to insert corresponding records into flax_user_finish
    		$flax_questionids = array_keys($DB->get_records_select(QUESTION_TBL, 'flaxid=?', array($flax->id),'id'));
    		foreach($flax_questionids as $questionid) {
    			$fid = create_finish_record($flax, $questionid, $USER->id);
    			$record_ids[] = $fid;
    		}
    	}else{
    		// This exercise instance has been attempted before    	    		
    		foreach($finish_records as $a){
    			if(flax_is_question_finished($a->finished)) {
    				$num_question_closed++;
    			}else{
    				$all_questions_finished = false;
    			}
    			$record_ids[] = $a->id;
    		}    		
    	} //End of else

    	if($all_questions_finished) {
    		// All questions in the instance have been attempted and closed (applying to grading mode exercises only)
    		parent::display_exercise_finished();
    		return false;
    	}

    	$mdl_site_id = flax_get_mdl_site_id();
    	$activity_specific_params = array(NUMCLOSED=>$num_question_closed, COURSEID=>$COURSE->id, COURSEPAGEURL=>$CFG->wwwroot.'/course/view.php');
    	return parent::view_graded_exercise($flax, $view->id, $mdl_site_id, $user_exercise_score, 
    			implode(',', $record_ids), $activity_specific_params);
    }
    /**
     * When a user wants to view a report for an activity (i.e. under Course -> General -> *Activityname* -> Report -> View report of particular attempt)
     */
    public function print_report(stdClass $flax, stdClass $obj){
    	$this->print_report_header($flax, $obj);
    	$this->print_report_body($flax, $obj);
    }
    public function print_report_header(stdClass $flax, stdClass $obj){
    	$score = 0;
    	if($obj->submissions){
    		foreach($obj->submissions as $sub){
    			$score = $score + intval($sub->score);
    		}
    	}
    	$table = new html_table();
    	$table->head  = array (get_string('exercisename','flax'), get_string('exercisetype','flax'), get_string('totalscore','flax'));
    	$table->align = array ('left','left','left');
    	$table->data[] = array($flax->name, get_string($flax->activitytype, 'flax'), $score);
		echo html_writer::table($table);
    	return $table; 
    }
	/**
	 * Alternative print_report_header method to include score/maxscore 
	 */
	public function print_report_header_with_percent(stdClass $flax, stdClass $obj, $score, $maxscore){
    	$percent = ' (' . number_format((($score / $maxscore) * 100), 0) . '%)';

		// Print report header
		$headtable = new html_table();
		$headtable -> head = array(get_string('exercisename', 'flax'), get_string('exercisetype', 'flax'), get_string('totalscore', 'flax'));
		$headtable -> align = array('left', 'left', 'left');
		$headtable -> data[] = array($flax -> name, get_string($flax -> activitytype, 'flax'), $score . '/' . $maxscore . $percent);
		echo html_writer::table($headtable);
    }
    public function print_report_body(stdClass $flax, stdClass $obj){
    	if(!$obj->submissions){
    		global $OUTPUT;
    		echo $OUTPUT->box (get_string('nosubmission','flax'), 'generalbox boxwidthwide');
    	
    	}else{
    		$show_correct_answer = false;
    		if($obj->permission_view_all || flax_exercise_open_close_check($flax)){
    			$show_correct_answer = true;
    		}
    		echo '<ol>';
    		foreach($obj->submissions as $sub){
    			$correct_answer = $show_correct_answer? $sub->answer:get_string('hiddenuntilclose','flax');
    			echo '<li>';
    			$table        = new html_table();
    			$table->colclasses = array(null, 'highlight-target-words');
    			$table->align = array ('left','left');
    			$table->data[] = array(get_string('question','flax'), $sub->content);
    			$table->data[] = array(get_string('youranswer','flax'), $sub->useranswer);
    			$table->data[] = array(get_string('score','flax'), $sub->score);
    			$table->data[] = array(get_string('correctanswer','flax'), $correct_answer);
    			echo html_writer::table($table);
    			echo '</li>';
    		}
    		echo '</ol>';
    	}
    }
	/* report_alt is used by a few activities as an alternative print_report format */
	public function print_report_alt(stdClass $flax, stdClass $obj){
		$this->print_report_header($flax, $obj);
		$this->print_report_body_alt($flax, $obj);
	}
	public function print_report_body_alt(stdClass $flax, stdClass $obj){
		if(!$obj->submissions){
    		global $OUTPUT;
    		echo $OUTPUT->box (get_string('nosubmission','flax'), 'generalbox boxwidthwide');
			return false;
    	}
		echo '<ol>';
		foreach($obj->submissions as $sub){
			echo '<li>';
			$table = new html_table();
			$table->align = array('left','left');
			$table->head = array(get_string('youranswer','flax'), get_string('correctanswer','flax'));
			$table->data[] = array($sub->useranswer, $sub->answer);
			$table->data[] = array('<strong>'.get_string('score','flax').'</strong>', $sub->score);
			echo html_writer::table($table);
			echo '</li>';
		}
		echo '</ol>';
	}


	/**
	 * Function to parse and create the paramkeys + paramvalues attributes before creating the submission record
	 * Used by activities that have multiple parameters stored in their $responsecontent
	 * $useranskey is the key of the parameter in $responsecontent to be stored in the 'useranswer' attribute of the submission table
	 */
	public function create_submission_record_with_param($flax, $record, $view, $score, $responsecontent, $useranskey){
	
		global $DB, $CFG;
		
		$score = clean_param($score, PARAM_INT);
		$record->finished = YES;
		if (!$DB -> update_record(FINISH_TBL, $record)) {
			error_log('failed to update finish table record');
			return false;
		}
			
		// ANS_SEPARATOR has a literal value of '\\' - to use in regex, must repeat twice (as regex treats the / as an escape)
		$a = ANS_SEPARATOR.ANS_SEPARATOR;
		$pattern = "/([^".$a."]+)".$a."([^".$a."]+)/";
		preg_match_all($pattern, $responsecontent, $pairs);	// split the content string into two arrays (param values and param keys)
		$com = array_combine($pairs[1], $pairs[2]);			// combine the two arrays into one associative array

		$paramkeys = '';
		$paramvalues = '';

		// loop through the associative array and create the paramkeys + paramvalues strings
		foreach ($pairs[1] as $p){
			if ($p != $useranskey){		// skip if it's the useranswer parameter
				$paramkeys .= $p;
				$paramvalues .= $com[$p];
				if ($p !== end($pairs[1])){
					$paramkeys .= ARG_SEPARATOR;
					$paramvalues .= ARG_SEPARATOR;
				}
			}
		}
		
		$uans = $com[$useranskey];
		
		$sub_record = parent::create_submission_record($flax, $record->questionid, $view, $score, $uans, $paramkeys, $paramvalues);
		parent::update_view_record($view, $sub_record->id, $score);

		require_once ($CFG->dirroot.'/mod/flax/lib.php');
		flax_update_grades($flax, $view->userid);
		 	
		return true;
	}
}