<?php

if(session_id() == '') {
    session_start();
}

if(!isset($_SESSION['user_id']) || session_id() == '' || $_SESSION['user_profile_id'] == "public")
{
    header("Location: ./../");
    die();
}

if($_SESSION['user_group_id']==1) include("../application/dashboard/dashboard_main.php");
elseif($_SESSION['user_group_id']==3) include("../application/dashboard/dashboard_province.php");
elseif($_SESSION['user_group_id']==4) include("../application/dashboard/dashboard_city.php");
elseif($_SESSION['user_group_id']==5) include("../application/dashboard/dashboard_institution.php");
elseif($_SESSION['user_group_id']==6) include("../application/dashboard/dashboard_surveyor.php");
elseif($_SESSION['user_group_id']==10) include("../application/dashboard/dashboard_applicant.php");
?>
