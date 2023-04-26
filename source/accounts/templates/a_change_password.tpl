<FORM METHOD="post"  ACTION=""  NAME="a_set"  ID="a_set"  ENCTYPE="multipart/form-data">
	<DIV STYLE="width: 70%">
		<DIV STYLE="width: 90%; text-align: center; clear: both; padding-bottom: 20px">
			{if !empty($arrErr)}
			<span style="color:red;">Проверьте введённые данные</span>
			{elseif !empty($arrPassword.ok)}
			<span style="color:green;">Пароль был изменён</span>
			{else}
			&nbsp;
			{/if}
		</DIV>
		<DIV>
			<TABLE CLASS="info">
				<TBODY>
					<TR>
						<TD style="{if $arrErr.passwd_n==true}color:red;{/if}">Новый пароль:</TD>
						<TD>
							<INPUT TYPE="password" NAME="arrPassword[passwd_n]" VALUE="{$arrPassword.passwd_n|escape}" CLASS="elogin">
						</TD>
					</TR>
					<TR>
						<TD style="{if $arrErr.passwd_n_c==true}color:red;{/if}">Повторите новый пароль:</TD>
						<TD>
							<INPUT TYPE="password" NAME="arrPassword[passwd_n_c]" VALUE="{$arrPassword.passwd_n_c|escape}" CLASS="elogin">
						</TD>
					</TR>
				</TBODY>
			</TABLE>
		</DIV>
		<DIV STYLE="width: 90%; text-align: center; clear: both; padding-top: 20px">
			<button>
				<table cellpadding="0" cellspacing="0">
					<tr valign="top">
						<td><img src="/i/agt_action_success.png"></td>
						<td>&nbsp;Сохранить</td>
					</tr>
				</table>
			</button>
			&nbsp;&nbsp;&nbsp;
			<button type="reset">
				<table cellpadding="0" cellspacing="0">
					<tr valign="top">
						<td><img src="/i/agt_stop.png"></td>
						<td>&nbsp;Отменить</td>
					</tr>
				</table>
			</button>
		</DIV>
	</DIV>
	<input type="hidden" name="arrPassword[save]" value="1">
</FORM>