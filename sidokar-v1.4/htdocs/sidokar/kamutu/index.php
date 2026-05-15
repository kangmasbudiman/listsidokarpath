<?php

include_once("./autostart.php");
include(strtolower($_SESSION["APPS_NAME"])."_login.php");
echo $_SESSION["APPS_NAME"]."_login.php" ;
die();
?>