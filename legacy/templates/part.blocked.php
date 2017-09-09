<?php

	$URLcurrent = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$URLhttps = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>


<div id="blockedcontent">
	<div class="icon-passwords-blocked"></div>
	<h2><?php p($l->t("ACCESS BLOCKED")); ?></h2>
	<p><b><?php p($l->t("There is no secure, encrypted connection.")); ?></b></p>
	<br>
	<p><a href="<?php p($URLhttps); ?>"><b><green><?php p($l->t("Click here")); ?></green></b></a> <?php p($l->t("for a secured connection, or")); ?> <a href="<?php p($URLcurrent); ?>?d=1"><b><red><?php p(strtolower($l->t("Click here"))); ?></red></b></a> <?php p($l->t("to continue without a secure connection.")); ?></p>
	<br>
	<p><?php p($l->t("Please note that without a secure connection, your passwords may be accessible by everyone who is also connected to your network (WiFi or 3G/4G). The only security that remains, is the security of the network you are connected to. A company network is safer than a public network like in restaurants or shops.")); ?></p> 
</div>