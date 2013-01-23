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
 * @author alex.xf.yu@gmail.com
 **/

/** Make sure this isn't being directly accessed */
defined('MOODLE_INTERNAL') || die();

require_once ($CFG->dirroot.'/mod/flax/locallib.php');
require_once('classes/flax_base.class.php');
require_once('classes/flax_base_group_a.class.php');
require_once('classes/flax_interface.class.php');

/**
 * Activity class
 */
class flax_activity_Hangman extends flax_base_group_a implements flax_interface {
	
	protected $flax_type = 'Hangman';

	/**
     * This method should 
     * @param
     * @return ?
     */
//     public function view($flax){
//     	return parent::view($flax);    	
//     }
    /**
     * This method should 
     * @param
     * @return ?
     */
    public function process_submission($flax, $record, $view, $score/*either 1 or 0; converted to int in attepmt.php*/, $responseconent){
    	global $DB, $CFG;
    	
    	$score = clean_param($score, PARAM_INT);
    	$record->finished = YES;
    	if(!$DB->update_record(FINISH_TBL, $record)){
    		error_log('failed to update finish table record');
    		return false;
    	}
    	preg_match_all("/([^\\\\]+)\\\\([^\\\\]+)/", $responseconent, $pairs);
    	$results = array_combine($pairs[1], $pairs[2]);
    	$answer_feedback = $results['answerfeedback'];
    	$wl = 'wrongletters'; $hu = 'hintused'; $wc = 'wordcompletion';
    	$paramkeys = $wl.ARG_SEPARATOR.$hu.ARG_SEPARATOR.$wc; 
    	$paramvalues = $results[$wc].ARG_SEPARATOR.$results[$hu].ARG_SEPARATOR.$results[$wl];
    	$sub_record = parent::create_submission_record($flax, $record->questionid, $view, $score, $answer_feedback, $paramkeys, $paramvalues);
    	parent::update_view_record($view, $sub_record->id, $score);
    	
    	require_once ($CFG->dirroot.'/mod/flax/lib.php');
    	flax_update_grades($flax, $view->userid);
    	    	
    	return true;
    }
    /**
     * This method 
     * @param
     * @return ?
     */
    public function print_report(stdClass $flax, stdClass $obj){
    	$table = parent::print_report_header($flax, $obj);
    	if(!$obj->submissions){
    		global $OUTPUT;
    		echo $OUTPUT->box (get_string('nosubmission','flax'), 'generalbox boxwidthwide');
    		 
    	}else{
    		echo '<ol>';
    		foreach($obj->submissions as $sub){
    			$paramvalues = explode(ARG_SEPARATOR, $sub->paramvalues);
    			$wordcompletion = $paramvalues[0];
    			$user_helped_by_hint = $paramvalues[1];
    			$user_guessed_wrong_letters = $paramvalues[2];
    			echo '<li>';
    			$table = new html_table();
//     			$table->colclasses = array(null, 'highlight-target-words');
    			$table->align = array ('left','left');
    			$table->data[] = array(get_string('score','flax'), $sub->score);
    			$table->data[] = array(get_string('wordcompletion','flax'), $wordcompletion);
    			$table->data[] = array(get_string('missedletters','flax'), $sub->useranswer);
    			$table->data[] = array(get_string('wrongletters','flax'), $user_guessed_wrong_letters);
    			$table->data[] = array(get_string('hintused','flax'), $user_helped_by_hint);
    			$table->data[] = array(get_string('wordhint','flax'), $sub->content);
    			echo html_writer::table($table);
    			echo '</li>';
    		}
    		echo '</ol>';
    	}
    }
}
