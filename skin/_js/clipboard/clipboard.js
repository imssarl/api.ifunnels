var Clipboard = new Class({
	Implements : [Options],
	options : { // опции по умолчанию
		flash_location: '/skin/_js/clipboard/clipboard.swf',
		flash_content: 'clipboard_content',
		flash_id: 'clipboard',
		alert_text : 'Для использования функции копирования нужен flash player не младше 9 версии',
		alert_flash: true
	},
	initialize : function(elements,text,options) {
		this.setOptions(options);
		if(typeof elements != 'object') return;
		this.text = text;
		this.alert_text = this.options.alert_text;
		this.flash_content = this.options.flash_content;
		this.flash_id = this.options.flash_id;
		this.flash_location = this.options.flash_location;
		this.elements = elements;
		this.alert_flash = this.options.alert_flash;
		this.init();
	}
});

Clipboard.implement({
	init: function(){
		var parent = this;
		if(Browser.Engine.trident){
			this.ie = true;
		}else{
			this.ie = false;
			if(Browser.Plugins.Flash.version>8){
				this.flash=true;
				this.initFlash();
			}else{
				this.flash=false;
			}
		}
		$each(this.elements,function(el){
			el.addEvent('click',function(e){
				if(!parent.flash & !parent.ie){
					if(parent.alert_flash) alert(parent.alert_text);
				}
				if(parent.ie){
					parent.currect_element = this;
					parent.copy();
				}
				e.stop();
			});
		});
		$each(this.elements,function(el){
			el.addEvent('mouseenter',function(e){
				if(parent.flash){
					parent.currect_element = this;
					var el_pos = $(this).getPosition();
					$(parent.flash_content).setStyle('top',el_pos.y+'px');
					$(parent.flash_content).setStyle('left',el_pos.x+'px');
					$(parent.flash_id).set('width',$(this).getWidth());
					$(parent.flash_id).set('height',$(this).getHeight());
				}
			});
		});
	},
	initFlash: function(){
		var c = $(this.flash_content);
		if(!$chk($(this.flash_id))) {
			this.flash_obj = new Swiff(this.flash_location+'?'+Math.random(), {
			    id: this.flash_id,
				name: this.flash_id,
				container: $(this.flash_content),
			    width: 1,
			    height: 1,
			    params: {
			        wmode: 'transparent'
			    }
			});
			$(c).setStyle('position','absolute');
		}
	},
	getText: function(el){
		var el_classes = $(el).get('class').split(' ');
		if(this.ie){
			var el_id = el_classes[1].replace(/clipboard-id-/,'');
		}else{
			var el_id = el_classes[1].replace(/clipboard-id-/,'');
		}
		
		var texts = $$('.clipboard-text.clipboard-id-'+el_id);
		if(texts[0].tagName=='INPUT'||texts[0].tagName=='TEXTAREA') {
			this.text = texts[0].get('value');
			texts[0].select();
		}else{
			this.text = texts[0].innerHTML;
		}
	},
	swf: function(){
		if (navigator.appName.indexOf("Microsoft") != -1) {
			return window[this.flash_id];
		} else {
			return document[this.flash_id];
		}
	},
	copy : function() {
		this.getText(this.currect_element);
		if(this.ie){
			clipboardData.setData('Text', this.text);
			r.alert( 'Information', 'The data is in your clipboard now', 'roar_information' );
			return;
		}else{
			if(this.flash){
				this.swf().setText(this.text);
				r.alert( 'Information', 'The data is in your clipboard now', 'roar_information' );
				return;
			}
		}
	}
});
