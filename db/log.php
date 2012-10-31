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
 * Definition of log events
 *
 * @package    mod
 * @subpackage flax
 * @author flax@cs.waikato.ac.nz
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    // flax instance log actions
    array('module'=>'flax', 'action'=>'view', 'mtable'=>'flax', 'field'=>'name'),
    array('module'=>'flax', 'action'=>'add', 'mtable'=>'flax', 'field'=>'name'),
    array('module'=>'flax', 'action'=>'update', 'mtable'=>'flax', 'field'=>'name'),
    array('module'=>'flax', 'action'=>'delete', 'mtable'=>'flax', 'field'=>'name'),
    array('module'=>'flax', 'action'=>'attempt', 'mtable'=>'flax', 'field'=>'name'),
    array('module'=>'flax', 'action'=>'viewreport', 'mtable'=>'flax', 'field'=>'name'),
);
