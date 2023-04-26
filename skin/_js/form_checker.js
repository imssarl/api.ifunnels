/**
* Form checker
* inline {@internal назначается на опрделённые поля и форму и контроллирует их}}
* @date 07.08.2008
* @package framework
* @author Rodion Konnov
* @contact kindzadza@mail.ru
* @version 1.3
**/
var form_checker=new Class({

	Implements: [Options],

	options: {
		prefix:'class_name_prefix',
		frm:'_form',
		btn:'_button', 
		fld:'_checker',
		stop_submit: false,
		mess:'input correct data'
	},

	initialize: function(options) {
		this.setOptions(options);
		if ( this.options.prefix!='class_name_prefix' ) {
			this.setOptions({
				frm:this.options.prefix+this.options.frm,
				btn:'.'+this.options.prefix+this.options.btn, 
				fld:'.'+this.options.prefix+this.options.fld,
				stop_submit: true
			});
		} else {
			this.setOptions({
				btn:'.'+this.options.btn, 
				fld:'.'+this.options.fld
			});
		}
		this.init_form();
	},

	init_form: function() {
		$$(this.options.btn).each(function(el) {
			el.addEvent('click',this.check_data.bindWithEvent(this));
		},this);
		$$(this.options.fld).each(function(el) {
			if ( /passwd/.test(el.className) ) { // set fake class passwd to passwd field
				changeInputType(el,'text','Password',false,true);
				return;
			}
			el.onfocus=function() {
				if (this.value==this.title) this.value='';
			};
			el.onblur=function() {
				if (!this.value) this.value=this.title;
			};
		});
	},

	check_data: function(e) {
		if ( this.options.stop_submit ) {
			e.stop();
		}
		this.error_fld={}
		this.good=$$(this.options.fld).every(function(el) {
			if ( !$defined(this.error_fld.name)&&(el.value==el.title||el.value=='') ) {
				this.error_fld=el;
				return false;
			}
			return true;
		},this);
		var bool=this.parent_check();
		if ( bool&&this.options.stop_submit ) {
			$(this.options.frm).fireEvent('submit');
		}
		return bool;
	},

	parent_check: function(){
		if (!this.good) {
			r.alert( 'Alert message!', this.options.mess );
			this.error_fld.focus();
		}
		return this.good;
	}
});

// input <-> password for stupid IE hack
function changeInputType(
  oldElm, // a reference to the input element
  iType, // value of the type property: 'text' or 'password'
  iValue, // the default value, set to 'password' in the demo
  blankValue, // true if the value should be empty, false otherwise
  noFocus) {  // set to true if the element should not be given focus

  if(!oldElm || !oldElm.parentNode || (iType.length<4) || 
    !document.getElementById || !document.createElement) return;

  var newElm = document.createElement('input');
  newElm.type = iType;
  if(oldElm.name) newElm.name = oldElm.name;
  if(oldElm.title) newElm.title = oldElm.title;
  if(oldElm.id) newElm.id = oldElm.id;
  if(oldElm.className) newElm.className = oldElm.className;
  if(oldElm.size) newElm.size = oldElm.size;
  if(oldElm.tabIndex) newElm.tabIndex = oldElm.tabIndex;
  if(oldElm.accessKey) newElm.accessKey = oldElm.accessKey;

  newElm.onfocus = function(){return function(){
    if(this.hasFocus) return;
    var newElm = changeInputType(this,'password',iValue,
      (this.value.toLowerCase()==iValue.toLowerCase())?true:false);
    if(newElm) newElm.hasFocus=true;
  }}();

  newElm.onblur = function(){return function(){
    if(this.hasFocus)
    if(this.value=='' || (this.value.toLowerCase()==iValue.toLowerCase())) {
      changeInputType(this,'text',iValue,false,true);
    }
  }}();

 // hasFocus is to prevent a loop where onfocus is triggered over and over again
  newElm.hasFocus=false;
  oldElm.parentNode.replaceChild(newElm,oldElm);
  if(!blankValue) newElm.value = iValue;

  if(!noFocus || typeof(noFocus)=='undefined') {
    window.tempElm = newElm;
    setTimeout("tempElm.hasFocus=true;tempElm.focus();",1);
  }

  return newElm;
}