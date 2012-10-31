<?php  //$Id: upgrade.php
/**
 * @author alex.xf.yu@gmail.com
 * 
 * This file keeps track of upgrades to the flax module 
 */

// 
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

/**
 * flax module upgrade task
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool always true
 */
function xmldb_flax_upgrade($oldversion=0) {
	
//@TODO -  See moodle/lib/adminlib.php/upgrade_activity_modules() and how this xmldb_flax_upgrade is called by the function

    global $CFG, $THEME, $db;

    $result = true;

/// And upgrade begins here. For each one, you'll need one 
/// block of code similar to the next one. Please, delete 
/// this comment lines once this file start handling proper
/// upgrade code.

/// if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
///     $result = result of "/lib/ddllib.php" function calls
/// }

    return $result;
}

?>
