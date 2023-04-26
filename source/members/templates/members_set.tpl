{if !empty($strError)}<div class="red">Error: {$strError}</div>{/if}
<form action="" method="POST" enctype="multipart/form-data" class="wh" >
{if $arrData.id}<input type="hidden" name="arrData[id]" value="{$arrData.id}" />{/if}
<fieldset>
	<legend></legend>
	{if Core_Acs::haveAccess( array( 'Super Admin', 'user_manager_pro' ) )}
	<ol>
		<li>
			<label></label>
			<input type="submit" name="" value="Submit" />
			<input type="reset" name="" value="Reset" />
		</li>
	</ol>
	{/if}
	<ol>
		<li>
			<label>Email <em>*</em></label>
			<input name="arrData[email]" type="text" class="required {if $arrErr.email}error{/if}" value="{$arrData.email}" />
		</li>
		<li>
			<label>Password{if !$arrData.id} <em>*</em>{/if}</label>
			<input name="arrData[passwd]" type="text" class="{if $arrErr.passwd}error{/if}" />
		</li>
		<!--li>
			<label>FB ID:</label>
			<input type="text" value="{$arrData.fb_user_id}" readonly /><br/>
			<input type="text" value="{$arrData.fb_messenger_id}" readonly />
		</li-->
		<li>
			<label>Credits:</label>
			<input name="arrData[amount]" type="text" value="{$arrData.amount}"/>
		</li>
		<li>
			<label>Zonterest Site Limit:<br/></label>
			<select name="arrData[zonterest_limit]">
			<option value="-1" {if $arrData.zonterest_limit==-1}selected{/if}>Unlimit</option>
			<option value="0" {if !isset($arrData.zonterest_limit) || empty($arrData.zonterest_limit)}selected{/if}>0</option>
			<option value="1" {if $arrData.zonterest_limit==1}selected{/if}>1</option>
			<option value="3" {if $arrData.zonterest_limit==3}selected{/if}>3</option>
			<option value="5" {if $arrData.zonterest_limit==5}selected{/if}>5</option>
			<option value="10" {if $arrData.zonterest_limit==10}selected{/if}>10</option>
			</select>
		</li>
		<li>
			<label>Free Hosting Site Limit:<br/></label>
			<input name="arrData[hosting_limit]" type="number" min="0" value="{$arrData.hosting_limit}"/>
			</select>
		</li>
		<li>
			<label>Automation Limit:<br/></label>
			<input name="arrData[automation_limit]" type="number" min="0" value="{$arrData.automation_limit}"/>
			</select>
		</li>
		<li>
			<label>Contact Limit:<br/></label>
			<input name="arrData[contact_limit]" type="number" min="0" value="{$arrData.contact_limit}"/>
			</select>
		</li>
		<li>
			<label>Sub Accounts Limit:<br/></label>
			<input name="arrData[subaccounts_limit]" type="number" min="0" value="{$arrData.subaccounts_limit}"/>
			</select>
		</li>
		<li>
			<label>Add LPB View Limit:<br/></label>
			<input name="arrData[lpb_limits]" type="text" value="" />
			<input name="arrData[lpb_limits_type]" type="hidden" value="0" />
			<input name="arrData[lpb_limits_type]" type="checkbox" value="1" />&nbsp;Extra (now {if !empty( $arrData.lpb_limits )}{$arrData.lpb_limits}{else}0{/if})
			<br/>If sended "unlim" - add unlimit credits. If sended "-all" - remove all credits (and unlim). 
		</li>
		<li>
			<label>Add Traffic Credits:<br/></label>
			<input name="arrData[traffic_credits]" type="number" value="0" />(now {if !empty( $arrData.traffic_credits )}{$arrData.traffic_credits}{else}0{/if})
		</li>
		<li>
			<label>Language:</label>
			<select name="arrData[lang]">
				{html_options options=Core_i18n::$lang selected=$arrData.lang}
			</select>
		</li>
		<li>
			<label>Timezone:</label>
			<select name="arrData[timezone]">
				{html_options options=Core_Datetime::getTimezonesToSelect() selected=$arrData.timezone}
			</select>
		</li>
		<li>
			<label>Rights:</label><select name="arrData[flg_rights]">
				<option {if $arrData.flg_rights==0}selected="1"{/if} value="0">Write/Read</option>
				<option {if $arrData.flg_rights==1}selected="1"{/if} value="1">Only Read</option>
			</select>
		</li>
		<li>
			{if $arrData.flg_expiry==0}
			<label>Non expired (months/days):</label>
			{else}
			<label>User is debtor (months/days):</label>
			{/if}
			<input name="arrData[flg_expire]" type="hidden" value="{$arrData.flg_expire}"/>
			<input name="arrData[expiry]" type="text" value="{$arrData.expiry}"/>
		</li>
		<li>
			<label>Unsubscribe</label>
			<input type="checkbox" name="arrData[flg_unsubscribe]" value="1"{if $arrData.flg_unsubscribe!=0} checked="checked"{/if}/>
		</li>
	</ol>
	<ol>
		<li>
			<label>Flags</label>
			<div>
<table>
<tr>
{foreach Core_Users_Management::$flags as $flag}
	{if ($flag@iteration-1) is div by 3}
</tr>
<tr>
	{/if}
	<td width="30%" class="for_checkbox">
		<input type="checkbox" {if !Core_Acs::haveAccess( array( 'Super Admin', 'user_manager_pro' ) )}disabled {/if}name="arrData[{$flag@key}]"{if $arrData[$flag@key]} checked{/if} id="f_{$flag@key}">
		<label for="f_{$flag@key}">{$flag}</label>
	</td>
{/foreach}
</tr>
</table>
			</div>
		</li>
	</ol>
	<ol>
		<li>
			<label>Groups</label>
			<div>
<table>
<tr>
{foreach $arrG as $group}
	{if ($group@iteration-1) is div by 3}
</tr>
<tr>
	{/if}
	<td width="30%" class="for_checkbox">
		<input type="checkbox" {if !Core_Acs::haveAccess( array( 'Super Admin', 'user_manager_pro' ) )}disabled {/if}name="arrData[groups][{$group['id']}]" value="{$group['sys_name']}"{if $arrData['groups'][$group['id']]} checked=""{/if} id="g_{$group@key}">
		<label for="g_{$group@key}">{$group['title']}</label>
	</td>
{/foreach}
</tr>
</table>
			</div>
		</li>
	</ol>
	{if $arrPkg}
	<ol>
		<li>
			<label>Package</label>
			<select name="arrData[package_id]">
				{*<option value=""> - to hold things in place - </option>*}
				{html_options options=$arrPkg selected=$arrData.package_id}
			</select>
		</li>
	</ol>
	{/if}
	{if Core_Acs::haveAccess( array( 'Super Admin', 'user_manager_pro' ) )}
	<ol>
		<li>
			<label></label>
			<input type="submit" name="" value="Submit" />
			<input type="reset" name="" value="Reset" />
		</li>
	</ol>
	{/if}
</fieldset>
</form>


{if count( $arrBalance )>0}
{assign var=arrPg value=$arrPgBalance}
<div class="content-box"><!-- Start Content Box -->
	<div class="content-box-header">
		<h3>Balance history</h3>
	</div>
	<div class="content-box-content">
		<table>
			<thead>
			<tr>
				<th>Description</th>
				<th width="15%" align="center">Amount</th>
				<th width="15%" align="center">Credit Balance</th>
				<th width="15%" align="center">Added</th>
				<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			{foreach from=$arrBalance key='k' item='v'}
			<tr{if $k%2=='0'} class="matros"{/if}>
				<td>{$v.description}</td>
				<td align="center">{if $v.flg_status==1}+{else}-{/if} {$v.amount}</td>
				<td align="center">{$onPageBalance}{$onPageBalance=$onPageBalance+( ($v.flg_status==1)?-$v.amount :$v.amount ) }</td>
				<td align="center">{$v.added|date_local:$config->date_time->dt_full_format}</td>
				<td align="center">
					<a href="?id={$arrData.id}&return={$v.id}">return credits</a>
				</td>
			</tr>
			{/foreach}
			</tbody>
			<tfoot>
				<tr>
					<td colspan="12">
						{include file="../../pgg_frontend.tpl"}
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>
{/if}