<?php
/**
 * @author alex.xf.yu@gmail.com
 * 
 * Displays a form in which a FLAX exercise can be added by invoking a separate FLAX exercise design window.  
 * 
 **/

defined('MOODLE_INTERNAL') || die();

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once ($CFG->dirroot.'/mod/flax/locallib.php');

class mod_flax_mod_form extends moodleform_mod {

    /** @var object the course this instance is part of */
    protected $course = null;
    protected $exercise = 'exercise';
    protected $resource = 'resource';
    /**
     * Constructor
     */
    public function __construct($current, $section, $cm, $course) {
        register_site_id();
        flax_load_js_lib($course->id);
        $this->course = $course;
        parent::__construct($current, $section, $cm, $course);
    	$this->init_flax();
    }
    function init_flax(){
    	$flaxtype = $this->exercise;
        $collection = '';
        $activitytype_or_docid = '';
        // A non-empty value of $this->current->instance indicates editing an existing instance
        if($this->current->instance){
        	$flaxtype = $this->current->flaxtype;
            $collection = $this->current->collection;
            if($flaxtype == $this->exercise){
            	$activitytype_or_docid = $this->current->activitytype;
            }else{//$flaxtype=='resource'
            	$activitytype_or_docid = $this->current->docid;
            }
        }
        global $PAGE;
        $PAGE->requires->js_init_call('M.mod_flax.init_flax', array($flaxtype, $collection, $activitytype_or_docid), true);
	}
	function definition() {
		
         global $CFG, $USER, $PAGE, $COURSE, $DB;
         $mform =& $this->_form;
 
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// The elements defined in the $mform must comply with what's been declared in the flax table in install.xml     //
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////	

//-----------------------------------------------------------------------------------------------
//         $mform->addElement('header', 'general', get_string('general', 'form'));
//-----------------------------------------------------------------------------------------------

// A text field to enter the exercise name
        $mform->addElement('text', 'name', get_string('activity_name', 'flax'), array('size' => '40'));
        $mform->setDefault('name', get_string('activity_name_default', 'flax'));
        $mform->addRule('name', get_string('activity_name_error', 'flax'), 'required');

        //eliminates 'Undefined property stdClass::$introeditor in modedit.php line 321
        //$mform->addElement('hidden', 'introeditor', '', array('id'=>'introeditor'));
        //$mform->addElement('editor', 'hidden', 'dummy label', null, array('style'=>'display:none;'));        
        $this->add_intro_editor(false);//false makes it optional field
        //ids of the following hidden elements (eg, 'collection') are used by module.js to set/get values
        $mform->addElement('hidden', 'collection', '', array('id'=>'collection'));
        $mform->addElement('hidden', 'flaxurl', '', array('id'=>'flaxurl'));
        $mform->addElement('hidden', 'flaxtype', '', array('id'=>'flaxtype'));
        $mform->addElement('hidden', 'docid', '', array('id'=>'docid'));
        $mform->addElement('hidden', 'activitytype', '', array('id'=>'activitytype'));
        $mform->addElement('hidden', 'contentsummary', '', array('id'=>'contentsummary'));
        $mform->addElement('hidden', 'paramkeys', '', array('id'=>'paramkeys'));
        $mform->addElement('hidden', 'paramvalues', '', array('id'=>'paramvalues'));
        $mform->addElement('hidden', 'activitycontents', '', array('id'=>'activitycontents'));
        $mform->addElement('hidden', 'activityanswers', '', array('id'=>'activityanswers'));
        $mform->addElement('hidden', 'activitymode', '', array('id'=>'activitymode'));
        $mform->addElement('hidden', 'gradeover', 1, array('id'=>'gradeover'));
        $mform->addElement('hidden', 'intro', 'dummyintro');
        $mform->addElement('hidden', 'introformat', FORMAT_HTML);
//-----------------------------------------------------------------------------------------------
		$mform->addElement('header', 'contentblock', get_string('contentheader','flax'));

		$content_html = 
		'<label>
			<input type="radio" checked="true" value="exercise" name="flaxtype" flaxtype="exercise"/>
			Language exercises
		</label>
		<br>					
		<label>
			<input type="radio" value="resource" name="flaxtype" flaxtype="resource"/>
			Language resources
		</label>';
		$att_array = array('element_id'=>FLAXTYPE_WRAP,
		                   'element_style'=>'',
		                   'element_label'=>get_string('addflaxtype', 'flax'),
		                   'element_content_id'=>'flaxtype',
						   'element_content'=>$content_html);
		$html = flax_create_element_html($att_array);
		
		/*$html = '<div id="'.LEARNING_TYPE_WRAP.'">'.
		'<input id="learning_type_exercise" name="learning_type" value="exercise" checked="true" type="radio"/>'.
		'<label for="learning_type_exercise">Language exercises</label>'.
		'<br/>'.
		'<input id="learning_type_resource" name="learning_type" value="resource" type="radio"/>'.
		'<label for="learning_type_resource">Language resources</label>'.
		'</div>';*/
		//$typeblock = $mform->createElement('html', $html);
		//$typeblock->_generateId('typeblock_id');
		$mform->addElement('html', $html);
		//$typeblock->updateAttributes(array('id' => 'typeblock_id'));
		//$mform->getElement('typeblock')->updateAttributes(array('id' => 'typeblock_id'));
		//$mform->addElement('static', 'typeblock', 'Add to the course', $html);
		//$typeblock = HTML_QuickForm_element('typeblock', 'Add to the course', array('id' => 'typeblock_id'));
		
		// Collection list
		$html = '<div id="'.COLL_LIST_ID.'">'.flax_icon_progress('Loading collections ...').'</div>'.
		'<div><input comment="dummy element for css purposes" style="visibility:hidden;" type="radio"/>'.
		'<span style="display:none;" id="new_coll_btn">'.get_string('makenewcollection','flax').'</span></div>';
		$att_array = array('element_id'=>COLL_LIST_WRAP,
		                   'element_style'=>'',
		                   'element_label'=>get_string('selectcollection', 'flax'),
		                   'element_content_id'=>'coll_list_content_wrap',
						   'element_content'=>$html);
		$html = flax_create_element_html($att_array);
		$mform->addElement('html', $html);
		
		// Activity list
		$att_array = array('element_id'=>ACTIVITY_LIST_WRAP,
		                   'element_style'=>'',
		                   'element_label'=>get_string('selectactivity', 'flax'),
		                   'element_content_id'=>ACTIVITY_LIST_ID,
						   'element_content'=>flax_icon_progress('Loading activities ...'));
		$html = flax_create_element_html($att_array);
		$mform->addElement('html', $html);
		
		// Exercise content summary
		$html = '<div id="'.CONTENTSUMMARY_ID.'" style="display:block;">'.flax_icon_progress('Loading exercise content ...').'</div>'.
		'<div><input disabled="true" id="edit_btn" type="button" value="'.get_string("modifyactivity","flax").'"/></div>';
		$att_array = array('element_id'=>CONTENTSUMMARY_WRAP,
		                   'element_style'=>'',
		                   'element_label'=>get_string('exercisecontent', 'flax'),
		                   'element_content_id'=>'contentsummary_content_wrap',
						   'element_content'=>$html);
		$html = flax_create_element_html($att_array);
		$mform->addElement('html', $html);
		
		// Document list
		$att_array = array('element_id'=>DOCUMENT_LIST_WRAP,
		                   'element_style'=>'display:none;',
		                   'element_label'=>get_string('selectarticle', 'flax'),
		                   'element_content_id'=>DOCUMENT_LIST_ID,
						   'element_content'=>flax_icon_progress('Loading articles ...'));
		$html = flax_create_element_html($att_array);
		$mform->addElement('html', $html);
		
		// A place holder containing collection info from flax server
		$html = '<div id="'.PLACE_HOLDER_ID.'" style="display:none;">';
		$html .= query_flax_collections();
		$html .= '</div>';
		$mform->addElement('static','', '', $html);
      
//-----------------------------------------------------------------------------------------------
		$mform->addElement('header', 'gradeshdr', get_string('grade', 'flax'));
//-----------------------------------------------------------------------------------------------        
/**
      $options = array();
      $options[YES] = get_string("modegrade", "flax");
      $options[NO] = get_string("modenograde", "flax");
      $mform->addElement('select', GRADED, get_string('exercisemode', 'flax'), $options);
      $mform->setDefault(GRADED, YES);
      $mform->addHelpButton(GRADED, 'exercisemode', 'flax');
*/        
		//-----------------------------------------
		// Maximum grade the user could achieve for the exercise
		//-----------------------------------------
		$grade_config = flax_maxgrades_config();
		$mform->addElement('select', MAX_GRADE, get_string('grade', 'flax'), $grade_config->maxgrades);
		$mform->setDefault(MAX_GRADE, $grade_config->default);
		$mform->disabledIf(MAX_GRADE, 'flaxtype', 'eq', $this->resource);

//-----------------------------------------------------------------------------------------------
		$mform->addElement('header', 'accesscontrolhdr', get_string('accesscontrol', 'lesson'));
//-----------------------------------------------------------------------------------------------
		// Open time
		$mform->addElement('date_time_selector', 'timeopen', get_string('exerciseopen', 'flax'), array('optional'=>true));
		$mform->addHelpButton('timeopen', 'exerciseopen', 'flax');
		// Close time
		$mform->addElement('date_time_selector', 'timeclose', get_string('exerciseclose', 'flax'), array('optional'=>true));
		$mform->addHelpButton('timeclose', 'exerciseclose', 'flax');
//-----------------------------------------------------------------------------------------------
//      Standard hidden fields and default action buttons
//-----------------------------------------------------------------------------------------------
		$mform->addElement('hidden', 'cmidnumber', '');
		$mform->addElement('hidden', 'visible', '');
	    $this->standard_hidden_coursemodule_elements();
	    $this->add_action_buttons();
     }

    /**
     * A good function to override of the parent's if necessary
     * 
     * Function gets called twice: first during displaying module page, and then upon clicking submit button
     * Order of calling when submit button is clicked: set_data() -> validation() -> get_data() 
     * 
     *  This function always gets called after set_data() gets called
     *  
     * @see moodleform::get_data()
     */
//     function get_data(){
//     	$data = parent::get_data();
//     	echo('data in get_data:');
//     	print_r($data);
//     	return $data;
//     }
    /**
     * A good function to override of the parent's if necessary
     * 
     * Translate the "ref_group" field into 'exerciseurl'.
     * 
     * @param object $default_values the default data collected from this form (mform)
     * @return void (called by reference)
     */
    /**
    function set_data($default_values) {
        $default_values = (array)$default_values;

//         if (isset($default_values[self::URL_GROUP]) and isset($default_values[self::URL_GROUP][self::EXERCISEURL])) {
//             $default_values[self::EXERCISEURL] = $default_values[self::URL_GROUP][self::EXERCISEURL];
//         }
//         unset($default_values[self::URL_GROUP]);
//         $this->data_preprocessing($default_values);//flax doesn't need this step
        parent::set_data($default_values);
        echo('default_values:');
        var_dump($default_values);
    }*/
    /**
     * A must-have function for everything to proceed - even when it does nothing!
     * 
     * This function is called in formslib.php/is_validated() upon the submition of the form, via modedit.php.
     * 
     * @param object $data form data submitted from client side
     * @return object return an empty array if form data is valid, or an array containing error messages in case failure.
     */
    function validation($data, $uploaded_files) {
    	$errors = array();
        return $errors;
    }
}
?>