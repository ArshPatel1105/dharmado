<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php'); // Redirect to login if not logged in
    exit();
}

include '../dbconnect.php';

$currentMonth = date('F'); // e.g., "September"
$currentYear = date('Y');  // e.g., "2023"
$tableName = "remaining_dharmado_" . strtolower($currentMonth) . "_" . $currentYear; // e.g., "remaining_dharmado_september_2023";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $id = strtoupper(mysqli_real_escape_string($conn, $_POST['id']));
    $name = strtoupper(mysqli_real_escape_string($conn, $_POST['name']));
    $gender = strtoupper(mysqli_real_escape_string($conn, $_POST['gender']));
    $contact = strtoupper(mysqli_real_escape_string($conn, $_POST['contact']));
    $dharmadoamount = strtoupper(mysqli_real_escape_string($conn, $_POST['dharmadoamount']));
    $occupation = strtoupper(mysqli_real_escape_string($conn, $_POST['occupation']));
    $book_receipt = strtoupper(mysqli_real_escape_string($conn, $_POST['book_receipt']));
    $dharmado_process = isset($_POST['dharmado_process']) ? 1 : 0;
    $year = strtoupper(mysqli_real_escape_string($conn, $_POST['year']));
    $month = strtoupper(mysqli_real_escape_string($conn, $_POST['month']));
    $taluko = strtoupper(mysqli_real_escape_string($conn, $_POST['taluko']));

    if (empty($taluko)) {
        echo "<div class='alert alert-danger'>Error: Taluko is required.</div>";
    } else {
        // Check if the user has already submitted a form for the same month and year
        $check_query = "SELECT id FROM monthly_report WHERE user_id = '$id' AND year = '$year' AND month = '$month'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            echo "<div class='alert alert-danger'>Error: This user has already submitted a report for the selected month and year.</div>";
        } else {
            // Insert into monthly_report table
            $insert_query = "INSERT INTO monthly_report (user_id, name, gender, contact, dharmadoamount, occupation, taluko, book_receipt, dharmado_process, year, month) 
                             VALUES ('$id', '$name', '$gender', '$contact', '$dharmadoamount', '$occupation', '$taluko', '$book_receipt', '$dharmado_process', '$year', '$month')";

            if (mysqli_query($conn, $insert_query)) {
                // Update `dharmado_status` to 'Done' in the dynamic table
                $updateStatusQuery = "UPDATE $tableName SET dharmado_status = 'Done' WHERE user_id = '$id'";
                mysqli_query($conn, $updateStatusQuery);

                echo "<div class='alert alert-success'>Details saved successfully! User status updated to 'Done'.</div>";
            } else {
                echo "<div class='alert alert-danger'>Error saving details: " . mysqli_error($conn) . "</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monthly Report - BDMS</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link href="../vendor/metisMenu/metisMenu.min.css" rel="stylesheet">
    <link href="../dist/css/sb-admin-2.css" rel="stylesheet">
    <link href="../vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="../icofont/icofont.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
</head>

<body>
    <div id="wrapper">
        <?php include 'includes/nav.php'; ?>
        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Monthly Report</h1>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            Select Year, Month, and Username
                        </div>
                        <div class="panel-body">
                            <form method="GET" action="">
                                <div class="form-group">
                                    <label for="year">Select Year:</label>
                                    <select name="year" id="year" class="form-control" required>
                                        <option value="">-- Select Year --</option>
                                        <?php
                                        $currentYear = date('Y');
                                        for ($y = $currentYear; $y >= $currentYear - 10; $y--) {
                                            echo "<option value='$y'>$y</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="month">Select Month:</label>
                                    <select name="month" id="month" class="form-control" required>
                                        <option value="">-- Select Month --</option>
                                        <?php
                                        for ($m = 1; $m <= 12; $m++) {
                                            $monthName = date('F', mktime(0, 0, 0, $m, 1));
                                            echo "<option value='$monthName'>$monthName</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="search">Search by Username:</label>
                                    <select name="search" id="search" class="form-control select2" required>
                                        <option value="">-- Select Username --</option>
                                        <?php
                                        $user_query = "SELECT DISTINCT id, name FROM user ORDER BY name ASC";
                                        $user_result = mysqli_query($conn, $user_query);
                                        if ($user_result) {
                                            while ($user_row = mysqli_fetch_assoc($user_result)) {
                                                echo "<option value='" . htmlspecialchars($user_row['id']) . "'>" . htmlspecialchars($user_row['name']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Search</button>
                            </form>
                            <br>
                            <?php
                            if (isset($_GET['search']) && !empty($_GET['search'])) {
                                $user_id = mysqli_real_escape_string($conn, $_GET['search']);
                                $year = mysqli_real_escape_string($conn, $_GET['year']);
                                $month = mysqli_real_escape_string($conn, $_GET['month']);

                                $query = "SELECT id, name, gender, contact, dharmadoamount, occupation, taluko FROM user WHERE id = '$user_id'";
                                $result = mysqli_query($conn, $query);

                                if ($result && mysqli_num_rows($result) > 0) {
                                    $user = mysqli_fetch_assoc($result);
                                    ?>
                                    <form method="POST" action="">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Field</th>
                                                    <th>Value</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>ID</td>
                                                    <td><input type="text" name="id" class="form-control" value="<?php echo htmlspecialchars($user['id']); ?>" readonly></td>
                                                </tr>
                                                <tr>
                                                    <td>Name</td>
                                                    <td><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" readonly></td>
                                                </tr>
                                                <tr>
                                                    <td>Gender</td>
                                                    <td><input type="text" name="gender" class="form-control" value="<?php echo htmlspecialchars($user['gender']); ?>" readonly></td>
                                                </tr>
                                                <tr>
                                                    <td>Contact</td>
                                                    <td><input type="text" name="contact" class="form-control" value="<?php echo htmlspecialchars($user['contact']); ?>" readonly></td>
                                                </tr>
                                                <tr>
                                                    <td>Occupation</td>
                                                    <td><input type="text" name="occupation" class="form-control" value="<?php echo htmlspecialchars($user['occupation']); ?>" readonly></td>
                                                </tr>
                                                <tr>
                                                    <td>Taluko</td>
                                                    <td><input type="text" name="taluko" class="form-control" value="<?php echo htmlspecialchars($user['taluko']); ?>" readonly></td>
                                                </tr>
                                                <!-- Editable Dharmado Amount Field -->
                                                <tr>
                                                    <td>Dharmado Amount</td>
                                                    <td><input type="number" name="dharmadoamount" class="form-control" value="<?php echo htmlspecialchars($user['dharmadoamount']); ?>" placeholder="Enter Dharmado Amount"></td>
                                                </tr>
                                                <tr>
                                                    <td>Book/Receipt No</td>
                                                    <td><input type="text" name="book_receipt" class="form-control" placeholder="Enter Book/Receipt No"></td>
                                                </tr>
                                                <tr>
                                                    <td>Dharmado Process</td>
                                                    <td><input type="checkbox" name="dharmado_process" value="1"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <input type="hidden" name="year" value="<?php echo htmlspecialchars($year); ?>">
                                        <input type="hidden" name="month" value="<?php echo htmlspecialchars($month); ?>">
                                        <button type="submit" name="update" class="btn btn-success">Save</button>
                                    </form>
                                    <?php
                                } else {
                                    echo "<div class='alert alert-danger'>No user found with the selected ID.</div>";
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="fixed-footer">
        <p>&copy; <?php echo date("Y"); ?>: Developed By BAPS MODASA</p>
    </footer>
    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="../vendor/metisMenu/metisMenu.min.js"></script>
    <script src="../dist/js/sb-admin-2.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Search and select a username",
                allowClear: true
            });
        });
    </script>
</body>

</html>
