var Wh_Languages=new Class({
	Implements: Options,
	options:{
		'jsonLang':'',
		'elementsClass':'',
		'imgDir':'/skin/_js/wh-language/flags/'
	},
	initialize: function( options ){
		this.setOptions( options );
		this.createTab();
	},
	createTab: function(){
		if( $$( '.'+this.options.elementsClass).length==0 ){
			return;
		}
		var form=false;
		var hashLang=new Hash(JSON.decode(this.options.jsonLang));
		$$( '.'+this.options.elementsClass ).each(function(element){
			var div=new Element('div',{
				'class':'wh-lang-tab',
			});
			hashLang.each( function(lang,lang_id){
				var value=element.getPrevious('input.'+lang.ico.replace('.gif','')).value;
				var a=new Element('a',{
					'href':'#',
					'class':'wh-lang-a  ' + ((lang.def==1)?' wh-lang-active wh-lang-default':( ( !$chk(value) )?' wh-lang-empty':' wh-lang-noempty' )),
					'rel':lang.ico.replace('.gif','')
				});
				var img=new Element('img',{
					'class':'wh-lang-img' + ( ($chk(value))? ' langtips':''),
					'rel':value,
					'src':this.options.imgDir+lang.ico,
					'title':lang.title
				});
				img.inject(a);
				a.inject(div);
			},this);
			div.inject(element,'after');
			element.setStyle('display','none');
			if( form==false ){
				form=element.getParent('form');
			}
		},this);
		var div=new Element('div',{
			'class':'wh-lang-tab',
			'html':'Switch all fields: '
		});
		hashLang.each( function(lang,lang_id){
				var a=new Element('a',{
					'href':'#',
					'class':'wh-lang-global lang-tips',
					'rel':lang.ico.replace('.gif','')
				});
				var img=new Element('img',{
					'class':'wh-lang-img ',
					'src':this.options.imgDir+lang.ico,
					'title':lang.title
				});
			img.inject(a);
			a.inject(div);
		},this);
		div.inject( form, 'top' );
		this.initEvents();
	},
	initEvents: function(){
		this.initTips();
		$$('.wh-lang-default').each(function(a){
			var input_default=a.getParent('div.wh-lang-tab').getPrevious('input.'+a.rel);
			var input_original=a.getParent('div.wh-lang-tab').getPrevious('input.'+this.options.elementsClass);
			input_default.addEvent('change',function(){
				input_original.set('value',input_default.value );
			});
		},this);
		$$('.wh-lang-global').each(function(global){
			
			global.addEvent('click', function(e){
				e && e.stop();
				$$('.wh-lang-global').each(function(el){el.removeClass('wh-lang-active');});
				$$('input.wh-lang-input').each(function(el){
					if( el.hasClass( global.rel ) ){
						el.setStyle('display','block');
					} else {
						el.setStyle('display','none');
					}
				});
				$$('a.wh-lang-a').each(function(el){
					if( el.rel == global.rel ){
						el.addClass('wh-lang-active');
						global.addClass('wh-lang-active');
					} else {
						el.removeClass('wh-lang-active');
					}					
				});
			});
		});
		$$('.wh-lang-a').each(function( element ){
			element.addEvent('click',function(e){
				e && e.stop();
				element.getParent('div.wh-lang-tab').getChildren('a').removeClass('wh-lang-active');
				element.addClass('wh-lang-active');
				element.getParent('div.wh-lang-tab').getAllPrevious('input.wh-lang-input').setStyle('display','none');
				element.getParent('div.wh-lang-tab').getPrevious('input.'+element.rel).setStyle('display','block');
				$$('a.wh-lang-a').each(function(a){
					if( $chk( a.getParent('div.wh-lang-tab').getPrevious('input.'+a.rel) ) && $chk( a.getParent('div.wh-lang-tab').getPrevious('input.'+a.rel).value ) ){
						a.removeClass('wh-lang-empty');
						a.addClass('wh-lang-noempty');
					}
				},this);
			}.bind(this));
		},this);
	},
	initTips: function(){
		var optTips = new Tips('.langtips', {
			showDelay: 400,
			hideDelay: 400,
			fixed: true
		});		
	}
});