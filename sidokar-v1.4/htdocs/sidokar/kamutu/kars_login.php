<?php

if(session_id() == '') {
    session_start();
}
include_once("./autostart.php");
if(isset($_SESSION['user_profile_id']) && session_id() != '')
{
    header("Location: ./application/dashboard/");
    die();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="../../images/<?php echo FAVICON ?>">
    <title><?php echo APPLICATION_NAME?></title>

    <!-- page css -->
    <link href="./dist/css/pages/login-register-lock.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src='https://kit.fontawesome.com/a076d05399.js'></script>
    <!-- Custom CSS -->
    <link href="./dist/css/style.css" rel="stylesheet">


    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha256-eZrrJcwDc/3uDhsdt61sL2oOBY362qM3lon1gyExkL0=" crossorigin="anonymous" />
      <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/font-awesome-line-awesome/css/all.min.css">
       <script type="text/javascript">
       function zoom() {
            document.body.style.zoom = "100%" ;
           $(window).on("resize", function () {
			    // Set .right's width to the window width minus 480 pixels
			    $(".content .right").width( $(this).width() - 480 );
			// Invoke the resize event immediately
			}).resize();
			        }
		</script>



</head>

<body class="skin-default card-no-border"
style="background-image:url(./images/background/background.jpg); background-repeat: no-repeat; height: 100%;
background-position: center;background-repeat: no-repeat;background-size: cover;" onload='zoom()'>
    <!-- ============================================================== -->
    <!-- Preloader - style you can find in spinners.css -->
    <!-- ============================================================== -->
    <div class="preloader">
        <div class="loader">
            <div class="loader__figure"></div>
            <p class="loader__label"><?php echo APPLICATION_NAME ?></p>
        </div>
    </div><br />
    <center><table cellspacing="0" style="width: 80%; border: none;">
        <tr>
            <td style="width: 10%;vertical-align: top;" ><img src="./images/logoKARS.png" style="width:100%;"></td>
            <td style="width: 80%;text-align: center;"><b style="font-size: 25px;">KOMISI AKREDITASI RUMAH SAKIT</b><br /><b style="font-size: 16px;">Gedung Epicentrum Walk Unit 716 B<br />Jl. Boulevard Epicentrum Selatan - Kawasan Rasuna Epicentrum Kuningan</b><br /><p style="font-size: 14px;">Jakarta Selatan, DKI Jakarta 12960 - Indonesia
                <br />Email: info@kars.or.id; Telepon : (021) 299 41552 / 299 41553 Fax: (021) 299 41317 <br /> Bank BNI 46 Cabang Tebet, Jakarta No. Rekening : 0011-802-402</p></td>
            <td style="width: 10%;vertical-align: top;"><img src="./images/isqua.png" style="width:300px; height: 120px;"></td>
        </tr>
    </table></center><br />


    <!-- ============================================================== -->
    <!-- Main wrapper - style you can find in pages.scss -->
    <!-- ============================================================== -->
    <section id="wrapper">
        <div class="login-register">
            <div class="login-box card">
                <div class="card-body">
                    <form class="form-horizontal form-material" id="loginform" method="post">
                        <div class="form-group">
                            <div class="col-xs-12 text-center">
                                <div class="user-thumb text-center"> <img alt="thumbnail" class="img-circle" width="80" src="./images/key.png">
                                    <h3><?php echo APPLICATION_NAME?></h3> </div>
                            </div>
                        </div>
                        <div class="form-group ">
                            <div class="col-xs-12">
                                <input id="access_login" name="access_login" class="form-control" type="text" required="" placeholder="Username"> </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12">
                                <input id="access_password" name="access_password" class="form-control" type="password" required="" placeholder="Password"> </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-12">
                                <div class="custom-control custom-checkbox">

                                    <a href="javascript:void(0)" id="to-recover" class="text-dark pull-right"><i class="fa fa-lock m-r-5"></i> Lupa Password</a>
                                </div>
                            </div>
                        </div>
                        <div class="form-group text-center">
                            <div class="col-xs-12 p-b-20">
                                <button class="btn btn-block btn-lg btn-info btn-rounded" type="submit">Log In</button>
                            </div>
                        </div>
                        <div id="results"></div>
                        <div class="form-group m-b-0">
                            <div class="col-sm-12 text-center">
                                Google Form <a href="https://forms.gle/sHggXbcDVidSwFVn7"><span style="color:red;">DAFTAR AKUN SIKARS</span></a><br />
                                <!--Daftarkan <a href="<?php echo URL_INSTITUTION?>/application/sikars_registration/sikars_self_registration.php" class="text-info m-l-5"><b>Akun SIKARS</b></a><br />
                                Daftar Sebagai <a href="<?php echo strtolower($_SESSION["APPS_NAME"])?>_register.php" class="text-info m-l-5"><b>Calon Surveior</b></a>    -->
                            </div>
                        </div>
                    </form>
                    <form class="form-horizontal" id="recoverform" method="post">
                        <div class="form-group ">
                            <div class="col-xs-12">
                                <h3>Reset Password</h3>

                                <p class="text-muted">Masukkan alamat email, dan instruksinya akan dikirim melalui email tersebut! </p>
                            </div>
                        </div>
                        <div class="form-group ">
                            <div class="col-xs-12">
                                <input id="access_email" name="access_email" class="form-control" type="text" required="" placeholder="Email"> </div>
                        </div>
                        <div class="form-group text-center m-t-20">
                            <div class="col-xs-12">
                                <button class="btn btn-primary btn-lg btn-block text-uppercase waves-effect waves-light" type="submit">Reset</button>
                            </div>
                        </div>
                        <div id="recover_msg"></div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================== -->
    <!-- End Wrapper -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- All Jquery -->
    <!-- ============================================================== -->
    <script src="./lib/js/jquery/jquery-3.2.1.min.js"></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="./lib/js/popper/popper.min.js"></script>
    <script src="./lib/js/bootstrap/dist/js/bootstrap.min.js"></script>
    <!--Custom JavaScript -->
    <script type="text/javascript">
        $(function() {
            $(".preloader").fadeOut();
        });
        $(function() {
            $('[data-toggle="tooltip"]').tooltip()
        });
        // ==============================================================
        // Login and Recover Password
        // ==============================================================
        $('#to-recover').on("click", function() {
            $("#loginform").slideUp();
            $("#recoverform").fadeIn();
        });
    </script>

    <script>
        $(document).ready(function() {
            $("#loginform").submit(function() {

                var $this = $(this);
                var access_login    = $this.find('#access_login').val();
                var access_password = $this.find('#access_password').val();
                var dataJSON = JSON.stringify([{"name":"user_name","value":access_login},{"name":"user_password","value":access_password}]);
                //alert(data);
                $.ajax({
        			type: "POST",
        			url: 'login_action.php',
        			data: dataJSON,
                    dataType: 'json',
        			success: function(data) {
        				// Inserting html into the result div
        				//$('.alert').removeClass('hide');
                        //$("html, body").animate({ scrollTop: 0 }, "fast");
                        $('#results').html(data);
                        if (data === "") {
                            window.location='./application/dashboard/'
                        }
        			},
        			error: function(jqXHR, text, error){
                    // Displaying if there are any errors
                    	$('#results').html(data);
                    }
                });
                return false;
           	});

            $("#recoverform").submit(function() {
                $(this).find("button[type='submit']").prop('disabled',true);
                var $this = $(this);
                var access_email    = $this.find('#access_email').val();

                var recoveryJSON = JSON.stringify([{"name":"user_name","value":access_email}]);

                $.ajax({
        			type: "POST",
        			url: 'password_recovery.php',
        			data: recoveryJSON,
                    dataType: 'json',
        			success: function (data) {
        				// Inserting html into the result div
        				//$('.alert').removeClass('hide');
                        //$("html, body").animate({ scrollTop: 0 }, "fast");
                        $('#recover_msg').html(data);
        			},
        			error: function(jqXHR, text, error){
                    // Displaying if there are any errors
                    	$('#recover_msg').html(data);
                    }
                });
                return false;
           	});
        });
        </script>

</body>

</html>