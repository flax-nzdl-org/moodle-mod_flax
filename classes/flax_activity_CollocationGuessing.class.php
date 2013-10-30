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

require_once ($CFG -> dirroot . '/mod/flax/locallib.php');
require_once ('classes/flax_base.class.php');
require_once ('classes/flax_base_group_a.class.php');
require_once ('classes/flax_interface.class.php');

/**
 * Activity class for CollocationGuessing - can define custom php methods that apply only to this specific activity
 */
class flax_activity_CollocationGuessing extends flax_base_group_a implements flax_interface {

	protected $flax_type = 'CollocationGuessing';

	/**
	 * Custom process_submission method
	 */
	public function process_submission($flax, $record, $view, $score, $responsecontent) {
		// List of all attempts (incorrect + correct) made by the user, formatted red/green depending on correctness, 
		// will be stored in useranswer attribute field of db
		$al = 'attemptList';	
		if (parent::create_submission_record_with_param($flax, $record, $view, $score, $responsecontent, $al)){
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
		// js function to show/hide the div element that all the collocations used in the quesiton
		echo '<script type="text/javascript">
		function toggle(aElem){
			var qNum = aElem.id.substring(1, aElem.id.length);
			var toggElem = document.getElementById("togg" + qNum);
			if (toggElem.style.display == "block"){
				toggElem.style.display = "none";
				aElem.innerHTML = "show collocations";
			}
			else{
				toggElem.style.display = "block";
				aElem.innerHTML = "hide collocations";
			}
		}
		</script>';

		if (!$obj -> submissions) {
			global $OUTPUT;
			echo $OUTPUT -> box(get_string('nosubmission', 'flax'), 'generalbox boxwidthwide');
			return false;
		}

		$qnum = 0;
		$totscore = 0;
		$maxscore = 0;
		$totmaxscore = 0;
		
		$qtablearray = array(); // temporary storage array for each of the question tables created
		// each table isn't printed as it's created, as global information (e.g. total score) is still being calculated

		foreach ($obj->submissions as $sub) {
			$paramkeys = explode(ARG_SEPARATOR, $sub->paramkeys);
			$paramvalues = explode(ARG_SEPARATOR, $sub->paramvalues);

			$astr = explode(parent::ANS_SEPARATOR, $sub -> useranswer);
			// answer string contains multiple fields of information
			$numattempts = $paramvalues[array_search('attempts', $paramkeys)];
			$maxscore = $paramvalues[array_search('possScore', $paramkeys)];

			$table = new html_table();
			$table -> align = array('left', 'left');
			$table -> data[] = array(get_string('correctanswer', 'flax'), $sub -> answer);
			$table -> data[] = array(get_string('yourattempts', 'flax') . ' (' . $numattempts . ')', $sub->useranswer);
			$table -> data[] = array(get_string('score', 'flax'), $sub -> score . '/' . $maxscore);
			// Displaying all the collocations for each question can take up a lot of space on the report page, so the question's collocations are hidden with a show/hide toggle
			$table -> data[] = array(get_string('question', 'flax'), '<a id="q' . $qnum . '" onclick="toggle(this)" style="cursor: pointer;">
				show collocations</a><div id="togg' . $qnum . '" style="border-top: 1px solid black; display:none; margin-top:5px; padding-top: 5px">' . $sub->content . '</div>');
			$qnum++;
			$totscore += intval($sub -> score);
			$totmaxscore += intval($maxscore);

			array_push($qtablearray, $table);
		}

		parent::print_report_header_with_percent($flax, $obj, $totscore, $totmaxscore);

/*		$percent = ' (' . number_format((($totscore / $totmaxscore) * 100), 0) . '%)';

		// Print report header
		$headtable = new html_table();
		$headtable -> head = array(get_string('exercisename', 'flax'), get_string('exercisetype', 'flax'), get_string('totalscore', 'flax'));
		$headtable -> align = array('left', 'left', 'left');
		$headtable -> data[] = array($flax -> name, get_string($flax -> activitytype, 'flax'), $totscore . '/' . $totmaxscore . $percent);
		echo html_writer::table($headtable);
*/
		// Print report body
		echo '<ol>';
		foreach ($qtablearray as $tab) {
			echo '<li>' . html_writer::table($tab) . '</li>';
		}
	}
}
?>
