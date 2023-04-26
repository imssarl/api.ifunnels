	<ol>
		<li>
			<label>Yahoo Application ID: <em>*</em></label>
			<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][appkey]" value="{if !empty($arrCnt.{$i.flg_source}.settings.appkey)}{$arrCnt.{$i.flg_source}.settings.appkey}{else}{/if}"/>
			<a target="_blank" href="http://developer.yahoo.com/answers/" style="text-decoration:none" class="Tips" title="This setting is required for the Yahoo Answers module to work! Click to go to the Yahoo API sign up page!"><b> ?</b></a>
		</li>		
		<li>
			<label>Add "Powered by Yahoo! Answers" text to footer?<em>*</em></label>
				<input class="required" name="arrCnt[{$i.flg_source}][settings][addtext]" type="checkbox" {if $arrCnt.{$i.flg_source}.settings.addtext == '1'}checked="checked"{/if} value="1" />
				<a style="text-decoration:none" class="Tips" title="By the Yahoo Answers TOS it is required that you display the text \'Powered by Yahoo! Answers\' on pages you use the API on. If you disable this option you can display the text anywhere else on your weblog."><b> ?</b></a>
		</li>		
		<li>
			<label>Country: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][lang]">
							<option value="us" {if $arrCnt.{$i.flg_source}.settings.lang == "us"}selected="selected"{/if} >USA</option>
							<option value="uk" {if $arrCnt.{$i.flg_source}.settings.lang == "uk"}selected="selected"{/if} >United Kingdom</option>								
							<option value="ca" {if $arrCnt.{$i.flg_source}.settings.lang == "ca"}selected="selected"{/if} >Canada</option>	
							<option value="au" {if $arrCnt.{$i.flg_source}.settings.lang == "au"}selected="selected"{/if} >Australia</option>			
							<option value="de" {if $arrCnt.{$i.flg_source}.settings.lang == "de"}selected="selected"{/if} >Germany</option>
							<option value="fr" {if $arrCnt.{$i.flg_source}.settings.lang == "fr"}selected="selected"{/if} >France</option>
							<option value="it" {if $arrCnt.{$i.flg_source}.settings.lang == "it"}selected="selected"{/if} >Italy</option>	
							<option value="es" {if $arrCnt.{$i.flg_source}.settings.lang == "es"}selected="selected"{/if} >Spain</option>		
							<option value="br" {if $arrCnt.{$i.flg_source}.settings.lang == "br"}selected="selected"{/if} >Brazil</option>
							<option value="ar" {if $arrCnt.{$i.flg_source}.settings.lang == "ar"}selected="selected"{/if} >Argentina</option>
							<option value="mx" {if $arrCnt.{$i.flg_source}.settings.lang == "mx"}selected="selected"{/if} >Mexico</option>
							<option value="sg" {if $arrCnt.{$i.flg_source}.settings.lang == "sg"}selected="selected"{/if} >Singapore</option>								
				</select>
		</li>		
		<li>
			<label>Amazon Website: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][cat]">
					<option value="" {if $arrCnt.{$i.flg_source}.settings.cat == ""}selected="selected"{/if} >All</option>
					<option  value="396545012" {if $arrCnt.{$i.flg_source}.settings.cat == "396545012"}selected="selected"{/if} >Arts &amp; Humanities</option>
					<option  value="396545144" {if $arrCnt.{$i.flg_source}.settings.cat == "396545144"}selected="selected"{/if} >Beauty &amp; Style</option>
					<option  value="396545013" {if $arrCnt.{$i.flg_source}.settings.cat == "396545013"}selected="selected"{/if} >Business &amp; Finance</option>
					<option  value="396545311" {if $arrCnt.{$i.flg_source}.settings.cat == "396545311"}selected="selected"{/if} >Cars &amp; Transportation</option>
					<option  value="396545660" {if $arrCnt.{$i.flg_source}.settings.cat == "396545660"}selected="selected"{/if} >Computers &amp; Internet</option>
					<option  value="396545014" {if $arrCnt.{$i.flg_source}.settings.cat == "396545014"}selected="selected"{/if} >Consumer Electronics</option>
					<option  value="396545327" {if $arrCnt.{$i.flg_source}.settings.cat == "396545327"}selected="selected"{/if} >Dining Out</option>
					<option  value="396545015" {if $arrCnt.{$i.flg_source}.settings.cat == "396545015"}selected="selected"{/if} >Education &amp; Reference</option>
					<option  value="396545016" {if $arrCnt.{$i.flg_source}.settings.cat == "396545016"}selected="selected"{/if} >Entertainment &amp; Music</option>
					<option  value="396545451" {if $arrCnt.{$i.flg_source}.settings.cat == "396545451"}selected="selected"{/if} >Environment</option>
					<option  value="396545433" {if $arrCnt.{$i.flg_source}.settings.cat == "396545433"}selected="selected"{/if} >Family &amp; Relationships</option>
					<option  value="396545367" {if $arrCnt.{$i.flg_source}.settings.cat == "396545367"}selected="selected"{/if} >Food &amp; Drink</option>
					<option  value="396545019" {if $arrCnt.{$i.flg_source}.settings.cat == "396545019"}selected="selected"{/if} >Games &amp; Recreation</option>
					<option  value="396545018" {if $arrCnt.{$i.flg_source}.settings.cat == "396545018"}selected="selected"{/if} >Health</option>
					<option  value="396545394" {if $arrCnt.{$i.flg_source}.settings.cat == "396545394"}selected="selected"{/if} >Home &amp; Garden</option>
					<option  value="396545401" {if $arrCnt.{$i.flg_source}.settings.cat == "396545401"}selected="selected"{/if} >Local Businesses</option>
					<option  value="396545439" {if $arrCnt.{$i.flg_source}.settings.cat == "396545439"}selected="selected"{/if} >News &amp; Events</option>
					<option  value="396545443" {if $arrCnt.{$i.flg_source}.settings.cat == "396545443"}selected="selected"{/if} >Pets</option>
					<option  value="396545444" {if $arrCnt.{$i.flg_source}.settings.cat == "396545444"}selected="selected"{/if} >Politics &amp; Government</option>
					<option  value="396546046" {if $arrCnt.{$i.flg_source}.settings.cat == "396546046"}selected="selected"{/if} >Pregnancy &amp; Parenting</option>
					<option  value="396545122" {if $arrCnt.{$i.flg_source}.settings.cat == "396545122"}selected="selected"{/if} >Science &amp; Mathematics</option>
					<option  value="396545301" {if $arrCnt.{$i.flg_source}.settings.cat == "396545301"}selected="selected"{/if} >Social Science</option>
					<option  value="396545454" {if $arrCnt.{$i.flg_source}.settings.cat == "396545454"}selected="selected"{/if} >Society &amp; Culture</option>
					<option  value="396545213" {if $arrCnt.{$i.flg_source}.settings.cat == "396545213"}selected="selected"{/if} >Sports</option>
					<option  value="396545469" {if $arrCnt.{$i.flg_source}.settings.cat == "396545469"}selected="selected"{/if} >Travel</option>
					<option  value="396546089" {if $arrCnt.{$i.flg_source}.settings.cat == "396546089"}selected="selected"{/if} >Yahoo! Products</option>				
				</select>
		</li>
		<li>
			<label>Strip All Links from questions:<em>*</em></label>
				<input class="required" name="arrCnt[{$i.flg_source}][settings][striplinksq]" type="checkbox" {if $arrCnt.{$i.flg_source}.settings.striplinksq == 'on'}checked="checked"{/if} /> 
		</li>
		<li>
			<label>Strip All Links from answers <em>*</em></label>
				<input class="required" name="arrCnt[{$i.flg_source}][settings][striplinksa]" type="checkbox" {if $arrCnt.{$i.flg_source}.settings.striplinksa == 'on'}checked="checked"{/if} />
		</li>
	</ol>