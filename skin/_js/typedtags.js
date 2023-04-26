	var Tags = new Class({
		Extends: Options,
		options: {
			textarea:'.wh-tag-textarea',
			buttonSave:'.wh-tag-save',
			buttonCancel:'.wh-tag-cancel',
			buttonEdit:'.wh-tag-edit',
			url:'',
			itemId:0,
			type:'null',
			searchUrl:''
		},
		initialize: function( options ) {
			this.setOptions( options );
			this.cachTag=Array.from({});
			this.initEvents();
		},
		initEvents: function(){
			// кнопка Save
			$$( this.options.buttonSave ).each(function(el){
				if( !this.check(el) ) {
					return false;
				}
				el.addEvent('click', function(e){
					e.stop();
					this.save(el);
				}.bind(this));
			},this);
			// кнопка Edit
			$$( this.options.buttonEdit ).each( function(el){
				if( !this.check(el) ) {
					return false;
				}
				el.addEvent('click', function(e){
					e.stop();
					this.edit(el);
				}.bind(this));
			},this);
			// кнопка Cancel
			$$( this.options.buttonCancel ).each( function(el){
				if( !this.check(el) ) {
					return false;
				}
				el.addEvent('click', function(e){
					e.stop();
					this.cancel(el);
				}.bind(this));
			},this);
			$$( '.wh-tag-delete' ).each( function(el){
				el.addEvent('click', function(e){
					e && e.stop();
					this.deleteTag(el);
				}.bind(this) );
			},this);
		},
		deleteTag: function(element){
			if( !$chk( this.cachTag[this.options.itemId] )){
				this.cachTag[this.options.itemId]=element.getParent('div').get('html');
			}
			this.setBorder( element.getParent('div') );
			element.getParent('span').destroy();
		},
		check: function(el){
			var conteiner=el.getParent('div').getPrevious('div');
			if( conteiner.id != 'wh-tag-cloud-'+this.options.itemId ){
				return false;
			}
			return true;
		},
		save: function( element ){
			if( this.cachTag[this.options.itemId] == undefined){
				return;
			}
			var conteiner=element.getParent('div').getPrevious('div');
			if( conteiner.getChildren('textarea')[0]!=null ){
				var strTags=conteiner.getChildren('textarea')[0].get('value');
			} else {
				conteiner.getChildren('span').each( function(span){ span.getChildren('a.wh-tag-delete').each(function(el){ el.destroy() }); });
				var strTags=conteiner.get('html').stripTags();
			}
			this.clearErrors(element);
			if( strTags=='' ){
				var span=new Element('span',{
						'html':'Error! Can\'t save tags',
						'class':'wh-tag-errors',
						styles:{
							color:'red',
							'font-size':'10px'
						}
					});
				span.inject(conteiner,'after');
				return false;
			}
			var obj=this;
			var tagRequest=new Request.JSON({
				url:this.options.url,
				onSuccess: function(result){
					if( result.error ){
						conteiner.set('html',obj.cashTag[obj.options.itemId]);
						var span=new Element('span',{
							'html':'Error! Can\'t save tags',
							'class':'wh-tag-errors',
							styles:{
								color:'red',
								'font-size':'10px'
							}
						});
						span.inject(conteiner,'after');
						return;
					}
					var strTags='';
					result.tags.each(function( item ){
						if( $chk(obj.options.searchUrl) ){
							strTags+='<span><a target="_blank" href="'+obj.options.searchUrl+'?tag='+item.decoded+'">'+item.decoded+'</a><a href="#" class="wh-tag-delete"> x</a>, </span>';
						} else {
							strTags+='<span>'+item.decoded+'<a href="#" class="wh-tag-delete"> x</a>, </span>';
						}
					});
					conteiner.set('html',strTags.replace(/, <\/span>$/i,'') );
					obj.clearBorder(conteiner);
					obj.initEvents();
				},
				onRequest: function(){
					conteiner.empty();
					var img=new Element('img',{
						'src':'/skin/i/frontends/design/ajax_loader_line.gif',
						'id':'loader'
					});
					img.inject(conteiner);
				},
				onComplete: function(){ conteiner.empty(); }
			}).post({ type:this.options.type, item_id:this.options.itemId, tags:strTags });
			this.cachTag[this.options.itemId]=undefined;
		},
		edit: function(element){
			if( $chk(this.cachTag[this.options.itemId]) ){
				return;
			}
			this.clearErrors(element);
			var conteiner=element.getParent('div').getPrevious('div');
			this.clearBorder(conteiner);
			this.cachTag[this.options.itemId]=conteiner.get('html');
			conteiner.getChildren('span').each( function(span){ span.getChildren('a.wh-tag-delete').each(function(el){ el.destroy() }); });
			var tags=conteiner.get('html');
			conteiner.empty();
			var textarea=new Element('textarea',{
				name:'tags',
				value:tags.stripTags(),
				rows:6,
				cols:10,
				class: this.options.textarea.replace('.','')
			});
			textarea.inject(conteiner);
		},
		cancel: function(element){
			this.clearErrors(element);
			var conteiner=element.getParent('div').getPrevious('div');
			this.clearBorder(conteiner);
			if( !$chk(this.cachTag[this.options.itemId]) ){
				return;
			}
			conteiner.set( 'html', this.cachTag[this.options.itemId] );
			this.cachTag[this.options.itemId]=false;
			this.initEvents();
		},
		setBorder: function( block ){
			var myEffects=new Fx.Morph( block,{duration: 1000, transition: Fx.Transitions.Sine.easeOut});
			myEffects.start({
				'border-width':'1',
				'border-style':'solid',
				'border-color': ['#FFFFFF','#EE0000']
			});
		},
		clearBorder: function( block ){
			block.setStyle('border-color','#FFFFFF');
		},
		clearErrors: function(element){
			var conteiner=element.getParent('div').getPrevious('div');
			var errors=conteiner.getNext('span');
			if( errors ){
				errors.destroy();
			}
		}
	});