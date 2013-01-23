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
 * Local library class for flax.  These are non-standard functions that are used
 * only by flax, and especially shared by all flax activity classes.
 *
 * @package    mod
 * @subpackage flax
 * @author xiao@waikato.ac.nz
 **/

/** Make sure this isn't being directly accessed */
defined('MOODLE_INTERNAL') || die();

/**
 * This file defines interface of all flax activity classes
 *
 * @package    mod
 * @subpackage flax
 * @author xiao@waikato.ac.nz
 */

defined('MOODLE_INTERNAL') || die();

/**
 * flax_activity interface defines all methods that an individual activity has to implement
 */
interface flax_interface {

    /**
     * Method to display the activity student view
     *
     * @param stdClass $flax flax definition object
     * @return void
     */
    public function view($flax);
    
    /**
     * Invoked in submit.php to process info sent back during attempting an exercise
     * @param unknown_type $flax
     * @param unknown_type $answer
     * @param unknown_type $view
     * @param unknown_type $score
     * @param unknown_type $responseconent
     */
    public function process_submission($flax, $answer, $view, $score, $responseconent);
    
    /**
     * Print report of one attempt (ie, one view), consisting of multiple submissions
     * @param stdClass $flax
     * @param stdClass $obj
     */
    public function print_report(stdClass $flax, stdClass $obj);
}
