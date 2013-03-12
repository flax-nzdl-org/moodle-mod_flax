<?php  // $Id: view.php,
/**
 * 
 * Displays a FLAX exercise at run time. This script calls flax_view.php. 
 * Be aware that the exercise page is embedded in an iframe and sourced from the FLAX server,
 * i.e., the main page and the iframe belong to different domains.  
 * 
 * @author alex.xf.yu@gmail.com
 * 
 **/
$path2thisfile = dirname(__FILE__);

require_once(dirname(dirname($path2thisfile)).'/config.php');
// require_once('../../config.php');
require_once ($CFG->dirroot.'/lib/weblib.php');
require_once($path2thisfile.'/locallib.php');
// require_once($CFG->libdir.'/completionlib.php');

$iframeid = 'flax_iframe_id';
/*
// Flag for displaying content of the proxy iframe 'flax_iframe'
// iframe.src is set at the end of the script (below)
$display_proxy_iframe = optional_param($iframeid, '', PARAM_ALPHA);
if($display_proxy_iframe){
	global $SESSION;
	echo '<html><head>'.
			'<script type="text/javascript" >'.
			'function autoSubmit(){'.
			'document.getElementById("flax_view_form_id").submit();'.
			'}</script>'.
			'</head><body onload="autoSubmit()">'.
			'<form id="flax_view_form_id" name="flax_view" method="POST" action="'.$SESSION->{FLAXURL}.'&mdlflax">'.
			'<input type="hidden" name="s1.params" value="'.$SESSION->{PARAMS}.'"/>'.
			'<input type="hidden" name="s1.moduleParams" value="'.$SESSION->{MODULEPARAMS}.'"/>'.
			'</form>'.
			'</body></html>';
	exit;
}
*/
//Flag for relaying ajax requests between js-client (teacher's interface only - in module.js/M.mod_flax._query_flax() and backend flax server
$flag = optional_param('ajax', '', PARAM_ALPHA);
if($flag == 'listcollactivity'){
	$collection_name = optional_param('c', '', PARAM_ALPHANUM);
	if(!$collection_name){
		flax_debug_log('in view.php, listcollactivity request missing c (collection name) parameter');
		echo ''; exit;
	}
	//Comma separated string
	$activity_list = list_flax_collection_activities($collection_name);
	echo $activity_list;
	exit;
}else 
if($flag == 'queryflax'){

	$data = '';
// 	flax_debug_log(http_build_query($_POST));
	foreach($_POST as $key=>$value){
		//TODO: find out why s1. becomes s1_ in $_POST
		$data .= str_replace('s1_', 's1.', $key) .'='. $value .'&';
	}
	// 	rtrim($data, '&');
	$data .= 'a=pr&ro=1&rt=r&o=xml';

	// $s = optional_param('s', '', PARAM_RAW);
	// $svce = optional_param('s1.service', '', PARAM_RAW);
	// $collname = optional_param('s1.collname', '', PARAM_RAW);
	// $c = optional_param('c', '', PARAM_RAW);
	// $site = optional_param('site', '', PARAM_RAW);
	// $lang = optional_param('lang', '', PARAM_RAW);

	$response = query_flax($data);
	// flax_debug_log('data='.$data);
	// flax_debug_log('response='.$response);
	echo $response;
	exit;
}else 
//Flag for uploading files (teacher's interface only - in module.js/M.mod_flax._upload_flax() and backend flax server
// $flag = optional_param('ajax', '', PARAM_ALPHA);
if($flag == 'uploadflax'){

// flax_debug_log('dump files='.print_r($_FILES, true));
	if($_FILES['file']['error'] > 0){
		echo flax_get_xml_response(false);
		exit;
	}
	$data = '';
	foreach($_POST as $key=>$value){
		$data .= $key .'='. $value .'&';		
	}
	$data .= 'a=pr&ro=1&rt=r&o=xml';
	//Rename the file (was given a random name by php) to its original on flax server
	$data .= '&rename='.$_FILES['file']['name'];
	
	$url = flax_get_full_domain().'?'.$data;
	$file_param = array('file'=>'@'.$_FILES['file']['tmp_name']);
	$response = upload_flax($url, $file_param);

	echo $response;
	exit;
}

// Flag for auto-readjusting the height of the proxy iframe 'flax_iframe' according to its actual content
// This was invoked by Activity.js
$flax_iframe_height = optional_param('iframe_height', 0, PARAM_INT); 
if($flax_iframe_height && $flax_iframe_height != 0){
?>
	<script type="text/javascript">
		var iframe = parent.parent.document.getElementById('<?php echo $iframeid; ?>');
		iframe.height = ''+<?php echo $flax_iframe_height; ?>+'px';
		//console.log('in view.php: '+<?php echo $flax_iframe_height; ?>);
	</script>
<?php exit;	
}

$id = required_param('id', PARAM_INT); // id in the course_modules table
if ($id) {
	if (! $cm = $DB->get_record('course_modules', array('id'=>$id), '*', MUST_EXIST)) {
		print_error('Course Module ID was incorrect id=$id');
	}
	if (! $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST)) {
		print_error('Course is misconfigured id=$cm->course');
	}
	if (! $flax = $DB->get_record('flax', array('id'=>$cm->instance), '*', MUST_EXIST)) {
		print_error('Flax id is incorrect (id=$cm->instance)');
	}
}

global $USER;

require_login($course, false, $cm);
// require_capability('mod/flax:submit', $PAGE->context);

$PAGE->set_url('/mod/flax/view.php', array('id'=>$id));
$PAGE->set_pagelayout('base');
$context = get_context_instance(CONTEXT_MODULE, $id);
$PAGE->set_context($context);

// Title of browser window
$PAGE->set_title(format_string($course->shortname) . ': '.get_string('modulenameplural', 'flax'));

// $coursenode_on_navbar = $PAGE->navigation->find($cm->course, navigation_node::TYPE_COURSE);
// $PAGE->navbar->add('falkjflajlfjlasdf');
// $coursenode_on_navbar->add($flax->name, new moodle_url('/s.php'));

// Heading of the page, in this case, the name of the course
$PAGE->set_heading($course->fullname, 2, 'mod-flax-page-view-course-name');

// $coursenode_on_navbar = $PAGE->navigation->find($course, navigation_node::TYPE_COURSE);
// $coursenode_on_navbar->add($PAGE->, $this->url->out());

// $PAGE->navbar->ignore_active();
// $PAGE->navbar->add('preview', new moodle_url('/a.php'));
// $PAGE->navbar->add('bkag', new moodle_url('/a.php'));

// Before doing anything, check the open/close time of the activity
$accesstime = time();
if ($flax->timeopen && $flax->timeopen > $accesstime) {
	echo $OUTPUT->header();
// 	notice('<div style="text-align:center;">'.get_string('activitynotavailableuntil', 'flax', userdate($flax->timeopen)).'</div>');
	echo $OUTPUT->notification('<div style="text-align:center;">'.get_string('activitynotavailableuntil', 'flax', userdate($flax->timeopen)).'</div>', 'notifysuccess');
	// 	echo $OUTPUT->footer();
	exit;
}
// check that the activity is not closed
if ($flax->timeclose && $flax->timeclose < $accesstime) {
	echo $OUTPUT->header();
// 	notice('<div style="text-align:center;">'.get_string('activityclosedon', 'flax', userdate($flax->timeclose)).'</div>');
	echo $OUTPUT->notification('<div style="text-align:center;">'.get_string('activityclosedon', 'flax', userdate($flax->timeclose)).'</div>', 'notifysuccess');
// 	echo $OUTPUT->footer();
	exit;
}

// This logging contributes to the user_outline report 
flax_log('view', array('cid'=>$course->id, 'cmid'=>$cm->id, 'url'=>'view.php?id=$course->id', 'info'=>'FLAX '.$flax->activitytype.' exercise : '.$flax->name.' attempted'));
// add_to_log($course->id, 'flax', 'view', 'view.php?id=$course->id', 'FLAX '.$flax->activitytype.' exercise : '.$flax->name.' attempted', $cm->id);

// Mark viewed (refer to Activity Completion)
// $completion = new completion_info($course);
// $completion->set_module_viewed($cm);

$PAGE->requires->css('/mod/flax/styles.css');
// $PAGE->requires->js_init_call($function)


// This not only generates the <head> section of the page html,
// it also prints out the navigation bar just below the heading (the course name)
// To control what is printed, you should set properties on $PAGE. (TODO)
echo $OUTPUT->header();


$flax->accesstime = $accesstime;
// print_object($flax->accesstime);

// Make sure the external FLAX server is up and running
query_flax('');

// require_once('autoclassloader.php');
if(flax_is_type_exercise($flax)){
	//
	// Begin processing activity data (composing activity practice page in an iframe)
	//
		
	$act_class_name = flax_get_activity_class_name($flax->activitytype);
	$filename = 'classes/'.flax_get_activity_class_filename($flax->activitytype);
	if(!file_exists($filename)){
		notice('unknown class name: '.$filename.'. Please report to system administrator');
		exit;
	}
	require_once($filename);
	$activity_instance = new $act_class_name($flax, $cm, $course, $context);
	if (!in_array('flax_interface', class_implements($activity_instance))) {
		throw new coding_exception($act_class_name . ' does not implement flax_activity interface');
	}
	
	$view_obj = $activity_instance->view($flax);
	$src = '';
	if($view_obj){
		$src = $view_obj->{FLAXURL}.'&mdlflax&s1.'.PARAMS.'='.$view_obj->{PARAMS}.'&s1.'.MODULEPARAMS.'='.$view_obj->{MODULEPARAMS};
	}
}else{
	$src = $flax->{FLAXURL}.'&mdlsiteurl='.$CFG->wwwroot;
}
if($src){
	?>
	<iframe id='<?php echo $iframeid; ?>' name='<?php echo $iframeid; ?>' src='<?php echo $src;?>' height='600' width='100%' seamless='seamless'  style='overflow:hidden;border:none;'>
	</iframe>
	
	<noscript>
	<div id="noscript">
	Javascript must be enalbed for FLAX activities to function properly.
	</div>
	</noscript>
	<?php
}
// echo $OUTPUT->footer();