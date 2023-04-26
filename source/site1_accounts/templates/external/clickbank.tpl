{literal}<script type="text/javascript" src="/skin/_js/typedtags.js"></script>{/literal}
	<ol>
		<li>
			<label>Search tags: <em>*</em></label>
			<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][tags]" value="{if !empty($arrCnt.{$i.flg_source}.settings.tags)}{$arrCnt.{$i.flg_source}.settings.tags}{else}{/if}"/>
		</li>
		<li>
			<label>Language </label>
			<select name="arrCnt[{$i.flg_source}][settings][flg_language]" class="required" id="language">
				{foreach from=Core_Language::$flags item=flags key=lang_id}<option {if $arrData.flg_language==$lang_id} selected {/if} value="{$lang_id}">{$flags.title}</option>
				{/foreach}
			</select>
		</li>		
		<li>
			<label>Category <em>*</em></label>
			<select  class="required"  id="category_clickbank">
				<option value="">- select -</option>
				{foreach from=$arrCategories item=category}<option {if $arrData.category_id == $category.id}selected='selected'{/if} value="{$category.id}">{$category.title}</option>
				{/foreach}
			</select>
		</li>		
		<li><label></label>
			<select class="required" name="arrCnt[{$i.flg_source}][settings][category_id]" id="category_clickbank_child">
				<option value="">- select -</option>
			</select>
				{if $arrErrors.category_id}<span class="error">this fields can't be empty</span>{/if}
		</li>	
	</ol>{literal}
	<script type="text/javascript" src="/skin/_js/categories.js"></script>
<script type="text/javascript">
var categoryId = {/literal}{$arrData.category_id|default:'null'}{literal};
var jsonCategory = {/literal}{$arrCatTree|json|default:'null'}{literal};

var Clickbank=new Class({
	initialize: function(){
		this.initEvents();
	},
	initEvents: function(){
		$('language').addEvent('change',function(e){
			this.setLanguage( $('language').get('value') );
		}.bind(this));
	},
	setLanguage: function(lang){
		this.getCategory2Lang(lang);
	},
	getCategory2Lang: function(lang){
		var r=new Request({
			url:'{/literal}{url name="content_clickbank" action="ajax_get"}{literal}',
			onRequest: function(){
					$('category_clickbank').disabled=true; 
					$('category_clickbank_child').disabled=true;
					var img=new Element('img',{
						'src':'/skin/i/frontends/design/ajax_loader_line.gif',
						'id':'loader'
					});
					img.inject($('language').getPrevious('label'),'bottom');
			},
			onComplete: function(){
					$('category_clickbank').disabled=false; 
					$('category_clickbank_child').disabled=false;
					if( $('loader') ){
						$('loader').destroy();
					}
			},
			onSuccess: function(json){
				arr=JSON.decode(json);
				hash=new Hash(arr);
				$('category_clickbank').empty();
				$('category_clickbank_child').empty();
				var option=new Element('option',{'value':'','html':'- select -'});
				option.inject($('category_clickbank'),'bottom');
				option.clone().inject($('category_clickbank_child'),'bottom');
				hash.each(function(item){
					if(item.level>1){
						return;
					}
					var option=new Element('option',{'value':item.id,'html':item.title});
					option.inject($('category_clickbank'),'bottom');
				});
			}
		}).post({'lang':lang,'action':'get_category'});
	}
});

window.addEvent('domready',function(){
	new Clickbank();
	new Categories({
		firstLevel:'category_clickbank',
		secondLevel:'category_clickbank_child',
		intCatId: categoryId,
		jsonTree: jsonCategory
	});

});
</script>
{/literal}