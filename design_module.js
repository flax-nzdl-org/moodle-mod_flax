/**
 * @author alex.xf.yu@gmail.com & mjl61@students.waikato.ac.nz
 * 
 * All classes in this files are utilized in module.js
 * 
 * To implement a standalone activity type in the Moodle module:
 *   1. In <activity_name>.js, implement LLDL.activities.<activity_name>Module function.  
 *   2. Implement LLDL.activities.Design<activity_name>Module function in design_module.js
 *   3. To achieve auto-save for module, the function call of designQuery in designInterfaceCallback in Design<activity_name>.js must be:
 *   
 *      this.designQuery(this.config_info.post_fn);
 *      
 *      where the value of post_fn ('saveExercise') is passed in from _load_exercise_content() in module.js
 *      And, appropriate relay calls also need to be applied in designQueryCallback() as well (via 'post_fn')
 *      
 *   4. Implement classes/flax_activity_<activity name>.class.php
 * 
 * Generally, for an activity type to be configured for a particular collection in module,
 * ie, for the exercise type to appear in the activity list of a collection when the module loads up in mod_form.php:
 *   1. It must be implemented in the moodle module (as above)
 *   2. A corresponding serviceRack element must be added in the buildConfig.xml of the collection, and configured true via message router
 *   
 * Little documentation on hooking up the module with the backend flax server:
 *   When mod_form.php is invoked (register_site_id() call in the _construct), if the module hasn't registered itself with 
 *   its configured flax server (checking is done thru $cfg object), it will make contact with the flax server which in turn
 *   saves the module's info in a file called flaxAccess.txt on the server. The information will be later used by the flax
 *   server to send back exercise information, for example grades, to the moodle server for report purposes. 
 * 
 * UPDATE May 2014: Revamp of how sending grades back to moodle server works.
 *   The whole module storage with flaxAccess on the server was not working sufficiently reliably. 
 *   e.g. A user installs the moodle module on their local machine. They access it via "localhost"/127.0.0.1/internal loopback address. Their
 *   server can talk to the main flax.nzdl.org server fine (i.e. is always making the requests, so can read the responses fine), but when it comes
 *   to the main flax server making a call to the user's local server (using the module info in flaxAccess), this will not work, because:
 *   a) The flaxAccess file defines the address of the user's local machine as "localhost", which is obviously not the same localhost on the flax server.
 *   b) There is no simple way to get the actual IP address of the user's server, and even if it is known, requests may not be allowed to be directly 
 *      access it.
 *   The only way the flaxAccess module system was going to work was if the flax server was on the same domain as the module server (i.e. the user will also
 *   have to install their own flax server in order to get their moodle system to work.) This is not desirable if we're encouraging users to link their moodle
 *   module to the main flax.nzdl.org server by default.
 *
 *   The sending of grades used to work as follows:
 *   The exercise currently being accessed would send a request with the exercise grade data to the flax server with a 'SCOREBACK_SERVICE' service parameter.
 *   The flax server would then send this data to the moodle server, which was exposed to the potential issues outlined above.
 *
 *   The updated methodology is as follows:
 *   The client should just send the exercise grade data to the moodle server directly. This isn't as simple as it seems though, as the exercise itself is
 *   contained in an iframe within the main moodle page. Making an ajax call to the moodle server directly from this iframe results in a violation of the 
 *   same-origin policy. Therefore as a workaround, the exercise in the iframe sends a postMessage (as part of the cross-document messaging API introduced with 
 *   HTML5) which is caught by the moodle page outside of the exercise iframe. This contains the updated grade data, and consequently the moodle page can 
 *   create an appropriate ajax request with the received exercise grade data to the actual moodle server.
 *
 *   The code for this methodology lies in the send2moodle method of Activity.js on the flax side, and view.php on the moodle side.
 * 
 *   As a consequence of this updated methodology, the whole flaxAccess/registering site id is a little redundant now. It has been left there for consistency,
 *   and due to potential legacy functions still wanting it to exist.
 */
 
/**
 * 1. ScrambleSentence
 */
LLDL.activities.DesignScrambleSentenceModule = function (root_el, info) {
	var dss_obj = new LLDL.activities.DesignScrambleSentence(root_el, info);
	this.info = info;
	this.dss_obj = dss_obj;
	var dssm_obj = this;
	
	/**
	 * when doing javascript function overriding: 
	 * (1) override instance's function in which its prototype function is called.
	 * (2) do not use keyword 'this' to reference the instance function when overriding, 
	 *     ie, this.dss_obj.saveExercise = function() {...}
	 */
	dss_obj.saveExercise =  function(){
		var params_o = LLDL.activities.DesignScrambleSentence.prototype.saveExercise.call(dss_obj);
        dssm_obj.saveExercise(params_o);
	};
};
LLDL.activities.DesignScrambleSentenceModule.prototype = {
	saveExercise: function(params_o){
		var nodeanswers=[], nodecontents=[], nodeid_keys=[], nodeids = [];
        for(var item=null, i=0; i<this.dss_obj.sentences.length; i++) {
            item = this.dss_obj.sentences[i];
            if(item.i_am_selected) {
            	nodeid_keys.push('nodeid');
                nodeids[nodeids.length] = item.nodeid;
                nodecontents[nodecontents.length] = item.sen_text;
                nodeanswers[nodeanswers.length] = item.answer_sen_text;
            }
        }
		if(nodecontents.length === 0){
			return null;//invalid content
		}
        params_o[this.info.caller_obj.consts.PARAMKEYS] = nodeid_keys.join(LLDL.text_separator);
        params_o[this.info.caller_obj.consts.PARAMVALUES] = nodeids.join(LLDL.text_separator);
        params_o[this.info.caller_obj.consts.ACTIVITYCONTENTS] = nodecontents.join(LLDL.text_separator);
        params_o[this.info.caller_obj.consts.ACTIVITYANSWERS] = nodeanswers.join(LLDL.text_separator);
        params_o[this.info.caller_obj.consts.ACTIVITYMODE] = 'i';
        params_o[this.info.caller_obj.consts.CONTENTSUMMARY] = 'This Scrambled Sentences exercise contains '+nodecontents.length+' sentences';
        params_o[this.info.caller_obj.consts.GRADEOVER] = ''+nodecontents.length;
		return params_o;		
	}	
};
/**
 * 2. SplitSentences
 */
LLDL.activities.DesignSplitSentencesModule = function (root_el, info) {
	this.info = info;
	var mdl_obj = this;
	var base_obj = new LLDL.activities.DesignSplitSentences(root_el, info);
	this.base_obj = base_obj;
	base_obj.saveExercise = function(){
		var o = LLDL.activities.DesignSplitSentences.prototype.saveExercise.call(base_obj);
		mdl_obj.saveExercise(o);
	}
};
LLDL.activities.DesignSplitSentencesModule.prototype = {
	saveExercise : function(o){
		
		var sentences = this.base_obj.sentence_map.valSet();
		var questions = [];
		var ql = this.base_obj.question_length;
		var qText = '';
		var sIndex = 0;
		var sen = '';
		
		for (s=0; s<sentences.length; s++){
			sen = sentences[s];
			if (!sen.include) continue;
			qText += '<p>' + sen.raw + '</p>';
			sIndex++;
			if (sIndex == ql){
				questions.push(qText);
				qText = '';
				sIndex = 0;
			}
		}
		
		if (qText != ''){
			questions.push(qText);
		}
		
		o[this.info.caller_obj.consts.ACTIVITYCONTENTS] = 'N/A';
		o[this.info.caller_obj.consts.ACTIVITYANSWERS] = questions.join(LLDL.text_separator);
		o[this.info.caller_obj.consts.ACTIVITYMODE] = 'i';
		o[this.info.caller_obj.consts.CONTENTSUMMARY] = 'This Split Sentences exercise has ' + this.base_obj.num_selected + ' sentences contained in ' + 
														addPluralText('question', questions.length) + ".";
		o[this.info.caller_obj.consts.GRADEOVER] = this.base_obj.num_selected;
		return o; 
	}
};
/**
 * 3. ScrambleParagraph
 */
LLDL.activities.DesignScrambleParagraphModule = function (root_el, info) {
	this.info = info;
	var dspm_obj = this;
	var dsp_obj = new LLDL.activities.DesignScrambleParagraph(root_el, info);
	this.dsp_obj = dsp_obj;
	dsp_obj.saveExercise = function(){
		var o = LLDL.activities.DesignScrambleParagraph.prototype.saveExercise.call(dsp_obj);
		dspm_obj.saveExercise(o);
	}
};
LLDL.activities.DesignScrambleParagraphModule.prototype = {
	saveExercise : function(o){ 
		var all_question_content = [];
		var all_question_answer = [];
		var all_docs = this.dsp_obj.question_map.valSet();
		if(all_docs.length === 0){
			return null;//invalid content
		}
		var all_docid_keys = [];
		var num_marks = 0;	// to store number of marks in total
		for(var i = 0; i < all_docs.length; i++){
			var question = all_docs[i];
			var inorderparaset = [];
			var parainfo = question.inorderparaset;
			for (var j=0; j < parainfo.length; j++) {
				var para_o = parainfo[j];
				inorderparaset.push('<li>'+para_o.data+'</li>');
			}
			all_question_answer.push('<ol>'+inorderparaset.join('')+'</ol>');

			var questionparaset = [];
			var parainfo = question.questionparaset;
			for (var j=0; j < parainfo.length; j++) {
				var para_o = parainfo[j];
				questionparaset.push(''+para_o.order);
			}
			all_question_content.push(questionparaset.join(','));
			
			all_docid_keys.push('docid');
			num_marks += inorderparaset.length;
		}
		
		var num_questions = this.dsp_obj.question_map.keySet().length;
		
		// reduce number of marks if fix is more than 0 - total marks will either be 1 less or 2 less per document 
		// (depending on whether first and/or last paragraph is selected to be fixed)
		var f = this.dsp_obj.fix;
		var reduce_fact = (f < 1) ? 0 : (f < 3) ? 1 : 2;
		num_marks -= (reduce_fact * num_questions);
		
        o[this.info.caller_obj.consts.PARAMKEYS] = all_docid_keys.join(',');
        o[this.info.caller_obj.consts.PARAMVALUES] = this.dsp_obj.question_map.keySet().join(LLDL.text_separator);
		o[this.info.caller_obj.consts.ACTIVITYCONTENTS] = all_question_content.join(LLDL.text_separator);
		o[this.info.caller_obj.consts.ACTIVITYANSWERS] = all_question_answer.join(LLDL.text_separator);
		o[this.info.caller_obj.consts.ACTIVITYMODE] = 'i';
		o[this.info.caller_obj.consts.CONTENTSUMMARY] = 'This Scrambled Paragraphs exercise contains ' + addPluralText('question', num_questions) + 
														' and will be marked out of ' + num_marks + '.';
		o[this.info.caller_obj.consts.GRADEOVER] = num_marks;
		return o; 
	}
};
/**
 * 4. MissingPunctuation
 */
LLDL.activities.DesignMissingPunctuationModule = function (root_el, info) {
	this.info = info;
	var mdl_obj = this;
	var base_obj = new LLDL.activities.DesignMissingPunctuation(root_el, info);
	this.base_obj = base_obj;
	base_obj.saveExercise = function(){
		var o = LLDL.activities.DesignMissingPunctuation.prototype.saveExercise.call(base_obj);
		mdl_obj.saveExercise(o);
	}
};
LLDL.activities.DesignMissingPunctuationModule.prototype = {
	saveExercise : function(o){ 
		var all_question_content = [];
		var all_question_answer = [];
		var docid_keys = [];
		var all_questions = this.base_obj.question_map.valSet();
		for(var qc=[], qa=[], q = 0; q < all_questions.length; q++){
			var question = all_questions[q];
			for (var w = 0; w < question.wordsets.length; w++) {
				qc.push('<p>'+question.wordsets[w].join(' ')+'</p>');
				qa.push('<p>'+question.data[w].join(' ')+'</p>');
			}
			all_question_content.push(qc.join(''));
			all_question_answer.push(qa.join(''));
			docid_keys.push('docid');
		}
		if(all_question_content.length === 0){
			return null;//invalid content
		}
		var num_questions = this.base_obj.question_map.keySet().length;
        o[this.info.caller_obj.consts.PARAMKEYS] = docid_keys.join(LLDL.text_separator);
        o[this.info.caller_obj.consts.PARAMVALUES] = this.base_obj.question_map.keySet().join(LLDL.text_separator);
		o[this.info.caller_obj.consts.ACTIVITYCONTENTS] = all_question_content.join(LLDL.text_separator);
		o[this.info.caller_obj.consts.ACTIVITYANSWERS] = all_question_answer.join(LLDL.text_separator);
		o[this.info.caller_obj.consts.ACTIVITYMODE] = 'i';
		o[this.info.caller_obj.consts.CONTENTSUMMARY] = 'This Missing Punctuation exercise contains '+num_questions+' questions';
		o[this.info.caller_obj.consts.GRADEOVER] = ''+num_questions;
		return o; 
	}	
};
/**
 * 5. CollocationFillinBlanks
 */
LLDL.activities.DesignCollocationFillinBlanksModule = function (root_el, info) {
	var dcf_obj = new LLDL.activities.DesignCollocationFillinBlanks(root_el, info);
	this.info = info;
	this.dcf_obj = dcf_obj;
	var dcfm_obj = this;
	dcf_obj.saveExercise = function(){
		var o = LLDL.activities.DesignCollocationFillinBlanks.prototype.saveExercise.apply(dcf_obj);
		dcfm_obj.saveExercise(o);
	}

}
LLDL.activities.DesignCollocationFillinBlanksModule.prototype = {
	saveExercise: function(params_o){
		var nodeanswers = [], nodecontents = [];
		var collo_id_keys = [], collo_ids = [];
		var chosen_collos = this.dcf_obj.getChosenCollos();
		console.log(chosen_collos);
		for(var i=0; i< chosen_collos.length;i++){
			var collo = chosen_collos[i];
			console.log(collo);
			var data4moodle = this.getData4moodle(collo);
			nodeanswers.push(''+(i+1)+'. '+data4moodle.answer);
			nodecontents.push(''+(i+1)+'. '+data4moodle.question);
			
			collo_id_keys.push('collo_id');
			collo_ids.push(collo.id);
		}
		if(nodecontents.length === 0){
			return null;//invalid content
		}

        params_o[this.info.caller_obj.consts.PARAMKEYS] = collo_id_keys.join(',');
        params_o[this.info.caller_obj.consts.PARAMVALUES] = collo_ids.join(',');
        params_o[this.info.caller_obj.consts.ACTIVITYCONTENTS] = nodecontents.join(LLDL.text_separator);
        params_o[this.info.caller_obj.consts.ACTIVITYANSWERS] = nodeanswers.join(LLDL.text_separator);
        params_o[this.info.caller_obj.consts.ACTIVITYMODE] = 'i';
        params_o[this.info.caller_obj.consts.CONTENTSUMMARY] = 'This Completing Collocations exercise contains '+nodecontents.length+' questions';
        params_o[this.info.caller_obj.consts.GRADEOVER] = ''+nodecontents.length;
        return params_o;
	},
	getData4moodle: function(collo){
		var text = '', word = '';
		if(collo.meta_map.get("sent_before") != "null"){
			text += collo.meta_map.get("sent_before") + ' ';
		}
		var temp_div = LLDL.createEl("div");
		yud.setStyle(temp_div, 'display','none');
		temp_div.innerHTML = collo.meta_map.get("sent");
		var span_els = LLDL.getNamedElements(temp_div, 'span', 'class', 'collo-word');
		for(var i=0; i<span_els.length;i++){
			if(collo.gap_collo_word_idx == i) {
				word = LLDL.trim(span_els[i].firstChild.data);
				span_els[i].innerHTML = '_____';
			}
		}
		text += temp_div.innerHTML;
		if(collo.meta_map.get("sent_after") != "null"){
			text += " "+collo.meta_map.get("sent_after");
		}
		var data = {question:text, answer:word};
		return data;
	}
};
/**
 * 6. ContentWordGuessing
 */
LLDL.activities.DesignContentWordGuessingModule = function(root_el, info) {
	this.info = info;
	var dcwg_module = this;
	var dcwg = new LLDL.activities.DesignContentWordGuessing(root_el, info);
	this.dcwg = dcwg;
	dcwg.saveExercise = function(){
		var params_o = LLDL.activities.DesignContentWordGuessing.prototype.saveExercise.call(dcwg);
		dcwg_module.saveExercise(params_o);
	};
}; 
LLDL.activities.DesignContentWordGuessingModule.prototype = {
	saveExercise: function(params_o){
        var docid = this.dcwg.design_param[LLDL.DOCID];
        var fdoc = this.dcwg.cached_doc_map.get(docid);
        var pos_missing_words = fdoc.getQuestionWordPos();
        if(!pos_missing_words){
        	return null;	//invalid content
        }
                
        var num_words = pos_missing_words.split(',').length;
        params_o[this.info.caller_obj.consts.PARAMKEYS] = 'missing_word_position';
        params_o[this.info.caller_obj.consts.PARAMVALUES] = pos_missing_words;
		params_o[this.info.caller_obj.consts.ACTIVITYCONTENTS] = this.replaceDocWordsForContent(pos_missing_words, fdoc);
		params_o[this.info.caller_obj.consts.ACTIVITYANSWERS] = fdoc.getQuestionDocumentAnswer();
        params_o[this.info.caller_obj.consts.ACTIVITYMODE] = this.dcwg.design_param[LLDL.IGMODE];
		params_o[this.info.caller_obj.consts.CONTENTSUMMARY] = 'Exercise contains '+num_words+' missing words';
		params_o[this.info.caller_obj.consts.GRADEOVER] = ''+num_words;
		return params_o;
	},
	// Used to replace the words to be guessed in the document string
	// (Unable to use the fdoc.doc_string_with_blanks, as this doesn't take into account words 
	// that may have been toggled off by user [i.e in toggle_word_pos_arr])
	replaceDocWordsForContent: function(pos_missing_words, fdoc){
        var miss_word_arr = pos_missing_words.split(",");
        var doc_str_arr = fdoc.doc_string.split(/\s+/);
        var doc_str_arr_with_blanks = [];
        doc_str_arr_with_blanks = doc_str_arr_with_blanks.concat(doc_str_arr);
        var label_num = 1;
        for (var i=0; i<doc_str_arr.length; i++){
        	var w = doc_str_arr[i];
        	if (i == parseInt(miss_word_arr[0])){
        		miss_word_arr = miss_word_arr.slice(1);
        		// need to check for any punctuation that may be at the end of a word
        		// if it exists, add it as part of the doc_str_arr_with_blanks (so it is still displayed in report)
        		var punc = "";
        		var r = w.search(/[^\w\s]+$/);
        		if (r != -1){
        			punc += w.substring(r, w.length);
        		}
        		doc_str_arr_with_blanks[i] = '_|' + label_num + '|_' + punc;
        		label_num++;
        	}
        }
        return doc_str_arr_with_blanks.join(' ');
	}
};
/**
 * 7. ImageGuessing
 */
LLDL.activities.DesignImageGuessingModule = function(root_el, info) {
	this.info = info;
	this.info.auto_save = true;
	var dig = new LLDL.activities.DesignImageGuessing(root_el, info);
	this.dig = dig;
	var digm = this;
	dig.saveExercise = function(){
		var o = LLDL.activities.DesignImageGuessing.prototype.saveExercise.call(dig);
		digm.saveExercise(o);
	}
};
LLDL.activities.DesignImageGuessingModule.prototype = {
	saveExercise: function(o){
		var img_arr = [];
		for(var img, i=0; i<this.dig.chosen_imgarr.length; i++) {
			img = this.dig.chosen_imgarr[i];
			img_arr.push(img.imgid + LLDL.param_nv_separator + img.imgsrc);
		}
		//Note: this.design_param[LLDL.TIME_LIMIT] is in seconds and string format
		var time_limit = parseInt(this.dig.design_param[LLDL.TIME_LIMIT]); 
		if(time_limit > 0){
			time_limit = LLDL.getTimeStr(parseInt(this.dig.design_param[LLDL.TIME_LIMIT])*1000);
		}else{
			time_limit = 'No time limit';
		}
		if(img_arr.length < 2){
			return null;//invalid content
		}
        o[this.info.caller_obj.consts.PARAMKEYS] = 'time_limit';
        o[this.info.caller_obj.consts.PARAMVALUES] = time_limit;
		o[this.info.caller_obj.consts.ACTIVITYCONTENTS] = img_arr.join(LLDL.param_arg_separator);
		o[this.info.caller_obj.consts.ACTIVITYANSWERS] = 'NA';
		o[this.info.caller_obj.consts.ACTIVITYMODE] = 'p';// p for pair
		o[this.info.caller_obj.consts.CONTENTSUMMARY] = 'This Image Guessing exercise contains '+ img_arr.length+' images; time limit: '+time_limit;
		o[this.info.caller_obj.consts.GRADEOVER] = ''+img_arr.length;
		return o;
	}
};
/**
 * 8. Hangman
 */
LLDL.activities.DesignHangmanModule = function (root_el, info) {
	var base_obj = new LLDL.activities.DesignHangman(root_el, info);
	this.info = info;
	this.base_obj = base_obj;
	var m_obj = this;
	base_obj.saveExercise =  function(){
		var params_o = LLDL.activities.DesignHangman.prototype.saveExercise.call(base_obj);
        m_obj.saveExercise(params_o);
	};
};
LLDL.activities.DesignHangmanModule.prototype = {
	saveExercise: function(params_o){
		if(!params_o){
			return false;
		}
		if(!this.base_obj.complete_word_list || this.base_obj.complete_word_list.length === 0){
			return false;
		}
		var nodeanswers=[], nodecontents=[], nodeid_keys=[], nodeids = [];
		for (var wo, i = 0; i < this.base_obj.complete_word_list.length; i++) {
			wo = this.base_obj.complete_word_list[i];
			if (wo.selected) {
				nodeanswers[nodeanswers.length] = wo.word;
				nodecontents[nodecontents.length] = this.base_obj.showhint? wo.hint_text : 'HINT_NOT_SET';
			}
		}

        params_o[this.info.caller_obj.consts.PARAMKEYS] = 'showhint';
        params_o[this.info.caller_obj.consts.PARAMVALUES] = this.base_obj.showhint;
        params_o[this.info.caller_obj.consts.ACTIVITYCONTENTS] = nodecontents.join(LLDL.text_separator);
        params_o[this.info.caller_obj.consts.ACTIVITYANSWERS] = nodeanswers.join(LLDL.text_separator);
        params_o[this.info.caller_obj.consts.ACTIVITYMODE] = 'i';
        params_o[this.info.caller_obj.consts.CONTENTSUMMARY] = 'This Hangman exercise contains '+nodeanswers.length+' words';
        params_o[this.info.caller_obj.consts.GRADEOVER] = nodeanswers.length * 10;	// maximum score is 10 per question
		return params_o;
	}	
};
/**
 * 9. CollocationMatching
 */
LLDL.activities.DesignCollocationMatchingModule = function (root_el, info) {
	this.info = info;
	var mdl_obj = this;
	var base_obj = new LLDL.activities.DesignCollocationMatching(root_el, info);
	this.base_obj = base_obj;
	base_obj.saveExercise = function(){
		var o = LLDL.activities.DesignCollocationMatching.prototype.saveExercise.call(base_obj, true);
		mdl_obj.saveExercise(o);
	}
};
LLDL.activities.DesignCollocationMatchingModule.prototype = {
	saveExercise : function(o){ 
		var totalQuestions = this.base_obj.quest_len;
		
		var colloCount = 0;
		for (var i=0; i<this.base_obj.question_map.length; i++){
			colloCount += this.base_obj.question_map[i].checkCount;
		}
		
		var ansString = this.prepareAnsString(this.base_obj.colloText, this.base_obj.cOrderArray, this.base_obj.qOrderArray);
				
		var unused = this.base_obj.unusedNum;
		
		// calculate how many marks will be on offer (number of collocations selected - unused amount)
		var markCount = colloCount - unused;		
		
		o[this.info.caller_obj.consts.CONTENTSUMMARY] = 'This Collocation Matching exercise contains ' + addPluralText('question', totalQuestions) + 
														', and will be marked out of ' + markCount + '.';
		if (unused > 0){
			o[this.info.caller_obj.consts.CONTENTSUMMARY] += '<br>(' + colloCount + ' collocations are selected, and ' + unused + ' will be unused.)';
		}

		this.base_obj.previewExercise();
		
        o[this.info.caller_obj.consts.PARAMVALUES] = false;
		o[this.info.caller_obj.consts.ACTIVITYCONTENTS] = 'N/A';
		o[this.info.caller_obj.consts.ACTIVITYANSWERS] = ansString;
		o[this.info.caller_obj.consts.ACTIVITYMODE] = 'i';
		o[this.info.caller_obj.consts.GRADEOVER] = markCount;
		return o; 
	},
	
	/** Function to prepare the answer string that will be stored for a Matching activity.
	 * @param tArr 2d array that has the plain text of each collocation for each target word
	 * @param cArr 2d array that contains the order that the collocations will be shuffled in for each target word
	 * @param qArr 2d array that contains the order that each question will display its collocations 
	 */
	prepareAnsString: function(tArr, cArr, qArr){
		var tmp = [];
		for (var i=0; i<tArr.length; i++){
			tmp[i] = [];
			for (var j=0; j<tArr[i].length; j++){
				tmp[i].push(tArr[i][cArr[i][j]]);
			}
		}
		tmp.sort(LLDL.sortByArrLength);		// sort the tmp array so that it is in decreasing order in terms of targWord number of collos
		var ansArr = [];					// (i.e. the targWord(s) with the most collos will be at the front of the array)
		for (var i=0; i<qArr.length; i++){
			var cStr = '';
			for (var j=0; j<qArr[i].length; j++){
				cStr += '<p>' + tmp[qArr[i][j]][i] + '</p>';
			}
			ansArr.push(cStr);
		}
		return ansArr.join(LLDL.text_separator);
	},
	
	testSort: function(arrA, arrB){
		return arrB.length - arrA.length;
	}
};
/**
 * 10. RelatedWords
 */
LLDL.activities.DesignRelatedWordsModule = function (root_el, info) {
	this.info = info;
	var mdl_obj = this;
	var base_obj = new LLDL.activities.DesignRelatedWords(root_el, info);
	this.base_obj = base_obj;
	base_obj.saveExercise = function(){
		var o = LLDL.activities.DesignRelatedWords.prototype.saveExercise.call(base_obj, true);
		mdl_obj.saveExercise(o);
	}
};
LLDL.activities.DesignRelatedWordsModule.prototype = {
	saveExercise : function(o){ 
		
		var ansString = this.prepareAnsString(this.base_obj.textArray, this.base_obj.orderArray);
		
		var colloCount = this.base_obj.textArray.length;
		o[this.info.caller_obj.consts.CONTENTSUMMARY] = 'This Related Words exercise contains ' + colloCount + ' collocations.';
		
		this.base_obj.previewExercise();
		
        o[this.info.caller_obj.consts.PARAMVALUES] = false;
		o[this.info.caller_obj.consts.ACTIVITYCONTENTS] = 'N/A';
		o[this.info.caller_obj.consts.ACTIVITYANSWERS] = ansString;
		o[this.info.caller_obj.consts.ACTIVITYMODE] = 'i';
		o[this.info.caller_obj.consts.GRADEOVER] = colloCount;
		return o; 
	},
	
	/* Function that prepares the answer string in terms of <p> elements to be displayed.
	 * (The order array is passed as a parameter so that the answer string will display
	 * the collocations in the same order as was displayed to the user) */
	prepareAnsString: function(textArr, ordArr){
		var str = '';
		for (var i=0; i<textArr.length; i++){
			str += '<p>' + textArr[ordArr[i]] + '</p>';
		}
		return str;
	}
};
/**
 * 11. CollocationDominoes
 */
LLDL.activities.DesignCollocationDominoesModule = function (root_el, info) {
	this.info = info;
	var mdl_obj = this;
	var base_obj = new LLDL.activities.DesignCollocationDominoes(root_el, info);
	this.base_obj = base_obj;
	base_obj.saveExercise = function(){
		var o = LLDL.activities.DesignCollocationDominoes.prototype.saveExercise.call(base_obj);
		mdl_obj.saveExercise(o);
	}	
};
LLDL.activities.DesignCollocationDominoesModule.prototype = {
	saveExercise : function(o){ 
		
		// -1 as globalColloCount includes the 'half' collocation that is still being created
		var colloCount = this.base_obj.globalColloCount - 1;
				
		// If there are no questions set, inform the user to edit the exercise
		if (colloCount < 2){
			o[this.info.caller_obj.consts.CONTENTSUMMARY] = this.info.caller_obj.consts.REQUIRES_EDITING;
		}else{
			o[this.info.caller_obj.consts.CONTENTSUMMARY] = 'This Collocation Dominoes exercise contains ' + colloCount + ' dominoes.';
		}	
		
		// Prepare the answer string that will display in the Moodle report (the chosenWordArray can be simply used 
		// without having to worry about things like shuffled orders of collocations)
		var ansString = this.prepareAnsString(this.base_obj.chosenWordArray);
		
        o[this.info.caller_obj.consts.PARAMVALUES] = false;
		o[this.info.caller_obj.consts.ACTIVITYCONTENTS] = 'N/A';
		o[this.info.caller_obj.consts.ACTIVITYANSWERS] = ansString;
		o[this.info.caller_obj.consts.ACTIVITYMODE] = 'i';
		o[this.info.caller_obj.consts.GRADEOVER] = colloCount;
		return o; 
	},

	// function to create <p> elements for each of the correct dominoes
	prepareAnsString: function(wordArray){
		var ansString = '';
		for (var i=0; i<wordArray.length-1; i++){
			ansString += '<p>' + wordArray[i] + ' ' + wordArray[i+1] + '</p>';
		}
		return ansString;
	}
};
/**
 * 12. CollocationGuessing
 */
LLDL.activities.DesignCollocationGuessingModule = function (root_el, info) {
	this.info = info;
	var mdl_obj = this;
	var base_obj = new LLDL.activities.DesignCollocationGuessing(root_el, info);
	this.base_obj = base_obj;
	base_obj.saveExercise = function(){
		var o = LLDL.activities.DesignCollocationGuessing.prototype.saveExercise.call(base_obj, true);
		mdl_obj.saveExercise(o);
	}
};
LLDL.activities.DesignCollocationGuessingModule.prototype = {
	saveExercise : function(o){ 
		var qLen = this.base_obj.quest_len;
		
		var markCount = 0;
		
		var ansArray = [];
		for (var i=0; i<qLen; i++){
			ansArray.push(this.base_obj.textArray[this.base_obj.orderArray[i]]);
			markCount += this.base_obj.question_map[i].totalCheckCount;
		}
		
		var cString = this.prepareContentString(this.base_obj.colloText, this.base_obj.orderArray);
				
		o[this.info.caller_obj.consts.CONTENTSUMMARY] = 'This Collocation Guessing exercise contains ' + addPluralText('word', qLen) + ' to guess, and will be marked out of ' + 
														markCount + '.';

		this.base_obj.previewExercise();
		
        o[this.info.caller_obj.consts.PARAMVALUES] = false;
		o[this.info.caller_obj.consts.ACTIVITYCONTENTS] = cString;
		o[this.info.caller_obj.consts.ACTIVITYANSWERS] = ansArray.join(LLDL.text_separator);
		o[this.info.caller_obj.consts.ACTIVITYMODE] = 'i';
		o[this.info.caller_obj.consts.GRADEOVER] = markCount;
		return o; 
	},
	
	/* All the collocations used by a particular target word will be stored in the content attribute
	 * of the question entry in the db. The below function prepares all the collocations for when they 
	 * are to be displayed in the report.
	 * (Currently not necessarily in order they were/are to be displayed in the exercise itself...
	 * - possible improvement for future?) */
	prepareContentString: function(arr, orderArr){
		var cArray = [];
		for (var i=0; i<arr.length; i++){
			var cStr = '';
			for (var j=0; j<arr[orderArr[i]].length; j++){
				cStr += '<p>' + arr[orderArr[i]][j] + "</p>";
			}
			cArray.push(cStr);
		}
		return cArray.join(LLDL.text_separator);
	}
};

// helper function that determines whether to add the plural on the end of a given word
// it is passed the value that determines this fact (e.g. number of questions)
// it also prints out the value of the number beforehand (e.g. '4 questions')
function addPluralText(str, num){
	return num + ' ' + ((num > 1) ? str + 's' : str);
}