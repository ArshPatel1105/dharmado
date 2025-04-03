<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php'); // Redirect to login if not logged in
    exit();
}
?>
<html>

<head>


<title>BDMS</title>

<!-- Corrected path for bootstrap.min.css -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

<!-- MetisMenu CSS -->
<link href="../vendor/metisMenu/metisMenu.min.css" rel="stylesheet">

<!-- DataTables CSS -->
 <link href="../css/dataTables/dataTables.bootstrap.css" rel="stylesheet">
 
<!-- DataTables Responsive CSS -->
<link href="../css/dataTables/dataTables.responsive.css" rel="stylesheet">

<!-- Custom CSS -->
<link href="../dist/css/sb-admin-2.css" rel="stylesheet">

<!-- Custom Fonts -->
<link href="../vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

<!-- Corrected path for icofont.min.css -->
<link rel="stylesheet" href="../icofont/icofont.min.css">

</head>


<body>
<div id="wrapper">

<?php include 'includes/nav.php'?>


<div id="page-wrapper">
<div class="container-fluid">
<div class="row">
<div class=".col-lg-12">
               <h1 class="page-header">View User Detail</h1>
                </div>
  </div>  

				<div class="row">
                        <div class=".col-lg-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    Total Records of User
                                </div>
								
								 <div class="panel-body">
                                    <div class="table-responsive">
									<table class="table table-striped table-bordered table-hover" id="dataTables-example">
									
									<?php

						include "dbconnect.php";

						$qry="select * from user";
						$result=mysqli_query($conn,$qry);


						echo"
						<thead>
						<tr>
							<th>User ID</th>
							<th>Full Name</th>
							<th>Gender</th>
							<th>Dharmado Amount</th>
							<th>Starting Date</th>
							<th>Occupation</th>
							<th>Contact</th>
							<th>D.O.B</th>
							<th>Taluko</th>
							<th>Address</th>
							 <!-- Added Taluko column -->
						</tr>
						</thead>";

						echo "<tbody>";
						while($row=mysqli_fetch_array($result)){
						  echo"<tr class='gradeA'>
						  <td>".htmlspecialchars($row['id'])."</td>
						  <td>".htmlspecialchars($row['name'])."</td>
						  <td>".htmlspecialchars($row['gender'])."</td>
						  <td>".htmlspecialchars($row['dharmadoamount'])."</td>
						  <td>".date('d-m-Y', strtotime($row['startingdate']))."</td>
						  <td>".htmlspecialchars($row['occupation'])."</td>
						  <td>".htmlspecialchars($row['contact'])."</td>
						  <td>".date('d-m-Y', strtotime($row['dob']))."</td>
						  <td>".htmlspecialchars($row['taluko'])."</td>
						  <td>".htmlspecialchars($row['address'])."</td>
						   <!-- Display Taluko -->
						</tr>";
						}
						echo "</tbody>";

						?>
						</table>
									
				</div>
				</div>		
		</div>
		</div>	
		</div>	
		</div>	
		</div>
		</div>

  <!-- jQuery -->
  <script src="../vendor/jquery/jquery.min.js"></script>

<!-- Bootstrap Core JavaScript -->
<script src="../vendor/bootstrap/js/bootstrap.min.js"></script>

<!-- Metis Menu Plugin JavaScript -->
<script src="../vendor/metisMenu/metisMenu.min.js"></script>

<!-- Custom Theme JavaScript -->
<script src="../dist/js/sb-admin-2.js"></script>

<!-- DataTables JavaScript -->
<script src="../js/dataTables/jquery.dataTables.min.js"></script>
<script src="../js/dataTables/dataTables.bootstrap.min.js"></script>

</body>

<footer class="fixed-footer">
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