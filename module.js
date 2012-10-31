/**
 * @todo - Modify this file to utilise Y
 * 
 *  This file contains all inline scripts invoked from within php pages
 */

/**
 * functions whose name starts with an underscore are only used locally, eg, won't be called via $PAGE->requires->js_init_call
 * 
 * @namespace flax module namespace initialisation
 */

M.mod_flax = M.mod_flax || {};
M.mod_flax = {
	config: function(Y, mdlsiteurl, flaxmodulename, flaxdlmodulename, flaxserver, mdlsiteid, mdlusername, courseid, is_teacher, 
			aval_activity_names, form_elem_id_arr){
		this.cfg = {
			mdlsiteurl:mdlsiteurl,
			flax_module_name:flaxmodulename,
			flaxdl_module_name:flaxdlmodulename,
			flaxserver:flaxserver,//rosebud:9000
			flax_ajax_url: flaxserver+'/greenstone3/flax?a=pr&rt=r&ro=1&xml',
			flax_images_url: flaxserver+'/greenstone3/interfaces/flax/images/',
			mdlsiteid:mdlsiteid,
			login_username:mdlusername,
			courseid:courseid,
			is_teacher:is_teacher,
			aval_activity_arr:aval_activity_names.split(','),

			content_panel_body_id:'_content_panel_body_id',
			buildcoll_panel_body_id : '_buildcoll_panel_body_id',
			coll_el_name: 'cname',
			activity_el_name: 'aname',

			dummydoc_id:'_dummydoc',
			flaxurl:'flaxurl',
			docid:'docid',
			collection:'collection',
			
			flaxtype_wrap_id:form_elem_id_arr[0],
			coll_list_wrap_id: form_elem_id_arr[1],
			activity_list_wrap_id: form_elem_id_arr[2],
			contentsummary_wrap_id: form_elem_id_arr[3],
			document_list_wrap_id: form_elem_id_arr[4],
			coll_list_body_id: form_elem_id_arr[5],
			activity_list_body_id: form_elem_id_arr[6],
			contentsummary_id: form_elem_id_arr[7],
			document_list_body_id: form_elem_id_arr[8],
			place_holder_id: form_elem_id_arr[9]

		};
		
		this.cfg.trans_modal_mask = 
					new YAHOO.widget.Panel("_module_transparent_modal_mask_",  { 
						width: "0", 
						height: "0", 
						fixedcenter: true, 
						close: false, 
						draggable: false, 
						modal: true,
						visible: false,
						underlay: "matte",
						constraintoviewport : true
					} 
					);
		this.cfg.trans_modal_mask.setBody("");
		this.cfg.trans_modal_mask.setHeader("");
		this.cfg.trans_modal_mask.render(document.body); 
		yud.setStyle(yud.get("_module_transparent_modal_mask_").parentNode, 'display', 'none');		

		// These will be used by saveExercise() of all activity design classes in js
		this.consts = {
				PARAMKEYS:'paramkeys',
				PARAMVALUES:'paramvalues',
				ACTIVITYCONTENTS:'activitycontents',
				ACTIVITYANSWERS:'activityanswers',
				ACTIVITYMODE:'activitymode',
				ACTIVITYTYPE:'activitytype',
				CONTENTSUMMARY:'contentsummary',
				GRADEOVER:'gradeover'
		};
	},
	init_mod_ref_el: function(){
		var mod_ref_el = yud.get('mform1');
		this.cfg.mod_ref_el = mod_ref_el.parentNode;
	},
	show_activity_list_view: function(){
		yud.setStyle(this.cfg.document_list_wrap_id, 'display', 'none');
		yud.setStyle(this.cfg.activity_list_wrap_id, 'display', 'block');
		yud.setStyle(this.cfg.contentsummary_wrap_id, 'display', 'block');
		this.collection_manager._carry_out_chain_evt();
	},
	show_document_list_view: function(){
		yud.setStyle(this.cfg.activity_list_wrap_id, 'display', 'none');
		yud.setStyle(this.cfg.contentsummary_wrap_id, 'display', 'none');
		yud.setStyle(this.cfg.document_list_wrap_id, 'display', 'block');
		this.collection_manager._carry_out_chain_evt();
	},
	show_view: function(){
		if(this.flaxtype === 'exercise'){
			this.show_activity_list_view();
		}else{
			this.show_document_list_view();
		}
	},
	bind_chain_manager: function(){
		if(this.flaxtype === 'exercise'){
			this.collection_manager.set_chain_manager(this.activity_manager);
		}else{
			this.collection_manager.set_chain_manager(this.document_manager);
		}
	},
	init_flax: function(Y, init_flaxtype, collname, activitytype_or_docid){
		this.init_mod_ref_el();
		this.flaxtype = init_flaxtype;
		yue.on(this.cfg.flaxtype_wrap_id, 'click', function(evt){
			var el = yue.getTarget(evt);
			var tag_name = el.tagName.toLowerCase();
			if(tag_name == 'input'){
				var flaxtype = el.getAttribute('flaxtype');
				if(!flaxtype) return;
				if(flaxtype === this.flaxtype){
					return;//clicked on already checked radio button
				}
				this.flaxtype = flaxtype;
				this.bind_chain_manager();
				this.show_view();
			}

		}, this, true);
		
		var place_holder_el = yud.get(this.cfg.place_holder_id);
		var colls = place_holder_el.getElementsByTagName('collectionList')[0].getElementsByTagName('collection');
		var all_activity_info = place_holder_el.getElementsByTagName('flaxActivityList')[0];
		var model_collection_config = place_holder_el.getElementsByTagName('collectionConfig')[0];

		this.collection_manager = M.mod_flax.CollectionManager;
		this.activity_manager = M.mod_flax.ActivityManager;
		this.document_manager = M.mod_flax.DocumentManager;
		this.collection_manager.init(this.cfg, colls, model_collection_config, all_activity_info);		
		this.activity_manager.init(this.cfg, this.consts, all_activity_info.getElementsByTagName('activity'));
		this.document_manager.init(this.cfg);
				
		this.bind_chain_manager();
		var default_collname = collname || 'password';
		var default_activitytype_or_docid = activitytype_or_docid;
		this.collection_manager.display_collection_list(default_collname, default_activitytype_or_docid);
		
		LLDL.attachHelpInfoLoader(M.mod_flax._query_flax);
	}
};
M.mod_flax.mod_panel = function(id, title){
	this.init(id, title);	
	this.hide();
};
M.mod_flax.mod_panel.prototype = {
	init: function(id, title){
		this.panel = LLDL.createEl('div', id, '', 'mod-flax-panel');
		yud.insertBefore(this.panel, M.mod_flax.cfg.mod_ref_el);
		
		var header = LLDL.createEl('h2','','','main help');
		header.innerHTML = M.mod_flax.lib._get_icon_flax() + '<span style="margin-left:5px;">'+title+'</span>';
		this.panel.appendChild(header);
		this.body = LLDL.createEl('div');
		this.panel.appendChild(this.body);
	},
	show: function(){
		yud.setStyle(this.panel, 'display', '');
		yud.setStyle(M.mod_flax.cfg.mod_ref_el, 'display', 'none');
	},
	hide: function(){
		yud.setStyle(this.panel, 'display', 'none');
		yud.setStyle(M.mod_flax.cfg.mod_ref_el, 'display', '');		
	},
	clean: function(){
		this.body.innerHTML = '';
	}
};

M.mod_flax.lib = {
	_get_icon_progress: function(text){
		var src = M.mod_flax.cfg.mdlsiteurl+'/theme/image.php?theme=standard&image=i%2Floading_small';
		return '<img class="smallicon" src="'+src+'"/><span style="margin-left:5px;">'+text+' ...</span>';
	},
	_get_icon_flax: function(){
		var src = M.mod_flax.cfg.mdlsiteurl+'/theme/image.php?theme=standard&image=icon&component=flax';
		return '<img class="smallicon" src="'+src+'"/>';
	},
	_get_icon: function(icon_name, collname){
		var src = M.mod_flax.cfg.mdlsiteurl+'/theme/image.php?theme=standard&image=t%2F'+icon_name;
		var title = (icon_name=='edit')? 'Edit this collection': (icon_name=='delete')? 'Delete this collection': 'unknown icon name';
		return '<img collname="'+collname+'" action="'+icon_name+'" class="iconsmall" hspace="3" src="'+src+'" title="'+title+'"/>';
	}
};
/**
 * A note on the confusion of two definitions of collection object used here:
 * 1. LLDL.Bean.FlaxCollection (normally with variable names 'flaxbean')
 * 2. M.mod_flax._Collection (normally with variable names 'cobj')
 */
M.mod_flax.CollectionManager = {
	cfg : null,
	model_collection_config: null,
	activity_xml: null,
	clistbody: null,
	//What to do following a collection is selected: either displaying its activity list or its article list
	chain_manager: null,
	coll_map: new HashMap(),

	
	init: function(cfg, colls, model_collection_config, activity_xml){
		this.cfg = cfg;
		this.listbody = yud.get(this.cfg.coll_list_body_id);
		this.cfg.model_collection_config = model_collection_config;
		this.cfg.activity_xml = activity_xml;
		this._init_buildcoll_panel();
		for(var coll, i=0; i<colls.length; i++){
			var flaxmeta = this._extract_flaxmeta(colls[i]);
			if(flaxmeta.category == LLDL.bundle.BuildCollection.c0//'flaxcoll'
					|| flaxmeta.category == LLDL.bundle.BuildCollection.c2//'public'
					){
				var flaxbean = new LLDL.Bean.FlaxCollection(this.cfg, flaxmeta);
				if(!flaxbean.doc_map || flaxbean.doc_map.size()<=0){
					console.log('invalid coll: '+flaxbean.meta.title);
					continue;//invalid collection
				}
				coll = new M.mod_flax._Collection(this.cfg, flaxbean);
				this.coll_map.put(coll.name, coll);
			}
		}	
	},
	set_chain_manager: function(manager){
		this.chain_manager = manager;
	},
	_extract_flaxmeta: function(flaxbean){
    	var meta = {};
        var metadata_list = flaxbean.getElementsByTagName('metadata');
        for(var m=0; m<metadata_list.length; m++) {
            var meta_el = metadata_list[m];
            var meta_name = meta_el.getAttribute('name');
            if(meta_name == 'collocation' || meta_name == 'docList') {
            	meta[meta_name] = meta_el;
            	continue;
            }
            var meta_value = (meta_el.firstChild)? meta_el.firstChild.nodeValue : '';
            meta[meta_name] = meta_value;
        }
        meta.collname = flaxbean.getAttribute('name');
        return meta;
    },
	_buildcol: function(collname){
		this.buildcoll_panel.show();
		
        var cfg = {
        		ajax_fn: M.mod_flax._query_flax,
        	    upload_fn: M.mod_flax._upload_flax,
        		ajax_server_url: this.cfg.flax_ajax_url,
        		help_server_url: this.cfg.flax_ajax_url,
        		images_url:      this.cfg.flax_images_url,
        		all_activity_map: new LLDL.Bean.FlaxActivityMap(this.cfg.activity_xml),
        		model_collection_config_xml : this.cfg.model_collection_config,
	      		username: this.cfg.login_username,
	      		mdlsiteid: this.cfg.mdlsiteid,
	        	exitbtn : {label:'Exit', scope:this, fn:null},
        		buildbtn : {label:'Build collection', scope:this, prefn:function(){
        						this.dismiss_buildcoll_panel();
			        			this.show_building_mask();
			        		}, 
			        		fn:null}
        };
        
        if(collname){//this is to edit an existing coll
        	cfg['collobj'] = this.coll_map.get(collname).flaxbean;
        	cfg['updatecoll'] = {fn:this._update_coll_handler, scope: this};
        	cfg['exitbtn'].fn = this._exit_edit_coll;
        	cfg['buildbtn'].fn = this._post_build_editcoll_handler;
        }else{
        	cfg['exitbtn'].fn = this._exit_build_newcoll;
        	cfg['buildbtn'].fn = this._post_build_newcoll_handler;
        }
		new LLDL.CollectionBuilder(this.buildcoll_panel.body, cfg);
		return;
	},
	_exit_build_newcoll: function(coll_flaxbean) {
	    this.dismiss_buildcoll_panel();
	    
	    if(coll_flaxbean.meta.collname != '_newcoll_') {
	    	// A dir for the collection had been created, delete it on server
	    	this._del_coll_on_server(coll_flaxbean.name, coll_flaxbean.category);
	    }
	},
	_exit_edit_coll: function(flaxbean/*instanceof LLDL.Bean.FlaxCollection*/){
		this.dismiss_buildcoll_panel();
		
		if(flaxbean.doc_map.size() == 0) {
			// The user has deleted all documents of the collection, let's delete the whole collection dir on server
			this._del_coll_on_server(flaxbean.meta.collname, flaxbean.meta.category);
		}else{
			this._update_coll_handler(flaxbean);
		}
	},
	_post_build_newcoll_handler: function(flaxbean, success){
		this.dismiss_building_mask();
		if(!success){
			console.log('in _post_build_newcoll_handler: build failed');
			return;
		}else{
			console.log('in _post_build_newcoll_handler: success');
		}
        var collname = flaxbean.meta.collname;
        var obj = {caller_obj: this,
                   callback_fn: this._post_build_newcoll_handler_callback,
                   passover_obj: flaxbean,
                   postdata: 's=BuildCollection&s1.service='+LLDL.services.SET_COLL_CATEGORY+
                   "&s1.collname="+collname+"&s1.category="+lbb.c2+"&s1.stus=public"//lbb.c2=='public'
        };
        M.mod_flax._query_flax(obj);
	},
	_post_build_newcoll_handler_callback: function(xml, flaxbean){

	  // Add collection node in html	    
	    var cobj = new M.mod_flax._Collection(this.cfg, flaxbean);
	    this.coll_map.put(cobj.name, cobj);
	    
	    this.append_coll(cobj);
	    this._programmatic_click_a_collection(cobj);
	    console.log(cobj);
	},
	_post_build_editcoll_handler: function(flaxbean){
		this.dismiss_building_mask();
		
		this._update_coll_handler(flaxbean);
		if(this.select_collection != flaxbean.meta.collname){
			// Make the collection in focus
			var coll = this.coll_map.get(flaxbean.meta.collname);
			this._programmatic_click_a_collection(coll);
		}
	},
	show_building_mask: function(){
		if(!this.build_coll_mask){			
			this.build_coll_mask = 
				new YAHOO.widget.Panel("_module_build_coll_mask_",  { width: "300px", 
					fixedcenter: true, 
					close: false, 
					draggable: false, 
//                                              zindex:4,
					modal: true,
					visible: false,
					underlay: "matte",
					constraintoviewport : true
				} 
				);
			
			var img_src = this.cfg.flax_images_url + 'blocking_loading.gif';    
			this.build_coll_mask.setBody("<p style='text-align:center;'>Processing, please wait ...</p>");
			this.build_coll_mask.setHeader("<img width=100% src=\""+img_src+"\"/>");
			this.build_coll_mask.render(document.body); 
		}
	    
	    this.build_coll_mask.show();
	},
	dismiss_building_mask: function(){
		this.build_coll_mask.hide();
	},
	dismiss_buildcoll_panel: function(){
		this.buildcoll_panel.clean();
		this.buildcoll_panel.hide();
	},
	_update_coll_handler: function(flaxbean/*instanceof LLDL.Bean.FlaxCollection*/){
		this.dismiss_buildcoll_panel();
		var collname = flaxbean.meta.collname;
		var mdl_coll_obj = this.coll_map.get(collname);
		mdl_coll_obj.update(flaxbean);
	},
    delete_coll: function(collname) {
    	var collobj = this.coll_map.get(collname);
    	this._del_coll_on_server(collname, collobj.flaxbean.category);
    	
    	this.listbody.removeChild(yud.get(collobj.collbody_id));
    	this.coll_map.remove(collname);
    	if(collname == this.select_collection){
    		//If the collection deleted was in focus, select another one and carry out the following up stuff
    		var another_coll = this.coll_map.valSet()[0];
    		if(another_coll){
    			this._programmatic_click_a_collection(another_coll);
    		}
    	}
    },
    _programmatic_click_a_collection: function(coll){
		this._set_current_collection(coll.name);
		coll.set_selected(true);
		this._carry_out_chain_evt(coll.name, null);
		
		var colls = this.coll_map.valSet();
		for(var i=0; i<colls.length; i++){
			colls[i].set_selected(colls[i].name == coll.name);
		}
    },
    _del_coll_on_server: function(collname, collcategory){
    	// Send a request to delete the collection on server
    	var obj = { caller_obj: this,
	                callback_fn: function(){ console.log('Deleting collection: '+collname+' was successful'); },
	                postdata: 's=BuildCollection&s1.service='+LLDL.services.DEL_COLL
	    			+"&s1.collname="+collname+"&s1.category="+collcategory };
    	M.mod_flax._query_flax(obj);
    	
    },
	_set_current_collection: function(collname){
		this.select_collection = collname;
		yud.get(this.cfg.collection).value = collname;//set mod_form value
//		this.coll_map.get(collname).display_activity_list(yud.get('flax_activity_list_container_id'));
	},
	_carry_out_chain_evt: function(collname, activitytype_or_docid){
		if(!collname){
			collname = this.select_collection;
		}
		this.chain_manager.show_view(this.coll_map.get(collname), activitytype_or_docid);
	},
	append_coll: function(coll){
		this.listbody.appendChild(coll.get_body());
	},
	display_collection_list: function(default_collname, default_activitytype_or_docid){
		this.listbody.innerHTML = '';
		if(this.coll_map.containsKey(default_collname) == false){
			default_collname = 'password';
		}
		this._set_current_collection(default_collname);//this.coll_map.keySet()[0]
		this.append_coll(this.coll_map.get('password'));//put 'password' collection on top
//		var html = '';
		for(var coll, i=0; i<this.coll_map.valSet().length; i++){
			coll = this.coll_map.valSet()[i];
			if(coll.name == 'password') continue;
			if(coll.is_flaxcoll()){
				this.append_coll(coll);
			}
		}
		for(var coll, i=0; i<this.coll_map.valSet().length; i++){
			coll = this.coll_map.valSet()[i];
			if(!coll.is_flaxcoll()){
				this.append_coll(coll);
			}
		}
		this.coll_map.get(default_collname).set_selected(true);
		
//		clistbody.innerHTML = html? html : '<em>No collections available</em>';
		yud.setStyle('new_coll_btn','display', '');
		
		this._carry_out_chain_evt(default_collname, default_activitytype_or_docid);

		yue.on(this.listbody, 'click', function(evt){
			var el = yue.getTarget(evt);
			var tag_name = el.tagName.toLowerCase();
			
			if(tag_name == 'input'){
				if(el.name == this.cfg.coll_el_name) {
					var cname = el.value;
					if(cname == this.select_collection) {
						//No change has been made
						return;
					}
					this._set_current_collection(cname);
					this._carry_out_chain_evt(cname);
					return;
				}
			}
			if(tag_name == 'img'){
				var collname = el.getAttribute('collname');
				var action = el.getAttribute('action');
				if(action=='edit'){
					this._buildcol(collname);
				}else if(action=='delete'){
					var coll_title = this.coll_map.get(collname).title;
					var x = window.confirm('Are you absolutely sure you want to completely delete collection '+coll_title+'?');
					if(x){
						this.delete_coll(collname);
					}
				}
				return;
			}
		}, this, true);
		yue.on('new_coll_btn', 'click', function(evt){//'new_coll_btn_container' defined in mod_form
			this._buildcol(null);
		}, this, true);
	},
	_init_buildcoll_panel: function(){
        // Init panel for building new collections
        this.buildcoll_panel = new M.mod_flax.mod_panel(this.cfg.buildcoll_panel_body_id, 'Build your own digital library collection');
	}
};
M.mod_flax.ActivityManager = {
	cfg: null,
	consts: null,
	//This is like an activity database that contains info about all activity types
	activity_map: new HashMap(),
	
	init: function(cfg, consts, activities){
		this.cfg = cfg;
		this.listbody = yud.get(this.cfg.activity_list_body_id);
		this.consts = consts;
		this._init_content_panel();

		for (var i=0; i<activities.length; i++) {
	    	var act_el = activities[i];
	    	var act_name = act_el.getAttribute('name');
			var act = new M.mod_flax._Activity(this.cfg, act_name, act_el);
			this.activity_map.put(act_name, act);
	    }
	},
	set_cobj_on_focus: function(cobj){
		this.cobj_on_focus = cobj;
	},
	/**
	 * 
	 * @param cobj The currently selected collection object
	 */
	show_view: function(cobj, activitytype_in_edit){
		this.set_cobj_on_focus(cobj);
		this.listbody.innerHTML = M.mod_flax.lib._get_icon_progress('Loading collection activities ...');
		//This contains activity types configured for the collection "collname"
		var html = '';
		var act_arr = this.cobj_on_focus.act_arr;
		if(act_arr && act_arr.length > 0) {			
//			act_arr.sort();//sort by ascending alphabetically
//			act_arr.reverse();//reverse to descending order
			var default_act = activitytype_in_edit || act_arr[0];		
			this._set_current_activity(default_act);
			this._load_exercise_content(this.cobj_on_focus.name);
			for(var actname, act, i=0; i<act_arr.length; i++){
				actname = act_arr[i];
				if(this.cfg.aval_activity_arr.indexOf(actname) == -1) continue;
				act = this.activity_map.get(actname);
				var checked = (actname == this.select_activity);
				html += act.get_body_html(checked, i) + '<br/>';
			}
		} else {
			html = '<em>No activity was configured for the collection</em>';
		}
		this.listbody.innerHTML = html;

		yue.on(this.listbody, 'click', function(evt){
			var el = yue.getTarget(evt);
			var tag_name = el.tagName.toLowerCase();
            if(tag_name == 'input'){
            	if(el.name == this.cfg.activity_el_name) {
            		var actname = el.value;
            		if(actname == this.select_activity) {
            			//No change has been made
            			return;
            		}
            		this._set_current_activity(actname);
            		this._load_exercise_content(this.cobj_on_focus.name);
            		return;
            	}
            }
		}, this, true);
		yue.on('edit_btn', 'click', function(evt){//'edit_btn' defined in mod_form
			this.content_panel.show();
			return;
		}, this, true);
	},
	_set_current_activity: function(actname){
		this.select_activity = actname;
		this._set_activitytype(actname);
	},
	_set_activitytype: function(actname){
		yud.get(this.consts.ACTIVITYTYPE).value = actname;
	},
	_load_exercise_content: function(collname){
//		if(!window.navigator.onLine){
//			var msg = 'Your computer is not connected to any network';
//			this._set_content_summary(msg, false);
//			this.cfg.trans_modal_mask.hide();
//			this.content_panel.clean();
//			return;
//		}
		// show loading indicators
		this._set_content_summary(M.mod_flax.lib._get_icon_progress('Loading exercise content'), false);
		this.cfg.trans_modal_mask.show();

		this.content_panel.clean();

        var script_name = 'Design' + this.select_activity;

	    var o=LLDL.getDesignActivityConfigObject(  
	    		/*1: ajax_server_url*/         null, 
	            /*2: help_server_url*/         this.cfg.flax_ajax_url,
	            /*3: url to use in case error*/null, 
	            /*4: images_url*/              this.cfg.flax_images_url, 
	            /*5: serving_activity_url*/    '', 
	            /*6: caller_obj*/              this, 
	            /*7: caller_obj_callback_fn*/  null, 
	            /*8: ajax_fn*/                 M.mod_flax._query_flax,//this._relay_flax_4_design_exercise, 
	            /*9: coll_name*/               collname,
	            /*10:is_teacher*/              this.cfg.is_teacher,
	            /*11:lang*/                    'en'
	      );
	//flax_server_url is particularly important to ImageGuessing activity in module when displaying all pool images 
	// (unlike stand-alone version, it needs absolute url address)                                     
	    o.wwwroot = this.cfg.flaxserver + '/greenstone3/';
	    o.post_fn = 'saveExercise';
	    var activity_design_obj = new LLDL.activities[script_name+'Module'](this.content_panel.body, o);
	    activity_design_obj.isMoodle = true;//so that the 'Previous' button is not shown, and iframe height will be auto adjusted as well

//		var save_exercise_fn = LLDL.activities[script_name+'Module'].prototype.saveExercise;

		var this_obj = this;
		activity_design_obj.saveExercise = function(){
			//The execution context here is the instance of LLDL.activities[module_classname], 
			// hence the declaration of 'activity_design_obj' above
			var o = LLDL.activities[script_name+'Module'].prototype.saveExercise.call(activity_design_obj, arguments[0]);
//			this_obj._set_exercise_content(arguments[0]);
			this_obj._set_exercise_content(o, collname);
			this_obj.content_panel.hide();
			
			//Finish loading
//			this_obj.cfg.loading_mask.hide();
		};
	},
	_set_content_summary: function(summary, show_edit_btn){
		yud.get(this.cfg.contentsummary_id).innerHTML = summary;
		this.cfg.trans_modal_mask.hide();
		yud.get('edit_btn').disabled = show_edit_btn? false : true;		
	},
	_set_exercise_content: function(param_o, collname){
		if(!param_o){
			this._set_content_summary('Content not valid', false);
			return;
		}
		this._set_content_summary(param_o[this.consts.CONTENTSUMMARY], true);//'Illustrative exercise content summary';
		
		var serving_activity_url_common_params =  
            '/greenstone3/flax?a=g&rt=r&c=' + collname + '&s1.collname=' + collname + 
            '&sa=Activity&s=' + this.select_activity +'&s1.service=' + LLDL.services.ACTIVITY_SERVICE; 
		var link = this.cfg.flaxserver+serving_activity_url_common_params + '&s1.'+LLDL.params+'=' + param_o.activity_params;
		
		yud.get(this.cfg.flaxurl).value = link;
		
	    for (var p in param_o) {
	    	//The loop is to set mod_form hidden elements (paramkeys, paramvalues, activitycontents, activityanswers, activitymode, contentsummary)
	    	//which later will be picked up by lib.php/flax_add_instance
	    	if(param_o.hasOwnProperty(p)){
	    		var elem = yud.get(p);
	    		if(elem){
	    			
	    			elem.value = param_o[p];
	    		}
	    	}
	    }
	    this._set_activitytype(this.select_activity);
		
		yud.get(this.consts.GRADEOVER).value = param_o[this.consts.GRADEOVER];
	},
	//Hacky: function is called spcifically by activity classes
	getDesignInterfaceButtons: function(btn_panel,cfg) {
		var lang = 'en';
		var config_info = {//TODO
			help_server_url: this.cfg.flax_ajax_url,
			images_url: this.cfg.flax_images_url
		};
		var external_btns = new HashMap();
		if(cfg.preview) {
			var preview_btn = LLDL.createButtonEl(lb.designactivity.preview_btn_id, LLDL.bundle.designactivity.preview_btn_label);
			btn_panel.appendChild(preview_btn);
			btn_panel.appendChild(LLDL.getHelpButtonEl(config_info, cfg.activityname+".preview", lang, LLDL.bundle.designactivity.preview_btn_label));
			external_btns.put('preview', preview_btn);
		}
		if(cfg.save) {
		    var save_btn  = LLDL.createButtonEl(lb.designactivity.save_btn_id, LLDL.bundle.designactivity.save_and_exit_btn_label);
		    btn_panel.appendChild(save_btn);
		    btn_panel.appendChild(LLDL.getHelpButtonEl(config_info, "activity.save", lang, LLDL.bundle.designactivity.save_and_exit_btn_label));
		    external_btns.put('save', save_btn);
		}
		
		var cancel_btn  = LLDL.createButtonEl('mod_flax_cancel_btn_id', 'Cancel');
		btn_panel.appendChild(cancel_btn);
		btn_panel.appendChild(LLDL.getHelpButtonEl(config_info, "mod_flax.cancel", lang, 'Cancel'));
		external_btns.put('cancel', cancel_btn);
		yue.on(cancel_btn, 'click', function(){
			this.content_panel.hide();
		}, this, true);
		return external_btns;
	},
	_set_mod_form_default_values: function(collname, activitytype){
		yud.get('collection').value = collname;
		var act_type_el = yud.get('activitytype');
		if(act_type_el){
			act_type_el.value = activitytype;
		}
	},
	_init_content_panel: function(){
		// Init pane for edit exercise content
        this.content_panel = new M.mod_flax.mod_panel(this.cfg.content_panel_body_id, 'Edit exercise content');
	}
	
};
M.mod_flax._query_flax = function(obj) {
	var url = M.mod_flax.cfg.mdlsiteurl+'/mod/flax/view.php?ajax=queryflax';
	var postdata = obj.postdata;
	yuc.initHeader("Cache-Control", "no-cache", true); 
	yuc.initHeader("Expires", "-1", true); 
	yuc.asyncRequest('POST', url, {
		success:function(o){ 
			// response type is string (see query_flax.php and locallib.php/query_flax)
			obj.callback_fn.call(obj.caller_obj, LLDL.string2xml(o.responseText), obj.passover_obj);                                                    
		},                 
		failure:function(o){ 

			obj.callback_fn.call(obj.caller_obj, obj.err_msg, false);
			console.log(LLDL.getObjProperty(o));
		}
	}, postdata);    
};
M.mod_flax._upload_flax = function(obj) {
	if(!obj || !obj.form) return false;
	var url = M.mod_flax.cfg.mdlsiteurl+'/mod/flax/view.php?ajax=uploadflax';
	//see setForm function for details
	YAHOO.util.Connect.setForm(obj.form, true, true);
	callback = {
	    upload:function(o){ 
            var xml = LLDL.talk2serverGetResponseXML(o);
            obj.callback_fn.call(obj.caller_obj, xml, obj.passover_obj);                                                    
        }
	};
	YAHOO.util.Connect.asyncRequest('POST', url, callback, obj.postdata);
};
M.mod_flax.DocumentManager = { 
	cfg:null,
	listbody:null,
	
	init: function(cfg){
		this.cfg = cfg;
		console.log(this.cfg.document_list_body_id);
		this.listbody = yud.get(this.cfg.document_list_body_id);
	},
	set_cobj_on_focus: function(cobj){
		this.cobj_on_focus = cobj;
	},
	show_view: function(cobj, docid_in_edit){
		this.set_cobj_on_focus(cobj);
		var collname = this.cobj_on_focus.name;
		this.listbody.innerHTML = M.mod_flax.lib._get_icon_progress('Loading collection articles ...');
		var html = '';
		//A special Document object representing the whole collection 
		//is added as the first item of cobj.doc_map in M.mod_flax._Collection.init()
		//if there are more than one document in the collection
		var doc_arr = this.cobj_on_focus.doc_map.valSet();
		var default_docid = docid_in_edit || doc_arr[0].docid;
		this._set_current_doc(default_docid, collname);
		for(var doc, i=0; i<doc_arr.length; i++){
			doc = doc_arr[i];
			var checked = false;
			if(doc.docid === this.cfg.dummydoc_id){
				checked = (default_docid !== this.cobj_on_focus.name&&doc_arr.length>1);
			}else{
				if(default_docid == doc.docid){
					checked = true;
				}else{
					checked = false;
				}
			}
			html += doc.get_body_html(checked) + '<br/>';
		}
		this.listbody.innerHTML = html;

		yue.on(this.listbody, 'click', function(evt){
			var el = yue.getTarget(evt);
			var tag_name = el.tagName.toLowerCase();
			if(tag_name !== 'input') return;
			var docid = el.id;
			if(!docid) return;
    		if(docid == this.select_docid) {
    			//No change has been made
    			return;
    		}
        	if(docid === this.cfg.dummydoc_id){
        		if(this.select_docid !== this.cobj_on_focus.name){
        			// One of the docs is already selected, ignore
        			return;
        		}
        		var whole_coll_doc = this.cobj_on_focus.doc_map.get(this.cobj_on_focus.name);
        		whole_coll_doc.set_selected(false);
        		var first_doc = this.cobj_on_focus.doc_map.get(this.cobj_on_focus.first_doc_id);
        		first_doc.set_selected(true);
        		this._set_current_doc(this.cobj_on_focus.first_doc_id, collname);
        	}
        	if(el.name == 'doc') {
        		var dummydoc = this.cobj_on_focus.doc_map.get(this.cfg.dummydoc_id);
        		if(docid === this.cobj_on_focus.name){//clicking on whole coll
        			console.log(dummydoc.docid);
        			dummydoc.set_selected(false);
        		}else{
        			dummydoc.set_selected(true);
        		}
        		this._set_current_doc(docid, collname);
        	}
		}, this, true);
	},
	_set_current_doc: function(docid, collname){
		this.select_docid = docid;
		//set mod_form values
		yud.get(this.cfg.docid).value = docid;
		var url = this.cfg.flaxserver + '/greenstone3/flax?c='+collname+'&';
		if(docid == collname){//the whole collection has been selected
			url += 'a=b&rt=r&s=ClassifierBrowse&cl=CL1';
		}else{
			url += 'a=md&dt=simple&dib=1&p.sa=&p.s=classifierBrowse&d='+docid;
		}
		yud.get(this.cfg.flaxurl).value = url;
	}
};
	
M.mod_flax._Collection = function(cfg, flaxbean){
	this.cfg = cfg;
//	this.coll_xml = coll;
	this.coll_idx = null;
	this.collbody_id = null;
//	this.init(coll_info);
	this.init(flaxbean);
};
M.mod_flax._Collection.prototype = {
	init: function(flaxbean, chain_manager){
		this.flaxbean = flaxbean;
    	this.name = flaxbean.meta.collname;
    	this.title =flaxbean.meta.title;
    	this.description =flaxbean.meta.description;
    	this.category =flaxbean.meta.category;
    	this.creator =flaxbean.meta.creator;
    	this.act_arr = this._extract_activity_info();
    	
    	//Convert FlaxDocument to M.mod_flax._Document
    	this.doc_map = new HashMap();
    	var docs =flaxbean.doc_map.valSet();
    	var doc_el_name = 'doc', dummy_doc_el_name = 'ddoc';
    	if(docs.length === 1){
    		var doc = docs[0];
    		this.doc_map.put(doc.Identifier, new M.mod_flax._Document(this.cfg, doc_el_name, doc.Identifier, doc.Title));
    	}else{
    		
    		// If there are more than one document in the collection, 
    		// add as first element to the map an 'all_doc' document (i.e., all documents of the collection)
    		var all_docid = this.name;
    		var all_doc = new M.mod_flax._Document(this.cfg, doc_el_name, all_docid, 'The whole collection');
    		this.doc_map.put(all_docid, all_doc);
    		// add as second element to the map an 'dummy_doc' document (i.e., caption of the documents in the collection)
    		// set its docid to the id of the first document
    		var dummy_doc = new M.mod_flax._Document(this.cfg, dummy_doc_el_name, this.cfg.dummydoc_id, 'A single article');
    		this.doc_map.put(this.cfg.dummydoc_id, dummy_doc);
    		
    		//picked up by DocumentManager/click event-handler
    		this.first_doc_id = docs[0].Identifier;
    		var indent_right = true;
    		for(var doc, i=0; i<docs.length; i++){
    			doc = docs[i];
    			this.doc_map.put(doc.Identifier, new M.mod_flax._Document(this.cfg, doc_el_name, doc.Identifier, doc.Title, indent_right));
    		}
    	}
    	
	},
	_extract_activity_info: function(){
    	var act_arr = this.flaxbean.meta.activity.split(',');
    	var collo_act_arr = [];
    	var collo_metas = this.flaxbean.meta.collocation.getElementsByTagName('meta');
    	if(collo_metas && collo_metas.length > 0){
    		for(var i=0; i<collo_metas.length; i++){
    			var collo = collo_metas[i];
    			var collo_name = collo.getAttribute('name');
    			if(collo_name == 'activity'){
    				var act = collo.getAttribute('value');
    				if(act && act.length>0){
    					collo_act_arr = collo_act_arr.concat(act.split(','));
    				}
    			}
    		}
    	}
    	return act_arr.concat(collo_act_arr);
	},
	get_body: function(){
		this.collbody_id = 'collbody_'+this.name;
		var cname = this.cfg.coll_el_name;
		var el_id = cname+'_'+this.name;
		var html = '<input type="radio" name="'+cname+'" value="'+this.name+'" id="'+el_id+'"/>'+
		'<label for="'+el_id+'" id="clabel_'+this.name+'" title="'+this.description+'">'+this.title + '</label>';
		if(this.creator == this.cfg.login_username){
			//for private collections, append functional buttons at the end
			html += M.mod_flax.lib._get_icon('edit', this.name);//
			html += M.mod_flax.lib._get_icon('delete', this.name);
		}
		var bd = LLDL.createInnerHTML('div', 'collbody_'+this.name, html);
		return bd;
	},
	update: function(flaxbean){
		this.init(flaxbean);
		this.refresh_title_in_dom();
	},
	refresh_title_in_dom: function(){
		yud.get('clabel_'+this.name).innerHTML = this.title;
	},
	is_flaxcoll: function(){ 
		return this.category == 'flaxcoll';
	},
	set_selected: function(selected){
		yud.get('cname_'+this.name).checked = selected;
	}
};
M.mod_flax._Activity = function(cfg, act_name, act_el){
	this.cfg = cfg;
	this.name = act_name;
	this.init(act_el);
};
M.mod_flax._Activity.prototype = {
	init: function(act_el){
		this.title = LLDL.getDomFirstChildTextNodeValue(act_el, 'title');
		var para_arr = act_el.getElementsByTagName('paragraph');
		
		var html = '';
		if(para_arr && para_arr.length>0){
			for(var i=0; i<para_arr.length; i++){
				html += para_arr[i] + '<br/>';
			}
		}
		this.description = html;
	},
	get_body_html: function(checked, idx){
		var aname = this.cfg.activity_el_name;
		var el_id = aname + '_'+this.name;
		var check_it = checked?'checked':'';
		return '<input '+check_it+' type="radio" name="'+aname+'" value="'+this.name+'" id="'+el_id+'"/>'+
		'<label for="'+el_id+'" title="">'+this.title + '</label>';
	}
};
M.mod_flax._Document = function(cfg, el_name, docid, doc_title, indent_right){
	this.cfg = cfg;
	this.el_name = el_name;
	this.docid = docid;
	this.title = doc_title;
	this.indent_right = indent_right;
};
M.mod_flax._Document.prototype = {
	get_body_html: function(checked){
		var check_it = checked?'checked':'';
		var html = '<input '+check_it+' type="radio" name="'+this.el_name+'" value="doc_'+this.docid+'" id="'+this.docid+'"'+
			(this.indent_right?' class="mod_flax_indent_right"':'') + '/>'+
			'<label for="'+this.docid+'" title="">'+this.title + '</label>';
		return html;
	},
	set_selected: function(selected){
		yud.get(this.docid).checked = selected;
	}
};