<?php  // $Id: report.php
/**
 * @author xiaofyu@gmail.com
 * 
 * This script defines displays an 'Overview' activity report of the flax module, presented as a big html table.
 */
 
class flax_report extends flax_default_report {

	function display(&$flax, &$cm, &$course, &$users, &$attempts, &$questions, &$options) {
		$tables = array();
		$this->create_overview_table($flax, $cm, $course, $users, $attempts, $questions, $options, $tables);
		$this->print_report($course, $flax, $tables, $options);
		return true;
	}
	function create_overview_table(&$flax, &$cm, &$course, &$users, &$attempts, &$questions, &$options, &$tables) {
		global $CFG;

		$strtimeformat = get_string('strftimedatetime');
		$is_html = ($options['reportformat']=='htm');
		$spacer = $is_html ? '&nbsp;' : ' ';
		$br = $is_html ? "<br />\n" : "\n";
		$table = null;
		// initialize $table
		unset($table);
		$table->border = 1;
		$table->width = "100%";
		$table->head = array();
		$table->align = array();
		$table->size = array();
		if ($is_html) {
			$table->head[] = $spacer;
			$table->align[] = 'center';
			$table->size[] = 10;
		}
		array_push($table->head,
			get_string("name"),
			flax_grade_heading($flax, $options),
			get_string("lastattempttime", "flax"),
			get_string("report", "flax")
		);
		array_push($table->align, "center", "center", "center", "center", "center", "center");
		array_push($table->size, "*", "*", "*", "*", "*", "*");

		// here, $users has been selected as 'allparticipants'
		//(allparticipants-anyone who is allowed to access this flax; allusers-anyone who has accessed this flax)
		foreach ($users as $user) {
			// shortcut to user info held in first attempt record
			$u = &$user->attempts[0];
			$picture = '';
			$name = fullname($u);
			if ($is_html) {
				$picture = print_user_picture($u->userid, $course->id, $u->picture, false, true);
				$name = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$u->userid.'&amp;course='.$course->id.'">'.$name.'</a>';
			}

			$userscore = flax_get_user_score($flax, $u->userid);

			$grade = ($flax->gradescale > 0)?$userscore : get_string("practicemode", "flax");
			$data = array();

			if ($is_html) {
				$data[] = $picture;
			}
			array_push($data, $name, $grade);

			//get the very last time the user attempted on this flax
			foreach ($user->attempts as $count=>$attempt) {
				$lastattempttime = $attempt->accesstime;
				if($lastattempttime < $attempt->accesstime) {
					$lastattempttime = $attempt->accesstime;
				}
			}

			array_push($data, trim(userdate($lastattempttime, $strtimeformat)));
			
			// A link to the 'Overview' report - which is, for now, the only report plugin the flax module has
            $report = flax_get_overview_report_link_html($flax->id, $u->userid, $userscore);
            
			array_push($data, $report);

			$table->data[] = $data;
			$table->data[] = 'hr';
		} // end foreach $user

		// remove final 'hr' from data rows
		array_pop($table->data);

		$tables[] = &$table;
	}
	function deleteform_javascript() {
		// alert string which is used by deleting selected attempts.
		$strselectattempt = addslashes(get_string('selectattempt','flax'));
		return <<<END_OF_JAVASCRIPT
<script type="text/javascript">
<!--
function deletecheck(p, v, x) {
	var r = false; // result
	// get length of form elements
	var f = document.getElementById('deleteform');
	var l = f ? f.elements.length : 0;
	// count selected items, if necessary
	if (!x) {
		x = 0;
		for (var i=0; i<l; i++) {
			var obj = f.elements[i];
			if (obj.type && obj.type=='checkbox' && obj.checked) {
				x++;
			}
		}
	}
	// confirm deletion
	var n = navigator;
	if (x || (n.appName=='Netscape' && parseInt(n.appVersion)==2)) {
		r = confirm(p);
		if (r) {
			f.del.value = v;
		}
	} else {
		alert('$strselectattempt');
	}
	return r;
}
//-->
</script>
END_OF_JAVASCRIPT
;
	} // end function
} // end class

?>
