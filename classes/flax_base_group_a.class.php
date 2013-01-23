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
 * @author alex.xf.yu@gmail.com
 **/

/** Make sure this isn't being directly accessed */
defined('MOODLE_INTERNAL') || die();

require_once ($CFG->dirroot.'/mod/flax/locallib.php');
require_once('classes/flax_base.class.php');
require_once('classes/flax_interface.class.php');

/**
 * Activity class
 */
class flax_base_group_a extends flax_base {
	
	/**
     * This method should 
     * @param
     * @return ?
     */
    public function view($flax){
    	global $CFG, $COURSE, $USER, $DB;
    	$view = parent::create_view_record($flax);

    	if(! flax_is_graded($flax)){
    		return parent::view_ungraded_exercise($flax);    		
    	}
   	
//     	$num_question_closed = $DB->count_records(SUBMISSION_TBL, array('flaxid'=>$flax->id,  'userid'=>$USER->id));
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
    	} 	else {
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
//     	print_r($record_ids.' -- '.$num_question_closed);
//     	debugging(implode(',', $record_ids));
    	$mdl_site_id = flax_get_mdl_site_id();
    	$activity_specific_params = array(NUMCLOSED=>$num_question_closed, COURSEID=>$COURSE->id, COURSEPAGEURL=>$CFG->wwwroot.'/course/view.php');
    	return parent::view_graded_exercise($flax, $view->id, $mdl_site_id, $user_exercise_score, 
    			implode(',', $record_ids), $activity_specific_params);
    }
    /**
     * This method should 
     * @param
     * @return ?
     */
//     public function process_submission($flax, $record, $view, $score/*either 1 or 0; converted to int in attepmt.php*/, $responseconent){
//     	parent::process_submission($flax, $record, $view, $score, $responseconent);
//     }
    /**
     * This method 
     * @param
     * @return ?
     */
    public function print_report(stdClass $flax, stdClass $obj){
    	$table = $this->print_report_header($flax, $obj);
    	$this->print_report_body($flax, $obj, $table);
    }
    public function print_report_header(stdClass $flax, stdClass $obj){
    	$score = 0;
    	if($obj->submissions){
    		foreach($obj->submissions as $sub){
    			$score = $score + intval($sub->score);
    		}
    	}
    	$table        = new html_table();
    	//     	$table->attributes = array('align' => 'center');
    	$table->head  = array (get_string('exercisename','flax'), get_string('exercisetype','flax'), get_string('totalscore','flax'));
    	$table->align = array ('left','left','left');
    	$table->data[] = array($flax->name, get_string($flax->activitytype, 'flax'), $score);
    	echo html_writer::table($table);
    	return $table; 
    }
    public function print_report_body(stdClass $flax, stdClass $obj, $table){
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
    			//     			$table->attributes = array('');
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
}