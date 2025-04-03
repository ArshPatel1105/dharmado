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

    <title>BDMS</title>

    <!-- Bootstrap Core CSS -->
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- MetisMenu CSS -->
    <link href="../vendor/metisMenu/metisMenu.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="../dist/css/sb-admin-2.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="../vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <link rel="stylesheet" href="../icofont/icofont.min.css">


</head>

<body>

    <div id="wrapper">

        <?php include 'includes/nav.php'?>

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">BBMS</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            MESSAGE BOX
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <form role="form" action="#" method="post">

								<?php
									if ($_SERVER['REQUEST_METHOD'] == 'POST') {
										$id = isset($_POST['id']) ? $_POST['id'] : '';
										$name = isset($_POST['name']) ? $_POST['name'] : '';
										$gender = isset($_POST['gender']) ? $_POST['gender'] : '';
										$dob = isset($_POST['dob']) ? $_POST['dob'] : '';
										
										$occupation = isset($_POST['occupation']) ? $_POST['occupation'] : '';
										$address = isset($_POST['address']) ? $_POST['address'] : '';
										$contact = isset($_POST['contact']) ? $_POST['contact'] : '';
										$dharmadoamount = isset($_POST['dharmadoamount']) ? $_POST['dharmadoamount'] : '';
										$startingdate = isset($_POST['startingdate']) ? $_POST['startingdate'] : '';

										include 'dbconnect.php';

										$qry = "UPDATE user SET 
												name = '$name', 
												gender = '$gender', 
												dob = '$dob', 
												occupation = '$occupation', 
												address = '$address', 
												contact = '$contact', 
												dharmadoamount = '$dharmadoamount', 
												startingdate = '$startingdate' 
												WHERE id = '$id'";

										$result = mysqli_query($conn, $qry);

										if (!$result) {
											echo "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
										} else {
											echo "<div class='alert alert-success'>User details updated successfully!</div>";
										}
									}
								?>

                                  </form>
                                </div>
                                
                            </div>
                            <!-- /.row (nested) -->
                        </div>
                        <!-- /.panel-body -->
                    </div>
                    <!-- /.panel -->
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->

    <!-- jQuery -->
    <script src="../vendor/jquery/jquery.min.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="../vendor/bootstrap/js/bootstrap.min.js"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="../vendor/metisMenu/metisMenu.min.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="../dist/js/sb-admin-2.js"></script>

</body>

<footer>
        <p>&copy; <?php echo date("Y"); ?>: Developed By BAPS MODASA</p>
    </footer>
	
	<style>
	footer{
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

</html>
