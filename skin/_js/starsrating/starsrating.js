/**
* Tag-interface handler
* inline {@internal поддержка интерфейса работы с тэгами}}
* @date 12.08.2008
* @package framework
* @author Rodion Konnov
* @contact kindzadza@mail.ru
* @version 1.0
**/


var starsraiting=new Class({

	Implements: Options,

	options: {
		url: null,
		leftMargin: 0,  /* The width in pixels of the margin before the stars. */
		starWidth: 17,  /* The width in pixels of each star. */
		starMargin: 4,  /* The width in pixels between each star. */
		scale: 5,       /* It's a five-star scale. */
		snap: 1         /* Will snap to the nearest star (can be made a decimal, too). */
	},

	initialize: function(options) {
		this.setOptions(options);
		/*var activeColor = this.options.activeColor;
		var votedColor  = this.options.votedColor;
		var fillColor   = this.options.fillColor;*/
		$each($$('.rabidRating'),function(el) {
			if ( Browser.Engine.trident4 ) {
				this.for_oldbrowser(el);
			} else {
				this.for_newbrowser(el);
			}
		}.bind(this));
	},

	for_newbrowser: function(el) {
		el=this.parse_id(el);
		el.wrapper = el.getElement('.wrapper');
		el.text_element = el.getElement('.ratingText');
		el.offset = el.getPosition().x;
		el.fill = el.getElement('.ratingFill');
		
		this.fill_element(el);
		el.currentFill = this.get_fill(el.cur_precent);

		el.morphFx = new Fx.Morph(el.fill, {link:'chain'});
		el.widthFx = new Fx.Tween(el.fill, 'width', {link:'chain'});

		if ( el.state=='disallow' ) {
			el.morphFx.start('.rabidRating .ratingVoted');
			return;
		}

		el.mouseCrap = function(e) { 
			var fill = e.client.x - el.offset;
			var step=(this.options.snap === 0)?1:(100 / this.options.scale) * this.options.snap;
			el.cur_precent = ( Math.floor(this.get_vote(fill) / step) + 1 ) * step;
			this.fill_element(el);
		}.bind(this);

		el.wrapper.addEvent('mouseenter', function(e) {
			el.morphFx.start('.rabidRating .ratingActive');
			el.wrapper.addEvent('mousemove', el.mouseCrap);
		});

		el.wrapper.addEvent('mouseleave', function(e) {
			el.removeEvent(el.mouseCrap);
			el.morphFx.start('.rabidRating .ratingFill');
			el.widthFx.start('width',el.currentFill);
		});

		el.wrapper.addEvent('click', function(e) {
			el.currentFill = el.new_fill;
			el.morphFx.start('.rabidRating .ratingVoted');
			el.wrapper.removeEvents();
			el.addClass('ratingVoted');
			el.text_element.addClass('loading');
			if (this.options.url != null) {
				var jr=new Request.JSON({
					url: this.options.url,
					onComplete: el.on_complete,
					onFailure: function(obj) {r.alert( 'Error on client side', 'you tags not set', 'roar_error' );}
				}).post({
					flg_type:el.rate_type,
					item_id:el.item_id,
					rate:this.get_vote(el.new_fill)
				});
			}
		}.bind(this));

		el.on_complete=function(obj) {
			var hash=$H(obj);
			if ( !hash.has('error') ) {
				r.alert( 'Error on server side', 'server script fall', 'roar_error' );
				return;
			} else if ( hash.error ) {
				r.alert( 'Error on server side', obj.error, 'roar_error' );
				return;
			}
			// всё получилось
			el.text_element.removeClass('loading');
			el.text_element.set('text', hash.rate+'/'+this.options.scale+' stars ('+hash.votes+' vote'+((hash.votes>1)?'\'s':'')+')' );
		}.bind(this);
	},

	// for IE6 TODO!!!
	for_oldbrowser: function() {},

	// format anytext-<state:allow/disallow>-<rate_type:text>-<item_id:int>-<current:float>-<scale:float>
	// example starsraiting-allow-f_files-10-2.3-5 rate_type=f_files, item_id=10, cur=2.3, scale=5
	parse_id: function(el) {
		var info=el.get('id').match(/(\w*)-(\w*)-(\d*)-(\d*\.?\d+)-(\d*\.?\d+)$/);
		el.state=info[1];
		el.rate_type=info[2];
		el.item_id=info[3];
		el.cur_precent=(info[4].toFloat()/info[5].toFloat()) * 100;
		return el;
	},

	fill_element: function(el) {
		el.new_fill=this.get_fill(el.cur_precent);
		if ( this.get_vote(el.new_fill)>100 ) {el.new_fill = this.get_fill(100);}
		el.fill.setStyle('width', el.new_fill);
	},

	get_fill: function(precent) {
		return (precent/100)*((this.options.starWidth+this.options.starMargin)*this.options.scale) + this.options.leftMargin;
	},

	get_vote: function(divPosition) {
		var starsWidth = (this.options.starWidth+this.options.starMargin)*this.options.scale;
		var starPosition = divPosition - this.options.leftMargin;
		return (starPosition / starsWidth * 100).round(2);
	}
});