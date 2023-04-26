/**
* Cloud of tags
* inline {@internal облако тэгов}}
* @date 14.08.2008
* @package framework
* @author Rodion Konnov
* @contact kindzadza@mail.ru
* @version 1.1
**/


var tagscloud = new Class({

	Implements: Options,

	options: {
		clouds: {},
		url: '#',
		tag_class: 'tag',
		href_class: 'tag_href',
		hidden_class: 'hidden',
		tag_sort: false,
		tag_sizes: [ '8px', '12px', '18px', '20px', '22px', '24px', '26px', '28px' ]
	},

	initialize: function( cloud, options ) {
		this.setOptions(options);
		this.cloud = $(cloud);
		this.depth = this.options.tag_sizes.length;
		this.tags = $A([]);
		$each(this.options.clouds, function(v, k){
			this.reset_bounds();
			$each(v.frequencies, function(v2, k2){
				this.expand_bounds(v2);
			}.bind( this ));
			$each(v.frequencies, function(v2, k2){
				this.update_tag( k, k2, v2 );
			}.bind( this ));
		}.bind( this )); 
		this.sort_tags();
	},

	update_tag: function(cloud, tag_content, frequency){
		var found = this.tags.some(function(tag, i) {
			if (tag.content == tag_content) {
				tag.cloud_weights.set(cloud, this.get_weight(frequency));
				return true;
			}
			return false;
		}.bind( this ));
		if (!found){
			var cloud_weights = new Hash();
			cloud_weights.set(cloud, this.get_weight(frequency));
			tag = { content: tag_content, cloud_weights: cloud_weights };
			tag.toString = function(){ return this.content }
			this.tags.push(tag);
		}
	},

	get_weight: function(frequency){
		// если у всех элементов одинковый вес то они скрываются, из-за того что class_i==NaN
		// для этого поставил нижний предел =0
		this.lower=0;
		var class_i = Math.floor( parseFloat( ( ( frequency-this.lower )/( this.upper-this.lower ) ), this.depth ) * this.depth );
		if (class_i == this.depth) class_i = class_i - 1;
		return class_i;
	},

	// нижнее и верхнее ограничение весов облака
	reset_bounds: function(){
		this.lower = 99999999999;
		this.upper = 0;
	},

	expand_bounds: function(v){
		if (v > this.upper) this.upper = v;
		if (v < this.lower) this.lower = v;
	},

	sort_tags: function( cloud_name ){
		if ( !this.options.tag_sort ) {
			return;
		}
		this.tags.sort();
	},

	draw: function( cloud_name ) {
		$each(this.tags, function( tag, i ){
			if (!tag.element) {
				tag.element = new Element( 'li', {
					'rel': 'tag',
					'class': this.options.tag_class
				});
				new Element('a', {
					'href': this.options.url+'?tag='+tag.content,
					'html': tag.content.replace(/_/,' '),
					'class': this.options.href_class
				}).inject(tag.element);
				tag.element.injectInside( this.cloud );
				tag.fx = new Fx.Morph( tag.element );
				this.cloud.appendText("\n");
			}
			if ( ''+tag.cloud_weights.get(cloud_name) != 'NaN' && ''+tag.cloud_weights.get(cloud_name) != 'null') {
				if (this.options.hidden_class) {
					tag.element.removeClass(this.options.hidden_class);
				}
				tag.fx.start({
					'opacity': 1,
					'font-size': this.options.tag_sizes[tag.cloud_weights.get(cloud_name)]
				});
				return;
			}
			if ( tag.element.getStyle('opacity') != 0 ) {
				tag.fx.start({
					'opacity': 0,
					'font-size': 0
				});
				if (this.options.hidden_class)
					tag.element.addClass(this.options.hidden_class);
				return;
			}
		}.bind( this ));
	}
});