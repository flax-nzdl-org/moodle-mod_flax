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
 * Activity class for ImageGuessing - can define custom php methods that apply only to this specific activity
 */
class flax_activity_ImageGuessing extends flax_base implements flax_interface {
	
	protected $flax_type = 'ImageGuessing';

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
    	
    	$mdl_site_id = flax_get_mdl_site_id();
    	$activity_specific_params = array(COURSEID=>$COURSE->id, COURSEPAGEURL=>$CFG->wwwroot.'/course/view.php');
    	parent::view_graded_exercise($flax, $view->id, $mdl_site_id, $user_exercise_score, 
    			$recordid, $activity_specific_params);
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
     * Refer to ImageGuessing.js, only correct answers are sent back as submissions
     * This method should 
     * @param record flax_user_finish table record
     * @return ?
     */
    public function process_submission($flax, $record, $view, $score/*either 1 or 0; converted to int in attepmt.php*/, $responseconent){
    	global $CFG, $DB;
    	$finish  = optional_param(COMPLETE, '', PARAM_TEXT);
    	if($finish){
    		$record->finished = YES;
    		$DB->update_record(FINISH_TBL, $record);
    	}
    	$score = clean_param($score, PARAM_INT);
  		$info = '';
    	$sub_record = parent::create_submission_record($flax, $record->questionid, $view, $score, $responseconent, $info);
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
    	global $OUTPUT;
    	flax_exercise_open_close_check($flax);
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
    		echo $OUTPUT->box (get_string('nosubmission','flax'), 'generalbox boxwidthwide');
    		return;
    	}
    	
    	global $DB;
    	$question = $DB->get_record_select(QUESTION_TBL, 'flaxid=?', array($flax->id),'id,content');
    	$img_arr = array();
    	$content_arr = explode(PARAM_ARG_SEPARATOR, $question->content);
    	foreach($content_arr as $img){
    		$nv = explode(PARAM_NV_SEPARATOR, $img);
    		$img_arr[$nv[0]] = $nv[1];
    	}
    	
    	//Display the pool of images used in the exercise
    	echo $OUTPUT->box_start('generalbox boxwidthwide');
    	echo $OUTPUT->heading_with_help(get_string('imgpool','flax'), 'imgpool', 'flax');
    	echo '<hr />';
    	foreach($img_arr as $img){
    		echo '<img src="'.$img.'"/>';
    	}
    	echo $OUTPUT->box_end();

    	// List all submissions of the exercise
    	$i = 1;
    	foreach($obj->submissions as $sub){
    		
    		// Display user's answer info on a particular submission
    		$user_answer_info = array();
    		// See ImageGuessing.js/getResponsecontent() for details of what's contained in useranswer
    		$uanswer = explode(PARAM_ARG_SEPARATOR, $sub->useranswer);
    		foreach($uanswer as $ua){
    			$nv = explode(PARAM_NV_SEPARATOR, $ua);
    			$user_answer_info[$nv[0]] = $nv[1];
    		}
    		echo get_string('game', 'flax', $i);
    		echo '<hr/>';
    		$table        = new html_table();
    		$guesser_img = $user_answer_info['guesserimg']=='timeout'? get_string('timeout','flax') : self::get_img_html($img_arr[$user_answer_info['guesserimg']]);
    		$table->head  = array (get_string('guesser','flax'), get_string('describer','flax'), get_string('guesserimg','flax'),
    				get_string('correctanswer','flax'), get_string('gameduration','flax'), get_string('score','flax'));
//     		$table->attributes = array('class'=>'image_guessing_submission_row');
    		$table->align = array ('left','left','left','left','left','left');
    		$table->data[] = array($user_answer_info[get_string('guesser','flax')], $user_answer_info[get_string('describer','flax')], $guesser_img,
    				self::get_img_html($img_arr[$user_answer_info['answer_id']]), $user_answer_info['game_duration'], $score);
    		echo html_writer::table($table);
    		
    		echo $OUTPUT->box_start('generalbox boxwidthwide');
    		echo '<h6 style="text-decoration:underline;">Conversation between Guesser and Describer</h6>';
    		echo $user_answer_info['conversation'];
    		echo $OUTPUT->box_end();
    		
    		$i++;
    	}
    }
    private function get_img_html($src){
    	return '<img src="'.$src.'" height="40px" width="30px"/>';
    }
}
