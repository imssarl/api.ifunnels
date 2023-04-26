<link href="/skin/_css/login.css" rel="stylesheet" type="text/css" media="screen" />
<div id="wrap">
	<div id="message">
		{if $intError}
		<font color='red'>
			{if $intError==1}
				Please enter correct login details
			{elseif $intError==2||$intError==3}
				Please enter correct login link
			{elseif $intError==4||$intError==5}
				Access denied. Please check your login details
			{elseif $intError==6}
				Can't connect to server
			{elseif $intError==7}
				Access denied. Please check your account service provider
			{/if}
		</font>
		{else}
			Please Enter Login Details!
		{/if}
	</div>
	<div id="panel">
		{literal}
		<form method="post" action="" name="login">
			<input type="text" value='Email / Username' onfocus="if(this.value=='Email / Username'){this.value='';this.style.color='#000';}else{this.select();}" onblur="if(this.value==''){this.value='Email / Username';this.style.color='000';}" name="username" id="username" /> <br />
			<input type="password" value='********' onfocus="if(this.value=='********'){this.value='';this.style.color='#000';}else{this.select();}" onblur="if(this.value==''){this.value='********';this.style.color='000';}" name="password" id="password" /> <br />
			<input class="button" value="Login" type="submit" name="submit" />
		</form>
		{/literal}
	</div>
</div>