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
        FROM kalmut_user
        LEFT JOIN kalmut_user_profile ON profile_id = user_profile_id
        WHERE md5(user_name) = '".md5($field['user_name'])."' AND user_password = '".md5($field['user_password'])."' AND user_record_status = 'A'";

  $result = mysqli_query($conn,$sql);
  //die($sql);
  if (mysqli_num_rows($result) > 0)
  {
        session_start();
        session_regenerate_id();

        while ($row = mysqli_fetch_array($result)) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['user_profile_id'] = $row['user_profile_id'];
            $_SESSION['user_name'] = $row['user_email'];
            $_SESSION['user_fullname'] = $row['user_fullname'];
            $_SESSION['user_level_id'] = $row['user_level_id'];
            $_SESSION['user_subgroup_id'] = $row['profile_subgroup_id'];
            $_SESSION['user_group_id'] = $row['profile_group_id'];
            $_SESSION['user_institution_code'] = $row['profile_institution_code'];
            $_SESSION['user_access_chapter_id'] = $row['user_access_chapter_id'];
            $_SESSION['user_department_id'] = $row['user_department_id'];
            $_SESSION['coder_nik'] = $row['user_coder_nik'];
            $_SESSION['user_tarrif_mapping'] = $row['user_tarrif_mapping'];
            $_SESSION['user_province_code'] = $row['profile_institution_province_code'];
            $_SESSION['user_city_code'] = $row['profile_institution_city_code'];
            $_SESSION['profile_work_type'] = $row['profile_work_type'];
            $_SESSION['profile_specialize_id'] = $row['profile_specialize_id'];
        }
        $sql_access = "
                    SELECT DISTINCT access_module_id, access_add, access_delete, access_update, access_view, access_report
                    FROM kalmut_user_access
                    WHERE access_user_id = {$_SESSION['user_profile_id']}

                    UNION
                    SELECT DISTINCT access_module_id, access_add, access_delete, access_update, access_view, access_report
                    FROM kalmut_user_group_access
                    WHERE access_group_id IN({$_SESSION['user_group_id']}) AND access_record_status = 'A'";

        //echo $sql_access; die();
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


        //$_SESSION['general'][10000] = 1;
        //$user_log = log_transaction($_SESSION['user_profile_id'],$_POST['username']." login successfully","Success");

        $sql_filemanager = "SELECT * FROM filemanager WHERE filemanager_user_id ='{$_SESSION['user_profile_id']}'";
        //echo $sql_filemanager;
        $result_access=mysqli_query($conn,$sql_filemanager);
        WHILE ($row_access = mysqli_fetch_assoc($result_access))
        {
            $_SESSION['rootDir']            = $row_access['filemanager_rootDir'];
            $_SESSION['fmView']             = $row_access['filemanager_fmView'];
            $_SESSION['hideDirNames']       = $row_access['filemanager_hideDirNames'];
            $_SESSION['enableUpload']       = $row_access['filemanager_enableUpload'];
            $_SESSION['enableDownload']     = $row_access['filemanager_enableDownload'];
            $_SESSION['enableBulkDownload'] = $row_access['filemanager_enableBulkDownload'];
            $_SESSION['enableEdit']         = $row_access['filemanager_enableEdit'];
            $_SESSION['enableDelete']       = $row_access['filemanager_enableDelete'];
            $_SESSION['enableRestore']      = $row_access['filemanager_enableRestore'];
            $_SESSION['enableRename']       = $row_access['filemanager_enableRename'];
            $_SESSION['enablePermissions']  = $row_access['filemanager_enablePermissions'];
            $_SESSION['enableMove']         = $row_access['filemanager_enableMove'];
            $_SESSION['enableCopy']         = $row_access['filemanager_enableCopy'];
            $_SESSION['enableNewDir']       = $row_access['filemanager_enableNewDir'];
            $_SESSION['enableSearch']       = $row_access['filemanager_enableSearch'];
        }

        //Baca foto
        $sql_photo = "SELECT * FROM `kalmut_user_profile_document`
                    WHERE document_type_id IN('4') AND document_requirement_id IN('1') AND `document_profile_id` IN('{$_SESSION['user_profile_id']}') AND `document_record_status` = 'A'";
        $result_photo = mysqli_query($conn,$sql_photo);
        if (mysqli_num_rows($result_photo) > 0)
        {
            WHILE ($row_access = mysqli_fetch_assoc($result_photo))
            {
                $_SESSION['profile_photo'] = $_SESSION['user_profile_id']."/".$row_access['document_filename'];
                $width_size = 150;
                list($width, $height) = getimagesize("./document/profile/".$_SESSION['profile_photo']);
                $ext = strtolower(pathinfo(realpath("./document/profile/".$_SESSION['profile_photo']), PATHINFO_EXTENSION));

                if($width > 150)
                {
                    $k = $width / $width_size;
                    $newwidth = $width / $k;
                    $newheight = $height / $k;
                    $thumb = imagecreatetruecolor($newwidth, $newheight);

                    if($ext == "jpg")
                    {
                        $source = imagecreatefromjpeg("./document/profile/".$_SESSION['profile_photo']);
                    }
                    if($ext == "png")
                    {
                        $source = imagecreatefrompng("./document/profile/".$_SESSION['profile_photo']);
                    }


                    imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
                    unlink(realpath("./document/profile/".$_SESSION['profile_photo']));
                    // menyimpan image yang baru

                    if($ext == "jpg")
                    {
                        imagejpeg($thumb, "./document/profile/".$_SESSION['profile_photo']."/".$_SESSION['user_fullname'].".$ext");
                    }
                    if($ext == "png")
                    {
                        imagepng($thumb, "./document/profile/".$_SESSION['profile_photo']."/".$_SESSION['user_fullname'].".$ext");
                    }

                    imagedestroy($thumb);
                    imagedestroy($source);

                    $sql_photo = "UPDATE `kalmut_user_profile_document` SET document_filename = '{$_SESSION['user_fullname']}.$ext'
                                    WHERE document_type_id IN('4') AND document_requirement_id IN('1') AND `document_profile_id` IN('{$_SESSION['user_profile_id']}') AND `document_record_status` = 'A'";
                    $result_photo = mysqli_query($conn,$sql_photo);
                    $_SESSION['profile_photo'] = $_SESSION['user_profile_id']."/{$_SESSION['user_fullname']}.$ext";
                }
             }
        } else $_SESSION['profile_photo'] = "nopicture.jpg";

        $sql_login = "UPDATE kalmut_user SET user_last_login = '".date('Y-m-d h:i:s')."', user_online_status='1', user_amount_login=COALESCE(user_amount_login+1,1), user_session='".session_id()."' WHERE user_profile_id = '{$_SESSION['user_profile_id']}'";
        $result_login = mysqli_query($conn,$sql_login);
        echo json_encode("");
    }
    else
    {
        echo json_encode("<p>User atau Password tidak valid!</p>");
    }
}
?>