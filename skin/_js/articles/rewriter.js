var hashVariations;
var Rewriter = new Class({
	Extends: Options,
	options:{
		conteiner: '.conteiner', // блоки с текстом для обработки;
		conteinerOriginal: 'container-original', // контейнер для хранения оригинала текста;
		conteinerDefaultVariations: 'default-variation', // Дефолтные вариации;
		conteinerUserVariations: 'user-variation', // Пользовательские вариации;
		data2post:'data-body', // готовый текст для отправки на сервер;
		data2postTitle:'data-title', // готовый текст для отправки на сервер;
		submitButton:'submit', // кнопка отправки формы;
		cancelButton:'cancel', // кнопка отмены поледнего изменения;
		cancelAllButton:'cancel-all', // кнопка отмены всех изменений;
		applyButton:'apply', // кнопка применить;
		apply2allButton:'apply-all', // кнопка применить для всех вариантов;
		rewriteBlock:'rewrite', // блок с кнопками rewrite и cancelAll;
		settingsBlock: 'settings', // блок с инструментами для подбора вариантов;
		linkDefultVariations: '', // ссылка для получения общих синонимов;
		linkUserVariations: '', // ссылка для получения пользовательских синонимов;
		userVars:'user-vars', // пользовательские варианты для отправки на сервер;
		selectArticleBlock:'select-article-block', // блок для выбора статей; 
		systemSynonyms:'default-variation', // seslect с общиими синонимами;
		systemSynonymsBlock:'system-synonyms', // seslect с общиими синонимами;
		usersSynonyms:'user-variation' // seslect с общиими синонимами;
	},
	initialize: function( options ){
		this.setOptions(options);
		this.initElements();
		this.objSelection={};
		this.initEvent();
		this.flgChange=false;
		hashVariations=new Hash();
	},
	// Добавление всем елементам нового метода ::getSelection();
	initElements: function(){
		Element.implement({
			getSelection: function() {
				var text = '', doc = this.getDocument();
				var uId = new Date().getTime();
				if ( $defined( window.getSelection ) ) {
					var selection = window.getSelection();
					text=selection.toString();
					if( text.test( /\|/g ) || text.test( /\n/g ) ){ // Если выделение пересекается с уже созданым;
						alert('Error: Selection not correct');
						return false;
					}
					if( !$chk(text) ){	return false; } // Есл пусто;
					try{
						var selection = window.getSelection();
						var range = selection.getRangeAt( 0 );
						if( range.endContainer.parentNode != range.startContainer.parentNode ){
							if( range.endContainer.parentNode.nodeName.toLowerCase() == 'span' ){
								range.setEndBefore(range.endContainer.parentNode);
							}
							if( range.startContainer.parentNode.nodeName.toLowerCase() == 'span' ){
								range.setStartAfter(range.startContainer.parentNode);
							}
						}
						var span = new Element( 'span', { 'id':'id-'+uId } );
						range.surroundContents( span );
					} catch( e ) { // Если выделение пересекается с уже созданым;
						alert('Error: Selection not correct: '+ e);
						return false;
					}
					text = selection.toString();
				} else if ( $defined( doc.selection ) ) {
					var range = doc.selection.createRange();
					if(!$chk(range.text)){
						return false;
					}
					if( range.text.test(/\|/) || range.htmlText.test(/<|>/i)  || range.text.test( /\r/ ) || range.text.test( /\n/ ) ){ // Если выделение пересекается с уже созданым;
						alert('Error: Selection not correct');
						return false;
					}
					text = range.text;
//					var span = new Element( 'span', { 'id':'id-'+uId,'html':range.text } );
					range.pasteHTML('<span id="id-'+uId+'">'+range.text+'</span>');
				}
				return { 'text': text, 'uId':uId }
			}
		});		
	},
	// Блокировка контейнера с текстом;
	selectBlocked: function(){
		var height=$('div2blocked').clientHeight+10;
		var width=$('div2blocked').clientWidth+10;
		$('blocked').setStyle('width',width);
		$('blocked').setStyle('height',height);
	},
	// Снятие блокировки контейнера с текстом;
	selectUnblocked: function(){
		$('blocked').setStyle('width',0);
		$('blocked').setStyle('height',0);
	},
	// Инициализация сообытий;
	initEvent: function(){ 
		// Selection text events;
		$$(this.options.conteiner).each(function(element){ 
			element.addEvent('mouseup',function(e) {	
				$(this.options.conteinerUserVariations).value='';
				$(this.options.conteinerDefaultVariations).value='';
				this.objSelection = element.getSelection();
				if ( !$chk( this.objSelection.text ) ) { // If not selected text
					return;
				}
				this.objSelection.element = element;
				this.setColor();
				$( this.options.settingsBlock ).setStyle('display','block');
				$( this.options.systemSynonymsBlock ).setStyle('display','block');
				$( this.options.systemSynonyms ).setStyle('display','none');
				$( this.options.selectArticleBlock ).setStyle('display','none');
				$( this.options.usersSynonyms ).set('readonly','true');
				$('system-loader').setStyle('display','inline');
				$('users-loader').setStyle('display','inline');
				this.startRewrite();
				this.selectBlocked();
			}.bindWithEvent( this ) );
		},this);
		// Users variation events;		
		$(this.options.conteinerUserVariations).addEvent('keyup',function(e){
			this.keyPressHandler();
		}.bindWithEvent(this) );
		
				
		// Default variations events;
		$(this.options.conteinerDefaultVariations).addEvent('dblclick',function(e){
			$(this.options.conteinerUserVariations).set('value',$(this.options.conteinerUserVariations).get('value') + '\n' + $(this.options.conteinerDefaultVariations).value );
			this.keyPressHandler();
		}.bindWithEvent(this) );
				
		// Button events;
		$( this.options.cancelButton ).addEvent('click',function(){
			this.cancel();
		}.bindWithEvent(this) );		
		
		$( this.options.cancelAllButton ).addEvent('click',function(){
			this.cancelAll();
		}.bindWithEvent(this) );
				
		$( this.options.applyButton ).addEvent('click',function(){
			this.apply();
		}.bindWithEvent(this) );
		
		$( this.options.apply2allButton ).addEvent('click',function(){
			this.apply2all();
		}.bindWithEvent(this) );
		
		$( this.options.submitButton ).addEvent('click',function(e){ 
			this.prepareData();
		}.bindWithEvent(this) );
	
	},
	// Подготовка данных для отправки на сервер;
	prepareData: function(){
		$(this.options.data2post).value=$('body').get('html').replace(/<br>/ig,'\r\n');
		$(this.options.data2postTitle).value=$('title').get('html');
		var strVariations='';
		hashVariations.each(function(value,key){ 
			strVariations += ( ( strVariations!='' ) ? '::|::':'' ) + value;
		});
		$(this.options.userVars).set('value', strVariations );		
	},
	// Старт процесса обраюотки выделенного текста;
	startRewrite: function(){  
		this.flgChange=false;
		this.getDefaultVariations();
		this.getUserVariations();
		$(this.options.conteinerUserVariations).set('value', this.objSelection.text );
		
	},
	// Общие варианты;
	getDefaultVariations: function(){
		if( this.objSelection.text.length > 75 ){ 
			$( this.options.systemSynonymsBlock ).setStyle('display','none');
			return false;
		}
		var objThis=this;
		var request = new Request.MyJSON({
			url:this.options.linkDefultVariations,
			onSuccess: function( res ){ 
				$('system-loader').setStyle('display','none');
				if( !$chk( res ) || res['arrVariations'].length == 0 ){ $(objThis.options.systemSynonymsBlock).setStyle('display','none');	return false; }
				$$('.options-synonyms').each(function(opt){
					opt.destroy();	
				});
				res['arrVariations'].each(function( item ){
					var option = new Element('option',{'html':item,'value':item,'class':'options-synonyms'});
					option.inject($( objThis.options.conteinerDefaultVariations ));
				});
				$( objThis.options.systemSynonyms ).setStyle('display','inline');
			}
		}).post({ selectedText:this.objSelection.text });
	},
	// Пользовательские варианты;
	getUserVariations: function(){
		var objThis=this;
		var request = new Request.MyJSON({
			url:this.options.linkUserVariations,
			onSuccess: function( res ){ 
				$('users-loader').setStyle('display','none');
				$( objThis.options.usersSynonyms ).removeProperty('readonly');
				if( !$chk( res ) || res['arrVariations'] == false ){ return false; }
				var strVariation = res['arrVariations'].join('\n');
				$( objThis.options.conteinerUserVariations ).set('value',$( objThis.options.conteinerUserVariations ).get('value') + '\n' + strVariation);
				objThis.keyPressHandler();
				
			}
		}).post({ selectedText:this.objSelection.text });		
	},
	// Выделение цветом выбранного текста;
	setColor: function(){
		if( $chk( this.objSelection.flg_color ) ){
			return false;
		}
		this.correctsSelection();
		$('id-'+this.objSelection.uId ).morph( { 'color':this.getRandomColor() } );		
		this.objSelection.flg_color = true;
	},
	// Корректировка выделенного текста.
	correctsSelection: function(){
		var html = this.objSelection.element.get('html');
		if( this.objSelection.text.substring(this.objSelection.text.length-1,this.objSelection.text.length) == ' ' ){
			html=html.replace(new RegExp('<span id="?id-' + this.objSelection.uId + '"?>.*?<\/span>','i'),'<span id="id-'+this.objSelection.uId+'">'+this.objSelection.text+'</span> ');
		}
		if( this.objSelection.text.substring(0,1) == ' ' ){
			html=html.replace(new RegExp('<span id="?id-' + this.objSelection.uId + '"?>.*?<\/span>','i'),' <span id="id-'+this.objSelection.uId+'">'+this.objSelection.text+'</span>');
		}
		html=html.replace(new RegExp('<span id="?id-' + this.objSelection.uId + '"?>.*?<\/span>','i'),'<span id="id-'+this.objSelection.uId+'">'+this.objSelection.text.trim()+'</span>');
		this.objSelection.element.set('html',html);
		this.objSelection.text = this.objSelection.text.trim();
	},
	// Обработка нажатия клавиши;
	keyPressHandler: function(){
		if( !$('id-' + this.objSelection.uId ) ){
			return false;
		}
		var variations = $( this.options.conteinerUserVariations ).get('value').replace(/\r/g,'').replace(/\n/g,'|');
		$('id-' + this.objSelection.uId ).set('html', variations );
		this.selectBlocked();
		if(!$chk(variations)){
				$('id-' + this.objSelection.uId ).destroy();
		}
		this.flgChange=(variations.test(/\|/)) || !$chk(variations);
		return true;
	},
	// Случайный цвет;
	getRandomColor: function(){
		var r = $random(40, 220);
		var g = $random(40, 220);
        var b = $random(40, 220);
		var rgb = [r,g,b].rgbToHex();
       	return rgb;
	},
	// Отмена всех изменений;
	cancelAll: function(){
		this.objSelection.element.set('html', $( this.options.conteinerOriginal + '-' + this.objSelection.element.id ).value.replace(/\r/g,"").replace(/\n/g,'<br>') );
		$( this.options.settingsBlock ).setStyle('display','none');
		$( this.options.rewriteBlock ).setStyle('display','none');
		$(this.options.userVars).set('value','');
		this.selectUnblocked();
	},
	// Отмена последних изменений;
	cancel: function(){
		if (!$chk($('id-'+this.objSelection.uId))){
			return false;
		}
		$( this.options.settingsBlock ).setStyle('display','none');
		$('id-'+this.objSelection.uId).removeProperty('style');
		$('id-'+this.objSelection.uId).set('html',this.objSelection.text);
		this.objSelection.element.set('html',this.objSelection.element.get('html').replace( new RegExp('<span id="?id-'+this.objSelection.uId+'"?>.*?</span>','i'), this.objSelection.text));
		$(this.options.conteinerUserVariations).value='';
		$(this.options.conteinerDefaultVariations).value='';
		this.selectUnblocked();
		this.initContextMenu();
	},

	// Инициализация нового метода объекта String; 
	// Выделяет искомый символ [search] в тег <span class="all-[uId]">[search]</span>, для всех вхождений;
	initPrepareReplace: function(){
		String.prototype.prepareReplace = function(search,  uId){ 
  			var text='';
  			var originalText=this.replace(/<br>/ig,'[-br-]');
  			for(var i=0; i<originalText.length; i++){
				if(originalText[i] != '<'){
					text+=originalText[i];
				} else {
					while(originalText[i] != '/' ){
						text+='_';
						i++;
					}
					while(originalText[i] != '>' ){
						text+='_';
						i++;
					}
					if(originalText[i]=='>'){text+='_';}					
				}
  			}
  			var arrSplit = text.split(search), arrPos=new Array();
  			arrSplit.each( function( s , i ){
  				arrPos[i]=(s.length > 0 )?s.length-1:s.length;
  				if( i > 0 ){ arrPos[i] = arrPos[i-1] + s.length + search.length; }
  			});
  			var str='', flg=-1;
  			for(var i=0; i<originalText.length; i++){
  				if(arrPos[0] != 0 ){ str+=originalText[i]; }
  				for(var j=0; j<arrPos.length-1; j++){
  					if( arrPos[j] == i ){
  						str+='<span id="id-'+ uId + j +'" class="all-'+ uId +'">';
  						flg=i;
  					}
  					if( flg>=0 && i == ( flg+search.length )){
  						str+='</span>';
  						flg=-1;
  					}
  				}
  				if(arrPos[0] == 0 ){ str+=originalText[i]; }
  			}
			return str.replace(/\[-br-\]/ig,'<br>');
		}			
	},
	// Кнопка Apply all;
	apply2all: function(){
		this.initPrepareReplace();
		$( this.options.settingsBlock ).setStyle('display','none');
		var color = $('id-'+this.objSelection.uId).style.color;
		this.objSelection.element.set('html',this.objSelection.element.get('html').prepareReplace(this.objSelection.text, this.objSelection.uId ));
		this.setUserVariationsData();
		this.keyPressHandler();
		$$('.all-'+this.objSelection.uId).each(function(element){
			element.morph( { 'color':this.getRandomColor() } );
			element.set('html',$('id-'+this.objSelection.uId).get('html'));
		},this);
		$( this.options.rewriteBlock ).setStyle('display','block');
		this.selectUnblocked();
		this.initContextMenu();
	},
	// Кнопка Apply;
	apply: function(){
		if(this.flgChange == 0){
			this.cancel();
			return;
		}
		$( this.options.settingsBlock ).setStyle('display','none');
		$( this.options.rewriteBlock ).setStyle('display','block');
		this.setUserVariationsData();
		this.keyPressHandler();
		this.selectUnblocked();
		this.initContextMenu();
	},	
	// подгатовка пользовательских синонимов для отправки на сервер;
	setUserVariationsData: function(){
		if( !$chk($(this.options.conteinerUserVariations).get('value')) ) {
			return false;
		}
		var variations = $( this.options.conteinerUserVariations ).get('value').replace(/\r/g,'').replace(/\n/g,'|');
		hashVariations.include('id-'+this.objSelection.uId, variations);
	},
	initContextMenu: function(){ 
		new ContMenu();
	}
});

var ContMenu = new Class({ 
	initialize: function(){ 
		if(!hashVariations){
			return false;
		}
		$$('.contextmenu-item').each(function(e){ e.removeEvents('click'); });
		$$('span').each(function(e){ e.removeEvents('click'); });
		var context = {};
	
				
	}
});