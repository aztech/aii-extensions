<object type="application/x-shockwave-flash" data="<?php echo $playerSWFFile; ?>" id="audioplayer<?php $playerId;?>" height="24" width="480">
	<param name="movie" value="<?php echo $playerSWFFile; ?>">
	<?php echo '<param name="FlashVars" value="'.$flashVars.'">'; ?>
	<param name="quality" value="high">
	<param name="menu" value="false">
	<param name="wmode" value="transparent">
</object>
