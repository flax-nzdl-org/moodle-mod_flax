<?php // $Id: version.php,v 1.0 2009/10/01 15:05:20 $
/**
 * Code fragment to define the version of MoodleFLAX
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author xiao@waikato.ac.nz & mjl61@students.waikato.ac.nz
 * @version $Id: version.php,v 1.0 27/06/2011 $
 * 
 *  MoodleFLAX version | FLAX server version
 * |===================|====================|
 * |                   |                    |
 * |   2.5.1           |    flax2.5         |
 * |                   |                    |
 **/

$module->version    = 2014052300;     // The current module version (Date: YYYYMMDDXX)
$module->requires   = 2011070102.01;  // Requires this moodle version to upgrade from
$module->component  = 'mod_flax';     // full name of the plugin (used for diagnostics)
$module->cron       = 60;             // give as a chance every minute
$module->maturity   = MATURITY_STABLE;
$module->release    = '2.5.1 (Build: 20140523)';
?>