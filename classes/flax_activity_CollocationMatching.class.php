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
require_once('classes/flax_base_group_a.class.php');
require_once('classes/flax_interface.class.php');

/**
 * Activity class
 */
class flax_activity_CollocationMatching extends flax_base_group_a implements flax_interface {
	
	protected $flax_type = 'CollocationMatching';
	
	/**
     * This method should 
     * @param
     * @return ?
     */
    public function view($flax){
    	return parent::view($flax);
    }
    /**
     * This method should 
     * @param
     * @return ?
     */
    public function process_submission($flax, $record, $view, $score/*either 1 or 0; converted to int in attepmt.php*/, $responseconent){
    	parent::process_submission($flax, $record, $view, $score, $responseconent);
    }
    /**
     * This method 
     * @param
     * @return ?
     */
    public function print_report(stdClass $flax, stdClass $obj){
    	parent::print_report($flax, $obj);
    }
}
