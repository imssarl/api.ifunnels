<script type="text/javascript" src="/skin/_js/ckeditor/ckeditor.js"></script>
{if !empty($arrErrors)}
<div class="red">{foreach from=$arrErrors item=i}{$i}<br/>{/foreach}</div>
{/if}
{if !empty($send)}
<div class="grn">{$send}</div>
{/if}
<div>
	<form action="" method="post" class="wh validate" id="form-mail">
		<fieldset>
			<ol>
				<li>
					<label>Select groups: <em>*</em></label><select name="groups[]" size="10" multiple="true" class="required">
						{foreach from=$arrGroups item=i}
						<option value="{$i.sys_name}" {if in_array($i.sys_name,$groups)}selected="1"{/if}>{$i.title}
						{/foreach}
					</select>
				</li>
				<li>
					<label>From name: <em>*</em></label><input type="text" name="arrMessage[name]" class="required" value="{$arrMessage.name|default:'Creative Niche Manager'}" />
				</li>
				<li>
					<label>From email: <em>*</em></label><input type="text" name="arrMessage[email]" class="required" value="{$arrMessage.email|default:'info@creativenichemanager.info'}" />
				</li>
				<li>
					<label>Theme: <em>*</em></label><input type="text" name="arrMessage[theme]" class="required" value="{$arrMessage.theme}" />
				</li>
				<li>
					<label>Message: <em>*</em></label>
					<div style="clear: both;"></div><div style="padding: 0 0 5px 5px;"><a href="#" id="change">HTML</a></a></div>
					<div id="text-input"><textarea rows="24" style="width:700px;" name="arrMessage[text]" class="required">{$arrMessage.text}</textarea></div>
					<div id="html-input" style="display: none;"><textarea rows="10" id="html" style="width:700px;" name="arrMessage[html]">{$arrMessage.html}</textarea></div>
					
					<span class="helper">{literal}You can insert the following tags:<br/>
{SEND_EMAIL} - to add to the text of the letter the user's mailbox.<br/>
{SEND_NAME} - to add to the text of the letter the name of the user.<br/>
{SEND_AMOUNT} - to add to the text of the letter Number of user credits.{/literal}</span>
				</li>
				<li>
					<input type="submit"  value="Send" id="send"  />
				</li>
			</ol>
		</fieldset>
	</form>
</div>

{literal}
<script type="text/javascript">
	window.addEvent('domready', function(){
		$('change').addEvent('click', function(e){
			e.stop();
			if( this.innerHTML=='HTML' ){
				this.innerHTML='Text';
				$('html-input').setStyle('display','block');
				$('text-input').setStyle('display','none');
				CKEDITOR.instances.html.setData($('text-input').getChildren('textarea')[0].get('value'));
			} else {
				this.innerHTML='HTML';
				$('text-input').setStyle('display','block');
				$('html-input').setStyle('display','none');
				$('text-input').getChildren('textarea')[0].set('value',CKEDITOR.instances.html.getData().stripTags().trim());
			}
		});
		CKEDITOR.replace( 'html', {
				toolbar : 'Basic',
				height:"300",
				width:"700"
			});
	});
</script>
{/literal}