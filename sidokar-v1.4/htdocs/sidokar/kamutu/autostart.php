<?php

/**
 * @author Diyurman Gea (diyurman@gmail.com)
 * @copyright 2018
 * @package 
 * @version 
 * @example 
 * @param   
 */

if(session_id() == '') {
    session_start();
}

$_SESSION["APPS_NAME"] = "KARS";

include("./lib/inc/".strtolower($_SESSION["APPS_NAME"])."_config.php");
include_once("./lib/inc/common_functions.php");

$t=time();
if(isset($_SESSION['logged']) && ($t - $_SESSION['logged'] > 900)) {
    $sql_login = "UPDATE user SET user_last_login = '".date('Y-m-d h:i:s')."', user_online_status='0' WHERE user_profile_id = '{$_SESSION['user_profile_id']}'";
    $result_login = mysqli_query($conn,$sql_login);
    session_destroy();
    session_unset();
    header('location: index.php');
}else {$_SESSION['logged'] = time();}


?>