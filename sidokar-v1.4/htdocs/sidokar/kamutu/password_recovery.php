<?php

/**
 * @author Diyurman Gea (diyurman@gmail.com)
 * @copyright 2019
 * @package 
 * @version 
 * @example 
 * @param   
 */


include("./autostart.php");
require_once("./lib/inc/phpmailer/class.phpmailer.php");

$form_data = json_decode(file_get_contents('php://input'));
foreach ($form_data as $key => $value) {
    $field[$value->name] = $value->value;
}

if(isset($field['user_name']) && !empty($field['user_name'])) 
{      
    $_SESSION['hostname'] = $_SERVER['SERVER_NAME'];
   
    $sql="SELECT * FROM user
          LEFT JOIN user_profile ON profile_id = user_profile_id
          WHERE md5(user_name) = '".md5($field['user_name'])."' AND user_record_status = 'A' ORDER BY user_id DESC LIMIT 1";
            
    $result = mysqli_query($conn,$sql);
    
    if (mysqli_num_rows($result) > 0)
    {                   
        session_start();
        session_regenerate_id();
        
        while ($row = mysqli_fetch_array($result)) {
            $user_id = $row['user_id'];
            $user_name = $row['user_name'];
            $user_fullname = $row['user_fullname'];
            $user_email = $row['user_email'];
            $user_birth_day = $row['user_birth_date'];
            $newpass = mt_rand();
            $user_institution_code = $row['profile_institution_code'];
            $user_group_id = $row['user_group_id'];
        }          
        
        $msg = "Password anda sudah dikirim ke email, silakan cek di folder Inbox atau folder Spam";
        $subject = "Permohonan Password Baru $user_fullname (Terkirim: ".date('d-m-Y H:i:s').")";
        if($user_group_id == '5')
        {
            $institution_code = "
                    <tr>
                    	<td>Kode RS</td>
                    	<td>:</td>
                    	<td><b>$user_institution_code</b></td>
                    </tr>";
        }
        else $institution_code = "";
        
        $body = "Yth Bpk/Ibu $user_fullname,<br /> <br />"
				."Anda telah melakukan permintaan password baru dan informasi akun anda adalah sbb:<br /><br />"
				."<table>
                    <tr>
                    	<td>Alamat Aplikasi SIKARS</td>
                    	<td>:</td>
                    	<td><a href='http://akreditasi.kars.or.id'>http://akreditasi.kars.or.id</a></td>
                    </tr>
                    <tr>
                    	<td>Username</td>
                    	<td>:</td>
                    	<td>$user_email</td>
                    </tr>
                    <tr>
                    	<td>Password</td>
                    	<td>:</td>
                    	<td><b>$newpass</b></td>
                    </tr>
                    $institution_code
                </table><br />"
                
                ."Jika Anda mengalami kesulitan, silakan menghubungi Sekretariat KARS. <br /><br /><br /> Terima kasih";
                
       
 
        $send_email = send_mail($subject, $body, $user_email, $user_fullname);

        
        $sql_update="UPDATE user SET user_password='".md5($newpass)."' WHERE user_name='".$user_email."'";
        $result=mysqli_query($conn, $sql_update);
        
        $msg = "<font color=green>Password baru anda sudah terkirim ke email, silakan cek di folder Inbox atau folder Spam</font>";
        
        echo json_encode("<p>Password anda sudah dikirim ke email, silakan cek di folder Inbox atau folder Spam!</p>");
    }
    else
    {
        echo json_encode("<p>Alamat email yang dimasukkan adalah salah atau tidak terdaftar!</p>");
    }
}
?>