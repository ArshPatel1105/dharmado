<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php'); // Redirect to login if not logged in
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>BDMS</title>

    <!-- Bootstrap Core CSS -->
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- MetisMenu CSS -->
    <link href="../vendor/metisMenu/metisMenu.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="../dist/css/sb-admin-2.css" rel="stylesheet">

    <!-- Morris Charts CSS -->
    <link href="../vendor/morrisjs/morris.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="../vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <link rel="stylesheet" href="../icofont/icofont.min.css">
	
	<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,700,800' rel='stylesheet' type='text/css'>
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script> 

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
	
	<style>
        .dashboard-carousel {
            height: 600px; /* Height of the section */
            text-align: center;
            color: white;
            border-radius: 10px;
            overflow: hidden; /* Ensure images fit within the section */
        }
        .carousel-inner img {
            width: 100%;
            height: 600px; /* Ensure images fit the section height */
            object-fit: cover; /* Ensure images cover the section proportionally */
        }
        .carousel-caption {
            position: absolute;
            top: 30%; /* Center vertically */
            left: 45%; /* Center horizontally */
            transform: translate(-50%, -50%); /* Adjust for centering */
            /* Semi-transparent background for captions */
            padding: 20px;
            border-radius: 10px;
            max-width: 900px; /* Limit the width of the caption */
            text-align: center;
            font-family: 'Open Sans', sans-serif;
        }
        .carousel-caption h1 {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
            white-space: nowrap;
            overflow: hidden;
            border-right: 3px solid white; /* Cursor effect */
            width: 0; /* Start with no width */
            animation: typing 4s linear, blink 0.5s step-end infinite alternate;
        }
        @keyframes typing {
            from {
                width: 0;
            }
            to {
                width: 30ch; /* Full width of the text */
            }
        }
        @keyframes blink {
            from {
                border-color: transparent;
            }
            to {
                border-color: white;
            }
        }
        .carousel-inner .item img {
            filter: brightness(70%); /* Reduce brightness to make the image less highlighted */
        }
	footer {
            background-color: #424558;
            bottom: 0;
            left: 0;
            right: 0;
            height: 35px;
            text-align: center;
            color: #CCC;
        }
        footer p {
            padding: 10.5px;
            margin: 0px;
            line-height: 100%;
        }
	</style>

</head>

<body>

    <div id="wrapper">

        <?php include 'includes/nav.php'; ?>

        <div id="page-wrapper">
            <div id="dashboardCarousel" class="carousel slide dashboard-carousel" data-ride="carousel" data-interval="4000">
                <!-- Indicators -->
                <ol class="carousel-indicators">
                    <li data-target="#dashboardCarousel" data-slide-to="0" class="active"></li>
                    <li data-target="#dashboardCarousel" data-slide-to="1"></li>
                    <li data-target="#dashboardCarousel" data-slide-to="2"></li>
                </ol>

                <!-- Wrapper for slides -->
                <div class="carousel-inner">
                    <div class="item active">
                        <img src="../img/admin-dashboard-bg1.jpg" alt="Background 1">
                        <div class="carousel-caption">
                            <h1>WELCOME TO THE BAPS MODASA</h1>
                        </div>
                    </div>
                    <div class="item">
                        <img src="../img/admin-dashboard-bg2.jpg" alt="Background 2">
                        <div class="carousel-caption">
                            <h1>WELCOME TO THE BAPS MODASA</h1>
                        </div>
                    </div>
                    <div class="item">
                        <img src="../img/admin-dashboard-bg3.jpg" alt="Background 3">
                        <div class="carousel-caption">
                            <h1>WELCOME TO THE BAPS MODASA</h1>
                        </div>
                    </div>
                </div>

                <!-- Controls -->
                <a class="left carousel-control" href="#dashboardCarousel" data-slide="prev">
                    <span class="glyphicon glyphicon-chevron-left"></span>
                </a>
                <a class="right carousel-control" href="#dashboardCarousel" data-slide="next">
                    <span class="glyphicon glyphicon-chevron-right"></span>
                </a>
            </div>
            <!-- Add other dashboard content here -->
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">Welcome to BDMS</h1>
                        <p>Click below to view the dashboard:</p>
                        <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /#wrapper -->

    <footer class="fixed-footer">
        <p>&copy; <?php echo date("Y"); ?>: Developed By BAPS MODASA</p>
    </footer>

    <!-- jQuery -->
    <script src="../vendor/jquery/jquery.min.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="../vendor/bootstrap/js/bootstrap.min.js"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="../vendor/metisMenu/metisMenu.min.js"></script>

    <!-- Morris Charts JavaScript -->
    <script src="../vendor/raphael/raphael.min.js"></script>
    <script src="../vendor/morrisjs/morris.min.js"></script>
    <script src="../data/morris-data.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="../dist/js/sb-admin-2.js"></script>
    <script>
        // Ensure the carousel starts automatically
        $('.carousel').carousel();
    </script>

</body>

</html>
