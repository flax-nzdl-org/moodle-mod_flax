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
 * @package    mod
 * @subpackage flax
 * @author xiao@waikato.ac.nz
 **/

/** Make sure this isn't being directly accessed */
defined('MOODLE_INTERNAL') || die();

require_once ($CFG->dirroot.'/mod/flax/locallib.php');
require_once('classes/flax_base.class.php');
require_once('classes/flax_interface.class.php');

/**
 * Activity class
 */
class flax_activity_ContentWordGuessing extends flax_base implements flax_interface {
	
	protected $flax_type = 'ContentWordGuessing';

    /**
     * This method should 
     * @param
     * @return ?
     */
    public function view($flax){
    	global $CFG, $COURSE, $DB, $USER;
    	$view = parent::create_view_record($flax);

      if(! flax_is_graded($flax)){
    		return parent::view_ungraded_exercise($flax);
    	}    	
    	
    	$finish_record = $DB->get_record_select(FINISH_TBL, 'flaxid=? AND userid=?', array($flax->id, $USER->id), 'id,questionid,finished');
    	if(! $finish_record){
    		$q = $DB->get_record(QUESTION_TBL, array('flaxid'=>$flax->id), 'id', MUST_EXIST);
    		$questionid = $q->id;
    		$recordid = create_finish_record($flax, $questionid, $USER->id);
    	}else{
    		if(flax_is_question_finished($finish_record->finished)){
    			parent::display_exercise_finished();
    		   return false;
    		}
    		
    		$questionid = $finish_record->questionid;
    		$recordid = $finish_record->id;
    	}
    	
    	$user_exercise_score = intval(flax_read_user_grade($COURSE->id, $flax->id, $USER->id));
    	
    	$donewordpos = '';
    	$submission_records = $DB->get_records_select(SUBMISSION_TBL, 'flaxid=? AND userid=? AND questionid=?', array($flax->id, $USER->id, $questionid), 'id,paramvalues');
    	if($submission_records){
    		$arr = array();
    		foreach ($submission_records as $s){
    			$arr[] = $s->paramvalues;//the position number of submitted word in the article
    		}
    		$donewordpos = implode(',', $arr);
    	}
    	    	
    	$mdl_site_id = flax_get_mdl_site_id();    	
    	parent::view_graded_exercise($flax, $view->id, $mdl_site_id, $user_exercise_score, 
    			$recordid, array('donewordpos'=>$donewordpos));
    	return true;
    }
    /**
     * 
     * @param unknown_type $flax
     */
    protected function load_submission_record($flax){
    	global $USER, $DB;
    
    	return NULL;
    }
    /**
     * Refer to ContentWordGuessing.js, only correct answers are sent back as submissions
     * This method should 
     * @param record flax_user_finish table record
     * @return ?
     */
    public function process_submission($flax, $record, $view, $score/*either 1 or 0*/, $responseconent){
    	global $CFG, $DB;
    	$finish  = optional_param(COMPLETE, '', PARAM_TEXT);
    	if($finish){
    		$record->finished = YES;
    		$DB->update_record(FINISH_TBL, $record);
    	}
    	
		$score = clean_param($score, PARAM_INT);
    	$answer_info = explode('-', $responseconent);
		$word_pos = $answer_info[0];
		$answer = $answer_info[1];
  		
    	$sub_record = parent::create_submission_record($flax, $record->questionid, $view, $score, $answer, 'response_word_pos', $word_pos);
    	parent::update_view_record($view, $sub_record->id, $score);
    	
    	require_once ($CFG->dirroot.'/mod/flax/lib.php');
    	flax_update_grades($flax, $userid);
    	    	
    	return true;
    }
    /**
     * This method 
     * @param
     * @return ?
     */
    public function print_report(stdClass $flax, stdClass $obj){
//     	flax_exercise_open_close_check($flax);
    	$score = 0;
    	if($obj->submissions){
    		foreach($obj->submissions as $sub){
    			$score = $score + intval($sub->score);
    		}
    	}
    	$show_correct_answer = false;
    	if($obj->permission_view_all || flax_exercise_open_close_check($flax)){
    		$show_correct_answer = true;
    	}
    	$table        = new html_table();
//     	$table->attributes = array('align' => 'center');
    	$table->head  = array (get_string('exercisename','flax'), get_string('exercisetype','flax'), get_string('exercisemode','flax'), get_string('totalscore','flax'));
    	$table->align = array ('left','left','left','left');
    	$table->data[] = array($flax->name, get_string($flax->activitytype, 'flax'), get_string('exercisemode'.$flax->activitymode, 'flax'), $score);
    	echo html_writer::table($table);
    	
    	if(!$obj->submissions){
    		global $OUTPUT;
    		echo $OUTPUT->box (get_string('nosubmission','flax'), 'generalbox boxwidthwide');
    		
    	}else{
    		global $DB;
//     		$question = $DB->get_record_select(QUESTION_TBL, 'flaxid=?', array($flax->id),'id,content,answer');
    		$search = array();
    		$replace   = array();    		
    		$useranswer = array();
    		$a_sub = reset($obj->submissions);
    		foreach($obj->submissions as $sub){
//     			$useranswer[] = $sub->useranswer;
    			preg_match('#(<sup>\d+</sup>)(.*)#', $sub->useranswer, $matches);
//     			var_dump($matches);
    			$search[] = $matches[1].'__';
    			$replace[] = $matches[1].'<span class="current_attempt_words">'.$matches[2].'</span>';
    		}
    		$question_content = $a_sub->content;
    		$question_answer = $a_sub->answer;
    		
    		$previous_subs = self::read_previous_submissions($flax, $a_sub->userid, $a_sub->accesstime);
    		foreach($previous_subs as $sub){
    			preg_match('#(<sup>\d+</sup>)(.*)#', $sub->useranswer, $matches);
    			$search[] = $matches[1].'__';
    			$replace[] = $matches[1].'<span class="previous_attempt_words">'.$matches[2].'</span>';
    		}
    		
//     		var_dump($search[0]);
//     		strpos($question_content, $seach))
//     		preg_match($search[0], $question_content, $result);
//     		print_object($result);
//     		print_object($seach); print_object($replace);
    		$content_and_answer = str_replace($search, $replace, $question_content);
    		
    			$correct_answer = $show_correct_answer? $question_answer:get_string('hiddenuntilclose','flax');
    			$table        = new html_table();
//     			$table->attributes = array('');
//     			$table->colclasses = array(null, 'mod-flax-'.$this->flax_type.'-highlight-words');
    			$table->align = array ('left','left');
    			$table->data[] = array(get_string('question','flax').'<br />(with your answers filled in, and words from current attempt highlighted)', $content_and_answer);
//     			$table->data[] = array(get_string('youranswer','flax'), implode(', ', $useranswer));
//     			$table->data[] = array(get_string('score','flax'), $score);
    			$table->data[] = array(get_string('correctanswer','flax'), $correct_answer);
    			echo html_writer::table($table);
    	}
    	
    	
    }

    private function read_previous_submissions($flax, $userid, $by_accesstime){
    	global $DB;
    	$params = array('flaxid'=>$flax->id, 'userid'=>$userid, 'accesstime'=>$by_accesstime);
    	$sql = "SELECT
    	s.id, s.useranswer
    	FROM
    	{".SUBMISSION_TBL."} s
    	WHERE
    	s.flaxid = :flaxid AND s.userid = :userid AND s.accesstime < :accesstime";
    	$submissions = $DB->get_records_sql($sql, $params);
    	// 	print_object($params);
//     		print_object($submissions);
    	return $submissions;
    }
}
