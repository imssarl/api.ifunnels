<h1>Authorized personnel only!</h1>
{if $arrErr.passwd||$arrErr.email||$arrErr.no_user}
	<div class="red">Wrong login or password.</div>
{/if}
<form action="" method="post">
<label>Email:</label>
<input name="arrL[email]" type="text" class="text" /><br />
<label>Password:</label>
<input name="arrL[passwd]" type="password" class="text" /><br />
<label>Remember me:</label>
<input type="checkbox" name="arrL[rem]"><br />
<input type="image" class="submit" src="/skin/i/backend/bt_login.gif" name="submit" />
</form>