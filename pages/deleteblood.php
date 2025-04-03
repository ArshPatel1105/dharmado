<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php'); // Redirect to login if not logged in
    exit();
}

include '../dbconnect.php';

if (isset($_GET['id'])) {
    $user_id = mysqli_real_escape_string($conn, $_GET['id']);

    // Delete related rows from the `monthly_report` table
    $deleteMonthlyReportQuery = "DELETE FROM monthly_report WHERE user_id = '$user_id'";
    mysqli_query($conn, $deleteMonthlyReportQuery);

    // Delete user from the `user` table
    $deleteUserQuery = "DELETE FROM user WHERE id = '$user_id'";
    if (mysqli_query($conn, $deleteUserQuery)) {
        // Delete user from all dynamically created "Remaining Dharmado" tables
        $showTablesQuery = "SHOW TABLES LIKE 'remaining_dharmado_%'";
        $tablesResult = mysqli_query($conn, $showTablesQuery);
        while ($tableRow = mysqli_fetch_row($tablesResult)) {
            $tableName = $tableRow[0];
            $deleteFromDynamicTableQuery = "DELETE FROM $tableName WHERE user_id = '$user_id'";
            mysqli_query($conn, $deleteFromDynamicTableQuery);
        }

        echo "<script>alert('User deleted successfully from all related tables.'); window.location.href='deleteblood.php';</script>";
    } else {
        echo "<script>alert('Error deleting user: " . mysqli_error($conn) . "'); window.location.href='deleteblood.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>BDMS</title>

    <!-- Corrected path for bootstrap.min.css -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

    <!-- MetisMenu CSS -->
    <link href="../vendor/metisMenu/metisMenu.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="../dist/css/sb-admin-2.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="../vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <!-- Corrected path for icofont.min.css -->
    <link rel="stylesheet" href="../icofont/icofont.min.css">
</head>

<body>
    <div id="wrapper">
        <?php include 'includes/nav.php'; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class=".col-lg-12">
                        <h1 class="page-header">Delete User Details</h1>
                    </div>
                </div>
                <div class="row">
                    <div class=".col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Total Records of Available Users
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover" id="dataTables-example">
                                        <?php
                                        $query = "SELECT * FROM user";
                                        $result = mysqli_query($conn, $query);

                                        if ($result) {
                                            echo "
                                            <thead>
                                                <tr>
                                                    <th>Full Name</th>
                                                    <th>Gender</th>
                                                    <th>Dharmado Amount</th>
                                                    <th>Starting Date</th>
                                                    <th>Occupation</th>
                                                    <th>Contact</th>
                                                    <th>D.O.B</th>
                                                    <th>Address</th>
                                                    <th><i class='fa fa-pencil'></i></th>
                                                </tr>
                                            </thead>";

                                            while ($row = mysqli_fetch_array($result)) {
                                                echo "
                                                <tbody>
                                                    <tr class='gradeA'>
                                                        <td>" . htmlspecialchars($row['name']) . "</td>
                                                        <td>" . htmlspecialchars($row['gender']) . "</td>
                                                        <td>" . htmlspecialchars($row['dharmadoamount']) . "</td>
                                                        <td>" . htmlspecialchars($row['startingdate']) . "</td>
                                                        <td>" . htmlspecialchars($row['occupation']) . "</td>
                                                        <td>" . htmlspecialchars($row['contact']) . "</td>
                                                        <td>" . htmlspecialchars($row['dob']) . "</td>
                                                        <td>" . htmlspecialchars($row['address']) . "</td>
                                                        <td><a href='deleteblood.php?id=" . $row['id'] . "'><i class='fa fa-trash' style='color:red'></i></a></td>
                                                    </tr>
                                                </tbody>";
                                            }
                                        } else {
                                            echo "Query failed: " . mysqli_error($conn);
                                        }
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
    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="../vendor/metisMenu/metisMenu.min.js"></script>
    <script src="../dist/js/sb-admin-2.js"></script>
</body>

<footer class="fixed-footer">
    <p>&copy; <?php echo date("Y"); ?>: Developed By BAPS MODASA</p>
</footer>

<style>
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

</html>