<?php
/**
 * @author xiaofyu@gmail.com
 * 
 * This script defines the capability definitions for the flax module. 
 */

//
// The capabilities are loaded into the database table when the module is
// installed or updated. Whenever the capability definitions are updated,
// the module version number should be bumped up.
//
// The system has four possible values for a capability:
// CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
//
//
// CAPABILITY NAMING CONVENTION
//
// It is important that capability names are unique. The naming convention
// for capabilities that are specific to modules and blocks is as follows:
//   [mod/block]/<component_name>:<capabilityname>
//
// component_name should be the same as the directory name of the mod or block.
//
// Core moodle capabilities are defined thus:
//    moodle/<capabilityclass>:<capabilityname>
//
// Examples: mod/forum:viewpost
//           block/recent_activity:view
//           moodle/site:deleteuser
//
// The variable name for the capability definitions array follows the format
//   $<componenttype>_<component_name>_capabilities
//
// For the core capabilities, the variable is $moodle_capabilities.

/**
 * Plugin capabilities
 *
 * Note: adding a new capability requires reinstalling the module, or a warning message shows
 * 
 * @package    mod_flax
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$capabilities = array(

    // Ability to add a new flax exercise to the course.
    'mod/flax:addinstance' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/course:manageactivities'
    ),

    // Ability to attempt on answering exercise questions. All users, except guests, have this capability
    'mod/flax:submit' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Ability to create flax exercise instances in a course. 
    // We use this in design_activity.php to determine the identity of the logged-on user (teacher or non-teacher)
    'mod/flax:create' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Ability to view own submission report.
    'mod/flax:viewreport' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Ability to view submission report of all users. Every user has the right
    // to view report of their own submissions
    'mod/flax:viewallreport' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // We save this definition and use moodle/grade:viewall (in moodle/lib/db/access.php) instead
    // Ability to grade exercises
    //'mod/flax:grade' => array(
    //
    //        'captype' => 'write',
    //        'contextlevel' => CONTEXT_MODULE,
    //        'archetypes' => array(
    //            'teacher' => CAP_ALLOW,
    //            'editingteacher' => CAP_ALLOW,
    //            'manager' => CAP_ALLOW
    //        )
    //    ),

    // Ability to ignore time restrictions (exercise start/end time) if they are defined
    'mod/flax:ignoredeadlines' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    )
);