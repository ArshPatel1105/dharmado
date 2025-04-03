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

    <!-- Custom Fonts -->
    <link href="../vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <link rel="stylesheet" href="../icofont/icofont.min.css">


    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body>

    <div id="wrapper">

        <?php include 'includes/nav.php'; ?>

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
                                    <form role="form" action="index.php" method="post">
                                        <?php
                                        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                                            include '../dbconnect.php';

                                            $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : '';
                                            $gender = isset($_POST['gender']) ? mysqli_real_escape_string($conn, $_POST['gender']) : '';
                                            $dob = isset($_POST['dob']) ? mysqli_real_escape_string($conn, $_POST['dob']) : '';
                                            $occupation = isset($_POST['occupation_select']) && $_POST['occupation_select'] === 'OTHER'
                                                ? mysqli_real_escape_string($conn, $_POST['occupation_custom'])
                                                : mysqli_real_escape_string($conn, $_POST['occupation_select']);
                                            $address = isset($_POST['address']) ? mysqli_real_escape_string($conn, $_POST['address']) : '';
                                            $contact = isset($_POST['contact']) ? mysqli_real_escape_string($conn, $_POST['contact']) : '';
                                            $dharmadoamount = isset($_POST['dharmadoamount']) ? mysqli_real_escape_string($conn, $_POST['dharmadoamount']) : '';
                                            $startingdate = isset($_POST['startingdate']) ? mysqli_real_escape_string($conn, $_POST['startingdate']) : '';

                                            // Validate contact number
                                            if (!preg_match('/^\d{10,15}$/', $contact)) {
                                                echo "<div class='alert alert-danger'>Error: Invalid contact number. Please enter a valid number.</div>";
                                            } else {
                                                // Check if the contact number already exists
                                                $check_query = "SELECT id FROM user WHERE contact = '$contact'";
                                                $check_result = mysqli_query($conn, $check_query);

                                                if (mysqli_num_rows($check_result) > 0) {
                                                    echo "<div class='alert alert-danger'>Error: Contact number already exists in the database.</div>";
                                                } else {
                                                    $qry = "INSERT INTO user (name, gender, dob, occupation, address, contact, dharmadoamount, startingdate) 
                                                            VALUES ('$name', '$gender', '$dob', '$occupation', '$address', '$contact', '$dharmadoamount', '$startingdate')";
                                                    $result = mysqli_query($conn, $qry);

                                                    if ($result) {
                                                        echo "<div class='alert alert-success'>User details submitted successfully!</div>";
                                                    } else {
                                                        echo "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
                                                    }
                                                }
                                            }
                                        } else {
                                            echo "<div style='text-align: center'><h1>ERROR</h1>";
                                            echo "<a href='addblood.php' class='btn btn-primary'>Go Back</a>";
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
