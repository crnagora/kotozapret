<?php
require_once("antizapret.php");
$AntiZapret=new AntiZapret();
$AntiZapret->banRsoc($_SERVER['REMOTE_ADDR']);
?>
text text text