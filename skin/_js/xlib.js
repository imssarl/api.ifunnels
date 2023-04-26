/**
* library of js function
* inline {@internal набор общих функций}}
* @date 04.11.2010
* @package framework
* @author Rodion Konnov
* @contact kindzadza@mail.ru
* @version 1.8
**/

Request.MyJSON = new Class({

	Extends: Request.JSON,

	success: function(text){
		this.response.json = $H( JSON.decode(text, this.options.secure) );
		if ( !this.response.json.has('error') ) {
			r.alert( 'Ошибка на стороне сервера', 'скрипт не сработал', 'roar_error' );
			return;
		} else if ( this.response.json.error ) {
			r.alert( 'Ошибка на стороне сервера', this.response.json.error, 'roar_error' );
			return;
		}
		this.onSuccess(this.response.json, text);
	},

	failure: function(){
		r.alert( 'Ошибка на стороне клиента', 'ajax не сработал', 'roar_error' );
		this.onFailure();
	}
});

var text_area_maxlength=new Class({
	initialize: function(options) {
		this.options = $extend({counter_container:'counter',toomuch_symbol:'toomuch'}, options||{});
		this.bind_to_textareas();
	},
	bind_to_textareas: function() {
		this.counter=new Element('div').addClass(this.options.counter_container);
		$each($$('textarea'),function(el){
			if ( !el.getAttribute('maxlength') ) {
				return;
			}
			var cont=this.counter.clone().set( 'html', '<span>0</span>/'+el.getAttribute('maxlength') );
			el.parentNode.insertBefore(cont,el.nextSibling);
			el.relatedElement = cont.getElementsByTagName('span')[0];
			el.addEvents({
				'keyup':this.check.bindWithEvent(this, [el, cont]),
				'change':this.check.bindWithEvent(this, [el, cont]),
				'blur':this.check.bindWithEvent(this, [el, cont]),
				'focus':this.check.bindWithEvent(this, [el, cont])
			});
			el.fireEvent('change');
		},this);
	},
	check: function(ev, el, counter) {
		var max=el.getAttribute('maxlength').toInt();
		if ( el.value.length>max ) {
			//counter.className = this.options.toomuch_symbol;
			el.value=this.value.substring(0,max);
			alert( 'too much symbols' );
		}/* else {
			counter.className = '';
		}*/
		el.relatedElement.firstChild.nodeValue=el.value.length;
	}
});

var ie_hover_tabletr_fix=new Class({
	initialize: function(options) {
		this.options=$extend({table_class:'glow',hover_class:'sfhover'}, options||{});
		var hc=this.options.hover_class;
		$each($$('.'+this.options.table_class),function(el){
			$each(el.getElementsByTagName('tr'),function(tr) {
				tr.onmouseover=function() {tr.className+=" "+hc;};
				tr.onmouseout=function() {tr.className=tr.className.replace(new RegExp(" ?"+hc+"\\b"), "");}
			},this);
		},this);
	}
});

Array.prototype.remove_byidx=function(dx) {
	if(isNaN(dx)||dx>this.length){self.status='Array_remove:invalid request-'+dx;return false}
	for(var i=0,n=0;i<this.length;i++) {
		if(this[i]!=this[dx]) {
			this[n++]=this[i];
		}
	}
	this.length-=1;
}

//event handler
function evt2obj(obj,evt,func) {
	var oldhandler = obj[evt];
	obj[evt] = (typeof obj[evt] != 'function') ? func : function(ev){oldhandler(ev);func(ev);};
}

function evt2id(id,evt,func) {
	obj=$(id);
	if (!obj) {
		return false;
	}
	evt2obj(obj,evt,func);
	return true;
}

// location on current page
function obj_location(obj) {
	var loc={x:0,y:0};
	while( obj ) {
		loc.x+=obj.offsetLeft;
		loc.y+=obj.offsetTop;
		obj=obj.offsetParent;
	}
	return loc;
}

// preload images
var arrImgTmp=[];
function img_preload(arrImg) {
	if ( arrImg.length==0 ) {
		return false;
	}
	for(var i=0;i<arrImg.length;i++) {
		arrImgTmp[arrImgTmp.length]=new Image();
		arrImgTmp[arrImgTmp.length-1].src=arrImg[i];
	}
	return true;
}

function js_pager(numItems, numOnPage, itemsPerPage, currentPage, pageLinkFunc) {
	if (numItems <= itemsPerPage) {
		return '';
	}
	if ( numItems-((currentPage*itemsPerPage)+numOnPage)<numOnPage ) {
		itemsPerPageN=numItems-((currentPage*itemsPerPage)+numOnPage);
	} else {
		itemsPerPageN=itemsPerPage;
	}
	var html = '';
	var numPages = Math.ceil(numItems/itemsPerPage);
	// gen html
	html +='Results '+ ((currentPage*itemsPerPage)+1) +'-'+ ((currentPage*itemsPerPage)+numOnPage) +' of '+ numItems;
	if ( numItems>itemsPerPage ) {
		html +=' &nbsp;|&nbsp; <span class="c07">';
	}
	if (currentPage > 0) {
		html += '&laquo; <a href="'+ pageLinkFunc(currentPage-1) +'" class="c07">Previous '+ itemsPerPage +'</a>';
	}
	if ( currentPage > 0 && currentPage< (numPages-1) ) {
		html +=' &nbsp;|&nbsp; ';
	}
	if ( currentPage< (numPages-1) ) {
		html +='<a href="' + pageLinkFunc(currentPage+1) + '" class="c07">Next '+ itemsPerPageN +'</a> &raquo;';
	}
	if ( numItems>itemsPerPage ) {
		html +='</span>';
	}
	return '<div class="tBrd2 bcE4 p5 pl15">' + html + '</div>';
}

// error massages
function show_mess(type) {
	alert((mess[type]>''?mess[type]:mess['0']));
}

// depercated!!!! 25.08.2010
function checkboxToggle(obj) {
	if ( !$chk(obj) ) return;
	$(obj.get('id')).addEvent('click', function() {
		$$('.check-me-'+obj.get('id')).set('checked',obj.get('checked'));
	});
}

function checkboxFullToggle(obj) {
	if ( !$chk(obj) ) return;
	// для выделения скопом
	$(obj.get('id')).addEvent('click', function() {
		$$('.check-me-'+obj.get('id')).set('checked',obj.get('checked'));
	});
	// для выделения по одному
	$$('.click-me-'+obj.get('id')).addEvent('click',function(e){
		e && e.stop();
		var el='check-'+this.get('id');
		if ( !$(el).get('checked') ) {
			$(el).set('checked',true);
			if ($(el).get('checked')) {
				$('delete').fireEvent('click');
			}
			$(el).set('checked',false);
		}
	});
}

function toggle_checkbox(id,objCh) {
	var obj=$(id);
	if ( !obj ) {
		return false;
	}
	for( i=1; i<obj.elements.length; i++ ) {
		if ( obj.elements[i].type=='checkbox' ) {
			obj.elements[i].checked=objCh.checked;
		}
	}
}

function toggle_multicheckbox(id,part,objCh) {
	var obj=$(id);
	if ( !obj ) {
		return false;
	}
	var pt = new RegExp(part);
	for( var i=0; i<obj.elements.length; i++ ) {
		if ( obj.elements[i].type=='checkbox'&&pt.test(obj.elements[i].name) ) {
			obj.elements[i].checked=objCh.checked;
		}
	}
}

function checkMail(email) {
	var filter  = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	if (filter.test(email)) return true;
	else return false;
}

/*
---
script: URI.Fields.js
description: Extends the URI class to add methods for send form fields through _GET method.
license: MIT-style license
authors:
- Rodion Konnov
requires:
- /Class.refactor
- /URI
provides: [URI.Fields]
...
*/
URI = Class.refactor(URI, {

	prepareToGo: function(fields,ext) {
		this.ext=ext;
		this.obj={};
		fields.each(this.collectFieldsValues,this);
		this.setData(this.obj, true);
		fields.each(this.cleaningNoActive,this);
		return this;
	},

	// собираем объект для генерации строки запроса
	collectFieldsValues: function(field) {
		if ( $chk($(field+this.ext)) ) {
			this.obj[field]=$(field+this.ext).value;
		}
	},

	// тут чистим строку
	cleaningNoActive: function(field) {
		if ( $chk($(field+this.ext)) ) {
			if ( $(field+this.ext).type=='checkbox'&&!$(field+this.ext).checked ) {
				this.setData( new Hash(this.getData()).filter(function(value, key){ return key!=field; }));
			} else if ( !$chk($(field+this.ext).value) ) {
				this.setData( new Hash(this.getData()).filter(function(value, key){ return key!=field; }));
			}
		}
	}
});

Array.implement({
	toURI: function(ext){
		var myURI=new URI();
		return myURI.prepareToGo(this,ext);
	}
});
