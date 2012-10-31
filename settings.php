<?php  //$Id: settings.php Exp $
/**
 * flax module admin settings and defaults - domain name and port number of the backend FLAX server
 *
 * @package    mod
 * @subpackage flax
 * @author xiaofyu@gmail.com
 * 
 * */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
require_once ($CFG->dirroot.'/mod/flax/locallib.php');

$settings->add(new admin_setting_heading('flax_server_heading', 
					 get_string('flaxserverhostconfigexplain', 'flax'), ''));

$settings->add(new admin_setting_configtext('flax/'.FLAX_SERVER_NAME, get_string('servername', 'flax'),
                   get_string('configservername', 'flax'), DEFAULT_FLAX_SERVER, PARAM_TEXT));

$settings->add(new admin_setting_configtext('flax/'.FLAX_SERVER_PORT, get_string('serverport', 'flax'),
                   get_string('configserverport', 'flax'), DEFAULT_FLAX_PORT, PARAM_INT));
}
?>
