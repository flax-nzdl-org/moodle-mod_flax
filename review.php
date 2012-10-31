<?PHP // $Id: review.php Exp $
/**
 * @author alex.xf.yu@gmail.com
 * 
 * Prints a review of a particular flax activity for a particular user.
 * This script is called in index.php
 */
    require_once('../../config.php');
    require_once ($CFG->dirroot.'/mod/flax/lib.php');
    
    global $DB;

    $fid = required_param('fid', PARAM_INT); // flax ID
    $uid = required_param('uid', PARAM_INT); // user ID
	$attempted = optional_param('attempted', 0, PARAM_INT); // how many attempts on this flax instance

	if (! $flax = $DB->get_record('flax', array('id'=>$fid), '*', MUST_EXIST)) {
		print_error('Course module is incorrect');
	}
	if (! $course = $DB->get_record('course', array('id'=>$flax->course), '*', MUST_EXIST)) {
		print_error('Course is misconfigured');
	}
	if (! $cm = get_coursemodule_from_instance('flax', $flax->id, $course->id)) {
		print_error('Course Module ID was incorrect');
	}

    require_login($course, false, $cm);

    $is_student = false;
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    if (!has_capability('mod/flax:viewreport',$context)) {
        //This is a student, check his id
        if ($uid != $USER->id) {
            print_error('This is not your attempt!');
        }
        $is_student = true;
    }

// Print the page header
    $strmodulenameplural = get_string('modulenameplural', 'flax');
    $strmodulename  = get_string('modulename', 'flax');

    $title = format_string($course->shortname) . ': '.$flax->name;
    $heading = $course->fullname;

    $navigation = build_navigation('', $cm);
    $button = update_module_button($cm->id, $course->id, $strmodulename);
    print_header($title, $heading, $navigation, '', '', true, $button, navmenu($course, $cm));
    print '<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>'; // for overlib
    $OUTPUT->heading($flax->name, 2, 'main', '');


    $print_fn = 'flax_print_report_'.$flax->activitytype;
    if(!function_exists($print_fn)){
    	$print_fn = 'flax_print_report_common';
    }
    $print_fn($flax, $uid, $is_student);

    print_footer($course);
    
    //
    // Executalbe script stops here
    //
    
///////////////////////////
//    functions used for reports
///////////////////////////
function flax_print_report_common($flax, $uid, $is_student) {
	global $DB;
    $response = $DB->get_record(TABLE_FLAX_RESPONSES, array('flax'=>$flax->id, 'userid'=>$uid));
    if (!$response) {
        notice('<div style="text-align:center;">'.get_string('reportnotavailable', 'flax').'</div>');
	    return;
    }
	$question = $DB->get_record(TABLE_FLAX_QUESTIONS, array('id'=>$response->questionid));
    if (!$question) {
        notice('<div style="text-align:center;">'.get_string('reportnotavailable', 'flax').'</div>');
		return;
    }

    if($is_student == true) {
	    if($flax->timeclose && $flax->timeclose > time()) {
		    $crtanswer = get_string("noanswerbeforeclose", "flax", userdate($flax->timeclose));
	    } else {
		    $crtanswer = $question->questionanswer;
	    }
    } else {
	    // this is a teacher: always show answer
	    $crtanswer = $question->questionanswer;
    }

    // start table
    print_simple_box_start("center", "80%", "#ffffff", 0);
    print '<table width="100%" border="1" valign="top" align="center" cellpadding="2" cellspacing="2" class="generaltable">'."\n";

    // print activity type
    print '<tr><th align="right" width="15%" valign="top" class="generaltableheader" scope="row">'.format_text(get_string('activitytype', 'flax')).'</th>
	<td colspan="" class="generaltablecell">'.format_text(get_string($flax->activitytype, 'flax')).'</td></tr>'."\n";

    // print score
    print '<tr><th align="right" width="15%" valign="top" class="generaltableheader" scope="row">'.format_text(get_string('score', 'flax')).'</th>
	<td colspan="" class="generaltablecell">'.format_text($response->score).'</td></tr>'."\n";

    // print your answer
    print '<tr><th align="right" width="15%" valign="top" class="generaltableheader" scope="row">'.format_text(get_string('youranswer', 'flax')).'</th>
	<td colspan="" class="generaltablecell">'.$response->responsecontent.'</td></tr>'."\n";

    // print correct answer
    print '<tr><th align="right" width="15%" valign="top" class="generaltableheader" scope="row">'.format_text(get_string('wordstopredict', 'flax')).'</th>
	<td colspan="" class="generaltablecell">'.format_text($crtanswer).'</td></tr>'."\n";

    // print question text
    print '<tr><th align="right" width="15%" valign="top" class="generaltableheader" scope="row">'.format_text(get_string('question', 'flax')).'</th>
	<td class="generaltablecell">'.$question->questioncontent.'</td></tr>'."\n";

    // finish table
    print "</table>\n";
    print_simple_box_end();
}
function flax_print_report_PredictingWords($flax, $uid, $is_student) {
	// 'PredictingWords' has the report format similar to ContentWordGuessing
	flax_print_report_ContentWordGuessing($flax, $uid, $is_student);
}
function flax_print_report_ContentWordGuessing($flax, $uid, $is_student) {
	global $DB;
    $response = $DB->get_record(TABLE_FLAX_RESPONSES, array('flax'=>$flax->id, 'userid'=>$uid));
    if (!$response) {
        notice('<div style="text-align:center;">'.get_string('reportnotavailable', 'flax').'</div>');
	    return;
    }
	$question = $DB->get_record(TABLE_FLAX_QUESTIONS, array('id'=>$response->questionid));
    if (!$question) {
        notice('<div style="text-align:center;">'.get_string('reportnotavailable', 'flax').'</div>');
		return;
    }

    if($is_student == true) {
	    if($flax->timeclose && $flax->timeclose > time()) {
		    $crtanswer = get_string("noanswerbeforeclose", "flax", userdate($flax->timeclose));
	    } else {
		    $crtanswer = $question->questionanswer;
	    }
    } else {
	    // this is a teacher: always show answer
	    $crtanswer = $question->questionanswer;
    }

    // start table
    print_simple_box_start("center", "80%", "#ffffff", 0);
    print '<table width="100%" border="1" valign="top" align="center" cellpadding="2" cellspacing="2" class="generaltable">'."\n";

    // print activity type
    print '<tr><th align="right" width="15%" valign="top" class="generaltableheader" scope="row">'.format_text(get_string('activitytype', 'flax')).'</th>
	<td colspan="" class="generaltablecell">'.format_text(get_string($flax->activitytype, 'flax')).'</td></tr>'."\n";

    // print score
    print '<tr><th align="right" width="15%" valign="top" class="generaltableheader" scope="row">'.format_text(get_string('score', 'flax')).'</th>
	<td colspan="" class="generaltablecell">'.format_text($response->score).'</td></tr>'."\n";

    // print your answer
    print '<tr><th align="right" width="15%" valign="top" class="generaltableheader" scope="row">'.format_text(get_string('youranswer', 'flax')).'</th>
	<td colspan="" class="generaltablecell">'.$response->responsecontent.'</td></tr>'."\n";

    // print correct answer
    print '<tr><th align="right" width="15%" valign="top" class="generaltableheader" scope="row">'.format_text(get_string('wordstopredict', 'flax')).'</th>
	<td colspan="" class="generaltablecell">'.format_text($crtanswer).'</td></tr>'."\n";

    // print question text
    print '<tr><th align="right" width="15%" valign="top" class="generaltableheader" scope="row">'.format_text(get_string('question', 'flax')).'</th>
	<td class="generaltablecell">'.format_text($question->questioncontent).'</td></tr>'."\n";


    // finish table
    print "</table>\n";
    print_simple_box_end();
}
function flax_print_report_ScrambleSentence($flax, $uid, $is_student) {
    // start table
    print_simple_box_start('center', '80%', '#ffffff', 0);
    print '<table width="100%" border="1" valign="top" align="center" cellpadding="2" cellspacing="2" class="generaltable">'."\n";

    $value = flax_print_report_summary_ScrambleSentence($flax, $uid);

    print $value;
    // finish table
    print '</table>';
    print_simple_box_end();

    //Print report details
    flax_print_report_detail_ScrambleSentence($flax, $uid, $is_student);

}
function flax_print_report_summary_ScrambleSentence($flax, $uid) {
	$v = '';
	$names = array('activitytype', 'totalquestions', 'finishedquestions', 'score');

    foreach ($names as $n) {
    	$name = get_string($n, 'flax');
    	switch($n) {
    		case 'activitytype':
    			$value = get_string($flax->activitytype, 'flax');
    			break;
    		case 'totalquestions':
    			$value = required_param('allquestion', PARAM_INT);
    			break;
    		case 'finishedquestions':
    			if($flax->gradescale && $flax->gradescale > 0){
    				$value = required_param('donequestion', PARAM_INT);
    	        } else {
    	        	$value = '(Practice mode exercise)';
    	        }
    			break;
    		case 'score':
    			$value = required_param('score', PARAM_ALPHANUM);

    			break;
            default:
                $value = 'Unknown: '.$n;
    	}
		$v .= '<tr><th align="right" width="50%" class="generaltableheader" scope="row">'.format_text($name).
				'</th><td class="generaltablecell">'.format_text($value).'</td></tr>';
    }
    return $v;
}
function flax_print_report_detail_ScrambleSentence($flax, $uid, $is_student=true) {

	global $DB;
    $items = array();//an array of objects
    $idx = 0;
    
    $closed_questions_flag = '0';
    $mode = ($flax->gradescale && $flax->gradescale > 0)? 0 : 1 ;
	if($mode == 0) {//grading mode (never close for practice mode)
		$closed_questions_flag = '1';
	}
    // get responses for this flax and this user
    //sort by id;
    $responses = $DB->get_records_select(TABLE_FLAX_RESPONSES, 'flax=? AND userid=? AND closed=?', array($flax->id, $uid, $closed_questions_flag), 'id', 'id,questionid,responsecontent,score');
    if (!$responses) {
        error_log('responses is null in flax_print_report_detail_ScrambleSentence() in review.php');
        notice('<div style="text-align:center;">'.get_string('reportnotavailable', 'flax').'</div>');
        exit;
    }
    $questions = $DB->get_records_select(TABLE_FLAX_QUESTIONS, 'flax=?', array($flax->id), '', 'id,questioncontent,questionanswer');//returns array with id as the array key 
    if ($responses) {
	    
	    foreach ($responses as $response) {
		    if(!$response->responsecontent || empty($response->responsecontent) || $response->responsecontent == 'Not attempted yet'){
			    // The user hasn't attempted this question yet. Ignore.
			    continue;
		    }
		    
		    $item = new stdClass();
		    $idx ++;
		    $item->index = $idx;
		    $item->question_name = get_string('question', 'flax');
		    $item->question_value = $questions[$response->questionid]->questioncontent;
		    $item->score_name = get_string('score', 'flax');
		    $item->score_value = $response->score;
		    $item->youranswer_name = get_string('youranswer', 'flax');
		    $item->youranswer_value = $response->responsecontent;
		    
		    $item->correctanswer_name = get_string('correctanswer', 'flax');
		    
		    if($is_student == true) {
			    if($flax->timeclose && $flax->timeclose > time()) {
				    $crtanswer = get_string('noanswerbeforeclose', 'flax', userdate($flax->timeclose));
			    } else {
				    $crtanswer = $questions[$response->questionid]->questionanswer;
			    }
		    } else {
			    // this is a teacher: always show answer
			    $crtanswer = $questions[$response->questionid]->questionanswer;
		    }
		    $item->correctanswer_value = $crtanswer;
		    
		    array_push($items, $item);
	    }
    }
    // start table
    print_simple_box_start('center', '80%', '#ffffff', 0);
    print '<table width="100%" border="1" valign="top" align="center" cellpadding="2" cellspacing="2" class="generaltable">'."\n";

    foreach ($items as $item) {

		print '<th align="left" colspan="200">'.format_text($item->index).'</th>';

		// print score
		print '<tr><th align="right" width="15%" class="generaltableheader" scope="row">'.format_text($item->score_name).'</th>
				<td colspan="" class="generaltablecell">'.format_text($item->score_value).'</td></tr>'."\n";
		// print question text
		print '<tr><th align="right" width="15%" class="generaltableheader" scope="row">'.format_text($item->question_name).'</th>
				<td colspan="" class="generaltablecell">'.format_text($item->question_value).'</td></tr>'."\n";
		// print your answer
		print '<tr><th align="right" width="15%" valign="top" class="generaltableheader" scope="row">'.format_text($item->youranswer_name).'</th>
				<td colspan="" class="generaltablecell">'.format_text($item->youranswer_value).'</td></tr>'."\n";
		// print correct answer
		print '<tr><th align="right" width="15%" valign="top" class="generaltableheader" scope="row">'.format_text($item->correctanswer_name).'</th>
				<td colspan="" class="generaltablecell">'.format_text($item->correctanswer_value).'</td></tr>'."\n";
    }

    // finish table
    print '</table>\n';
    print_simple_box_end();
}
//////////////////////////////////////////////////////////////////////
/////////////////End of functions used for reports
//////////////////////////////////////////////////////////////////////
?>
