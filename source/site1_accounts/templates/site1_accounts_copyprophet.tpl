<form action="" style="width:100%;">
<table align="center" border="0" width="750">
	<tr>
		<td valign="bottom" align="left" colspan="2"><b>Winner So Far:</b></td>
		<td align="right"  ><img src="small_cp.jpg" border="0" /></td>
	</tr>
	<tr>
		<td align="left"  colspan="3"><textarea readonly="readonly" rows="4" style="width:100%;" id="winner" name="winner"></textarea></td>
	</tr>
	<tr>
		<td align="left" valign="top" width="270"><b>Score: </b><input type="text" readonly="readonly" id="highscore" size="5" value="0" style="border: none; width:100px;"/></td>
		<td align="center" valign="top"><img src="putwinnerbackbtn.png" id="putbackwinner" name="putbackwinner"  /></td>
		<td align=left valign="top">&nbsp;</td>
	</tr>
	<tr>
		<td align="left"  colspan="3"><b>Enter text to score here:</b></td>
	</tr>
	<tr>
		<td align="left"  colspan="3"><textarea rows="4" style="width:100%;" id="texttoscore" name="texttoscore"></textarea></td>
	</tr>
	<tr>
		<td align="left"><b>Score: </b><input type="text" readonly="readonly" id="currentscore" size="5" value="0" style="border: none; width:100px;"/></td>
		<td align="center"><b>Count: </b><input type="text" readonly="readonly" id="charcount" size="5" value="0" style="border: none; width:100px;"/></td>
		<td align="left">&nbsp;</td>
	</tr>
	<tr>
		<td align="left" ><b>History Log:</b></td>
		<td align="center">&nbsp;</td>
		<td align="left">&nbsp;</td>
	</tr>
	<tr>
		<td align="left"  colspan="3"><textarea readonly="readonly" rows="4"  style="width:100%;" id="historylog" name="historylog"></textarea></td>
	</tr>
	<tr>
		<td align="right" ><img src="clearbtn.png" id="clear-button"/></td>
		<td align="center" >&nbsp;&nbsp;</td>
		<td align="left" ><img src=scorebtn.png id="start-score" /></td>
	</tr>
</table>

</form>

{literal}
<script type="text/javascript">
var CopyProphet=new Class({
	initialize: function(){
		this.initEvents();
	},
	initEvents: function(){
		$('texttoscore').addEvent('keyup',function(){ 
			this.updateCharCount();
		}.bindWithEvent(this));
		
		$('putbackwinner').addEvent('click', function(){
			this.putBackWinnerButtonClicked();
		}.bindWithEvent(this));
		
		$('start-score').addEvent('click',function(){
			this.score();
		}.bindWithEvent(this));
				
		$('clear-button').addEvent('click',function(){
			this.clear();
		}.bindWithEvent(this));
	},
	hexnib: function( d ){
		if( d < 10 ) { 
			return d; 
		} else {
			return String.fromCharCode( 65 + d - 10 );
		}
	},
	hexcode: function( url ){
     var result="";
     for( var i=0; i < url.length; i++ ){
        var cc=url.charCodeAt(i);
        var hex= this.hexnib((cc&240)>>4)+""+this.hexnib(cc&15);
        result+=hex;
     }
     return result;		
	},
	updateCharCount: function(){
		$('charcount').value=$('texttoscore').value.length;
	},
	putBackWinnerButtonClicked: function(){
  		$('texttoscore').value=$('winner').value;  
  		this.updateCharCount();		
	},
	clear: function(){
  		$('winner').value="";
  		$('texttoscore').value="";
  		$('historylog').value="";
  		$('highscore').value="0";
  		$('currentscore').value="0";
  		$('charcount').value="0";		
	},
	score: function(){ 
		var obj=this;
		var r=new Request({
			url:"{/literal}{url name='site1_accounts' action='copyprophet_ajax'}{literal}?s="+obj.hexcode( $('texttoscore').value ),
			method:'get',
			onSuccess: function(r){
				$('currentscore').value=r;
				if (($('currentscore').value*1)>($('highscore').value*1)){
					$('highscore').value=$('currentscore').value;
					$('winner').value=$('texttoscore').value;
				}
				$('historylog').value=$('texttoscore').value+"\nScore: "+$('currentscore').value+"\n\n"+$('historylog').value;
			}
		}).send();
		this.updateCharCount();
	}
});

window.addEvent('domready',function(){
	new CopyProphet();
});
</script>
{/literal}