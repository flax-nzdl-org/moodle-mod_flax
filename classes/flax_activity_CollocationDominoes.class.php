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
 * @author xiao@waikato.ac.nz & mjl61@waikato.ac.nz
 **/

/** Make sure this isn't being directly accessed */
defined('MOODLE_INTERNAL') || die();

require_once ($CFG->dirroot.'/mod/flax/locallib.php');
require_once('classes/flax_base.class.php');
require_once('classes/flax_base_group_a.class.php');
require_once('classes/flax_interface.class.php');

/**
 * Activity class for CollocationDominoes - can define custom php methods that apply only to this specific activity
 */
class flax_activity_CollocationDominoes extends flax_base_group_a implements flax_interface {
	
	protected $flax_type = 'CollocationDominoes';

    /**
     * Custom print_report method
     */
    public function print_report(stdClass $flax, stdClass $obj){
		parent::print_report_header($flax, $obj);
		
		if(!$obj->submissions){
    		global $OUTPUT;
    		echo $OUTPUT->box (get_string('nosubmission','flax'), 'generalbox boxwidthwide');
    	}else{		
    		echo '<ol>';
    		foreach($obj->submissions as $sub){
				$ansarray = $this->format_answers($sub->useranswer, $sub->answer);
				$table = new html_table();
				$table->align = array ('left','left');
				$table->head = array (get_string('youranswer','flax'), get_string('correctanswer','flax'));
    			$table->data[] = array($ansarray[0], $ansarray[1]);
    			echo html_writer::table($table);
    		}
    		echo '</ol>';
    	}
    }
	
	/*  Following is a bit of a hacky work-around that applies just to the Dominoes report.
	
		Due to the nature of how the Dominoes teacher's interface works, there is no 
		easy way to store the mid words [if they exist] of each of the collocations used in 
		an activity if it is created for Test Mode (the Moodle implementation of a Test
		Mode activity requires the activity answers to be sent/stored when the activity is 
		created, and then these are consequently displayed in this report_attempt.)
		
		To send the answers, the design_module creates the answers from the chosenWordArray
		from the teacher's interface object. However, this only keeps track of the target start
		and end words (which in a lot of cases is the only words used), but if there are any
		mid words used by any of the collocations, these are not kept track of. Consequently
		when the answers are displayed in the report, there are no mid words displayed.
		
		To determine if any mid words are missing and should be displayed, the below function
		looks at the useranswer string that is saved when the user finishes the activity, (this
		will contain the mid words if they exist as it is taken from the student's interface
		which naturally displays the mid words). The function essentially determines if a mid 
		word exists for a collocation, extracts that from the useranswer string, and inserts it 
		in the correct place in the correctanswer string.
		
		The function also adds some colour coding to the user's answers; a correct domino will
		be coloured green and an incorrect domino coloured red. (Could have been done client
		side with the sending of the user's answers, but that was taken out to reduce complexity
		in the already hacky function [e.g becomes harder to parse out the desired text when 
		there are extra html tags in the way])
	 */
	private function format_answers($userans, $corrans){
		$usertextarray = $this->extract_text($userans, '<p>', '</p>');
		$corrtextarray = $this->extract_text($corrans, '<p>', '</p>');
		
		$startspancorr = '<span style="color:#114927">';
		$startspanincorr = '<span style="color: red">';
		$endspan = '</span>';

		for ($i=0; $i<count($usertextarray); $i++){
			$splitusertext = array_values(array_filter(explode(' ', $usertextarray[$i])));
			$splitcorrtext = array_values(array_filter(explode(' ', $corrtextarray[$i])));
			
			$cindex = strpos($corrans, $corrtextarray[$i]);
			if (count($splitusertext) == 3){
				$midtext = $splitusertext[1];
				$lenfirstword = strpos($corrtextarray[$i], ' ');
				$newindex = $cindex+$lenfirstword;
				$corrans = $this->insert_string($corrans, $newindex, ' '.$midtext);
			}
			
			$sindex = strpos($userans, $usertextarray[$i]);
			if ($splitusertext[0] == $splitcorrtext[0] && $splitusertext[count($splitusertext)-1] == $splitcorrtext[count($splitcorrtext)-1]){
				$userans = $this->insert_string($userans, $sindex, $startspancorr);
			}else{
				$userans = $this->insert_string($userans, $sindex, $startspanincorr);
			}
			$eindex = strpos($userans, '</p>', $sindex);
			$userans = $this->insert_string($userans, $eindex, $endspan);		
		}
		return array($userans, $corrans);
	}
	
	// adapted from code from Justin Cook: http://www.justin-cook.com/wp/2006/03/31/php-parse-a-string-between-two-strings/
	private function extract_text($string, $start, $end){
		$textarray = array();
		$sindex = 0;
		while ($sindex<strlen($string)){
			$ini = strpos($string, $start, $sindex);
			$ini += strlen($start);
			$len = strpos($string, $end, $ini) - $ini;
			array_push($textarray, substr($string, $ini, $len));
			$sindex = $ini + $len + strlen($end);
		}
		return $textarray;
	}
	
	// insert a substring [text] into a string at a given index
	private function insert_string($string, $index, $text){
		return substr($string, 0, $index).$text.substr($string, $index);
	}
}