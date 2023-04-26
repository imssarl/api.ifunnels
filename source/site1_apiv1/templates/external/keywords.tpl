	<ol>
				<li>
					<fieldset>
						<legend>Keyword source: <em>*</em></legend>
						<label><input type="radio" name="arrCnt[{$i.flg_source}][settings][keyword_source]" value="1" class="keyword-source" {if $arrCnt.{$i.flg_source}.settings.keyword_source == 1}checked="checked"{/if}/> Text file (new line separated) or CSV file</label>
						<label><input type="radio" name="arrCnt[{$i.flg_source}][settings][keyword_source]" value="2" class="keyword-source" {if $arrCnt.{$i.flg_source}.settings.keyword_source == 2}checked="checked"{/if}/> Manually</label>
						<label><input type="radio" name="arrCnt[{$i.flg_source}][settings][keyword_source]" value="3" class="keyword-source" {if $arrCnt.{$i.flg_source}.settings.keyword_source == 3}checked="checked"{/if}/> Keyword Research</label>
					</fieldset>
				</li>
				<li {if $arrCnt.{$i.flg_source}.settings.keyword_source != 1}style="display:none;"{/if} class="keywords-source-block" id="keyword-file"><label>File</label><input type="file" name="arrCnt[{$i.flg_source}][settings][file]" /></li>
				<li {if $arrCnt.{$i.flg_source}.settings.keyword_source != 3}style="display:none;"{/if} class="keywords-source-block" id="keyword-research">
					<fieldset>
						<label><a rel="width:800" href="{url name='site1_content' action='selectcontent'}?label=keywords" class="smb">Get keywords from Keyword Research</a></label>
					</fieldset>
				</li>
				<li id="keywords-place" style="{if (empty($arrCnt.{$i.flg_source}.settings.keyword_source) || $arrCnt.{$i.flg_source}.settings.keyword_source == 1)}display:none;{/if};">
					<label><b>Selected keywords</b> <a href="#" id="edit-keywords">edit</a></label><div id="edit-keywords-list">
					</div>
					<textarea id="edit-keywords-value" name="arrCnt[{$i.flg_source}][settings][keywords]" style="{if (empty($arrCnt.{$i.flg_source}.settings.keyword_source) || $arrCnt.{$i.flg_source}.settings.keyword_source == 1)}display:none;{/if}height:200px;" >{if !empty($arrCnt.{$i.flg_source}.settings.keywords)}{$arrCnt.{$i.flg_source}.settings.keywords}{/if}</textarea>
			</li>
			<li>
					<fieldset>
					<legend>Post keywords: <em>*</em></legend>
						<label for="all"><input type="radio" id="all" name="arrCnt[{$i.flg_source}][settings][flg_generate]" {if $arrCnt.{$i.flg_source}.settings.flg_generate == 1}checked="checked"{/if} value="1" />&nbsp;all</label>
						<label><input type="radio" id="first" name="arrCnt[{$i.flg_source}][settings][flg_generate]" {if $arrCnt.{$i.flg_source}.settings.flg_generate == 2}checked="checked"{/if} value="2" /><input type="text" name="arrCnt[{$i.flg_source}][settings][keywords_first]" value="{if !empty($arrCnt.{$i.flg_source}.settings.keywords_first)}{$arrCnt.{$i.flg_source}.settings.keywords_first}{/if}" style="width:40px" />&nbsp;first keywords</label>
						<label><input type="radio" id="random" name="arrCnt[{$i.flg_source}][settings][flg_generate]" {if $arrCnt.{$i.flg_source}.settings.flg_generate == 3}checked="checked"{/if} value="3" /><input type="text" name="arrCnt[{$i.flg_source}][settings][keywords_random]"  value="{if !empty($arrCnt.{$i.flg_source}.settings.keywords_random)}{$arrCnt.{$i.flg_source}.settings.keywords_random}{/if}" style="width:40px" />&nbsp;random keywords</label>
					</fieldset>
			</li>
	</ol>

{literal}<script type="text/javascript">
	var KeywordsVisual = new Class( {
		initialize: function(){
			this.initKeywwordSource();
			this.initKeywordProject();
		},
		initKeywordProject: function(){
			if( !$('edit-keywords') ){return;}
			$('edit-keywords').addEvent('click', function(e){
				e && e.stop();
				$('edit-keywords').setStyle('display', 'none');
				$('edit-keywords-list').setStyle('display','none');
				$('edit-keywords-value').setStyle('display','block');
			});
			
		},
		initKeywwordSource: function(){
			$$('.keyword-source').each(function(el){
				el.addEvent('click', function(){
					$$('.keywords-source-block').each(function(li){ li.setStyle('display','none'); });
					switch( el.value ){
						case '1': 
							$('keyword-file').setStyle('display',''); 
							$('keywords-place').setStyle('display','none');
							break;
						case '2': 	
							$('keywords-place').setStyle('display','block');
							$('edit-keywords-list').setStyle('display','none');
							$('edit-keywords').setStyle('display','none');
							$('edit-keywords-value').setStyle('display','block'); 
							break;
						case '3':
							$('keyword-research').setStyle('display','block');
							$('edit-keywords-value').setStyle('display','block'); 
							$('keywords-place').setStyle('display','block');
							break;
					}
				});
			});
		},
	} );
	
	var keywvisual = {};
	window.addEvent('domready', function(){
	keywvisual = new KeywordsVisual();
	});
	
	if ( multibox == null ) {
	var multibox = {};
	var visual = {};

	window.addEvent('load', function() {
		multibox = new multiBox( {
				mbClass: '.smb',
				container: $( document.body ),
				useOverlay: true,
				nobuttons: true
			} );
	});
	}
	</script>{/literal}