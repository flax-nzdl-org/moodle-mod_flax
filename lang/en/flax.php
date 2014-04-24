<?PHP // $Id: flax.php
/**
 * @author alex.xf.yu@gmail.com
 * 
 */

$string['activity_name_default'] = 'FLAX language learning';
$string['activity_name'] = 'Name';
$string['activity_name_error'] = 'Please choose a name';
$string['addflaxtype'] = 'Add to the course';
$string['attempt'] = 'Attempt';
$string['attemptbyat'] = 'Attempt by {$a->fullname} <br /> at {$a->accesstime}';
$string['attemptindex'] = 'Attempt {$a}';
$string['attemptreport'] = 'Attempt report';
$string['attemptreportforuser'] = 'Exercise attempt report for {$a}';
$string['attempts'] = 'Attempts';
$string['configproxyservername'] = 'The domain name of the proxy to the FLAX server';
$string['configproxyserverport'] = 'The port number used by the proxy server';
$string['configproxyusername'] = 'Your username for the proxy server';
$string['configproxypassword'] = 'Your password for the proxy server';
$string['configservername'] = 'The domain name of the FLAX server';
$string['configserverport'] = 'The port number used by the FLAX server';
$string['contentheader'] = 'Content';
$string['correctanswer'] = 'Correct answer';
$string['introlabel'] = 'Description';
$string['description'] = 'Description';
$string['exercisefinished'] = 'Exercise finished';
$string['exerciseclose'] = 'Close exercise';
$string['exerciseclose_help'] = 'If enabled, the time when the exercise is closed';
$string['exercisecontent'] = 'Exercise content';
$string['exercisecontentinvalid'] = 'Content not valid';
$string['exercisedescription'] = 'Exercise description';
$string['exercisedescription_help'] = 'Add text to describe the exercise.';
$string['exerciseopen'] = 'Open exercise';
$string['exerciseopen_help'] = 'If enabled, the time when the exercise is open to the students';
$string['exercisemode'] = 'Exercise mode';
$string['exercisemode_help'] = 'This option specifies whether the exercise is going to be graded. If not, the exercise will remain in practice mode, and students will be able to redo the exercise as many times as they wish.';
$string['exercisemodeg'] = 'Group';
$string['exercisemodei'] = 'Individual';
$string['exercisemodep'] = 'Pair';
$string['exercisename'] = 'Exercise name';
$string['exercisetype'] = 'FLAX exercise type';

//The following strings are used for setting user role permissions
$string['flax:create'] = 'Create';
$string['flax:ignoredeadlines'] = 'Ignore deadlines';
$string['flax:submit'] = 'Submit';
$string['flax:viewreport'] = 'View all report';
$string['flax:viewallreport'] = 'View all report';

$string['flaxlanguageresource'] = 'FLAX language resource';
$string['flaxserverconnectionfailed'] = 'Failed to connect to external FLAX server';
$string['flaxserverhostconfigexplain'] = 'The MoodleFLAX module must communicate with a FLAX server for the module to function. In the following settings, fill up the FLAX server address or accept the defaults.';
$string['grading'] = 'Grading';
$string['gradeweight'] = 'Grade Weighting';
$string['gradeweight_help'] = 'Set the grade weighting for this exercise. e.g. If a value of 10 is selected, the exercise will have a grade range of 0-10. <br>Marks will be scaled according to this range. e.g. Getting a total of 3/5 in an exercise will result in a Grade of 6.00 (on the scale of 0-10)';
$string['gradedexercise'] = 'Graded';
$string['notgradedexercise'] = 'Not graded';

$string['makenewcollection'] = 'Make a new collection';
$string['modifyactivity'] = 'Edit content';
$string['modifyactivity_help'] = 'Click button to edit exercise content. This function is disabled if exercise has been attempted.';
$string['modifyactivitybuttontooltip'] = 'To edit this FLAX language exercise in a new window';
$string['modulename'] = 'FLAX language learning';
$string['modulename_help'] = 'FLAX helps automate the production and delivery of practice exercises for learning English. You, the teacher, can easily create exercises from the textual content of digital libraries. You can also create your own digital library collections.';
$string['modulename_link'] = 'mod/flax/view';
$string['modulenameplural'] = 'FLAX language learning';
$string['name'] = 'Name';
$string['noflaxes'] = 'There are no FLAX activities in this course';
$string['hiddenuntilclose'] = 'Hidden until exercise is closed';
$string['noattempts'] = 'No attempts';
$string['nosubmissions'] = 'No answer submissions during exercise attempt';
$string['permissionviewreport'] = 'You do not have permission to view the report';
$string['pluginadministration'] = 'FLAX administration';
$string['pluginname'] = 'FLAX';
$string['question'] = 'Question';
$string['report'] = 'Report';
$string['reporthidden'] = 'Reports are hidden from students in the course';
$string['score'] = 'Score';
$string['selectactivity'] = 'Select an activity';
$string['selectarticle'] = 'Select what to add';
$string['selectcollection'] = 'Select a collection';
$string['servername'] = 'Server name';
$string['serverport'] = 'Server port';
$string['totalscore'] = 'Total score';
$string['viewreport'] = 'View report';
$string['youranswer'] = 'Your answer';


/*Exercise titles*/
$string['CollocationAlternatives'] = 'Common Alternatives';
$string['CollocationDominoes'] = 'Collocation Dominoes';
$string['CollocationFillinBlanks'] = 'Complete Collocations';
$string['CollocationGuessing'] = 'Collocation Guessing';
$string['CollocationMatching'] = 'Collocation Matching';
$string['ContentWordGuessing'] = 'Word Guessing';
$string['Hangman'] = 'Hangman';
$string['ImageGuessing'] = 'Image Guessing';
$string['MultiChoices'] = 'Multiple Choices';
$string['PredictingWords'] = 'Predicting Words and Phrases';
$string['RelatedWords'] = 'Related Words';
$string['ScrambleParagraph'] = 'Scrambled Paragraphs';
$string['ScrambleSentence'] = 'Scrambled Sentences';
$string['SplitSentences'] = 'Split Sentences';


/*Exercise specific strings*/
/***** CollocationGuessing *****/
$string['yourattempts'] = 'Your attempts';

/***** Content Word Guessing *****/
$string['tableheadinfo'] = 'Your answer to the question is shown below. <br>Hover over any incorrect words (red) to view the correct word';

/***** Hangman *****/
$string['hintnotset'] = 'No hint was set';
$string['hintused'] = 'Hint used';
$string['missedletters'] = 'Answer (letters missed out are shown in red)';
$string['wordcompletion'] = 'Word completed';
$string['wordhint'] = 'Hint of the word';
$string['wrongletters'] = 'Letters you guessed incorrectly';

/***** ImageGuessing *****/
$string['describer'] = 'Describer';
$string['imgpool'] = 'Image pool';
$string['imgpool_help'] = 'Candidate images used in the exercise';
$string['game'] = 'Game {$a}';
$string['gameduration'] = 'Game lasts';
$string['guesser'] = 'Guesser';
$string['guesserimg'] = 'Guesser chooses image';
$string['timeout'] = 'Game timed out';

/***** Scrambled Paragraphs *****/
$string['paraorder'] = 'Paragraph order';

?>
