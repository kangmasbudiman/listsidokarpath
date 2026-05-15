<?php

/**
 * @author Diyurman Gea (diyurman@gmail.com)
 * @copyright 2019
 * @package
 * @version
 * @example
 * @param
 */

if(session_id() == '') {
    session_start();
}

include("./lib/inc/kars_config.php");



$form_data = json_decode(file_get_contents('php://input'));


foreach ($form_data as $key => $value) {
    $field[$value->name] = $value->value;
}


if(isset($field['user_name']) && !empty($field['user_name']) && !empty($field['user_password']))
{
   $_SESSION['hostname'] = $_SERVER['SERVER_NAME'];

   $sql="SELECT *
        FROM user_profile 
        WHERE profile_email = '" . $field['user_name'] . "' AND profile_password = '" . md5($field['user_password']) . "' AND profile_record_status = 'A'";

   

   $result = mysqli_query($conn,$sql);
  
		  if (mysqli_num_rows($result) > 0)
		  {
				session_start();
				session_regenerate_id();

				while ($row = mysqli_fetch_array($result)) {
					$_SESSION['user_id'] = $row['profile_id'];
					$_SESSION['user_profile_id'] = $row['profile_id'];
					$_SESSION['user_name'] = $row['profile_email'];
					$_SESSION['user_fullname'] = $row['profile_fullname'];
					//$_SESSION['user_level_id'] = $row['user_level_id'];
					$_SESSION['user_subgroup_id'] = $row['profile_subgroup_id'];
					$_SESSION['user_group_id'] = $row['profile_group_id'];
					$_SESSION['user_institution_code'] = $row['profile_institution_code'];
					//$_SESSION['user_access_chapter_id'] = $row['user_access_chapter_id'];
					$_SESSION['user_department_id'] = $row['profile_department_id'];
					//$_SESSION['coder_nik'] = $row['user_coder_nik'];
					//$_SESSION['user_tarrif_mapping'] = $row['user_tarrif_mapping'];
					$_SESSION['user_province_code'] = $row['profile_institution_province_code'];
					$_SESSION['user_city_code'] = $row['profile_institution_city_code'];
					$_SESSION['profile_work_type'] = $row['profile_work_type'];
					$_SESSION['profile_specialize_id'] = $row['profile_specialize_id'];
				}
				
				$sql_access = "
							SELECT DISTINCT access_module_id, access_add, access_delete, access_update, access_view, access_report
							FROM user_access
							WHERE access_user_id = {$_SESSION['user_profile_id']}

							UNION
							SELECT DISTINCT access_module_id, access_add, access_delete, access_update, access_view, access_report
							FROM user_group_access
							WHERE access_group_id IN({$_SESSION['user_group_id']}) AND access_record_status = 'A'";

				
				$result_access=mysqli_query($conn,$sql_access);
				WHILE ($row_access = mysqli_fetch_assoc($result_access))
				{
					 $_SESSION['add'][$row_access['access_module_id']] = $row_access['access_add'];
					 $_SESSION['delete'][$row_access['access_module_id']] = $row_access['access_delete'];
					 $_SESSION['update'][$row_access['access_module_id']] = $row_access['access_update'];
					 $_SESSION['view'][$row_access['access_module_id']] = $row_access['access_view'];
					 $_SESSION['report'][$row_access['access_module_id']] = $row_access['access_report'];

					 //echo json_encode($_SESSION['add'][$row_access['access_module_id']]."<br />");
				}
				
				$sql_login = "UPDATE user_profile SET profile_last_login = '".date('Y-m-d h:i:s')."', profile_online_status='1' WHERE profile_id = '{$_SESSION['user_profile_id']}'";
				$result_login = mysqli_query($conn,$sql_login);
				echo json_encode("");
			}
			else
			{
				echo json_encode("<p>User atau Password tidak valid!</p>");
			}
}
?>