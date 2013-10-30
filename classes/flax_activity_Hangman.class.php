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
 * @author alex.xf.yu@gmail.com @ mjl61.waikato.ac.nz
 **/

/** Make sure this isn't being directly accessed */
defined('MOODLE_INTERNAL') || die();

require_once ($CFG->dirroot.'/mod/flax/locallib.php');
require_once('classes/flax_base.class.php');
require_once('classes/flax_base_group_a.class.php');
require_once('classes/flax_interface.class.php');

/**
 * Activity class for Hangman - can define custom php methods that apply only to this specific activity
 */
class flax_activity_Hangman extends flax_base_group_a implements flax_interface {
	
	protected $flax_type = 'Hangman';

    /**
     * Custom process_submission method
	 */
    public function process_submission($flax, $record, $view, $score, $responsecontent){
		$af = 'answerfeedback';
		if (parent::create_submission_record_with_param($flax, $record, $view, $score, $responsecontent, $af)){
			return true;
		}else{
			flax_debug_log('Error creating submission record in flax_activity_Hangman.class.php');
			return false;
		}
    }
    /**
     * Custom print_report method
     */
    public function print_report(stdClass $flax, stdClass $obj){
    	$table = parent::print_report_header($flax, $obj);
    	if(!$obj->submissions){
    		global $OUTPUT;
    	    echo $OUTPUT->box (get_string('nosubmission','flax'), 'generalbox boxwidthwide');
    		 
    	}else{
    		echo '<ol>';
    		foreach($obj->submissions as $sub){
    			$paramkeys = explode(ARG_SEPARATOR, $sub->paramkeys);
    			$paramvalues = explode(ARG_SEPARATOR, $sub->paramvalues);
				
				$hintused = $paramvalues[array_search('hintused', $paramkeys)];
				$wordcompletion = $paramvalues[array_search('wordcompletion', $paramkeys)];
				$wrongletters = $paramvalues[array_search('wrongletters', $paramkeys)];
				
				$hinttext = $sub->content;
				if ($hinttext == 'HINT_NOT_SET'){
					$hinttext = get_string('hintnotset', 'flax');
				}
				
    			echo '<li>';
    			$table = new html_table();

    			$table->align = array ('left','left');
    			$table->data[] = array(get_string('score','flax'), $sub->score);
    			$table->data[] = array(get_string('wordcompletion','flax'), $wordcompletion);
   				$table->data[] = array(get_string('missedletters','flax'), $sub->useranswer);
    			$table->data[] = array(get_string('wrongletters','flax'), $wrongletters);
    			$table->data[] = array(get_string('hintused','flax'), $hintused);
    			$table->data[] = array(get_string('wordhint','flax'), $hinttext);
				
    			echo html_writer::table($table);
    			echo '</li>';
    		}
    		echo '</ol>';
    	}
    }
}
