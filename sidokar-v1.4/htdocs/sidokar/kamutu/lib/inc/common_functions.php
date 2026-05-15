<?php

/**
 *
 * @author Diyurman Gea <diyurman@gmail.com> - http://www.gbsolusindo.com
 * @copyright PT. Geamatika Bisnis Solusindo
 * @version 1.0
 * @since 2010
 *
 */

function standard_calculation($event_id,$accreditation_period)
{
    global $conn;
    $sql1 = "SELECT accreditation_version, accreditation_category_id, event_institution_code, event_institution_name
            FROM accreditation
            LEFT JOIN accreditation_event ON event_id = accreditation_event_id
            WHERE accreditation_event_id='$event_id'";
    $result1 = mysqli_query($conn,$sql1);
    while ($row = mysqli_fetch_assoc($result1))
    {
        $accreditation_version = $row['accreditation_version'];
        $accreditation_category_id = $row['accreditation_category_id'];
        $institution_code = $row['event_institution_code'];
        $institution_name = $row['event_institution_name'];
    }
    $sql2 = "
        INSERT INTO accreditation_transaction_std
        (transaction_std_event_id, transaction_std_version, transaction_std_category_id, transaction_std_accreditation_id, transaction_std_institution_code, transaction_std_institution_name, transaction_std_chapter_id, transaction_std_class_id, transaction_std_standard_id, transaction_std_score)

        SELECT '$event_id', $accreditation_version accreditation_version, $accreditation_category_id accreditation_category_id, transaction_accreditation_id, transaction_institution_code, '$institution_name' institution_name, transaction_chapter_id, transaction_class_id, transaction_standard_id, COALESCE(ROUND(SUM(transaction_final_score) / standard_maximum_score *100, 2 ),0) transaction_standard_score
        FROM accreditation_transaction_$accreditation_period
        LEFT JOIN accreditation_instrument_standard ON standard_id = transaction_standard_id AND standard_record_status = 'A'
        WHERE transaction_record_status = 'A' AND transaction_event_id='$event_id'
        GROUP BY transaction_event_id, transaction_standard_id
        ORDER BY transaction_event_id, transaction_standard_id
    ";
    $result2 = mysqli_query($conn,$sql2);
    return $result2;
}
function generate_consillor_task($event_id)
{
    global $conn;
    mysqli_autocommit($conn,FALSE);

    $sql_check_counsillor = "SELECT COUNT(*) c FROM accreditation_event_participant WHERE participant_event_id = $event_id AND participant_role = 7 AND participant_record_status = 'A'";
    if(get_data($sql_check_counsillor,'c') > 0) die("Konsilor sudah ditugaskan untuk melakukan pengecekan hasil di kegiatan ini. Terima kasih.");

    $start_date = date("Y-m-d");
    $end_date = add_days($start_date,7);
    $institution_code = get_data("SELECT event_institution_code FROM accreditation_event WHERE event_id IN('$event_id')","event_institution_code");
    $event_accreditation_version = get_data("SELECT event_accreditation_version FROM accreditation_event WHERE event_id IN('$event_id')","event_accreditation_version");

    $sql1   = "
               SELECT * FROM(
                    SELECT profile_id, profile_specialize_id, specialize_name, profile_fullname, profile_email
                    FROM `user_profile`
                    LEFT JOIN user_specialize ON specialize_id = profile_specialize_id
                    WHERE FIND_IN_SET('7',`profile_group_id`) AND `profile_specialize_id` !='' AND profile_id NOT IN
                    (
                        SELECT participant_profile_id FROM accreditation_event_participant WHERE participant_event_id = $event_id AND participant_role = 6 AND participant_record_status = 'A'
                    )
                    ORDER BY rand()
               )o
               GROUP BY profile_specialize_id";
    $result1 = mysqli_query($conn,$sql1);
    while ($row = mysqli_fetch_assoc($result1))
    {
        $profile_id    = $row['profile_id'];
        $specialize_name[] = $row['specialize_name'];
        $profile_fullname[]    = $row['profile_fullname'];
        $profile_email[]    = $row['profile_email'];
        $participant_chapter = chapters($profile_id,$event_accreditation_version,'4');//4 = RS

        $sql_insert_counsillor[] = "($event_id,$profile_id,'7','".$start_date."','".$end_date."','$institution_code','$participant_chapter','$event_accreditation_version','1','Hadir','','".$start_date."','{$_SESSION['user_profile_id']}','A')";

    }

    $sql_insert_counsillors = implode(",",$sql_insert_counsillor);
    $sql2 = "INSERT INTO accreditation_event_participant
					(participant_event_id,
					participant_profile_id,
                    participant_role,
					participant_start_date,
                    participant_end_date,
                    participant_institution_code,
                    participant_chapter_id,
                    participant_chapter_version,
					participant_is_approved,
					participant_attendance_status,
                    participant_attendance_note,
					participant_post_date,
                    participant_post_user_id,
                    participant_record_status)
			VALUES $sql_insert_counsillors";

    $result2 = mysqli_query($conn, $sql2);

    if($result2)
    {
        //*** Commit Transaction ***//
        mysqli_commit($conn);
        for($i==0;$i<count($profile_email);$i++)
        {
            $x = 0;
            $subject = "Penugasan Sejawat Sebagai Konsilor $specialize_name[$x] ($start_date - $end_date)";
            $body .= "Yth. Sejawat $profile_fullname[$x],<br />"
                    ."Konsilor Pejuang mutu KARS<br /><br />"

                    ."Kami menginformasikan bahwa KTS telah mengoreksi dan menyetujui hasil survei dan Sejawat ditunjuk sebagai konsilor pada hasil survei RS tersebut. "
                    ."Kiranya sejawat dapat melakukan pengecekan hasil survei tersebut selaku Konsilor $specialize_name[$x].<br />"
                    ."Hasil survei tersebut dapat diakses melalui http://akreditasi.kars.or.id <br />"
                    ."Bila mengalami kesulitan dalam mengunggah, jangan ragu hubungi sekretariat KARS pada nomor telp 29941552/29941553<br /><br />"
                    ."Terima kasih<br /><br />"


                    ."DR.Dr. Sutoto,M.Kes<br />"
                    ."Ketua Eksekutif KARS";

            //$send_email = send_mail_reminder($subject, $body, $user_email, $user_fullname);
            $x++;
        }
    }
    else
    {
        //*** RollBack Transaction ***//
        mysqli_rollback($conn);
        //phpgrid_error($sql1);
    }
    mysqli_close($result1);
    return $body;
}


function grade($event_id,$apps_name) //$apps_name = Nama Aplikasi, misalnya KARS, FKTP, dll
{
    global $conn;
    if($apps_name == "kars") {

        $chapter_total = get_data("SELECT COUNT(*) AS counter FROM accreditation_transaction_chapter WHERE trans_event_id = '$event_id' AND trans_final_is_tdd='0' GROUP BY trans_event_id","counter");

        $sql_score_total = "SELECT COUNT(`trans_final_score`) trans_final_score FROM accreditation_transaction_chapter WHERE trans_event_id = '$event_id' AND trans_final_is_tdd='0' AND trans_final_score >= '80' GROUP BY trans_event_id";
        $score_total = get_data($sql_score_total,"trans_final_score");

        if($score_total=="") $score_total='0';

        //$score_fail = get_data("SELECT COUNT(`trans_final_score`) counter FROM accreditation_transaction_chapter WHERE trans_event_id = '$event_id' AND trans_final_is_tdd='0' AND trans_final_score < 60 GROUP BY trans_event_id","counter");

        if($chapter_total == $score_total)
        {
            $status_id = '7'; //Lulus Parpurna
        }
        elseif($score_total >= 12)
        {
            $status_id = '6'; //Utama
        }
        elseif($score_total >= 8)
        {
            $status_id = '5'; //Madya
        }
        elseif($score_total >= 4)
        {
            $status_id = '4'; //Dasar
        }
        else $status_id = '9';


    }
    elseif($apps_name=='fktp')
    {
        $sql = "
        SELECT
            MAX(CASE WHEN trans_chapter_id = '1' THEN round(`trans_surveyor_score`,2) END) chapter1,
            MAX(CASE WHEN trans_chapter_id = '2' THEN round(`trans_surveyor_score`,2) END) chapter2,
            MAX(CASE WHEN trans_chapter_id = '3' THEN round(`trans_surveyor_score`,2) END) chapter3,
            MAX(CASE WHEN trans_chapter_id = '4' THEN round(`trans_surveyor_score`,2) END) chapter4,
            MAX(CASE WHEN trans_chapter_id = '5' THEN round(`trans_surveyor_score`,2) END) chapter5,
            MAX(CASE WHEN trans_chapter_id = '6' THEN round(`trans_surveyor_score`,2) END) chapter6,
            MAX(CASE WHEN trans_chapter_id = '7' THEN round(`trans_surveyor_score`,2) END) chapter7,
            MAX(CASE WHEN trans_chapter_id = '8' THEN round(`trans_surveyor_score`,2) END) chapter8,
            MAX(CASE WHEN trans_chapter_id = '9' THEN round(`trans_surveyor_score`,2) END) chapter9,
            MAX(CASE WHEN trans_chapter_id = '10' THEN round(`trans_surveyor_score`,2) END) chapter10,
            MAX(CASE WHEN trans_chapter_id = '11' THEN round(`trans_surveyor_score`,2) END) chapter11,
            MAX(CASE WHEN trans_chapter_id = '12' THEN round(`trans_surveyor_score`,2) END) chapter12,
            MAX(CASE WHEN trans_chapter_id = '13' THEN round(`trans_surveyor_score`,2) END) chapter13
        FROM accreditation_transaction_chapter
        WHERE trans_event_id='$event_id' AND trans_record_status = 'A'
        GROUP BY trans_event_id
                   ";

        $result = mysqli_query($conn,$sql);
        if (mysqli_num_rows($result) == 1)
        {
            while ($row = mysqli_fetch_assoc($result))
            {
                $chapter1      = $row['chapter1'];
                $chapter2      = $row['chapter2'];
                $chapter3      = $row['chapter3'];
                $chapter4      = $row['chapter4'];
                $chapter5      = $row['chapter5'];
                $chapter6      = $row['chapter6'];
                $chapter7      = $row['chapter7'];
                $chapter8      = $row['chapter8'];
                $chapter9      = $row['chapter9'];
                $chapter10      = $row['chapter10'];
                $chapter11      = $row['chapter11'];
                $chapter12      = $row['chapter12'];
                $chapter13      = $row['chapter13'];
             }
        }

        /** Rumus Kelulusan untuk FKTP **/
        $institution_group_id = get_data("SELECT event_institution_group_id FROM accreditation_event WHERE event_id='$event_id'","event_institution_group_id");
        if($institution_group_id == '5')
        {
            if($chapter1 >= 80 && $chapter2 >= 80 && $chapter3 >= 80 && $chapter4 >= 80 && $chapter5 >= 80 && $chapter6 >= 80 && $chapter7 >= 80 && $chapter8 >= 80 && $chapter9 >= 80)
            {
                $status_id = '5'; //Lulus Parpurna
            }
            elseif($chapter1 >= 80 && $chapter2 >= 80 && $chapter3 >= 60 && $chapter4 >= 80 && $chapter5 >= 80 && $chapter6 >= 60 && $chapter7 >= 80 && $chapter8 >= 80 && $chapter9 >= 60)
            {
                $status_id = '4'; //Utama
            }
            elseif($chapter1 >= 75 && $chapter2 >= 75 && $chapter3 >= 40 && $chapter4 >= 75 && $chapter5 >= 75 && $chapter6 >= 40 && $chapter7 >= 60 && $chapter8 >= 40 && $chapter9 >= 20)
            {
                $status_id = '3'; //Madya
            }
            elseif($chapter1 >= 60 && $chapter2 >= 60 && $chapter3 >= 20 && $chapter4 >= 60 && $chapter5 >= 60 && $chapter6 >= 20 && $chapter7 >= 60 && $chapter8 >= 40 && $chapter9 >= 20)
            {
                $status_id = '2'; //Dasar
            }
            else $status_id = '7';
        }elseif($institution_group_id == '12')
        {

            if($chapter10 >= 80 && $chapter11 >= 80 && $chapter12 >= 80 && $chapter13 >= 80)
            {
                $status_id = '5'; //Lulus Parpurna
            }
            elseif($chapter10 >= 80 && $chapter11 >= 80 && $chapter12 >= 80 && $chapter13 >= 60)
            {
                $status_id = '4'; //Lulus Utama
            }
            elseif($chapter10 >= 75 && $chapter11 >= 75 && $chapter12 >= 75 && $chapter13 >= 40)
            {
                $status_id = '3'; //Lulus Madya
            }
            elseif($chapter10 >= 75 && $chapter11 >= 60 && $chapter12 >= 60 && $chapter13 >= 40)
            {
                    $status_id = '2'; //Lulus Dasar
            }
            else $status_id = '7';
        }

    }
    //phpgrid_error($status_id);
    return $status_id;
}

function get_aspak_average($institution_code,$institution_type)
{
    $institution_city_code = get_data("SELECT institution_city_code FROM institution WHERE institution_code IN('$institution_code') AND institution_record_status='A'","institution_city_code");
    //$json = '[{"code":"P3404040202","rs":"GODEAN II","loc":"Kab. Sleman","id":"7165","sarcen":100,"pracen":54.72,"alcen":79.49,"rasalcen":15.9,"general":"-","bogeneral":"-","rerata":87.27,"borerata":61.83,"idloc":"26","idkabkot":"275","propinsi":"DI Yogyakarta","status":"-","pemilik":"Kemkes","tgla":"30-04-2019","tglsp":"04-04-2019"},{"code":"P3404140201","rs":"TEMPEL I","loc":"Kab. Sleman","id":"7181","sarcen":100,"pracen":54.72,"alcen":75.98,"rasalcen":15.2,"general":"-","bogeneral":"-","rerata":85.86,"borerata":61.55,"idloc":"26","idkabkot":"275","propinsi":"DI Yogyakarta","status":"Ranap","pemilik":"Kemkes","tgla":"30-04-2019","tglsp":"16-03-2019"},{"code":"P3404130201","rs":"SLEMAN","loc":"Kab. Sleman","id":"7180","sarcen":100,"pracen":52.83,"alcen":75.54,"rasalcen":15.11,"general":"-","bogeneral":"-","rerata":85.5,"borerata":61.33,"idloc":"26","idkabkot":"275","propinsi":"DI Yogyakarta","status":"Ranap","pemilik":"Kemkes","tgla":"30-04-2019","tglsp":"13-03-2019"},{"code":"P3404070201","rs":"DEPOK I","loc":"Kab. Sleman","id":"7170","sarcen":100,"pracen":60.38,"alcen":74.09,"rasalcen":14.82,"general":"-","bogeneral":"-","rerata":85.67,"borerata":61.96,"idloc":"26","idkabkot":"275","propinsi":"DI Yogyakarta","status":"-","pemilik":"Kemkes","tgla":"30-04-2019","tglsp":"27-03-2019"}]';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://aspak.yankes.kemkes.go.id/aplikasi/api/spalengkap?bps=$institution_city_code&j=$institution_type");//bps=3404&j=pkm
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    $err = curl_error($ch);
    //print_r($content);

    //$institution_code = 'P3404140201';
    $data = json_decode($content, true);
    foreach ($data as $institutions) {
        if($institution_code == $institutions['code'])
        {
            $average = $institutions['rerata'];
        }
    }
    return $average;
}

function get_bppsdmk_average($institution_code)
{
    $username = "siaf";
    $password = "AKREDTASIPKM2019";
    //$kdfasyankes = "P1101010101"; // <== kode puskesmas yang dipanggil

    $data_auth = file_get_contents("http://sisdmk.bppsdmk.kemkes.go.id/rest/login_ws?username=$username&password=$password");
    $array_data_auth = json_decode($data_auth,true);
    $token = $array_data_auth['token'];

    //-------WS Agregat SDMK Faskes
    $data_ws = file_get_contents("http://sisdmk.bppsdmk.kemkes.go.id/rest/keadaan_pkm?kdfasyankes=$institution_code&token=$token");
    //-------------------------

    $array_data_ws = json_decode($data_ws,true);

    $counter = 0;
    if($array_data_ws['data']['Dokter']['selisih'] >= 0) $counter++;
    if($array_data_ws['data']['Perawat']['selisih'] >= 0) $counter++;
    if($array_data_ws['data']['Dokter Gigi']['selisih'] >= 0) $counter++;
    if($array_data_ws['data']['Bidan']['selisih'] >= 0) $counter++;
    if($array_data_ws['data']['Tenaga_kefarmasian']['selisih'] >= 0) $counter++;
    if($array_data_ws['data']['Tenaga_Kesmas']['selisih'] >= 0) $counter++;
    if($array_data_ws['data']['Tenaga_kesehatan_Lingkungan']['selisih'] >= 0) $counter++;
    if($array_data_ws['data']['Tenaga_gizi']['selisih'] >= 0) $counter++;
    if($array_data_ws['data']['Ahli_Teknologi_Laboratorium_Medik']['selisih'] >= 0) $counter++;

    $average = round(($counter/9)*100,2);
    return $average;
}

function letter_number($config_attribut,$config_operator) //Generate nomor surat
{
    global $conn;
    $today = date("Y-m-d H:m:s");
    $sql = "SELECT config_value FROM config WHERE config_attribute = '$config_attribut' AND config_operator = '$config_operator'";
    $new_letter_code = get_data($sql,"config_value");
    return $new_letter_code;
}

function add_year($start_date,$years)
{
  $change_date = strtotime($start_date);
  $retDAY = date('Y-m-d', mktime(0,0,0,date('m',$change_date),date('d',$change_date)-1,date('Y',$change_date)+$years));
  return $retDAY;
}

function add_days($start_date,$days)
{
  $change_date = strtotime($start_date);
  $retDAY = date('Y-m-d', mktime(0,0,0,date('m',$change_date),date('d',$change_date)+$days,date('Y',$change_date)+$years));
  return $retDAY;
}

function recap_transaction($recap_name,$recap_attribute,$recap_period)
{
    global $conn;
    $today = date("Y-m-d H:i:s");

    $count = get_data("SELECT COUNT(*)counter FROM accreditation_event_participant WHERE participant_profile_id = '$recap_name' AND DATE_FORMAT(participant_start_date,'%Y') = '$recap_period' AND participant_record_status='A'","counter");

    $sql_update = "UPDATE accreditation_transaction_recap SET
                   recap_value = '$count'
            WHERE recap_profile_id='$recap_name'
                AND recap_attribute='$recap_attribute' AND recap_period='$recap_period'
                AND recap_record_status='A'";

    $sql_insert = "INSERT INTO `accreditation_transaction_recap`(`recap_profile_id`, `recap_attribute`, `recap_period`, `recap_value`, `recap_updated_time`)
                    VALUES ('$recap_name','$recap_attribute','$recap_period','$count','$today')";

    $sql_select = "SELECT recap_value FROM accreditation_transaction_recap
                   WHERE recap_profile_id='$recap_name'
                    AND recap_attribute='$recap_attribute' AND recap_period='$recap_period'
                    AND recap_record_status='A'";

    //echo $sql_select;
    $result = get_data($sql_select,"recap_value");

	if ($result > 0) {
		//echo $sql_update;
        mysqli_query($conn,$sql_update);
    }
    elseif($result < 1 || $result == null)
    {
        //echo $sql_insert;
        mysqli_query($conn,$sql_insert);
    }
}

function available_schedule($user_id,$start_date, $end_date) //Digunakan untuk mengecek jadwal tugas Surveyor/Pembimbing/Nara Sumber
{
	global $conn;

    $sql = "SELECT participant_id
            FROM accreditation_event_participant
            JOIN accreditation_event ON event_id = participant_event_id
            WHERE participant_profile_id = $user_id
            AND DATE_FORMAT(participant_start_date,'%Y%m%d') <= DATE_FORMAT('$start_date','%Y%m%d') AND DATE_FORMAT(participant_end_date,'%Y%m%d') >= DATE_FORMAT('$end_date','%Y%m%d')
            AND participant_role IN(6)
            AND participant_is_approved = 1
            AND participant_attendance_status = 'Hadir'
            AND participant_record_status = 'A'";

    $result = mysqli_query($conn,$sql);

	if (mysqli_num_rows($result) > 0) {
	    $available = "Surveior tersebut bentrok dengan jadwal lainnya";
	} else
	{
	    $available = "yes";
    }
    return $available;
}

function has_job_lastweek($user_id,$date) //Digunakan untuk mengecek jadwal tugas Surveyor/Pembimbing/Nara Sumber pada minggu sebelumnya
{
	global $conn;
    $week   = date("W", strtotime("+1 day",strtotime($date)));$weeks = $week - 2;
    $year   = date("Y", strtotime($date));

    $sql = "SELECT participant_id
            FROM accreditation_event_participant
            JOIN accreditation_event ON event_id = participant_event_id
            WHERE participant_profile_id = $user_id
            AND DATE_FORMAT(COALESCE(event_start_date,event_start_date_plan),'%Y%U')='$year$weeks'
            AND participant_role IN(6)
            AND participant_is_approved = 1
            AND participant_attendance_status = 'Hadir'
            AND participant_record_status = 'A'";

    $result = mysqli_query($conn,$sql);
	//phpgrid_error($sql);
	if (mysqli_num_rows($result) > 0) {
	    $available = 1;
	} else
	{
	    $available = 0;
    }
    return $available;
}

function surveyor_eligible($user_id,$institution,$start_date) //Digunakan untuk mengecek apakah eligible untuk melakukan survei di institusi tersebut
{
	global $conn;
    //Surveior yang pernah melakukan kegiatan survei, bimbingan kurang dari 5 tahun (1825 hari) di RS tersebut tidak boleh
    $sql1 = "SELECT participant_id
            FROM accreditation_event_participant
            WHERE participant_profile_id = $user_id
            AND (DATE_FORMAT('$start_date','%Y%m%d') - DATE_FORMAT(participant_start_date,'%Y%m%d')) < 1825
            AND participant_institution_code = '$institution'
            AND participant_role != 2
            AND participant_is_approved = 1
            AND participant_attendance_status = 'Hadir'
            AND participant_record_status = 'A'";

    $result1 = mysqli_query($conn,$sql1);

	if (mysqli_num_rows($result1) > 0) {
	    $available = "Surveior tersebut pernah melakukan kegiatan survei atau bimbingan kurang dari 5 tahun!";
	} else
	{
	    $available = "yes";
    }

    return $available;
}

function surveyor_institution($user_id,$institution) //Digunakan untuk mengecek apakah surveyor pernah atau sedang bekerja di institusi tersebut
{
	global $conn;
    $sql1 = "SELECT position_institution_code
            FROM user_position
            WHERE position_profile_id = '$user_id'
            AND position_institution_code = '$institution'
            AND position_record_status = 'A'";

    $result1 = mysqli_query($conn,$sql1);

	if (mysqli_num_rows($result1) > 0) {
	    $available = "Surveior tersebut pernah atau sedang bekerja di institusi yang sama!";
	} else
	{
	    $available = "yes";
    }

    return $available;
}

function chapters($profile_id,$version,$chapter_institution_group_id)//4=Rumah Sakit; 5 = Puskesmas; 12 = Klinik; 13=Praktek Dokter Mandiri
{
     global $conn;
     $sql1 = "
            SELECT DISTINCT chapter_id
            FROM accreditation_instrument_chapter
            LEFT JOIN user_profile ON profile_specialize_id = chapter_specialize_id
            WHERE profile_id IN($profile_id) AND chapter_version IN($version) AND chapter_institution_group_id IN('$chapter_institution_group_id')
            ORDER BY chapter_specialize_id ASC";

    $result1 = mysqli_query($conn,$sql1);
    while($row = mysqli_fetch_array($result1))
   	{
   	    $chapter[] = $row['chapter_id'];
    }
    $chapter_id = implode(",", $chapter);
    return $chapter_id;
}

function send_email_duty_notification($event_id,$user_profile_id)
{
    #Lakukan pengiriman email notifikasi kepada Surveior
    $sql1 = "SELECT DISTINCT profile_email, profile_fullname, event_institution_name, event_province_name, event_city_name, event_address_other, DATE_FORMAT(COALESCE(event_start_date,event_start_date_plan),'%d-%m-%Y') event_start_date, DATE_FORMAT(COALESCE(event_end_date,event_end_date_plan),'%d-%m-%Y') event_end_date
        FROM accreditation_event_participant
        LEFT JOIN user_profile ON participant_profile_id = profile_id AND profile_record_status ='A'
        LEFT JOIN accreditation_event ON event_id = participant_event_id
        WHERE profile_id= '$user_profile_id' AND participant_event_id = '$event_id' AND participant_record_status = 'A'";
    //echo $sql5;
    $result1 = mysqli_query($conn, $sql1);
    while ($row = mysqli_fetch_assoc($result1))
    {
        //$profile_email = $row['profile_email'];
        $profile_email      = $row['profile_email'];
        $profile_fullname   = $row['profile_fullname'];
        $event_start_date   = $row['event_start_date'];
        $event_end_date     = $row['event_end_date'];
        $event_address_other    = $row['event_address_other'];
        $event_city_name        = $row['event_city_name'];
        $event_province_name    = $row['event_province_name'];
        $participant_role   = $row['participant_role'];

        if($participant_role==9) {
            $subject = "Penugasan Sejawat Sebagai Pendamping Pada Tanggal $event_start_date - $event_end_date";
            $body = "Yth. Sejawat $profile_fullname,<br /><br />"

                    ."Dengan ini diberitahukan bahwa Sejawat telah ditugaskan untuk mendampingi kegiatan Survei pada:<br /><br />"
                    ."Nama Instansi : <em>$institution_name</em><br />"
                    ."Alamat : <em>$event_address_other</em><br />"
                    ."Kab/Kota : <em>$event_city_name</em><br />"
                    ."Provinsi : <em>$province_name</em><br />"
                    ."Tanggal : <em>$event_start_date - $event_end_date</em><br /><br />"
                    ."Surat penugasan Saudara sejawat dapat diunduh dari Sistem SIAF http://siaf.kemkes.go.id/siaf/.  Terima Kasih.<br /><br /><br />"
                    ."Selamat berjuang<br /><br />"

                    ."drg. Tini Suryanti Suhandi, M.Kes<br />"
                    ."Ketua Eksekutif Komisi FKTP<br /><br />";
        }
        //echo $body."<br />";
        $send_email = send_mail($subject, $body, $profile_email, $profile_fullname);

    }
}

function seo_friendly_url($string){
    $string = str_replace(array('[\', \']'), '', $string);
    $string = preg_replace('/\[.*\]/U', '', $string);
    $string = preg_replace('/&(amp;)?#?[a-z0-9]+;/i', ' ', $string);
    $string = htmlentities($string, ENT_COMPAT, 'utf-8');
    $string = preg_replace('/&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);/i', '\\1', $string );
    $string = preg_replace(array('/[^a-z0-9]/i', '/[-]+/') , ' ', $string);
    //return strtolower(trim($string, ' '));
    return $string;
}

function getCustomHeaders()
{
    $headers = array();
    foreach($_SERVER as $key => $value)
    {
        if(preg_match("/^HTTP_X_/", $key))
            $headers[$key] = $value;
    }
    return $headers;
}

function nama_bulan($m)
{
	if($m==1){$m="Januari";}else if($m==2){$m="Februari";}else if($m==3){$m="Maret";}else if($m==4){$m="April";}else if($m==5){$m="Mei";}else if($m==6){$m="Juni";}else if($m==7){$m="Juli";}else if($m==8){$m="Agustus";}else if($m==9){$m="September";}else if($m==10){$m="Oktober";}else if($m==11){$m="November";}else if($m==12){$m="Desember";}return $m;
}

function age($birthday)
{
    $bday = new DateTime($birthday); // Your date of birth
    $today = new Datetime(date('y-m-d'));
    $diff = $today->diff($bday);
    $age =  $diff->y." Tahun ". $diff->m." bulan " .$diff->d." hari ";

    return $age;
}

function day_different($date1, $date2)
{
    $start  = new DateTime($date1); // Your date of birth
    $end    = new Datetime($date2);
    $diff   = $end->diff($start);
    $days   = $diff->d."";

    if(intval($days) < 1) $days = "1";

    return $days;
}

function kekata($x) {
    $x = abs($x);
    $angka = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
    $temp = "";

    if ($x <12) {
        $temp = " ". $angka[$x];
    } else if ($x <20) {
        $temp = kekata($x - 10). " belas";
    } else if ($x <100) {
        $temp = kekata($x/10)." puluh". kekata($x % 10);
    } else if ($x <200) {
        $temp = " seratus" . kekata($x - 100);
    } else if ($x <1000) {
        $temp = kekata($x/100) . " ratus" . kekata($x % 100);
    } else if ($x <2000) {
        $temp = " seribu" . kekata($x - 1000);
    } else if ($x <1000000) {
        $temp = kekata($x/1000) . " ribu" . kekata($x % 1000);
    } else if ($x <1000000000) {
        $temp = kekata($x/1000000) . " juta" . kekata($x % 1000000);
    } else if ($x <1000000000000) {
        $temp = kekata($x/1000000000) . " milyar" . kekata(fmod($x,1000000000));
    } else if ($x <1000000000000000) {
        $temp = kekata($x/1000000000000) . " trilyun" . kekata(fmod($x,1000000000000));
    }

        return $temp;

}

function terbilang($x, $style=4) {

    if($x<0) {

        $hasil = "minus ". trim(kekata($x));

    } else {
        //$poin = trim(tkoma($x));
        $hasil = trim(kekata($x));

    }

    switch ($style) {

        case 1:
        if ($poin) {
                $hasil  = strtoupper($hasil). " KOMA " . strtoupper($poin);
        } else  $hasil = strtoupper($hasil);

            break;

        case 2:
        if ($poin) {
                $hasil  = strtolower($hasil). " koma " . strtolower($poin);
        } else $hasil = strtolower($hasil);

            break;

        case 3:
        if ($poin) {
                $hasil  = ucwords($hasil). " Koma " . ucwords($poin);
        } else $hasil = ucwords($hasil);

            break;

        default:
        if ($poin) {
                $hasil  = ucfirst($hasil). " koma " . ucfirst($poin);
        }else $hasil = ucfirst($hasil);

            break;

    }

    return $hasil;

}

function tkoma($x)
{
        $x              = stristr($x, '.');
        $angka  = array ("nol", "satu","dua","tiga","empat","lima","enam","tujuh","delapan","sembilan");
        $temp   = "";
        $panjang        = strlen($x);
        $pos = 1;

        while ($pos < $panjang) {
                $char   = substr($x, $pos, 1);
                $pos++;
                $temp   .= " ". $angka[$char];
        }
        return $temp;
}

function get_data($sql,$return_field)
{
    global $conn;
    $today = date("Y-m-d H:m:s");
    $result = mysqli_query($conn,$sql);
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $value    = $row[$return_field];
        }
    }
    //echo $sql;
    return $value;
}

function anti_sql_injection($string) {
    $string = stripslashes($string);
    $string = strip_tags($string);
    $string = mysql_real_escape_string($string);
    return $string;
}

function generate_code($string,$digit) //$string = character, $digit = berapa digit kode yang digenerate
{
	$today = date(Ym);
	$string_length = strlen($string);
	$count = $digit - $string_length;

	while ($i < $count) {
		  $prefix = $prefix . '0';
		  $i++;
	}
	$value = $prefix.$string;
	return $value;
}

function get_counter($attribute,$operator)
{
    $sql = "SELECT config_value FROM config WHERE config_attribute IN('$attribute') AND config_operator IN('$operator')";
    $get_number = get_data($sql,"config_value");
    //echo $sql;
    return $get_number;
}

function customer_medical_record($location_counter,$location_date,$format_date,$digit_number) {
    /** Algoritma Penomoran **/
    //$location_counter = "customer_counter.txt";
    //$location_date = "customer_date.txt";
    //$format_date = date("Y");
    //$digit_number = 5;
    /** New Day **/
    $aday = join('', file($location_date));
    trim($aday);

    if($aday==$format_date){
        /** Hari ini **/
        $counter = join('', file($location_counter));
        trim($counter);
        $counter++;

        $fp = fopen($location_counter,"w");
        fputs($fp, $counter);
        fclose($fp);
    }else{
        /** Hari yang baru **/
        $fp = fopen($location_counter,"w");
        fputs($fp, 0);
        fclose($fp);
        $counter = join('', file($location_counter));
        trim($counter);
        $counter++;
        /** Tulis hari baru **/
        $fp = fopen($location_counter,"w");
        fputs($fp, $counter);
        fclose($fp);
        /** Tulis di date.txt **/
        $fp = fopen($location_date,"w");
        fputs($fp, $format_date);
        fclose($fp);
    }
    $counter = generate_code($counter,$digit_number);
    return $counter;
}


function log_transaction($user_id,$action,$transaction_status)
{
    global $conn;
    $today = date("Y-m-d H:m:s");
    $ip = get_ip_address();
	//echo $ip;

    $sql = "INSERT INTO `user_log_activity`(`activity_id`, `activity_date`, `activity_user_id`, `activity_description`, `activity_status`, `activity_ipaddress`)
            VALUES (NULL,'$today',$user_id,'$action','$transaction_status','$ip')";
	//echo $sql;
    $result = mysqli_query($conn,$sql);
}

function get_ip_address() {
    $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                // trim for safety measures
                $ip = trim($ip);
                // attempt to validate IP
                if (validate_ip($ip)) {
                    return $ip;
                }
            }
        }
    }

    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
}


/**
 * Ensures an ip address is both a valid IP and does not fall within
 * a private network range.
 */
function validate_ip($ip)
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return false;
    }
    return true;
}

/** Contoh SQL Command untuk membaca sebuah field yang value-nya mengandung comma.  Misalnya: user_access_chapter_id = 1,2,3,4,5,6
    SELECT *
    FROM user
    WHERE FIND_IN_SET('3',user_access_chapter_id)
**/

/** *******************************************************************
 * Function ini digunakan untuk Bridging dengan aplikasi E-Klaim
 *
 * *******************************************************************/
// Encryption Function
function inacbg_encrypt($data, $key) {
    /// make binary representasion of $key
    $key = hex2bin($key);
    /// check key length, must be 256 bit or 32 bytes
    if (mb_strlen($key, "8bit") !== 32) {
        throw new Exception("Needs a 256-bit key!");
    }
    /// create initialization vector
    $iv_size = openssl_cipher_iv_length("aes-256-cbc");
    $iv = openssl_random_pseudo_bytes($iv_size); // dengan catatan dibawah
    /// encrypt
    $encrypted = openssl_encrypt($data,"aes-256-cbc",$key,OPENSSL_RAW_DATA,$iv );
    /// create signature, against padding oracle attacks
    $signature = mb_substr(hash_hmac("sha256",
    $encrypted,
    $key,
    true),0,10,"8bit");
    /// combine all, encode, and format
    $encoded = chunk_split(base64_encode($signature.$iv.$encrypted));
    return $encoded;
}

// Decryption Function
function inacbg_decrypt($str, $strkey){
    /// make binary representation of $key
    $key = hex2bin($strkey);
    /// check key length, must be 256 bit or 32 bytes
    if (mb_strlen($key, "8bit") !== 32) {
        throw new Exception("Needs a 256-bit key!");
    }
    /// calculate iv size
    $iv_size = openssl_cipher_iv_length("aes-256-cbc");
    /// breakdown parts
    $decoded = base64_decode($str);
    $signature = mb_substr($decoded,0,10,"8bit");
    $iv = mb_substr($decoded,10,$iv_size,"8bit");
    $encrypted = mb_substr($decoded,$iv_size+10,NULL,"8bit");
    /// check signature, against padding oracle attack
    $calc_signature = mb_substr(hash_hmac("sha256",$encrypted,$key,true),0,10,"8bit");

    if(!inacbg_compare($signature,$calc_signature)) {
        return "SIGNATURE_NOT_MATCH"; /// signature doesn't match
    }
    $decrypted = openssl_decrypt($encrypted,"aes-256-cbc",$key,OPENSSL_RAW_DATA,$iv);
    return $decrypted;
}


/// Compare Function
function inacbg_compare($a, $b) {
    /// compare individually to prevent timing attacks
    /// compare length
    if (strlen($a) !== strlen($b)) return false;
    /// compare individual
    $result = 0;
    for($i = 0; $i < strlen($a); $i ++) {
        $result |= ord($a[$i]) ^ ord($b[$i]);
    }
    return $result == 0;
}

function eclaimProcess($data){

    $json_request = json_encode($data);
    // data yang akan dikirimkan dengan method POST adalah encrypted:
    $payload = inacbg_encrypt($json_request,EKLAIM_KEY);
    // tentukan Content-Type pada http header
    $header = array("Content-Type: application/x-www-form-urlencoded");
    // url server aplikasi E-Klaim,
    // silakan disesuaikan instalasi masing-masing
    $url = EKLAIM_URL;
    // setup curl
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);
    // terlebih dahulu hilangkan "----BEGIN ENCRYPTED DATA----\r\n"
    // dan hilangkan "----END ENCRYPTED DATA----\r\n" dari response
    $first = strpos($response, "\n")+1;
    $last = strrpos($response, "\n")-1;
    $response = substr($response,$first,strlen($response) - $first - $last);

    // decrypt dengan fungsi inacbg_decrypt
    $response = inacbg_decrypt($response,EKLAIM_KEY);
    // hasil decrypt adalah format json, ditranslate kedalam array
    $msg = json_decode($response,true);
    //echo $response;
    return $msg;
}

function applicares($action,$room_id)
{
    global $conn;

    if(APPLICARES_IS_BRIDGING) {
        $sql2 = "SELECT class_code_1 kodekelas, room_type_2 koderuang, namaruang, room_amount total_TT, (room_gender_1 - room_gender_usage_1) kosong_male, (room_gender_0 - room_gender_usage_0) kosong_female, (room_gender_2 - room_gender_usage_2) kosong_male_female, '".date('Y-m-d H:i:s')."' tgl_update
                FROM `master_room` a
                LEFT JOIN master_room_type b ON a.room_type_id = b.room_type_id
                LEFT JOIN master_room_class ON room_class_id = room_class_id
                WHERE a.room_record_status = 'A' AND room_id = '$room_id'";

        $result2 = mysqli_query($conn, $sql2);

        if (mysqli_num_rows($result2) > 0) {
            $data_room = '';
        	while($row = mysqli_fetch_assoc($result2)) {
                $data_room .= '
                {
                    "kodekelas":"'.$row['kodekelas'].'",
                    "koderuang":"'.$row['koderuang'].'",
                    "namaruang":"'.$row['namaruang'].'",
                    "kapasitas":"'.$row['total_TT'].'",
                    "tersedia":"'.$row['kosong_male']+$row['kosong_female']+$row['kosong_male_female'].'",
                    "tersediapria":"'.$row['kosong_male'].'",
                    "tersediawanita":"'.$row['kosong_female'].'",
                    "tersediapriawanita":"'.$row['kosong_male_female'].'"
                }';
        	}

            $data = DATA_BPJS;
            $secretKey = SECRETKEY_BPJS;

            // Computes the timestamp
            date_default_timezone_set('UTC');
            $tStamp = strval(time()-strtotime('1970-01-01 00:00:00'));
            // Computes the signature by hashing the salt with the secret key as the key
            $signature = hash_hmac('sha256', $data."&".$tStamp, $secretKey, true);

            // base64 encode…
            $encodedSignature = base64_encode($signature);

            $ch = curl_init();
            $headers = array(
                'X-cons-id: '.$data .'',
                'X-timestamp: '.$tStamp.'' ,
                'X-signature: '.$encodedSignature.'',
                'Content-Type: Application/x-www-form-urlencoded'
            );

            $url = URL_BPJS_APLICARES."rest/bed/$action/".PPK_CODE;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_room);

            $content = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);
        	//echo $str;
        }else{
        	//echo "0 results";
        }
    }
}

function sub_js($assessment_id)
{
    $js .= "
            $(\"#check-$assessment_id\").click(function () {
                if ($(this).is(\":checked\")) {
                    $(\"#div-$assessment_id\").show();
                } else {
                    $(\"#div-$assessment_id\").hide();
                }
            });
        ";
    return $js;
}

function sub_text_js($assessment_id)
{
    $js .= "
            $(\"#check-$assessment_id\").click(function () {
                if ($(this).is(\":checked\")) {
                    $(\"#div-text$assessment_id\").show();
                } else {
                    $(\"#div-text$assessment_id\").hide();
                }
            });
        ";
    return $js;
}

function get_customer_info($treatment_id)
{
    global $conn;
    $sql_patient_info = "SELECT treatment_id, treatment_patient_age, DATE_FORMAT(customer_birthday,'%d-%m-%Y')customer_birthday, coalesce(customer_hospital_medical_record,customer_medical_record) customer_hospital_medical_record, customer_medical_record, customer_name, treatment_profile_id, treatment_diagnosis_code,
                        treatment_care_type, treatment_room_bed_id, insurance_name, DATE_FORMAT(treatment_in,'%d-%m-%Y %H:%i:%s') treatment_in,
                        CASE WHEN treatment_bracelet = 1 THEN 'red'
                            WHEN treatment_bracelet = 2 THEN 'yellow'
                            WHEN treatment_bracelet = 3 THEN 'green' END treatment_bracelet
                    FROM customer
                    INNER JOIN customer_treatment ON treatment_customer_id = customer_id
                    LEFT JOIN master_insurance ON insurance_id = treatment_insurance_id
                    WHERE treatment_id IN('$treatment_id')
                    ";
    $result = mysqli_query($conn,$sql_patient_info);
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $_SESSION['customer_treatment_id'] = $row['treatment_id'];
            $_SESSION['treatment_profile_id'] = $row['treatment_profile_id'];
            $_SESSION['customer_medical_record']    = $row['customer_medical_record'];
            $_SESSION['customer_hospital_medical_record'] = $row['customer_hospital_medical_record'];
            $_SESSION['customer_name']    = $row['customer_name'];
            $_SESSION['customer_birthday']    = $row['customer_birthday'];
            $_SESSION['customer_blood_type']    = $row['customer_blood_type'];
            $_SESSION['treatment_patient_age']    = $row['treatment_patient_age'];
            $_SESSION['treatment_in']    = $row['treatment_in'];
            $_SESSION['treatment_bracelet']    = $row['treatment_bracelet'];
            $_SESSION['treatment_diagnosis_code']    = $row['treatment_diagnosis_code'];
            $_SESSION['treatment_care_type']    = $row['treatment_care_type'];
            $_SESSION['treatment_room_bed_id']    = $row['treatment_room_bed_id'];
            $_SESSION['insurance_name']    = $row['insurance_name'];
        }
    }
    return true;
}

function menu()
{
    global $conn;

    $sql_1st = "SELECT DISTINCT * FROM
                (
                    SELECT modules_id,modules_title,modules_link,modules_icon,modules_parent
                    FROM kalmut_modules
                    INNER JOIN kalmut_user_group_access ON modules_id = access_module_id
                    WHERE modules_category IN(1) AND 'modules_parent' IN(0)
                    AND access_record_status='A' AND modules_is_display = 1
                    AND modules_position = 'left'
                    AND modules_record_status = 'A'
                ) o";
   // bimpun semua parent menu yang diaktifkan

    $result1 = mysqli_query($conn,$sql_1st);
    if (mysqli_num_rows($result1) > 0) {
        while($level_1st = mysqli_fetch_assoc($result1)){
    	   $menu_id_1st            = $level_1st['modules_id'];
           $modules_title_1st      = $level_1st['modules_title'];
           $modules_icon_1st       = $level_1st['modules_icon'];
           $modules_link_1st       = $level_1st["modules_link"];
           $modules_parent_1st     = $level_1st["modules_parent"];
           //himpun semua chile dari setiap parent
           $sql_2nd = "SELECT DISTINCT * FROM
                    (
                        SELECT modules_id,modules_title,modules_link FROM kalmut_modules
                        INNER JOIN kalmut_user_group_access ON modules_id = access_module_id
                        WHERE modules_parent='$menu_id_1st' AND modules_is_display = 1 AND modules_record_status = 'A'
                        ORDER BY modules_id ASC
                    )o";

    	   $result2 = mysqli_query($conn,$sql_2nd);

           if (mysqli_num_rows($result2) > 0)
           {
                //tampilkan parent
                $menu .= "<i class='$modules_icon_1st'></i><li> <a class='has-arrow waves-effect waves-dark' href='javascript:void(0)' aria-expanded='false'>
						                             <span class='hide-menu' style='color:blue;font-style:regular;font-weight:bold;font-size: 130%;'>$modules_title_1st</span>
						                             </a><ul aria-expanded='false' class='collapse'> ";


                while ($level_2nd = mysqli_fetch_assoc($result2)) {
                    $menu_id_2nd       = $level_2nd['modules_id'];
                    $modules_title_2nd = $level_2nd["modules_title"];
                    $modules_link_2nd  = $level_2nd["modules_link"];

                    $sql_3rd = "SELECT DISTINCT * FROM
                                (
                                    SELECT modules_id,modules_title,modules_link FROM kalmut_modules
                                    INNER JOIN kalmut_user_group_access ON modules_id = access_module_id
                                    WHERE modules_parent='$menu_id_2nd' AND modules_is_display = 1 AND modules_record_status = 'A'
                                    ORDER BY modules_id ASC
                                ) o";

                    //lalu tampilkan child
            	    $result3 = mysqli_query($conn,$sql_3rd);
                    if (mysqli_num_rows($result3) > 0) {

                          $menu .= "
				                                      <i class='$modules_icon_2nd'></i><li> <a class='has-arrow' href='javascript:void(0)' aria-expanded='false'>
				                                      <span style='color:blue;font-style:regular;font-weight:bold;font-size: 130%;'>$modules_title_2nd</span></a>
				                                      <ul aria-expanded='false' class='collapse'>";

                        while ($level_3rd = mysqli_fetch_assoc($result3)) {
                            $menu_id_3rd       = $level_3rd['modules_id'];
                            $modules_title_3rd = $level_3rd["modules_title"];
                            $modules_link_3rd  = $level_3rd["modules_link"];

                            $menu .= "<i class='$modules_icon_3rd'></i><li><a href='$modules_link_3rd'>
												                            <span style=' float : right ; display:inline-blockwhite-space: nowrap;overflow-x: hidden;
												                            color:blue;font-style:regular;font-weight:bold;font-size: 130%;'>$modules_title_3rd</span>
												                            </a></li>";


                        }
                         $menu .= "
                                        </ul>
                                    </li>

                        ";
                    }
                    else
                    {
                       //child display
                      $menu .= "<i class='$modules_icon_2nd'></i><li><a href='$modules_link_2nd'>
									                        <div style='padding-top: 25px;padding: 5px;padding-right: 10px; float : right; white-space: nowrap; overflow-x: hidden; height:350%; width: 170%; background:#4BF9FF ;display:inline-block;position: relative;box-shadow: 0px 8px 16px 0px rgba(0,0,0,5);'>
									                        <span style='margin-left: 5px; float : right;display:inline-block;white-space:
									                        nowrap;overflow-x: hidden;color:blue;font-style:regular;font-weight:bold;font-size: 130%;'>" .  ' ' . $modules_title_2nd . '  ' . "</span>
									                        </div>
									                        </a></li>";
                    }
                }

                $menu .= " </ul>
                          </li>";
            }
            else
            {
              if ($modules_parent_1st == 0 )
               {
               //parent display
            	$menu .= "<li style='color:blue;font-style:regular;font-weight:bold;font-size: 130%;'>
                <a href='$modules_link_1st' aria-expanded='false'>
                <i class='$modules_icon_1st' style='font-style:regular;font-weight:bold;font-size: 130%;'></i>
                <span class='hide-menu' style='color:blue;font-style:regular;font-weight:bold;font-size: 130%;'>$modules_title_1st </span></a></li>";
               }
            }
        }
    }
    return $menu;
}

function send_mail_reminder_reaccreditation($subject, $body, $to, $user_fullname)
{
	$mail             = new PHPMailer();

	$mail->IsSMTP(); // telling the class to use SMTP
	$mail->SMTPDebug  = 1;                     // enables SMTP debug information (for testing)
											   // 1 = errors and messages
											   // 2 = messages only
	$mail->SMTPAuth   = true;                  // enable SMTP authentication
	$mail->SMTPSecure = "ssl";                 // sets the prefix to the servier
	$mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
	$mail->Port       = 465;                   // set the SMTP port for the GMAIL server

    /**
	Akun GMail terdaftar: komisi.akreditasi@gmail.com, noreply.fktp@gmail.com, accreditation.fktp@gmail.com, info.fktp@gmail.com
	**/

    $mail->Username   = "survei@kars.or.id";
    $mail->Password   = "Symphonyk4r5";

	$mail->SetFrom("survei@kars.or.id","No Reply");
	$mail->AddReplyTo("survei@kars.or.id","Sekretariat - KARS");

	$mail->Subject    = $subject;
	$mail->MsgHTML($body);
	$mail->AddAddress($to, $user_fullname);
	$mail->Send();
}

function send_mail_reminder_verification($subject, $body, $to, $user_fullname)
{
	$mail             = new PHPMailer();

	$mail->IsSMTP(); // telling the class to use SMTP
	$mail->SMTPDebug  = 1;                     // enables SMTP debug information (for testing)
											   // 1 = errors and messages
											   // 2 = messages only
	$mail->SMTPAuth   = true;                  // enable SMTP authentication
	$mail->SMTPSecure = "ssl";                 // sets the prefix to the servier
	$mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
	$mail->Port       = 465;                   // set the SMTP port for the GMAIL server

    /**
	Akun GMail terdaftar: komisi.akreditasi@gmail.com, noreply.fktp@gmail.com, accreditation.fktp@gmail.com, info.fktp@gmail.com
	**/

    $mail->Username   = "verifikasi@kars.or.id";
    $mail->Password   = "verifikasikars";

	$mail->SetFrom("verifikasi@kars.or.id","No Reply");
	$mail->AddReplyTo("verifikasi@kars.or.id","Sekretariat - KARS");

	$mail->Subject    = $subject;
	$mail->MsgHTML($body);
	$mail->AddAddress($to, $user_fullname);
	$mail->Send();
}

function send_mail($subject, $body, $to, $user_fullname)
{
	$mail             = new PHPMailer();

	$mail->IsSMTP(); // telling the class to use SMTP
	$mail->SMTPDebug  = 1;                     // enables SMTP debug information (for testing)
											   // 1 = errors and messages
											   // 2 = messages only
	$mail->SMTPAuth   = true;                  // enable SMTP authentication
	$mail->SMTPSecure = "ssl";                 // sets the prefix to the servier
	$mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
	$mail->Port       = 465;                   // set the SMTP port for the GMAIL server

    /**
	Akun GMail terdaftar: komisi.akreditasi@gmail.com, noreply.fktp@gmail.com, accreditation.fktp@gmail.com, info.fktp@gmail.com
	**/

    $mail->Username   = "notify@kars.or.id";
    $mail->Password   = "notifikasikars!@#";

    /**
    $date = date('h');
    if($date % 2 == 1)
    {
        $mail->Username   = "info@fktp.or.id";
        $mail->Password   = "infofktp!@#";
    }elseif($date % 2 == 0)
    {
        $mail->Username   = "survei@fktp.or.id";
        $mail->Password   = "surveifktp!@#";
    }
	**/

	$mail->SetFrom("noreply@kars.or.id","No Reply");
	$mail->AddReplyTo("noreply@kars.or.id","Sekretariat - KARS");

	$mail->Subject    = $subject;

	//$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test

	$mail->MsgHTML($body);
	//Set CC address
    //$mail->addCC("sutoto@kars.or.id", "DR. Dr. Sutoto, M.Kes");
    //Set BCC address
    //$mail->addBCC("sochi.sohahaw@gmail.com", "Diyurman Gea");
	$mail->AddAddress($to, $user_fullname);

	//$mail->AddAttachment("images/phpmailer.gif");      // attachment
	//$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment

	/**
	if(!$mail->Send()) {
	  echo "Mailer Error: " . $mail->ErrorInfo;
	} else {
	  echo "Message sent!";
	}  **/

	$mail->Send();
}

function institution_event_info($event_id,$event_name)
{
    global $conn;
    $sql = "SELECT event_name, COALESCE(DATE_FORMAT(event_start_date,'%d-%m-%Y'), DATE_FORMAT(event_start_date_plan,'%d-%m-%Y')) event_start_date, COALESCE(DATE_FORMAT(event_end_date,'%d-%m-%Y'), DATE_FORMAT(event_end_date_plan,'%d-%m-%Y')) event_end_date
            FROM accreditation_event
            WHERE event_id = '$event_id' AND event_record_status = 'A'";

    $result = mysqli_query($conn,$sql);
    if (mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)){
            $info = "<div class=\"row\">

                                <div class=\"col-md-8 col-sm-8\">
                                    <h3 class=\"box-title m-b-0\">$event_name {$row['event_name']}</h3>
                                    <p>Survei tanggal {$row['event_start_date']} s/d {$row['event_end_date']} </p>
                                </div>
                            </div>";
        }
    }
    return $info;
}

function surveyor_info($event_id,$event_name, $participant_role)
{
    global $conn;
    $sql = "SELECT DISTINCT event_surveyor_amount, profile_id, profile_fullname, profile_specialize_id, specialize_name, participant_start_date, CONCAT(profile_address,'<br />', city_name) profile_address, profile_handphone1 profile_handphone, group_name, participant_is_leader, COALESCE(recap_value,'0') recap_value
            FROM accreditation_event_participant
            LEFT JOIN user_profile ON profile_id = participant_profile_id
            LEFT JOIN user_specialize ON specialize_id = profile_specialize_id
            LEFT JOIN location_city ON city_code = profile_city_code
            LEFT JOIN accreditation_event ON event_id = participant_event_id
            LEFT JOIN user_group ON group_id = participant_role
            LEFT JOIN accreditation_transaction_recap ON participant_profile_id = recap_profile_id AND recap_attribute = 'survey_reguler' AND recap_period = '".date('Y')."'
            WHERE participant_event_id = '$event_id' AND participant_role IN ($participant_role) AND participant_record_status = 'A'";

    $result = mysqli_query($conn,$sql);
    if (mysqli_num_rows($result) > 0) {

        $mod = 3;
        $i = 0;
        while($row = mysqli_fetch_assoc($result)){

            if(CHECK_JOB_LASTWEEK) { //cek apakah ada penugasan pada minggu sebelumnya
                $lastweek_job_status = has_job_lastweek($row['profile_id'],$row['participant_start_date']);
                if($lastweek_job_status > 0) $lastweek_job_message = ", dan sudah mendapat penugasan seminggu yang lalu";
                else $lastweek_job_message = "";
            }

            if($i % $mod == 0)
            {
                $info .= "<div class='row'>";
            }

            $i++;

            $info .= "<div class='col-md-4 col-sm-4'>
                        <div class='card'>
                            <div class='card-body'>
                                <div class='row'>
                                    <div class='col-md-4 col-sm-4 text-center'>
                                        <a href='contact-detail.html'><img src='../../document/profile/nopicture.jpg' alt='user' class='img-circle img-responsive' height='100px' width='100px'></a>
                                    </div>
                                    <div class='col-md-8 col-sm-8'>
                                        <h5 class='card-title m-b-0'>{$row['profile_fullname']}</h5> <small>{$row['group_name']} {$row['specialize_name']} <br />Frekwensi penugasan {$row['recap_value']} kali dalam tahun ini $lastweek_job_message</small>
                                        <p>
                                            <address>
                                                {$row['profile_address']}
                                                <br/>
                                                <br/>
                                                <abbr title='Phone'>P:</abbr> {$row['profile_handphone']}
                                            </address>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    ";

            if($i % $mod == 0)
            {
                $info .= "</div>";
            }

        }
    }
    return $info;
}

function transfer_data_result($event_id)
{
	global $conn;
    $today = date('Y-m-d H:i:s');
    //phpgrid_error("Masuk");
    mysqli_autocommit($conn,FALSE);
    $sql1 = "UPDATE accreditation_transaction SET transaction_record_status='D'
                  WHERE transaction_event_id = $event_id";
    //echo "Hapus data lama - ".$sql_delete;
    $result1 = mysqli_query($conn,$sql1);

    $sql_period = "SELECT accreditation_period FROM accreditation WHERE accreditation_event_id = '$event_id'";
    $period = get_data($sql_period,"accreditation_period");

	$sql2 = "INSERT INTO `accreditation_transaction` (`transaction_accreditation_id`, `transaction_event_id`, `transaction_instrument_id`, `transaction_chapter_id`, `transaction_class_id`, `transaction_standard_id`, `transaction_institution_code`, `transaction_institution_score`, `transaction_institution_update_date`, `transaction_institution_update_by`, `transaction_institution_is_approved`, `transaction_institution_approve_date`, `transaction_surveyor_update_by`, `transaction_surveyor_score`, `transaction_surveyor_fact_and_analysis`, `transaction_surveyor_recommendation`, `transaction_surveyor_need_to_discussed`, `transaction_surveyor_update_date`, `transaction_counselor_update_by`, `transaction_counselor_score`, `transaction_counselor_fact_and_analysis`, `transaction_counselor_note`, `transaction_counselor_message`, `transaction_evaluation_x1`, `transaction_evaluation_x2`, `transaction_evaluation_x3`, `transaction_counselor_update_date`, `transaction_counselor_is_approved`, `transaction_counselor_approved_date`, `transaction_remedial_surveyor_update_by`, `transaction_remedial_surveyor_score`, `transaction_remedial_surveyor_fact_and_analysis`, `transaction_remedial_surveyor_recommendation`, `transaction_remedial_surveyor_update_date`, `transaction_final_score`, `transaction_is_evaluated`, `transaction_is_discussed`, `transaction_verification_1`, `transaction_verification_1_recommendation`, `transaction_verification_1_insert_by`, `transaction_verification_1_insert_date`, `transaction_verification_2`, `transaction_verification_2_recommendation`, `transaction_verification_2_insert_by`, `transaction_verification_2_insert_date`, `transaction_record_status`, `transaction_period`)

                SELECT `transaction_accreditation_id`, `transaction_event_id`, `transaction_instrument_id`, `transaction_chapter_id`, `transaction_class_id`, `transaction_standard_id`, `transaction_institution_code`, `transaction_institution_score`, `transaction_institution_update_date`, `transaction_institution_update_by`, `transaction_institution_is_approved`, `transaction_institution_approve_date`, `transaction_surveyor_update_by`, `transaction_surveyor_score`, `transaction_surveyor_fact_and_analysis`, `transaction_surveyor_recommendation`, `transaction_surveyor_need_to_discussed`, `transaction_surveyor_update_date`, `transaction_counselor_update_by`, `transaction_counselor_score`, `transaction_counselor_fact_and_analysis`, `transaction_counselor_note`, `transaction_counselor_message`, `transaction_evaluation_x1`, `transaction_evaluation_x2`, `transaction_evaluation_x3`, `transaction_counselor_update_date`, `transaction_counselor_is_approved`, `transaction_counselor_approved_date`, `transaction_remedial_surveyor_update_by`, `transaction_remedial_surveyor_score`, `transaction_remedial_surveyor_fact_and_analysis`, `transaction_remedial_surveyor_recommendation`, `transaction_remedial_surveyor_update_date`, `transaction_final_score`, `transaction_is_evaluated`, `transaction_is_discussed`, `transaction_verification_1`, `transaction_verification_1_recommendation`, `transaction_verification_1_insert_by`, `transaction_verification_1_insert_date`, `transaction_verification_2`, `transaction_verification_2_recommendation`, `transaction_verification_2_insert_by`, `transaction_verification_2_insert_date`, `transaction_record_status`, `transaction_period`
                FROM `accreditation_transaction_".$period."`
                WHERE transaction_event_id = '$event_id' AND transaction_record_status='A'";

    $result2 = mysqli_query($conn,$sql2);


    /** Update status transfer data di accreditation_event **/
    $sql3 = "UPDATE accreditation SET accreditation_record_transfer_status='1'
                    WHERE accreditation_event_id = '$event_id'";
    //echo "Delete tabel Ongoing - ".$sql_delete;
    $result3 = mysqli_query($conn,$sql3);

    if($result1 && $result2 && $result3)
    {
        //*** Commit Transaction ***//
       mysqli_commit($conn);
       $status = true;

    }
    else
    {
        //*** RollBack Transaction ***//
       mysqli_rollback($conn);
       phpgrid_msg("Gagal melakukan transfer data, silakan cek data atau koneksi ..!");
       $status = false;
    }
    mysqli_close($conn);

    return $status;
}

/** --------------------------- Evaluasi Penilaian Kesalahan EP --------------------------------- **/
function evaluation_surveyor($event_id, $chapter_id, $period)
{
    global $conn;

    $sql1 = "
            SELECT DISTINCT `transaction_evaluation_x1` x, transaction_chapter_id, standard_code
            FROM `accreditation_transaction_$period`
            LEFT JOIN accreditation_instrument_standard ON standard_id = `transaction_standard_id`
            WHERE `transaction_event_id`='$event_id' AND `transaction_evaluation_x1` != '' AND transaction_chapter_id IN ($chapter_id)

            UNION

            SELECT DISTINCT `transaction_evaluation_x2` x, transaction_chapter_id, standard_code
            FROM `accreditation_transaction_$period`
            LEFT JOIN accreditation_instrument_standard ON standard_id = `transaction_standard_id`
            WHERE `transaction_event_id`='$event_id' AND `transaction_evaluation_x2` != '' AND transaction_chapter_id IN ($chapter_id)

            UNION

            SELECT DISTINCT `transaction_evaluation_x3` x, transaction_chapter_id, standard_code
            FROM `accreditation_transaction_$period`
            LEFT JOIN accreditation_instrument_standard ON standard_id = `transaction_standard_id`
            WHERE `transaction_event_id`='$event_id' AND `transaction_evaluation_x3` != '' AND transaction_chapter_id IN ($chapter_id)
            ";

    $result1 = mysqli_query($conn,$sql1);
    $counter = 0;
    $counter_approve = 0;

    if (mysqli_num_rows($result1) > 0)
    {
        $standard_code = "";
        $counter = 0;
        while ($row = mysqli_fetch_assoc($result1))
        {
            $counter++;
            if($row['x'] == 'A'){
                $standard_code_A[] = $row['standard_code'];
            }elseif($row['x'] == 'B'){
                $standard_code_B[] = $row['standard_code'];
            }elseif($row['x'] == 'C'){
                $standard_code_C[] = $row['standard_code'];
            }elseif($row['x'] == 'D'){
                $standard_code_D[] = $row['standard_code'];
            }elseif($row['x'] == 'E'){
                $standard_code_E[] = $row['standard_code'];
            }elseif($row['x'] == 'F'){
                $standard_code_F[] = $row['standard_code'];
            }elseif($row['x'] == 'G'){
                $standard_code_G[] = $row['standard_code'];
            }elseif($row['x'] == 'H'){
                $standard_code_H[] = $row['standard_code'];
            }elseif($row['x'] == 'J'){
                $standard_code_J[] = $row['standard_code'];
            }elseif($row['x'] == 'K'){
                $standard_code_K[] = $row['standard_code'];
            }
        }
    }
    $standard_codes_A = implode(", ",$standard_code_A);
    $standard_codes_B = implode(", ",$standard_code_B);
    $standard_codes_C = implode(", ",$standard_code_C);
    $standard_codes_D = implode(", ",$standard_code_D);
    $standard_codes_E = implode(", ",$standard_code_E);
    $standard_codes_F = implode(", ",$standard_code_F);
    $standard_codes_G = implode(", ",$standard_code_G);
    $standard_codes_H = implode(", ",$standard_code_H);
    $standard_codes_J = implode(", ",$standard_code_J);
    $standard_codes_K = implode(", ",$standard_code_K);

    if(!empty($standard_codes_A))
    {
        $counter_A = sizeof(explode(",",$standard_codes_A));
        $code = 'A';
        $check_status = get_data("SELECT COUNT(*) counter FROM accreditation_surveyor_evaluation WHERE evaluation_event_id = '$event_id' AND evaluation_instrument_code = '$code' AND evaluation_chapter_id IN ($chapter_id)","counter");
        if($check_status==0)
        {
            evaluation_insert($event_id, $code, $chapter_id, $counter_A,$standard_codes_A);
        }
        else{
            evaluation_update($event_id, $code, $chapter_id, $counter_A,$standard_codes_A);
        }
    }
    if(!empty($standard_codes_B))
    {
        $counter_B = sizeof(explode(",",$standard_codes_B));
        $code = 'B';
        $check_status = get_data("SELECT COUNT(*) counter FROM accreditation_surveyor_evaluation WHERE evaluation_event_id = '$event_id' AND evaluation_instrument_code = '$code' AND evaluation_chapter_id IN ($chapter_id)","counter");

        if($check_status==0)
        {
            evaluation_insert($event_id, $code, $chapter_id, $counter_B,$standard_codes_B);
        }
        else{
            evaluation_update($event_id, $code, $chapter_id, $counter_B,$standard_codes_B);
        }

    }
    if(!empty($standard_codes_C))
    {
        $counter_C = sizeof(explode(",",$standard_codes_C));
        $code = 'C';
        $check_status = get_data("SELECT COUNT(*) counter FROM accreditation_surveyor_evaluation WHERE evaluation_event_id = '$event_id' AND evaluation_instrument_code = '$code' AND evaluation_chapter_id IN ($chapter_id)","counter");

        if($check_status==0)
        {
            evaluation_insert($event_id, $code, $chapter_id, $counter_C,$standard_codes_C);
        }
        else{
            evaluation_update($event_id, $code, $chapter_id, $counter_C,$standard_codes_C);
        }

    }
    if(!empty($standard_codes_D))
    {
        $counter_D = sizeof(explode(",",$standard_codes_D));
        $code = 'D';
        $check_status = get_data("SELECT COUNT(*) counter FROM accreditation_surveyor_evaluation WHERE evaluation_event_id = '$event_id' AND evaluation_instrument_code = '$code' AND evaluation_chapter_id IN ($chapter_id)","counter");

        if($check_status==0)
        {
            evaluation_insert($event_id, $code, $chapter_id, $counter_D,$standard_codes_D);
        }
        else{
            evaluation_update($event_id, $code, $chapter_id, $counter_D,$standard_codes_D);
        }

    }
    if(!empty($standard_codes_E))
    {
        $counter_E = sizeof(explode(",",$standard_codes_E));
        $code = 'E';
        $check_status = get_data("SELECT COUNT(*) counter FROM accreditation_surveyor_evaluation WHERE evaluation_event_id = '$event_id' AND evaluation_instrument_code = '$code' AND evaluation_chapter_id IN ($chapter_id)","counter");

        if($check_status==0)
        {
            evaluation_insert($event_id, $code, $chapter_id, $counter_E,$standard_codes_E);
        }
        else{
            evaluation_update($event_id, $code, $chapter_id, $counter_E,$standard_codes_E);
        }

    }
    if(!empty($standard_codes_F))
    {
        $counter_F = sizeof(explode(",",$standard_codes_F));
        $code = 'F';
        $check_status = get_data("SELECT COUNT(*) counter FROM accreditation_surveyor_evaluation WHERE evaluation_event_id = '$event_id' AND evaluation_instrument_code = '$code' AND evaluation_chapter_id IN ($chapter_id)","counter");

        if($check_status==0)
        {
            evaluation_insert($event_id, $code, $chapter_id, $counter_F,$standard_codes_F);
        }
        else{
            evaluation_update($event_id, $code, $chapter_id, $counter_F,$standard_codes_F);
        }

    }
    if(!empty($standard_codes_G))
    {
        $counter_G = sizeof(explode(",",$standard_codes_G));
        $code = 'G';
        $check_status = get_data("SELECT COUNT(*) counter FROM accreditation_surveyor_evaluation WHERE evaluation_event_id = '$event_id' AND evaluation_instrument_code = '$code' AND evaluation_chapter_id IN ($chapter_id)","counter");

        if($check_status==0)
        {
            evaluation_insert($event_id, $code, $chapter_id, $counter_G,$standard_codes_G);
        }
        else{
            evaluation_update($event_id, $code, $chapter_id, $counter_G,$standard_codes_G);
        }
    }
    if(!empty($standard_codes_H))
    {
        $counter_H = sizeof(explode(",",$standard_codes_H));
        $code = 'H';
        $check_status = get_data("SELECT COUNT(*) counter FROM accreditation_surveyor_evaluation WHERE evaluation_event_id = '$event_id' AND evaluation_instrument_code = '$code' AND evaluation_chapter_id IN ($chapter_id)","counter");

        if($check_status==0)
        {
            evaluation_insert($event_id, $code, $chapter_id, $counter_H,$standard_codes_H);
        }
        else{
            evaluation_update($event_id, $code, $chapter_id, $counter_H,$standard_codes_H);
        }
    }
    if(!empty($standard_codes_J))
    {
        $counter_J = sizeof(explode(",",$standard_codes_J));
        $code = 'J';
        $check_status = get_data("SELECT COUNT(*) counter FROM accreditation_surveyor_evaluation WHERE evaluation_event_id = '$event_id' AND evaluation_instrument_code = '$code' AND evaluation_chapter_id IN ($chapter_id)","counter");

        if($check_status==0)
        {
            evaluation_insert($event_id, $code, $chapter_id, $counter_J,$standard_codes_J);
        }
        else{
            evaluation_update($event_id, $code, $chapter_id, $counter_J,$standard_codes_J);
        }
    }
    if(!empty($standard_codes_K))
    {
        $counter_K = sizeof(explode(",",$standard_codes_K));
        $code = 'K';
        $check_status = get_data("SELECT COUNT(*) counter FROM accreditation_surveyor_evaluation WHERE evaluation_event_id = '$event_id' AND evaluation_instrument_code = '$code' AND evaluation_chapter_id IN ($chapter_id)","counter");

        if($check_status==0)
        {
            evaluation_insert($event_id, $code, $chapter_id, $counter_K,$standard_codes_K);
        }
        else{
            evaluation_update($event_id, $code, $chapter_id, $counter_K,$standard_codes_K);
        }
    }
    //echo $sql1;
}

function evaluation_insert($event_id, $code, $chapter_id, $counter,$standard_codes)
{
    global $conn;
    $sql = "INSERT INTO `accreditation_surveyor_evaluation`(`evaluation_event_id`, `evaluation_instrument_code`, evaluation_chapter_id, `evaluation_finding_number`, `evaluation_finding_description`, `evaluation_insert_by`, `evaluation_insert_date`) VALUES
            ('$event_id', '$code','$chapter_id','$counter','$standard_codes','{$_SESSION['user_profile_id']}','".date('Y-m-d H:i:s')."')";
    $result = mysqli_query($conn,$sql);
}

function evaluation_update($event_id, $code, $chapter_id, $counter,$standard_codes)
{
    global $conn;
    $sql = "UPDATE accreditation_surveyor_evaluation SET
            `evaluation_finding_number` = '$counter',
            `evaluation_finding_description` = '$standard_codes',
            `evaluation_update_by` = '{$_SESSION['user_profile_id']}',
            `evaluation_update_date` = '".date('Y-m-d H:i:s')."'
            WHERE evaluation_event_id = '$event_id' AND evaluation_instrument_code = '$code' AND evaluation_chapter_id IN ($chapter_id)";
    $result = mysqli_query($conn,$sql);
}

/** --------------------------- Jadwal Kesedian Surveior --------------------------------- **/
function surveyor_available_schedule_list()
{
    global $conn;
    $sql = "SELECT DATE_FORMAT(available_start_date,'%d-%m-%Y') available_start_date, DATE_FORMAT(available_end_date,'%d-%m-%Y') available_end_date FROM `user_available` WHERE `available_profile_id` = {$_SESSION['user_profile_id']} AND YEAR(available_start_date) = '".date('Y')."' ORDER BY DATE_FORMAT(available_start_date,'%Y%m%d') DESC";

    $result = mysqli_query($conn,$sql);
    if (mysqli_num_rows($result) > 0) {
        $info = "<ol>";
        while($row = mysqli_fetch_assoc($result)){
            $info .= "<li>{$row['available_start_date']} s/d {$row['available_end_date']}</li>";
        }
        $info .= "</ol>";
    }
    else $info = "Belum melakukan pengisian jadwal";
    return $info;
}

//Fungsi untuk meng-generate regional atau wilayah yang dicari
function regional($regional_code)
{
    /** Mencari wilayah regional **/
    $tags = explode(',' , $regional_code);
    $num_tags = count($tags);

    for($i = 0; $i<$num_tags; $i++ ) {
        $regional[] = "FIND_IN_SET($tags[$i], available_province_regional) > 0";
    }

    $regional = " AND (".implode(" OR ",$regional).") ";
    /** end **/
    return $regional;
}

//Function untuk mengetahui ketersediaan sisa quota
function event_quota_status($date,$event_category_id,$format){
    global $conn;
    $week   = date("W", strtotime("+1 day",strtotime($date)));$weeks = $week - 1;
    $month   = date("m", strtotime($date));
    $year   = date("Y", strtotime($date));
    if($format == "1")
    {
        $where_format = "AND `quota_weekly` = '$weeks' AND `quota_yearly` = '$year' AND quota_category_id = '$event_category_id'";
    }
    elseif($format == "2")
    {
        $where_format = "AND `quota_monthly` = '$month' AND `quota_yearly` = '$year' AND quota_category_id = '$event_category_id'";
    }
    $sql1   = "SELECT COALESCE(quota_plan,0) quota_plan, COALESCE(quota_realization,0)quota_realization
               FROM `accreditation_event_quota`
               WHERE `quota_record_status` = 'A' $where_format";
    $result1 = mysqli_query($conn, $sql1);
    //phpgrid_error($sql1);
    $status = 0;
    if (mysqli_num_rows($result1) >= 0)
    {
        while ($row = mysqli_fetch_assoc($result1))
        {
            $plan           = $row['quota_plan'];
            $realization    = $row['quota_realization'];
        }
        if($plan <= $realization) $status = $realization;

    }
    return $status;
}

//Function untuk mengupdate ketersediaan sisa quota
function event_quota_update($date,$event_category_id,$format){
    global $conn;
    $week   = date("W", strtotime("+1 day",strtotime($date)));$weeks = $week - 1;
    $month   = date("m", strtotime($date));
    $year   = date("Y", strtotime($date));

    if($format == "1") //weekly
    {
        $where_format = "AND DATE_FORMAT(COALESCE(event_start_date,event_start_date_plan),'%Y%U')='$year$weeks' AND event_category_id IN ('$event_category_id')";
        $where_format_quota = " AND quota_category_id IN('$event_category_id') AND `quota_weekly` = '$weeks' AND `quota_yearly` = '$year'";
    }
    elseif($format == "2") //monthly
    {
        $where_format = "AND DATE_FORMAT(COALESCE(event_start_date,event_start_date_plan),'%Y%m')='$year$month' AND event_category_id IN ('$event_category_id')";
        $where_format_quota = " AND quota_category_id IN('$event_category_id') AND `quota_monthly` = '$month' AND `quota_yearly` = '$year'";
    }

    $sql1   = "
                SELECT COUNT(*) counter
                FROM accreditation_event
                WHERE event_record_status='A' $where_format";
    $total_row = get_data($sql1,"counter"); $total_row = $total_row+1 ;
    //phpgrid_error($sql1);
    $sql3 = "SELECT COUNT(*) counter FROM accreditation_event_quota WHERE `quota_record_status` = 'A' $where_format_quota";
    $row_is_exsist = get_data($sql3,"counter");
    if($row_is_exsist > 0)
    {
        $sql2   = "UPDATE accreditation_event_quota
                    SET `quota_realization` = '$total_row'
                    WHERE `quota_record_status` = 'A' $where_format_quota";
    }else
    {
        $sql2   = "INSERT INTO `accreditation_event_quota`(`quota_weekly`, `quota_monthly`,`quota_yearly`, `quota_category_id`, `quota_type`, `quota_plan`, `quota_realization`, `quota_record_status`) VALUES
                    ('$weeks','$month','$year','$event_category_id','$format','750','$total_row','A')";
    }
    //phpgrid_error($sql3);
    $result2 = mysqli_query($conn, $sql2);
    return true;
}

/** Function untuk Penilaian Surveior **/
function evaluation_question($profile_id,$group_id){
    global $conn;
    $sql1 = "SELECT `questions_id`, `questions_group_id`, `questions_type_id`, `questions_name`, `questions_score`, `questions_order`
            FROM `accreditation_surveyor_evaluation_questions`
            WHERE questions_record_status = 'A' AND questions_group_id = '$group_id' AND FIND_IN_SET(1, questions_type_id) > 0
               ";
    $result1 = mysqli_query($conn,$sql1);
    if (mysqli_num_rows($result1) > 0)
    {
        while ($row = mysqli_fetch_assoc($result1))
        {
            $questions_id           = $row['questions_id'];
            $questions_group_id     = $row['questions_group_id'];
            $questions_type_id      = $row['questions_type_id'];
            $questions_name         = $row['questions_name'];
            $questions_score        = $row['questions_score'];
            $questions_order        = $row['questions_order'];

            $html   .= "<tr>
                        <td class='title'>$questions_name</td>
                        <td><div class='custom-control custom-radio'>
                                <input type='radio' id='option1-{$profile_id}[$questions_id]' name='{$profile_id}[$questions_id]' class='custom-control-input' value='0' required>
                                <label class='custom-control-label' for='option1-{$profile_id}[$questions_id]'>0</label>
                            </div></td>
                        <td><div class='custom-control custom-radio'>
                                <input type='radio' id='option2-{$profile_id}[$questions_id]' name='{$profile_id}[$questions_id]' class='custom-control-input' value='1' required>
                                <label class='custom-control-label' for='option2-{$profile_id}[$questions_id]'>1</label>
                            </div></td>
                        <td><div class='custom-control custom-radio'>
                                <input type='radio' id='option3-{$profile_id}[$questions_id]' name='{$profile_id}[$questions_id]' class='custom-control-input' value='2' required>
                                <label class='custom-control-label' for='option3-{$profile_id}[$questions_id]'>2</label>
                            </div></td>
                        <td><div class='custom-control custom-radio'>
                                <input type='radio' id='option4-{$profile_id}[$questions_id]' name='{$profile_id}[$questions_id]' class='custom-control-input' value='3' required>
                                <label class='custom-control-label' for='option4-{$profile_id}[$questions_id]'>3</label>
                            </div></td>
                    </tr>";
        }
    }
    return $html;
}
function evaluation_question_box($profile_id, $group_id,$group_name)
{
    $out =
        "
            <div class='col-lg-6'>
                <div class='card'>
                    <div class='card-body'>
                        <table class='table color-table success-table' >
                            <thead>
                                <tr>
                                    <th scope='col' >$group_name</th>
                                    <th scope='col' ><i class='fa fa-thumbs-o-down'></i></th>
                                    <th scope='col' ><i class='ti-face-sad'></i></th>
                                    <th scope='col' ><i class='ti-face-smile'></i></th>
                                    <th scope='col' ><i class='fa fa-thumbs-o-up'></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                ".evaluation_question($profile_id,$group_id)."
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        ";
    return $out;
}


/**
 * Funtion untuk Finance dan Workshop
 * ****/

function tgl_indo($tgl){
            $tanggal = substr($tgl,8,2);
            $bulan = getBulan(substr($tgl,5,2));
            $tahun = substr($tgl,0,4);
            return $tanggal.' '.$bulan.' '.$tahun;
}

function tgl_grafik($tgl){
    $tanggal = substr($tgl,8,2);
    $bulan = getBulan(substr($tgl,5,2));
    $tahun = substr($tgl,0,4);
    return $tanggal.'_'.$bulan;
}

function jin_date_sql($date){
        $exp = explode('/',$date);
        if(count($exp) == 3) {
            $date = $exp[2].'-'.$exp[1].'-'.$exp[0];
        }
        return $date;
}

function jin_date_str($date){
    $exp = explode('-',$date);
    if(count($exp) == 3) {
        $date = $exp[2].'/'.$exp[1].'/'.$exp[0];
    }
    return $date;
}

function date_str($date){
    $exp = explode('-',$date);
    if(count($exp) == 3) {
        $date = $exp[2].'-'.$exp[1].'-'.$exp[0];
    }
    return $date;
}

function getBulan($bln){
            switch ($bln){
                case 1:
                    return "Jan";
                    break;
                case 2:
                    return "Feb";
                    break;
                case 3:
                    return "Mar";
                    break;
                case 4:
                    return "Apr";
                    break;
                case 5:
                    return "Mei";
                    break;
                case 6:
                    return "Jun";
                    break;
                case 7:
                    return "Jul";
                    break;
                case 8:
                    return "Agu";
                    break;
                case 9:
                    return "Sep";
                    break;
                case 10:
                    return "Okt";
                    break;
                case 11:
                    return "Nov";
                    break;
                case 12:
                    return "Des";
                    break;
            }
        }

function hari($xtgl){
    $a=strtotime($xtgl);
    $xhari = date("l",$a);
    if($xhari=='Sunday'){
        $nmhari =   "Minggu";
    }elseif($xhari=='Monday'){
        $nmhari =   "Senin";
    }elseif($xhari=='Tuesday'){
        $nmhari =   "Selasa";
    }elseif($xhari=='Wednesday'){
        $nmhari =   "Rabu";
    }elseif($xhari=='Thursday'){
        $nmhari =   "Kamis";
    }elseif($xhari=='Friday'){
        $nmhari =   "Jumat";
    }elseif($xhari=='Saturday'){
        $nmhari =   "Sabtu";
    }else{
        $nmhari = "Belum ada hari";
    }
        return $nmhari;
}
/** END **/

/** START - BNI Encryption **/
class BniEnc
{
	const TIME_DIFF_LIMIT = 480;

	public static function encrypt(array $json_data, $cid, $secret) {
		return self::doubleEncrypt(strrev(time()) . '.' . json_encode($json_data), $cid, $secret);
	}

	public static function decrypt($hased_string, $cid, $secret) {
		$parsed_string = self::doubleDecrypt($hased_string, $cid, $secret);
		list($timestamp, $data) = array_pad(explode('.', $parsed_string, 2), 2, null);
		if (self::tsDiff(strrev($timestamp)) === true) {
			return json_decode($data, true);
		}
		return null;
	}

	private static function tsDiff($ts) {
		return abs($ts - time()) <= self::TIME_DIFF_LIMIT;
	}

	private static function doubleEncrypt($string, $cid, $secret) {
		$result = '';
		$result = self::enc($string, $cid);
		$result = self::enc($result, $secret);
		return strtr(rtrim(base64_encode($result), '='), '+/', '-_');
	}

	private static function enc($string, $key) {
		$result = '';
		$strls = strlen($string);
		$strlk = strlen($key);
		for($i = 0; $i < $strls; $i++) {
			$char = substr($string, $i, 1);
			$keychar = substr($key, ($i % $strlk) - 1, 1);
			$char = chr((ord($char) + ord($keychar)) % 128);
			$result .= $char;
		}
		return $result;
	}

	private static function doubleDecrypt($string, $cid, $secret) {
		$result = base64_decode(strtr(str_pad($string, ceil(strlen($string) / 4) * 4, '=', STR_PAD_RIGHT), '-_', '+/'));
		$result = self::dec($result, $cid);
		$result = self::dec($result, $secret);
		return $result;
	}

	private static function dec($string, $key) {
		$result = '';
		$strls = strlen($string);
		$strlk = strlen($key);
		for($i = 0; $i < $strls; $i++) {
			$char = substr($string, $i, 1);
			$keychar = substr($key, ($i % $strlk) - 1, 1);
			$char = chr(((ord($char) - ord($keychar)) + 256) % 128);
			$result .= $char;
		}
		return $result;
	}

}

function get_content($url, $post = '') {
	$usecookie = __DIR__ . "/cookie.txt";
	$header[] = 'Content-Type: application/json';
	$header[] = "Accept-Encoding: gzip, deflate";
	$header[] = "Cache-Control: max-age=0";
	$header[] = "Connection: keep-alive";
	$header[] = "Accept-Language: en-US,en;q=0.8,id;q=0.6";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_VERBOSE, false);
	// curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_ENCODING, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.120 Safari/537.36");

	if ($post)
	{
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$rs = curl_exec($ch);

	if(empty($rs)){
		var_dump($rs, curl_error($ch));
		curl_close($ch);
		return false;
	}
	curl_close($ch);
	return $rs;
}

function create_va($client_id,$secret_key,$trx_id,$trx_amount,$datetime_expired,$virtual_account,$customer_name,$customer_email,$customer_phone,$description,$billing_type)
{
    $url = BNI_API;

    $data_asli = array(
    	'type' => 'createbilling',
    	'client_id' => $client_id,
    	'trx_id' => $trx_id, // fill with Billing ID
    	'trx_amount' => $trx_amount,
    	'billing_type' => $billing_type,
    	'datetime_expired' => $datetime_expired, // billing will be expired in 2 hours
    	'virtual_account' => $virtual_account,
    	'customer_name' => $customer_name,
    	'customer_email' => $customer_email,
    	'customer_phone' => $customer_phone,
    	'description' => $description
    );
    //echo "$client_id,$secret_key,$trx_id,$trx_amount,$datetime_expired,$virtual_account,$customer_name,$customer_email,$customer_phone,$description,$billing_type <br/>";
    $hashed_string = BniEnc::encrypt(
    	$data_asli,
    	$client_id,
    	$secret_key
    );

    $data = array(
    	'client_id' => $client_id,
    	'data' => $hashed_string,
    );

    $response = get_content($url, json_encode($data));
    $response_json = json_decode($response, true);

    if ($response_json['status'] !== '000') {
    	// handling jika gagal
    	//var_dump($response_json);
    	//echo "masuk...";
        //$transaction_id = '';
        //return $response_json['status'];
    }
    else {
    	$data_response = BniEnc::decrypt($response_json['data'], $client_id, $secret_key);
    	//$transaction_id =  $data_response['trx_id'];
        //return $transaction_id;
        // $data_response will contains something like this:
    	// array(
    	// 	'virtual_account' => 'xxxxx',
    	// 	'trx_id' => 'xxx',
    	// );
    	//var_dump($data_response);
        //return $response_json['status'];
    }
    return $response_json['status'];
}

//update va
function update_va($client_id,$secret_key,$trx_id,$trx_amount,$datetime_expired,$virtual_account,$customer_name,$customer_email,$customer_phone,$description,$billing_type)
{
    $url = BNI_API;

    $data_asli = array(
        'type' => 'updatebilling',
        'client_id' => $client_id,
        'trx_id' => $trx_id, // fill with Billing ID
        'trx_amount' => $trx_amount,
        'billing_type' => 'c',
        'datetime_expired' => $datetime_expired, // billing will be expired in 2 hours
        'virtual_account' => $virtual_account,
        'customer_name' => $customer_name,
        'customer_email' => $customer_email,
        'customer_phone' => $customer_phone,
        'description' => $description


    );
    //echo "$client_id,$secret_key,$trx_id,$trx_amount,$datetime_expired,$virtual_account,$customer_name,$customer_email,$customer_phone,$description,$billing_type <br/>";
    $hashed_string = BniEnc::encrypt(
        $data_asli,
        $client_id,
        $secret_key
    );

    $data = array(
        'client_id' => $client_id,
        'data' => $hashed_string,
    );

    $response = get_content($url, json_encode($data));
    $response_json = json_decode($response, true);

    if ($response_json['status'] !== '000') {
        // handling jika gagal
        //var_dump($response_json);
        //echo "masuk...";
        //$transaction_id = '';
        //return $response_json['status'];
    }
    else {
        $data_response = BniEnc::decrypt($response_json['data'], $client_id, $secret_key);
        //$transaction_id =  $data_response['trx_id'];
        //return $transaction_id;
        // $data_response will contains something like this:
        // array(
        //  'virtual_account' => 'xxxxx',
        //  'trx_id' => 'xxx',
        // );
        //var_dump($data_response);
        //return $response_json['status'];
    }
    return $response_json['status'];
}
/** END - BNI Encryption **/

//tax function
function tax_calculation($total_amount,$pkp) //$pkp = pendapatan kena pajak, $total_amount= Nilai dari akumulasi total pajak selama 1 tahun, $gross_income=honor yang dibayar perbulan
{
    //echo "Total = ".$total_amount. "PKP =".$pkp;
    if($total_amount + $pkp > 50000000)
    {
        //echo "Masuk 1";
        if($total_amount <= 50000000)
        {
            //$tax = ((($total_amount + $pkp) - 50000000)*0.05) + (($pkp - (($total_amount + $pkp) - 50000000)) * 0.15);
            $tax = ((50000000-$total_amount)*0.05)+((($total_amount + $pkp) - 50000000)*0.15);
        }
        else
        {
            $tax = $pkp * 0.15;
        }
    }
    elseif($total_amount + $pkp >250000000)
    {
        //echo "Masuk 2";
        if($total_amount <= 250000000)
        {
            //$tax = ((($total_amount + $pkp) - 250000000)*0.15) + (($pkp - (($total_amount + $pkp) - 250000000)) * 0.25);
            $tax = ((250000000-$total_amount)*0.15)+((($total_amount + $pkp) - 250000000)*0.25);
        }
        else
        {
            $tax = $pkp * 0.25;
        }
    }
    elseif($total_amount + $pkp > 500000000)
    {
        //echo "Masuk 3";
        if($total_amount <= 500000000)
        {
            //$tax = ((($total_amount + $pkp) - 500000000)*0.25) + (($pkp - (($total_amount + $pkp) - 500000000)) * 0.30);
            $tax = ((500000000-$total_amount)*0.25)+((($total_amount + $pkp) - 500000000)*0.30);
            //echo "(((".$total_amount. "+". $pkp.") - 50000000)*0.15) + ((((".$total_amount." +". $pkp.") - 50000000) - ".$pkp.") * 0.05);";
        }
        else
        {
            $tax = $pkp * 0.30;
        }
    }
    elseif($total_amount + $pkp <= 50000000)
    {
        $tax = $pkp * 0.05;

    }

    return $tax;
}
?>