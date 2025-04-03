<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php'); // Redirect to login if not logged in
    exit();
}

include '../dbconnect.php';

// Handle delete action
if (isset($_GET['delete_id'])) {
    $user_id = mysqli_real_escape_string($conn, $_GET['delete_id']);

    // Delete related rows from the `monthly_report` table
    $deleteMonthlyReportQuery = "DELETE FROM monthly_report WHERE user_id = '$user_id'";
    mysqli_query($conn, $deleteMonthlyReportQuery);

    // Delete user from all dynamically created "Remaining Dharmado" tables
    $showTablesQuery = "SHOW TABLES LIKE 'remaining_dharmado_%'";
    $tablesResult = mysqli_query($conn, $showTablesQuery);
    while ($tableRow = mysqli_fetch_row($tablesResult)) {
        $tableName = $tableRow[0];
        $deleteFromDynamicTableQuery = "DELETE FROM $tableName WHERE user_id = '$user_id'";
        mysqli_query($conn, $deleteFromDynamicTableQuery);
    }

    // Delete user from the `user` table
    $deleteUserQuery = "DELETE FROM user WHERE id = '$user_id'";
    if (mysqli_query($conn, $deleteUserQuery)) {
        echo "<script>alert('User and all related details deleted successfully.'); window.location.href='manageblood.php';</script>";
    } else {
        echo "<script>alert('Error deleting user: " . mysqli_error($conn) . "'); window.location.href='manageblood.php';</script>";
    }
}

// Handle edit action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
    $id = mysqli_real_escape_string($conn, $_POST['edit_id']);
    $name = strtoupper(mysqli_real_escape_string($conn, $_POST['name']));
    $gender = strtoupper(mysqli_real_escape_string($conn, $_POST['gender']));
    $dob = strtoupper(mysqli_real_escape_string($conn, $_POST['dob']));
    $occupation = strtoupper(mysqli_real_escape_string($conn, $_POST['occupation']));
    $address = strtoupper(mysqli_real_escape_string($conn, $_POST['address']));
    $contact = strtoupper(mysqli_real_escape_string($conn, $_POST['contact']));
    $dharmadoamount = strtoupper(mysqli_real_escape_string($conn, $_POST['dharmadoamount']));
    $startingdate = strtoupper(mysqli_real_escape_string($conn, $_POST['startingdate']));
    $taluko = strtoupper(mysqli_real_escape_string($conn, $_POST['taluko']));
    $photo = isset($_FILES['photo']['tmp_name']) ? $_FILES['photo']['tmp_name'] : null;

    // Validate contact number
    if (!preg_match('/^\d{10}$/', $contact)) {
        echo "<script>alert('Error: Contact number must be exactly 10 digits.'); window.location.href='manageblood.php';</script>";
    } else {
        // Handle photo content
        $path = $photo; // Replace with the actual path or logic to determine the file path
        if (!empty($path) && file_exists($path)) {
            $content = file_get_contents($path);
        } else {
            $content = ''; // Handle the case where the file path is invalid or empty
            error_log("Invalid or empty file path: $path");
        }

        // Update user data in the `user` table
        $updateUserQuery = "UPDATE user SET 
                            name = '$name', 
                            gender = '$gender', 
                            dob = '$dob', 
                            occupation = '$occupation', 
                            address = '$address', 
                            contact = '$contact', 
                            dharmadoamount = '$dharmadoamount', 
                            startingdate = '$startingdate',
                            taluko = '$taluko'" . ($content ? ", photo = '" . mysqli_real_escape_string($conn, $content) . "'" : "") . " 
                            WHERE id = '$id'";
        if (mysqli_query($conn, $updateUserQuery)) {
            // Update user data in the `monthly_report` table
            $updateMonthlyReportQuery = "UPDATE monthly_report SET 
                                         name = '$name', 
                                         contact = '$contact', 
                                         dharmadoamount = '$dharmadoamount', 
                                         occupation = '$occupation', 
                                         taluko = '$taluko' 
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

            // Update user data in all dynamically created "Complete Dharmado" tables
            $showTablesQuery = "SHOW TABLES LIKE 'complete_dharmado_%'";
            $tablesResult = mysqli_query($conn, $showTablesQuery);
            while ($tableRow = mysqli_fetch_row($tablesResult)) {
                $tableName = $tableRow[0];
                $updateCompleteDharmadoQuery = "UPDATE $tableName SET 
                                                name = '$name', 
                                                contact = '$contact', 
                                                dharmadoamount = '$dharmadoamount', 
                                                occupation = '$occupation', 
                                                taluko = '$taluko' 
                                                WHERE user_id = '$id'";
                mysqli_query($conn, $updateCompleteDharmadoQuery);
            }

            echo "<script>alert('User details updated successfully!'); window.location.href='manageblood.php';</script>";
        } else {
            echo "<script>alert('Error updating user details: " . mysqli_error($conn) . "'); window.location.href='manageblood.php';</script>";
        }
    }
}

// Fetch all users or filter by User ID or Username
$filterQuery = "SELECT * FROM user";
if ($_SERVER['REQUEST_METHOD'] == 'GET' && (isset($_GET['user_id']) || isset($_GET['username']))) {
    $user_id = isset($_GET['user_id']) ? mysqli_real_escape_string($conn, $_GET['user_id']) : null;
    $username = isset($_GET['username']) ? mysqli_real_escape_string($conn, $_GET['username']) : null;

    if (!empty($user_id)) {
        $filterQuery .= " WHERE id = '$user_id'";
    } elseif (!empty($username)) {
        $filterQuery .= " WHERE name LIKE '%$username%'";
    }
}
$result = mysqli_query($conn, $filterQuery);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manage User Details - BDMS</title>
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
                        <h3 class="page-header">Manage User Details</h3>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <form method="GET" action="" class="form-inline">
                            <div class="form-group">
                                <input type="text" name="user_id" class="form-control" placeholder="Search by User ID">
                            </div>
                            <div class="form-group">
                                <input type="text" name="username" class="form-control" placeholder="Search by Username">
                            </div>
                            <button type="submit" class="btn btn-primary">Search</button>
                            <a href="manageblood.php" class="btn btn-default">Reset</a>
                        </form>
                        <br>
                        <div class="panel panel-default">
                            <div class="panel-heading">Total Records of Available Users</div>
                            <div class="panel-body" style="max-height: 500px; overflow-y: auto;">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>User ID</th>
                                                <th>Photo</th>
                                                <th>Full Name</th>
                                                <th>Gender</th>
                                                <th>Dharmado Amount</th>
                                                <th>Starting Date</th>
                                                <th>Occupation</th>
                                                <th>Contact</th>
                                                <th>D.O.B</th>
                                                <th>Taluka</th>
                                                <th>Address</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($result && mysqli_num_rows($result) > 0) {
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo "<tr>";
                                                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                                    echo "<td>";
                                                    if (!empty($row['photo'])) {
                                                        echo "<img src='data:image/jpeg;base64," . base64_encode($row['photo']) . "' alt='User Photo' style='width: 50px; height: 50px; object-fit: cover; border: 1px solid #ddd; border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);'>";
                                                    } else {
                                                        echo "<div style='width: 50px; height: 50px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #6c757d; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);'>N/A</div>";
                                                    }
                                                    echo "</td>";
                                                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['dharmadoamount']) . "</td>";
                                                    echo "<td>" . date('d-m-Y', strtotime($row['startingdate'])) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['occupation']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['contact']) . "</td>";
                                                    echo "<td>" . date('d-m-Y', strtotime($row['dob'])) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['taluko']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['address']) . "</td>";
                                                    echo "<td>
                                                            <a href='#' class='btn btn-primary btn-sm' data-toggle='modal' data-target='#editModal" . $row['id'] . "'>Edit</a>
                                                            <a href='manageblood.php?delete_id=" . $row['id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this user?\")'>Delete</a>
                                                          </td>";
                                                    echo "</tr>";

                                                    // Edit Modal
                                                    echo "<div class='modal fade' id='editModal" . $row['id'] . "' tabindex='-1' role='dialog' aria-labelledby='editModalLabel" . $row['id'] . "' aria-hidden='true'>
                                                            <div class='modal-dialog' role='document'>
                                                                <div class='modal-content'>
                                                                    <div class='modal-header'>
                                                                        <h5 class='modal-title' id='editModalLabel" . $row['id'] . "'>Edit User Details</h5>
                                                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                                            <span aria-hidden='true'>&times;</span>
                                                                        </button>
                                                                    </div>
                                                                    <form method='POST' action='' enctype='multipart/form-data'>
                                                                        <div class='modal-body'>
                                                                            <input type='hidden' name='edit_id' value='" . $row['id'] . "'>
                                                                            <div class='form-group'>
                                                                                <label>Full Name</label>
                                                                                <input class='form-control' type='text' name='name' value='" . htmlspecialchars($row['name']) . "' required>
                                                                            </div>
                                                                            <div class='form-group'>
                                                                                <label>Gender</label>
                                                                                <input class='form-control' type='text' name='gender' value='" . htmlspecialchars($row['gender']) . "' required>
                                                                            </div>
                                                                            <div class='form-group'>
                                                                                <label>Date of Birth</label>
                                                                                <input class='form-control' type='date' name='dob' value='" . htmlspecialchars($row['dob']) . "' required>
                                                                            </div>
                                                                            <div class='form-group'>
                                                                                <label>Occupation</label>
                                                                                <select class='form-control' name='occupation' required>
                                                                                    <option value='TEACHER' " . ($row['occupation'] == 'TEACHER' ? 'selected' : '') . ">Teacher</option>
                                                                                    <option value='DOCTOR' " . ($row['occupation'] == 'DOCTOR' ? 'selected' : '') . ">Doctor</option>
                                                                                    <option value='FARMER' " . ($row['occupation'] == 'FARMER' ? 'selected' : '') . ">Farmer</option>
                                                                                    <option value='BUSINESS' " . ($row['occupation'] == 'BUSINESS' ? 'selected' : '') . ">Business</option>
                                                                                    <option value='PRIVATE JOB' " . ($row['occupation'] == 'PRIVATE JOB' ? 'selected' : '') . ">Private Job</option>
                                                                                    <option value='OTHER' " . ($row['occupation'] == 'OTHER' ? 'selected' : '') . ">Other</option>
                                                                                </select>
                                                                            </div>
                                                                            <div class='form-group'>
                                                                                <label>Address</label>
                                                                                <input class='form-control' type='text' name='address' value='" . htmlspecialchars($row['address']) . "' required>
                                                                            </div>
                                                                            <div class='form-group'>
                                                                                <label>Taluko</label>
                                                                                <select class='form-control' name='taluko' required>
                                                                                    <option value='Modasa' " . ($row['taluko'] == 'Modasa' ? 'selected' : ''). ">Modasa</option>
                                                                                    <option value='Dhansura' " . ($row['taluko'] == 'Dhansura' ? 'selected' : '').">Dhansura</option>
                                                                                    <option value='Bayad' " .  ($row['taluko'] == 'Bayad' ? 'selected' : ''). " >Bayad</option>
                                                                                    <option value='Malpur' " . ($row['taluko'] == 'Malpur' ? 'selected' : ''). " >Malpur</option>
                                                                                    <option value='Megharaj' " . ($row['taluko'] == 'Megharaj' ? 'selected' : ''). ">Megharaj</option>
                                                                                    <option value='Bhiloda'  " . ($row['taluko'] == 'Bhiloda' ? 'selected' : ''). " >Bhiloda</option>
                                                                                    <option value='OTHER'  " . ($row['taluko'] == 'OTHER' ? 'selected' : ''). " >Other</option>
                                                                                </select>
                                                                            </div>
                                                                            <div class='form-group'>
                                                                                <label>Contact</label>
                                                                                <input class='form-control' type='text' name='contact' value='" . htmlspecialchars($row['contact']) . "' required>
                                                                            </div>
                                                                            <div class='form-group'>
                                                                                <label>Dharmado Amount</label>
                                                                                <input class='form-control' type='number' name='dharmadoamount' value='" . htmlspecialchars($row['dharmadoamount']) . "' required>
                                                                            </div>
                                                                            <div class='form-group'>
                                                                                <label>Starting Date</label>
                                                                                <input class='form-control' type='date' name='startingdate' value='" . htmlspecialchars($row['startingdate']) . "' required>
                                                                            </div>
                                                                            <div class='form-group'>
                                                                                <label>Photo</label>
                                                                                <input class='form-control' type='file' name='photo' accept='image/*'>
                                                                                <small>Leave blank to keep the current photo.</small>
                                                                            </div>
                                                                        </div>
                                                                        <div class='modal-footer'>
                                                                            <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                                                            <button type='submit' class='btn btn-primary'>Save Changes</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                          </div>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='12' class='text-center'>No records found</td></tr>";
                                            }
                                            ?>
                                        </tbody>
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
    .fixed-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 35px;
        background-color: #424558;
        text-align: center;
        color: #CCC;
        z-index: 1000;
    }

    .fixed-footer p {
        padding: 10.5px;
        margin: 0px;
        line-height: 100%;
    }

    body {
        margin-bottom: 50px; /* Add margin to prevent content overlap with footer */
    }
</style>
</html>
