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
 * Activity class for ContentWordGuessing - can define custom php methods that apply only to this specific activity
 */
class flax_activity_ContentWordGuessing extends flax_base_group_a implements flax_interface {
	
	protected $flax_type = 'ContentWordGuessing';

	/**
	 * Custom process_submission method
	 */
	public function process_submission($flax, $record, $view, $score, $responsecontent) {
		// userAnsStr is the name of the parameter to be stored in the useranswer column instead of the paramkeys/values
		// spelling must match the spelling of the parameter when it is included in the o[RESPONSECONTENT] field 
		// [in LDL.activities.ContentWordGuessingModule.prototype->submitAnswer() in ContentWordGuessing.js
		$uas = 'userAnsStr';
		if (parent::create_submission_record_with_param($flax, $record, $view, $score, $responsecontent, $uas)){
			return true;
		}else{
			flax_debug_log('flax_activity_CollocationGuessing.class.php: Error creating submission record');
			return false;
		}
	}
	
	/**
	 * Custom print_report method
	 */
	public function print_report(stdClass $flax, stdClass $obj) {
		
		if (!$obj -> submissions) {
			global $OUTPUT;
			echo $OUTPUT -> box(get_string('nosubmission', 'flax'), 'generalbox boxwidthwide');
			return false;
		}
		
		// as ContentWordGuessing activity will only have one question, don't need to iterate through each question submission
		
		$sub = reset($obj->submissions);	// reset function returns the first item in the associative submissions array
		$useransarr = explode(",", $sub->useranswer);
		$ansarr = explode(",", $sub->answer);
		$cont = $this->add_user_ans($sub->content, $useransarr, $ansarr);
		
		$table = new html_table();
		$table->head = array(get_string('tableheadinfo', 'flax'));
		$table->align = array('left');
		$table->data[] = array($cont);
		$score = $sub->score;
		$maxscore = $sub->paramvalues;
		
		parent::print_report_header_with_percent($flax, $obj, $score, $maxscore);
		echo html_writer::table($table);
	}

	private function add_user_ans($cont, $useransarr, $ansarr){

		for ($i=0; $i<sizeof($ansarr); $i++){
			if ($useransarr[$i] == $ansarr[$i]){	// word was correct
				$rtext = '<span class="correct">'.$useransarr[$i].'</span>';
			}else{									// word was incorrect
				// mouseover and mouseout events are used so the user can hover over an incorrect word to view the correct word
				$rtext = '<span class="incorrect" onmouseover=toggleAns(this,"'.$ansarr[$i].'","black") onmouseout=toggleAns(this,"'.$useransarr[$i].'","red")>'.$useransarr[$i].'</span>';
			}
			
			$cont = str_replace('_|'.($i+1).'|_', $rtext, $cont);
		}
		?>
		<script type="text/javascript">
			// simple toggle function to change the text and colour of the element
			function toggleAns(el, text, colour){
				el.innerHTML = text;
				el.style.color = colour;
			}			
		</script>
		<style type="text/css">
			span.correct {
				color: #114927;
			}
			span.incorrect {
				color: red;
				cursor: pointer;
			}
			.correct, .incorrect {
				font-weight: bold; 
			}
		</style>
		<?php
		return $cont;
	}
}
?>