/**
 * For display Categories Tree
 * User: Pavel
 * Date: 23.03.11
 * Time: 17:11
 */

var Categories = new Class({
	Implements: Options,
	options: {
		firstLevel: 'category',
		secondLevel: 'category_child',
		intCatId: null,
		jsonTree:null
	},
	initialize: function( options ){
		this.setOptions( options );
		this.arrCategories = new Hash( this.options.jsonTree );
		$(this.options.firstLevel).addEvent('change',function(){
			this.setFromFirstLevel( $(this.options.firstLevel).value );
		}.bind( this ) );
		if( this.options.intCatId!=null && this.checkLevel(this.options.intCatId) ) {
			this.setFromFirstLevel( this.options.intCatId );
		} else if( this.options.intCatId!=null ) {
			this.setFromSecondLevel( this.options.intCatId );
		}
	},
	checkLevel: function( id ){
		var bool=false;
		this.arrCategories.each( function( el ){
			if( el.id == id ) { bool=true; }
		} );
		return bool;
	},
	setFromFirstLevel: function( id ){
		this.arrCategories.each( function( item ){
			if( item.id == id ) {
				Array.from( $(this.options.firstLevel).options ).each( function(i){
					if(i.value == id){
						i.selected=1;
					}
				});
				$( this.options.secondLevel ).empty();
				var option = new Element( 'option[html=- select -]' );
				option.inject( $(this.options.secondLevel) );
				var hash = new Hash( item.node );
				hash.each(function( i,k ){
					var option = new Element( 'option[value='+i.id+'][html='+i.title+']' );
					if( i.id == this.options.intCatId ){
						option.selected=1;
					}
					option.inject( $(this.options.secondLevel) );
				},this );
			}
		},this );
	},
	setFromSecondLevel: function( id ) {
		this.arrCategories.each( function( item ){
			var hash = new Hash(item.node);
			hash.each( function( el ){
				if ( id == el.id ) {
					this.setFromFirstLevel( el.pid );
				}
			},this );
		},this );
	}
});