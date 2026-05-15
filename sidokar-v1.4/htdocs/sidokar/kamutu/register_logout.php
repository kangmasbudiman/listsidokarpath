<?php

/**
 * @author Diyurman Gea
 * @copyright 2015
 * @filesource logout.php
 */


include("./autostart.php");

$sql_login = "UPDATE user SET user_last_login = '".date('Y-m-d h:i:s')."', user_online_status='0' WHERE user_profile_id = '{$_SESSION['user_profile_id']}'";
$result_login = mysqli_query($conn,$sql_login);

session_start();
if(session_destroy())
{
    $_SESSION = array();
    header("Location: ./");
}
?>