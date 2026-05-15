<?php

/**
 * @author Diyurman Gea
 * @copyright 2015
 * @filesource logout.php
 */

include("./autostart.php");

session_start();
if(session_destroy())
{
    $_SESSION = array();
    header("Location: ./");
}
?>