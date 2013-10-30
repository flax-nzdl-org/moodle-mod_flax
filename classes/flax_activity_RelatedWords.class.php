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
 * Activity class for RelatedWords - can define custom php methods that apply only to this specific activity
 */
class flax_activity_RelatedWords extends flax_base_group_a implements flax_interface {
	
	protected $flax_type = 'RelatedWords';

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
    			$table = new html_table();
    			$table->align = array ('left','left');
				$table->head = array (get_string('youranswer', 'flax'), get_string('correctanswer', 'flax'));
    			$table->data[] = array($sub->useranswer, $sub->answer);
    			echo html_writer::table($table);
    		}
    		echo '</ol>';
    	}
    }
}
