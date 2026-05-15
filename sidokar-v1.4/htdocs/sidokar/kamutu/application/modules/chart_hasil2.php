<?php
$module_name = "GRAPH";
$module_id = 64;
if(session_id() == '')
{
    session_start();
    include("../includes/autostart.php");
}


if (isset($_GET["id"])) {   
   $id = $_GET["id"];
   $id2 = $_GET["id2"];
   $_SESSION['recid'] = $id ;
   $_SESSION['idtopikmutu'] = $id2 ;
}

global $conn ;
global $SETME_XKORD;
global $SETME_DATA  ;
global $SETME_S
global $SETME_T;
;

//echo "<script>alert($id) </script>";

											   $sqltbl = "SELECT  " .
"kalmut_sheet_mutu1_dtl_summary.parent_recid, " .
"kalmut_sheet_mutu1_mst.periode_bln, " .
"kalmut_sheet_mutu1_mst.periode_thn, " .
"kalmut_sheet_mutu1_mst.idtopikmutu, " .
"kalmut_sheet_mutu1_mst.judulindikator, " .
"kalmut_sheet_mutu1_dtl_summary.day1, " .
"kalmut_sheet_mutu1_dtl_summary.day2, " .
"kalmut_sheet_mutu1_dtl_summary.day3, " .
"kalmut_sheet_mutu1_dtl_summary.day4, " .
"kalmut_sheet_mutu1_dtl_summary.day5, " .
"kalmut_sheet_mutu1_dtl_summary.day6, " .
"kalmut_sheet_mutu1_dtl_summary.day7, " .
"kalmut_sheet_mutu1_dtl_summary.day8, " .
"kalmut_sheet_mutu1_dtl_summary.day9, " .
"kalmut_sheet_mutu1_dtl_summary.day10, " .
"kalmut_sheet_mutu1_dtl_summary.day11, " .
"kalmut_sheet_mutu1_dtl_summary.day12, " .
"kalmut_sheet_mutu1_dtl_summary.day13, " .
"kalmut_sheet_mutu1_dtl_summary.day14, " .
"kalmut_sheet_mutu1_dtl_summary.day15, " .
"kalmut_sheet_mutu1_dtl_summary.day16, " .
"kalmut_sheet_mutu1_dtl_summary.day17, " .
"kalmut_sheet_mutu1_dtl_summary.day18, " .
"kalmut_sheet_mutu1_dtl_summary.day19, " .
"kalmut_sheet_mutu1_dtl_summary.day20, " .
"kalmut_sheet_mutu1_dtl_summary.day21, " .
"kalmut_sheet_mutu1_dtl_summary.day22, " .
"kalmut_sheet_mutu1_dtl_summary.day23, " .
"kalmut_sheet_mutu1_dtl_summary.day24, " .
"kalmut_sheet_mutu1_dtl_summary.day25, " .
"kalmut_sheet_mutu1_dtl_summary.day26, " .
"kalmut_sheet_mutu1_dtl_summary.day27, " .
"kalmut_sheet_mutu1_dtl_summary.day28, " .
"kalmut_sheet_mutu1_dtl_summary.day29, " .
"kalmut_sheet_mutu1_dtl_summary.day30, " .
"kalmut_sheet_mutu1_dtl_summary.day31, " .
"kalmut_sheet_mutu1_dtl_summary.totentry, " .
"kalmut_sheet_mutu1_dtl_summary.satuan, " .
"kalmut_sheet_mutu1_dtl_summary.standar, " .
"kalmut_sheet_mutu1_dtl_summary.target, " .
"kalmut_sheet_mutu1_dtl_summary.tobenchmark " .
" FROM " .
"kalmut_sheet_mutu1_dtl_summary " .
" Inner Join kalmut_sheet_mutu1_mst ON kalmut_sheet_mutu1_mst.recid = kalmut_sheet_mutu1_dtl_summary.parent_recid " .  "  where parent_recid = " .   $_SESSION['recid']  . " and  kalmut_sheet_mutu1_mst.idtopikmutu = "  .    $_SESSION['idtopikmutu'];  

 $xBLN = get_data($sqltbl,"periode_bln");
 $xTHN = get_data($sqltbl,"periode_thn");
 $xTOPIK = get_data($sqltbl , "judultopik");
 $keterangan = get_data("select * from kalmut_katalogmutu_deskripsi where idtopikmutu = " . $id2 , "defop");
 $xTarget = get_data($sqltbl,"target");
 $xStandar = get_data($sqltbl,"standar");
 $xTobenchmark = get_data($sqltbl,"tobenchmark");
 
 
 $judul = "GRAFIK MUTU - UNIT " . $_SESSION['namaunit'] . " <b>BULAN :</b> " .  $xBLN . 
          " <b>TAHUN :</b> " . $xTHN . "</br>" . $xTOPIK;
           ;
     $dataY  = array();
	 $dataX = array() ;
	 $dataS = array();
	 $dataT = array();
	 
     for ($x = 1; $x <= 31; $x++)
     {
			       $dataX[$x] =  "'" . $x . "'" ;
			       $dataY[$x] = 0 ;
			       $dataT[$x] = 0;
			       $dataS[$x] = 0;
			       
     }
    

	if ($result=mysqli_query($conn,$sqltbl))
   {
		        $result->data_seek(0);
			    while($dataz = mysqli_fetch_assoc($result))
			    { 
			          for ($x = 1; $x <= 31; $x++)
			          {
			                $dataY[$x] =  $dataz['day' . $x] ;
			                $dataT[$x] = $dataz['target'] ;
			                $dataS[$x] = $dataz['sasaran'];
			                
 			           }
			     }
    }
		                  $SETME_DATA =  "[" . join(",", $dataY) . "]";
		                  $SETME_S =  "[" . join(",", $dataS) . "]";
		                  $SETME_T =  "[" . join(",", $dataT) . "]";
		                  $SETME_XKORD = "[" . join(",", $dataX) . "]";
		                  $SETME_XKORD =  json_decode($SETME_XKORD);
		                  $SETME_DATA = json_decode( "[" . implode(",", array($SETME_DATA,$SETME_S) ) . "]") ;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Aplikasi Akreditasi">
    <meta name="author" content="Dr. Diyurman Gea, S.Kom.,MM">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="../../images/<?php echo FAVICON ?>">
    <title><?php echo APPLICATION_TITLE ?></title>
    <!-- Custom CSS -->
    <link href="../../dist/css/style.css" rel="stylesheet">
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
      <script type="text/javascript">
		        function zoom() {
		            document.body.style.zoom = "85%"
		        }
			</script>


    <style>
    logo-text {
      font: 20pt sans-serif;
      color: red;
    }

    #mytable {
    font-family: Arial, Helvetica, sans-serif;
    border-collapse: collapse;
    width: 100%;
    }

    #mytable td, #mytable th {
     border: 1px solid #ddd;
     padding: 8px;
     font-size:14px;
    }

    #mytable tr:nth-child(even){background-color: #f2f2f2;}

    #mytable tr:hover {background-color: #ddd;}

    #mytable th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: left;
    font-size:14px;
    background-color: #4CAF50;
    color: white;
    }

    </style>
</head>
<body class="horizontal-nav skin-megna fixed-layout" onload='zoom()'>
    <!-- ============================================================== -->
    <!-- Preloader - style you can find in spinners.css -->
    <!-- ============================================================== -->
    <div class="preloader">
        <div class="loader">
            <div class="loader__figure"></div>
            <p class="loader__label"><?php echo APPLICATION_TITLE ?></p>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- Main wrapper - style you can find in pages.scss -->
    <!-- ============================================================== -->
    <div id="main-wrapper">
        <!-- ============================================================== -->
        <!-- Topbar header - style you can find in pages.scss -->
        <!-- ============================================================== -->
        <?php
            include '../includes/header.php';
        ?>
        <!-- ============================================================== -->
        <!-- End Topbar header -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Left Sidebar - style you can find in sidebar.scss  -->
        <!-- ============================================================== -->
        <?php
            include '../includes/left-sidebar.php';
        ?>
        <!-- ============================================================== -->
        <!-- End Left Sidebar - style you can find in sidebar.scss  -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Page wrapper  -->
        <!-- ============================================================== -->
        <div class="page-wrapper">
            <!-- ============================================================== -->
            <!-- Container fluid  -->
            <!-- ============================================================== -->
            <div class="container-fluid">
                <!-- ============================================================== -->
                <!-- Bread crumb and right sidebar toggle -->
                <!-- ============================================================== -->
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?php echo "<b>" . $judul . "</b>" ; ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-right">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="javascript:void(0)"><b>| H O M E |</b></a></li>
                                <li class="breadcrumb-item active"><?php echo $module_name ?></li>
                            </ol>

                        </div>
                    </div>
                </div>
                <!-- ============================================================== -->
                <!-- End Bread crumb and right sidebar toggle -->
                <!-- ============================================================== -->
                <!-- ============================================================== -->
                <!-- Start Page Content -->
                <!-- ============================================================== -->
                <div class="row">
                    <!-- column -->
                    <!--<div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">GRAPHIC</h4>  -->
									<div class="table-responsive" >
										<?php
											   $sqltbl = "SELECT  " .
"kalmut_sheet_mutu1_dtl_summary.parent_recid, " .
"kalmut_sheet_mutu1_mst.periode_bln, " .
"kalmut_sheet_mutu1_mst.periode_thn, " .
"kalmut_sheet_mutu1_mst.idtopikmutu, " .
"kalmut_sheet_mutu1_mst.judulindikator, " .
"kalmut_sheet_mutu1_dtl_summary.day1, " .
"kalmut_sheet_mutu1_dtl_summary.day2, " .
"kalmut_sheet_mutu1_dtl_summary.day3, " .
"kalmut_sheet_mutu1_dtl_summary.day4, " .
"kalmut_sheet_mutu1_dtl_summary.day5, " .
"kalmut_sheet_mutu1_dtl_summary.day6, " .
"kalmut_sheet_mutu1_dtl_summary.day7, " .
"kalmut_sheet_mutu1_dtl_summary.day8, " .
"kalmut_sheet_mutu1_dtl_summary.day9, " .
"kalmut_sheet_mutu1_dtl_summary.day10, " .
"kalmut_sheet_mutu1_dtl_summary.day11, " .
"kalmut_sheet_mutu1_dtl_summary.day12, " .
"kalmut_sheet_mutu1_dtl_summary.day13, " .
"kalmut_sheet_mutu1_dtl_summary.day14, " .
"kalmut_sheet_mutu1_dtl_summary.day15, " .
"kalmut_sheet_mutu1_dtl_summary.day16, " .
"kalmut_sheet_mutu1_dtl_summary.day17, " .
"kalmut_sheet_mutu1_dtl_summary.day18, " .
"kalmut_sheet_mutu1_dtl_summary.day19, " .
"kalmut_sheet_mutu1_dtl_summary.day20, " .
"kalmut_sheet_mutu1_dtl_summary.day21, " .
"kalmut_sheet_mutu1_dtl_summary.day22, " .
"kalmut_sheet_mutu1_dtl_summary.day23, " .
"kalmut_sheet_mutu1_dtl_summary.day24, " .
"kalmut_sheet_mutu1_dtl_summary.day25, " .
"kalmut_sheet_mutu1_dtl_summary.day26, " .
"kalmut_sheet_mutu1_dtl_summary.day27, " .
"kalmut_sheet_mutu1_dtl_summary.day28, " .
"kalmut_sheet_mutu1_dtl_summary.day29, " .
"kalmut_sheet_mutu1_dtl_summary.day30, " .
"kalmut_sheet_mutu1_dtl_summary.day31, " .
"kalmut_sheet_mutu1_dtl_summary.totentry, " .
"kalmut_sheet_mutu1_dtl_summary.satuan, " .
"kalmut_sheet_mutu1_dtl_summary.standar, " .
"kalmut_sheet_mutu1_dtl_summary.target, " .
"kalmut_sheet_mutu1_dtl_summary.tobenchmark " .
" FROM " .
"kalmut_sheet_mutu1_dtl_summary " .
" Inner Join kalmut_sheet_mutu1_mst ON kalmut_sheet_mutu1_mst.recid = kalmut_sheet_mutu1_dtl_summary.parent_recid " .  "  where parent_recid = " .   $_SESSION['recid']   . " and kalmut_sheet_mutu1_mst.idtopikmutu = "  .    $_SESSION['idtopikmutu'] ;  

										  echo '<table id="mytable" width="100%" cellspacing="0">' ;
											  echo '<thead>' ;
												  echo '<tr>' ;
													  echo '<th>PERIODE</th>';
													  echo '<th>BESARAN/VARIABEL</th>' ;
													  for ($x = 1; $x <= 31; $x++)
													   {
													    $day_i = substr(strval($x + 100),1) ;
													    $day_var = "day" . strval($x) ;
														echo '<th>'. $day_i . '</thr>';
													   }
												 echo '</tr>';
											  echo '</thead>';

												  if ($result=mysqli_query($conn,$sqltbl))
												    {
													    while($dataz = mysqli_fetch_assoc($result))
													    {

										                    echo '<tbody>' ;
														    echo '<tr>';
												            echo '<td>' . $dataz['periode_bln'] . '/' . $dataz['periode_thn']   . '</td>' ;
												            echo '<td>' . $dataz['judulindikator'] . '</td>' ;
												            for ($x = 1; $x <= 31; $x++)
													        {
												                 echo '<td style="text-align:center">' . $dataz['day'.$x] . '</td>' ;
												            }    
												             echo '</tr></tbody>' ;
										               }
										            }

										     echo '</table>' ;
										?>

									</div>
                             <!-- </div>
                        </div>
                    </div>
                    <!-- column -->


                </div>

                <!--- roooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo -->
                <div class="row">
                        <!-- column -->
                        <div class="card">
                            <div class="card-body">
                              <div class="table-responsive" >
                                    <button class="tablink" onclick="openPage('graphic', this, 'red')" id="defaultOpen">RUN CHART</button>
							        <button class="tablink" onclick="openPage('keterangan', this, 'green')">KETERANGAN</button>
							        <!--
							        <button class="tablink" onclick="openPage('Print', this, 'orange')">Print</button>
							        //<button class="tablink" onclick="openPage('Export', this, 'orange')">Export</button>
							        <button class="tablink" onclick="openPage('About', this, 'orange')">About</button>    -->
									<div id="graphic" class="tabcontent">
									     <h4 class="card-title"></h4>
                                		 <div id="container" style="display: inline-block; position: relative">
                                   	  	      <canvas id="cvs2" width="1920" height="400" >[No canvas support]</canvas>
                                         </div>
									</div>

									<div id="keterangan" class="tabcontent">
									     <h4 class="card-title"></h4>
									     <h3><?php echo "<b>" . $keterangan . "/b>" ; ?></h3>
                                		 <div id="container" style="display: inline-block; position: relative">
                                   	  	      <canvas id="cvs3" width="1920" height="400" >[No canvas support]</canvas>
                                         </div>
									</div>


                               </div>
                            </div>
                        </div>

                </div>

                <!--- rooooooooooooooooooooooooooooooooooooooooooooooooooooooooooow -->
                <!-- ============================================================== -->
                <!-- End PAge Content -->
                <!-- ============================================================== -->
                <!-- ============================================================== -->
                <!-- Right sidebar -->
                <!-- ============================================================== -->
                <!-- .right-sidebar -->
                <?php
                    include '../includes/right-sidebar.php';
                ?>
                <!-- ============================================================== -->
                <!-- End Right sidebar -->
                <!-- ============================================================== -->
            </div>
            <!-- ============================================================== -->
            <!-- End Container fluid  -->
            <!-- ============================================================== -->

        </div>
        <!-- ============================================================== -->
        <!-- End Page wrapper  -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- footer -->
        <!-- ============================================================== -->
        <?php
            include '../includes/footer.php';
        ?>
        <!-- ============================================================== -->
        <!-- End footer -->
        <!-- ============================================================== -->
    </div>
    <!-- ============================================================== -->
    <!-- End Wrapper -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- All Jquery -->
    <!-- ============================================================== -->
    <script src="../../lib/js/jquery/jquery-3.2.1.min.js"></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="../../lib/js/popper/popper.min.js"></script>
    <script src="../../lib/js/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- slimscrollbar scrollbar JavaScript -->
    <script src="../../dist/js/perfect-scrollbar.jquery.min.js"></script>
    <!--Wave Effects -->
    <script src="../../dist/js/waves.js"></script>
    <!--Menu sidebar -->
    <script src="../../dist/js/sidebarmenu.js"></script>
    <script src="../../lib/js/RGraph/libraries/RGraph.common.core.js"></script>
    <script src="../../lib/js/RGraph/libraries/RGraph.common.dynamic.js"></script>
    <script src="../../lib/js/RGraph/libraries/RGraph.common.tooltips.js"></script>
    <script src="../../lib/js/RGraph/libraries/RGraph.common.key.js"></script>
    <script src="../../lib/js/RGraph/libraries/RGraph.hbar.js"></script>
    <script src="../../lib/js/RGraph/libraries/RGraph.bar.js"></script>
    <script src="../../lib/js/RGraph/libraries/RGraph.line.js" ></script>
    <script src="../../lib/js/RGraph/libraries/RGraph.drawing.yaxis.js" ></script>
    <script src="../../lib/js/RGraph/libraries/RGraph.common.effects.js" ></script>
    <!--Custom JavaScript -->
    <script src="../../dist/js/custom.min.js"></script>
    <!-- ============================================================== -->
    <!-- This page plugins -->
    <!-- ============================================================== -->
    <!-- Chart JS -->


 <script>

 data = <?php echo json_encode($SETME_DATA) ; ?>;
   xaxisLabels = ['1','2','3','4','5','6','7','8','9','10','11','12','13','14','15',
                  '16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31'];


    new RGraph.Line({
        id: 'cvs2',
        data: data ,
        options: {
            tooltips: '%{key}',
            tooltipsFormattedUnitsPost: '%',
            tooltipsFormattedKeyColors: ['black','red'],
            tooltipsFormattedKeyLabels: ['CAPAIAN INDIKATOR','STANDAR'],
            tooltipsCss: {
                fontSize: '16pt',
                textAlign: 'left'
            },
            backgroundGridVlines: true,
            backgroundGridBorder: true,
            colors: ['black','red'],
            linewidth: 2,
            spline: true,
            tickmarksStyle: null,
            xaxisLabels: xaxisLabels,
            xaxis: false,
            yaxis: false,
            marginLeft: 40
        }
    }).trace();
</script>




<script>
   function getAllUrlParams(url) {

  // get query string from url (optional) or window
  var queryString = url ? url.split('?')[1] : window.location.search.slice(1);

  // we'll store the parameters here
  var obj = {};

  // if query string exists
  if (queryString) {

    // stuff after # is not part of query string, so get rid of it
    queryString = queryString.split('#')[0];

    // split our query string into its component parts
    var arr = queryString.split('&');

    for (var i = 0; i < arr.length; i++) {
      // separate the keys and the values
      var a = arr[i].split('=');

      // set parameter name and value (use 'true' if empty)
      var paramName = a[0];
      var paramValue = typeof (a[1]) === 'undefined' ? true : a[1];

      // (optional) keep case consistent
      paramName = paramName.toLowerCase();
      if (typeof paramValue === 'string') paramValue = paramValue.toLowerCase();

      // if the paramName ends with square brackets, e.g. colors[] or colors[2]
      if (paramName.match(/\[(\d+)?\]$/)) {

        // create key if it doesn't exist
        var key = paramName.replace(/\[(\d+)?\]/, '');
        if (!obj[key]) obj[key] = [];

        // if it's an indexed array e.g. colors[2]
        if (paramName.match(/\[\d+\]$/)) {
          // get the index value and add the entry at the appropriate position
          var index = /\[(\d+)\]/.exec(paramName)[1];
          obj[key][index] = paramValue;
        } else {
          // otherwise add the value to the end of the array
          obj[key].push(paramValue);
        }
      } else {
        // we're dealing with a string
        if (!obj[paramName]) {
          // if it doesn't exist, create property
          obj[paramName] = paramValue;
        } else if (obj[paramName] && typeof obj[paramName] === 'string'){
          // if property does exist and it's a string, convert it to an array
          obj[paramName] = [obj[paramName]];
          obj[paramName].push(paramValue);
        } else {
          // otherwise add the property
          obj[paramName].push(paramValue);
        }
      }
    }
  }

  return obj;
}

</script>

<script>
function openPage(pageName, elmnt, color) {
  // Hide all elements with class="tabcontent" by default */
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }

  // Remove the background color of all tablinks/buttons
  tablinks = document.getElementsByClassName("tablink");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].style.backgroundColor = "";
  }

  // Show the specific tab content
  document.getElementById(pageName).style.display = "block";

  // Add the specific color to the button used to open the tab content
  elmnt.style.backgroundColor = color;
}

// Get the element with id="defaultOpen" and click on it
document.getElementById("defaultOpen").click();
</script>

</body>
</html>
