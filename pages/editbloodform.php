<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php'); // Redirect to login if not logged in
    exit();
}

include '../dbconnect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $name = strtoupper(mysqli_real_escape_string($conn, $_POST['name']));
    $gender = strtoupper(mysqli_real_escape_string($conn, $_POST['gender']));
    $dob = strtoupper(mysqli_real_escape_string($conn, $_POST['dob']));
    $occupation = strtoupper(mysqli_real_escape_string($conn, $_POST['occupation']));
    $address = strtoupper(mysqli_real_escape_string($conn, $_POST['address']));
    $taluko = strtoupper(mysqli_real_escape_string($conn, $_POST['taluko']));
    $contact = strtoupper(mysqli_real_escape_string($conn, $_POST['contact']));
    $dharmadoamount = strtoupper(mysqli_real_escape_string($conn, $_POST['dharmadoamount']));
    $startingdate = strtoupper(mysqli_real_escape_string($conn, $_POST['startingdate']));

    // Validate contact number
    if (!preg_match('/^\d{10}$/', $contact)) {
        echo "<div class='alert alert-danger'>Error: Contact number must be exactly 10 digits.</div>";
    } else {
        // Update user data in the `user` table
        $updateUserQuery = "UPDATE user SET 
                            name = '$name', 
                            gender = '$gender', 
                            dob = '$dob', 
                            occupation = '$occupation', 
                            address = '$address', 
                            taluko = '$taluko', 
                            contact = '$contact', 
                            dharmadoamount = '$dharmadoamount', 
                            startingdate = '$startingdate' 
                            WHERE id = '$id'";
        if (mysqli_query($conn, $updateUserQuery)) {
            // Update user data in the `monthly_report` table
            $updateMonthlyReportQuery = "UPDATE monthly_report SET 
                                         name = '$name', 
                                         contact = '$contact', 
                                         dharmadoamount = '$dharmadoamount', 
                                         occupation = '$occupation' 
                                         WHERE user_id = '$id'";
            mysqli_query($conn, $updateMonthlyReportQuery);

            // Update user data in all dynamically created "Remaining Dharmado" tables
            $showTablesQuery = "SHOW TABLES LIKE 'remaining_dharmado_%'";
            $tablesResult = mysqli_query($conn, $showTablesQuery);
            while ($tableRow = mysqli_fetch_row($tablesResult)) {
                $tableName = $tableRow[0];
                $updateRemainingDharmadoQuery = "UPDATE $tableName SET 
                                                 name = '$name', 
                                                 contact = '$contact', 
                                                 dharmadoamount = '$dharmadoamount', 
                                                 occupation = '$occupation', 
                                                 taluko = '$taluko' 
                                                 WHERE user_id = '$id'";
                mysqli_query($conn, $updateRemainingDharmadoQuery);
            }

            echo "<div class='alert alert-success'>User details updated successfully everywhere!</div>";
        } else {
            echo "<div class='alert alert-danger'>Error updating user details: " . mysqli_error($conn) . "</div>";
        }
    }
}

// Fetch user data for editing
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM user WHERE id = '$id'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
    } else {
        echo "<div class='alert alert-danger'>User not found.</div>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit User Details - BDMS</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link href="../vendor/metisMenu/metisMenu.min.css" rel="stylesheet">
    <link href="../dist/css/sb-admin-2.css" rel="stylesheet">
    <link href="../vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
</head>

<body>
    <div id="wrapper">
        <?php include 'includes/nav.php'; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">Edit User Details</h1>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <form method="POST" action="">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input class="form-control" type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Gender</label>
                                <input class="form-control" type="text" name="gender" value="<?php echo htmlspecialchars($user['gender']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input class="form-control" type="date" name="dob" value="<?php echo htmlspecialchars($user['dob']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Occupation</label>
                                <select class="form-control" name="occupation" required>
                                    <option value="TEACHER" <?php echo ($user['occupation'] == 'TEACHER') ? 'selected' : ''; ?>>Teacher</option>
                                    <option value="DOCTOR" <?php echo ($user['occupation'] == 'DOCTOR') ? 'selected' : ''; ?>>Doctor</option>
                                    <option value="FARMER" <?php echo ($user['occupation'] == 'FARMER') ? 'selected' : ''; ?>>Farmer</option>
                                    <option value="BUSINESS" <?php echo ($user['occupation'] == 'BUSINESS') ? 'selected' : ''; ?>>Business</option>
                                    <option value="PRIVATE JOB" <?php echo ($user['occupation'] == 'PRIVATE JOB') ? 'selected' : ''; ?>>Private Job</option>
                                    <option value="OTHER" <?php echo ($user['occupation'] == 'OTHER') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Address</label>
                                <input class="form-control" type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Taluko</label>
                                <select class="form-control" name="taluko" required>
                                    <option value="Modasa" <?php echo (trim($user['taluko']) === 'Modasa') ? 'selected' : ''; ?>>Modasa</option>
                                    <option value="Dhansura" <?php echo (trim($user['taluko']) === 'Dhansura') ? 'selected' : ''; ?>>Dhansura</option>
                                    <option value="Bayad" <?php echo (trim($user['taluko']) === 'Bayad') ? 'selected' : ''; ?>>Bayad</option>
                                    <option value="Malpur" <?php echo (trim($user['taluko']) === 'Malpur') ? 'selected' : ''; ?>>Malpur</option>
                                    <option value="Megharaj" <?php echo (trim($user['taluko']) === 'Megharaj') ? 'selected' : ''; ?>>Megharaj</option>
                                    <option value="Bhiloda" <?php echo (trim($user['taluko']) === 'Bhiloda') ? 'selected' : ''; ?>>Bhiloda</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Contact</label>
                                <input class="form-control" type="text" name="contact" value="<?php echo htmlspecialchars($user['contact']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Dharmado Amount</label>
                                <input class="form-control" type="number" name="dharmadoamount" value="<?php echo htmlspecialchars($user['dharmadoamount']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Starting Date</label>
                                <input class="form-control" type="date" name="startingdate" value="<?php echo htmlspecialchars($user['startingdate']); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </form>
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

</html>

