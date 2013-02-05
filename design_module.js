/**
 * @author alex.xf.yu@gmail.com
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
		//First, divide all sentences into questions based on question_length parameter
		var questions = [];
		var questions_sen_ids = [];
		var idx_arr = [];
		for(var i=0; i<this.base_obj.sentence_map.valSet().length; idx_arr.push(i), i++);
		if(idx_arr.length === 0){
			return null;//invalid content
		}
		
		var qn = 0;
		while(idx_arr.length>0){
			questions[qn] = [];
			questions_sen_ids[qn] = [];
			var arr = idx_arr.splice(0, this.base_obj.question_length);
			for(var i=0; i<arr.length; i++){
				questions[qn].push(this.base_obj.sentence_map.valSet()[arr[i]]);
				
				questions_sen_ids[qn].push(this.base_obj.sentence_map.valSet()[arr[i]].id);
			}
			qn ++;
		}

		// Concat text of sentences of each question
		var all_question_answer = [];
		for(var qa=[], q = 0; q < questions.length; q++){
			var question = questions[q];
			for(var sen, s = 0; s<question.length; s++){
				sen = question[s];
				var p = '';
				for (var i = 0; i < sen.words.length; i++) {
					if (i == sen.splitpoint) {
						p += '<span style="color:red;font-weight:bold;">| </span>';
					}
					p += '<span style="color:red;font-weight:bold;">'+sen.words[i] + ' '+'</span>';
				}
				
				qa.push('<p>'+p+'</p>');
			}
			all_question_answer.push(qa.join(''));
		}
		
		for(var i=0; i<questions_sen_ids.length; i++){
			questions_sen_ids[i] = questions_sen_ids[i].join(',');
		}
        o[this.info.caller_obj.consts.PARAMKEYS] = 'ids of sentences in each question';
        o[this.info.caller_obj.consts.PARAMVALUES] = questions_sen_ids.join(LLDL.text_separator);
		o[this.info.caller_obj.consts.ACTIVITYCONTENTS] = 'N/A';
		o[this.info.caller_obj.consts.ACTIVITYANSWERS] = all_question_answer.join(LLDL.text_separator);
		o[this.info.caller_obj.consts.ACTIVITYMODE] = 'i';
		o[this.info.caller_obj.consts.CONTENTSUMMARY] = 'This Split Sentences exercise contains '+all_question_answer.length+' questions';
		o[this.info.caller_obj.consts.GRADEOVER] = ''+all_question_answer.length;
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
		}
		var num_questions = this.dsp_obj.question_map.keySet().length;
        o[this.info.caller_obj.consts.PARAMKEYS] = all_docid_keys.join(',');
        o[this.info.caller_obj.consts.PARAMVALUES] = this.dsp_obj.question_map.keySet().join(LLDL.text_separator);
		o[this.info.caller_obj.consts.ACTIVITYCONTENTS] = all_question_content.join(LLDL.text_separator);
		o[this.info.caller_obj.consts.ACTIVITYANSWERS] = all_question_answer.join(LLDL.text_separator);
		o[this.info.caller_obj.consts.ACTIVITYMODE] = 'i';
		o[this.info.caller_obj.consts.CONTENTSUMMARY] = 'This Scrambled Paragraphs exercise contains '+num_questions+' questions';
		o[this.info.caller_obj.consts.GRADEOVER] = ''+num_questions;
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
		for(var i=0; i< chosen_collos.length;i++){
			var collo = chosen_collos[i];
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
        var pos_missing_words = LLDL.trim(fdoc.getQuestionWordPos());
        if(!pos_missing_words){
        	return null;//invalid content
        }
        var num_words = pos_missing_words.split(',').length;
        params_o[this.info.caller_obj.consts.PARAMKEYS] = 'missing_word_position';
        params_o[this.info.caller_obj.consts.PARAMVALUES] = pos_missing_words;
		params_o[this.info.caller_obj.consts.ACTIVITYCONTENTS] = fdoc.doc_string_with_blanks;
		params_o[this.info.caller_obj.consts.ACTIVITYANSWERS] = fdoc.getQuestionDocumentAnswer();
        params_o[this.info.caller_obj.consts.ACTIVITYMODE] = this.dcwg.design_param[LLDL.IGMODE];
		params_o[this.info.caller_obj.consts.CONTENTSUMMARY] = 'Exercise contains '+num_words+' missing words';
		params_o[this.info.caller_obj.consts.GRADEOVER] = ''+num_words;
		return params_o;

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
			console.log('params_o is null');
			return false;
		}
		if(!this.base_obj.complete_word_list || this.base_obj.complete_word_list.length ===0){
			console.log('Empty exercise content');
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
        params_o[this.info.caller_obj.consts.GRADEOVER] = ''+nodeanswers.length;
		return params_o;		
	}	
};