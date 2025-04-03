<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php'); // Redirect to login if not logged in
    exit();
}

include '../dbconnect.php';

$userDetails = null;
$monthlyReports = [];
$error = "";
$showModal = false;

// Function to send WhatsApp message using an API
function sendWhatsAppMessage($contact, $message) {
    $contact = preg_replace('/\D/', '', $contact); // Remove non-numeric characters
    if (strlen($contact) == 10) {
        $contact = '91' . $contact; // Add country code for India
    }

    // Example API URL for sending WhatsApp messages (replace with actual API details)
    $apiUrl = "https://api.whatsapp.com/send?phone=$contact&text=" . urlencode($message);

    // Log the WhatsApp URL for debugging
    error_log("WhatsApp URL: $apiUrl");

    // Uncomment the following lines if using a real API for sending messages
    // $ch = curl_init();
    // curl_setopt($ch, CURLOPT_URL, $apiUrl);
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // $response = curl_exec($ch);
    // curl_close($ch);
    // error_log("WhatsApp API Response: $response");
}

// Handle search by user ID or username
if ($_SERVER['REQUEST_METHOD'] == 'GET' && (isset($_GET['user_id']) || isset($_GET['username']))) {
    $user_id = isset($_GET['user_id']) ? mysqli_real_escape_string($conn, $_GET['user_id']) : null;
    $username_id = isset($_GET['username']) ? mysqli_real_escape_string($conn, $_GET['username']) : null;

    // Determine which search parameter to use
    if (!empty($user_id)) {
        $userQuery = "SELECT * FROM user WHERE id = '$user_id'";
    } elseif (!empty($username_id)) {
        $userQuery = "SELECT * FROM user WHERE id = '$username_id'";
    } else {
        $error = "Please provide either User ID or Username.";
    }

    if (isset($userQuery)) {
        $userResult = mysqli_query($conn, $userQuery);

        if ($userResult) {
            if (mysqli_num_rows($userResult) > 0) {
                $userDetails = mysqli_fetch_assoc($userResult);

                // Fetch monthly reports for the user
                $monthlyReportQuery = "SELECT * FROM monthly_report WHERE user_id = '{$userDetails['id']}' ORDER BY date ASC";
                $monthlyReportResult = mysqli_query($conn, $monthlyReportQuery);

                if ($monthlyReportResult) {
                    while ($row = mysqli_fetch_assoc($monthlyReportResult)) {
                        $monthlyReports[] = $row;
                    }
                } else {
                    $error = "Error fetching monthly reports: " . mysqli_error($conn);
                }
            } else {
                $error = "User not found.";
            }
        } else {
            $error = "Error fetching user details: " . mysqli_error($conn);
        }
    }
}

// Handle Add Dharmado Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_dharmado'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $dharmadoamount = mysqli_real_escape_string($conn, $_POST['dharmadoamount']);
    $book_receipt = mysqli_real_escape_string($conn, $_POST['book_receipt']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $dharmado_process = isset($_POST['dharmado_process']) ? 1 : 0;

    // Extract month and year from the date
    $month = date('F', strtotime($date));
    $year = date('Y', strtotime($date));

    // Dynamic table names
    $remainingTableName = "remaining_dharmado_" . strtolower($month) . "_" . $year;
    $completeTableName = "complete_dharmado_" . strtolower($month) . "_" . $year;

    // Validate required fields
    if (empty($id) || empty($date) || empty($dharmadoamount) || empty($book_receipt) || empty($payment_method)) {
        $error = "Error: All fields are required.";
        $showModal = true; // Flag to reopen the modal
    } else {
        // Check if a record already exists for the same user, month, and year
        $checkQuery = "SELECT id FROM monthly_report WHERE user_id = '$id' AND MONTHNAME(date) = '$month' AND YEAR(date) = '$year'";
        $checkResult = mysqli_query($conn, $checkQuery);

        if (mysqli_num_rows($checkResult) > 0) {
            echo "<script>
                alert('Error: Dharmado for $month $year has already been submitted. Please change the month and year.');
                window.location.href = 'userprofile.php?user_id=$id';
            </script>";
            exit();
        } else {
            // Fetch user details from the `user` table
            $userQuery = "SELECT name, gender, contact, occupation, taluko FROM user WHERE id = '$id'";
            $userResult = mysqli_query($conn, $userQuery);

            if ($userResult && mysqli_num_rows($userResult) > 0) {
                $userDetails = mysqli_fetch_assoc($userResult);

                // Insert a new record into `monthly_report`
                $insertQuery = "INSERT INTO monthly_report (user_id, name, gender, contact, dharmadoamount, occupation, taluko, book_receipt, payment_method, dharmado_process, date) 
                                VALUES ('$id', '{$userDetails['name']}', '{$userDetails['gender']}', '{$userDetails['contact']}', '$dharmadoamount', '{$userDetails['occupation']}', '{$userDetails['taluko']}', '$book_receipt', '$payment_method', '$dharmado_process', '$date')";

                if (mysqli_query($conn, $insertQuery)) {
                    // Ensure the `remaining_dharmado` table exists
                    $createRemainingTableQuery = "CREATE TABLE IF NOT EXISTS $remainingTableName (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        name VARCHAR(255) NOT NULL,
                        contact VARCHAR(15) NOT NULL,
                        dharmadoamount DECIMAL(10, 2) NOT NULL,
                        occupation VARCHAR(255),
                        taluko VARCHAR(255),
                        dharmado_process TINYINT(1) DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )";
                    mysqli_query($conn, $createRemainingTableQuery);

                    // Populate the `remaining_dharmado` table with users who have not submitted Dharmado for the given month and year
                    $populateRemainingQuery = "
                        INSERT INTO $remainingTableName (user_id, name, contact, dharmadoamount, occupation, taluko, dharmado_process)
                        SELECT id, name, contact, dharmadoamount, occupation, taluko, 0
                        FROM user
                        WHERE id NOT IN (
                            SELECT user_id FROM monthly_report WHERE MONTHNAME(date) = '$month' AND YEAR(date) = '$year'
                        )
                    ";
                    mysqli_query($conn, $populateRemainingQuery);

                    // Ensure the `complete_dharmado` table exists
                    $createCompleteTableQuery = "CREATE TABLE IF NOT EXISTS $completeTableName (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        name VARCHAR(255) NOT NULL,
                        contact VARCHAR(15) NOT NULL,
                        dharmadoamount DECIMAL(10, 2) NOT NULL,
                        occupation VARCHAR(255),
                        taluko VARCHAR(255),
                        dharmado_process TINYINT(1) DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )";
                    mysqli_query($conn, $createCompleteTableQuery);

                    // Insert the user into the `complete_dharmado` table
                    $insertCompleteQuery = "INSERT INTO $completeTableName (user_id, name, contact, dharmadoamount, occupation, taluko, dharmado_process)
                                            VALUES ('$id', '{$userDetails['name']}', '{$userDetails['contact']}', '$dharmadoamount', '{$userDetails['occupation']}', '{$userDetails['taluko']}', 1)";
                    mysqli_query($conn, $insertCompleteQuery);

                    // Remove the user from the `remaining_dharmado` table
                    $deleteRemainingQuery = "DELETE FROM $remainingTableName WHERE user_id = '$id'";
                    mysqli_query($conn, $deleteRemainingQuery);

                    // Send WhatsApp message
                    $contact = $userDetails['contact'];
                    $name = $userDetails['name'];
                    $message = "Dear $name, thank you for submitting your Dharmado of ₹$dharmadoamount. Your contribution is greatly appreciated!";
                    sendWhatsAppMessage($contact, $message);

                    echo "<script>
                        alert('Dharmado added successfully and user moved to Complete Dharmado table!');
                        window.location.href = 'userprofile.php?user_id=$id';
                    </script>";
                } else {
                    $error = "Error adding Dharmado: " . mysqli_error($conn);
                    $showModal = true; // Flag to reopen the modal
                }
            } else {
                $error = "Error: User not found.";
                $showModal = true; // Flag to reopen the modal
            }
        }
    }
}

// Check if the user has continuously not submitted Dharmado for the last two or more months
$status = "Regular";
if ($userDetails) {
    $currentMonth = date('n'); // Numeric representation of the current month (1-12)
    $currentYear = date('Y');

    $missedMonths = 0;

    // Check the last 12 months for continuous non-submission
    for ($i = 1; $i <= 12; $i++) {
        $monthToCheck = $currentMonth - $i;
        $yearToCheck = $currentYear;

        if ($monthToCheck <= 0) {
            $monthToCheck += 12;
            $yearToCheck--;
        }

        $monthName = date('F', mktime(0, 0, 0, $monthToCheck, 1));
        $query = "SELECT id FROM monthly_report 
                  WHERE user_id = '{$userDetails['id']}' 
                  AND MONTHNAME(date) = '$monthName' 
                  AND YEAR(date) = '$yearToCheck'";
        $result = mysqli_query($conn, $query);

        if ($result) {
            if (mysqli_num_rows($result) == 0) {
                $missedMonths++;
            } else {
                break; // Stop checking if a submission is found
            }
        } else {
            $error = "Error executing query: " . mysqli_error($conn);
            break; // Exit the loop if the query fails
        }
    }

    if ($missedMonths >= 2) {
        $status = "Irregular";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Profile - BDMS</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link href="../vendor/metisMenu/metisMenu.min.css" rel="stylesheet">
    <link href="../dist/css/sb-admin-2.css" rel="stylesheet">
    <link href="../vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <style>
        .progress-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .progress {
            width: 100%;
            height: 20px;
            margin: 0;
            border-radius: 5px;
        }

        .progress-bar {
            font-weight: bold;
            color: black;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .progress-text {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }

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

        .panel-body {
            max-height: calc(100vh - 200px); /* Adjust height to fit within the viewport */
            overflow-y: auto; /* Enable scrolling for the panel body */
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include 'includes/nav.php'; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12 text-center">
                        <form method="GET" action="" class="form-inline">
                            <div class="form-group">
                                <input type="text" name="user_id" id="user_id" class="form-control input-sm" placeholder="Enter User ID">
                            </div>
                            <div class="form-group">
                                <select name="username" id="username" class="form-control input-sm select2" style="width: 200px;">
                                    <option value="">-- Select Username --</option>
                                    <?php
                                    $userQuery = "SELECT id, name FROM user ORDER BY name ASC";
                                    $userResult = mysqli_query($conn, $userQuery);
                                    if ($userResult) {
                                        while ($userRow = mysqli_fetch_assoc($userResult)) {
                                            echo "<option value='" . htmlspecialchars($userRow['id']) . "'>" . htmlspecialchars($userRow['name']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Search</button>
                        </form>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <br>
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success">
                                <?php 
                                echo $_SESSION['success_message']; 
                                unset($_SESSION['success_message']); // Clear the message after displaying it
                                ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($userDetails && isset($userDetails['id'])): ?>
                            <div class="panel panel-default" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                                <div class="panel-heading" style="display: flex; justify-content: space-between; align-items: center;">
                                    <span>User Details - ID: <?php echo htmlspecialchars($userDetails['id']); ?></span>
                                    <?php if ($status === "Irregular"): ?>
                                        <span style="color: white; background-color: red; padding: 5px 10px; border-radius: 5px;">Irregular</span>
                                    <?php else: ?>
                                        <span style="color: white; background-color: green; padding: 5px 10px; border-radius: 5px;">Regular</span>
                                    <?php endif; ?>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <!-- Profile Photo Section -->
                                        <div class="col-md-4 text-center">
                                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; margin-top: 20px;">
                                                <?php if (!empty($userDetails['photo'])): ?>
                                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($userDetails['photo']); ?>" alt="Profile Photo" style="width: 150px; height: 200px; object-fit: cover; border: 1px solid #ddd; box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.3); border-radius: 10px;">
                                                <?php else: ?>
                                                    <div style="width: 150px; height: 200px; display: flex; justify-content: center; align-items: center; border: 1px solid #ddd; background-color: #f8f9fa; color: #6c757d; font-size: 14px; text-align: center; box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.3); border-radius: 10px;">
                                                        Photo Not Available
                                                    </div>
                                                <?php endif; ?>
                                                <!-- Add Dharmado Button -->
                                                <button class="btn btn-success btn-sm" style="margin-top: 30px;" data-toggle="modal" data-target="#addDharmadoModal">Add Dharmado</button>
                                            </div>
                                        </div>
                                        <!-- User Details Section -->
                                        <div class="col-md-8">
                                            <table class="table table-bordered">
                                                <tr>
                                                    <th>User ID</th>
                                                    <td><?php echo htmlspecialchars($userDetails['id']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Name</th>
                                                    <td><?php echo htmlspecialchars($userDetails['name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Gender</th>
                                                    <td><?php echo htmlspecialchars($userDetails['gender']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Date of Birth</th>
                                                    <td><?php echo date('d-m-Y', strtotime($userDetails['dob'])); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Occupation</th>
                                                    <td><?php echo htmlspecialchars($userDetails['occupation']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Address</th>
                                                    <td><?php echo htmlspecialchars($userDetails['address']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Taluka</th>
                                                    <td><?php echo htmlspecialchars($userDetails['taluko']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Contact</th>
                                                    <td><?php echo htmlspecialchars($userDetails['contact']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Dharmado Amount</th>
                                                    <td><?php echo htmlspecialchars($userDetails['dharmadoamount']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Starting Date</th>
                                                    <td><?php echo date('d-m-Y', strtotime($userDetails['startingdate'])); ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Add Dharmado Modal -->
                            <div class="modal fade" id="addDharmadoModal" tabindex="-1" role="dialog" aria-labelledby="addDharmadoModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <form method="POST" action="">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="addDharmadoModalLabel">Add Dharmado</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($userDetails['id'] ?? ''); ?>">
                                                <div class="form-group">
                                                    <label>Date <span style="color: red;">*</span></label>
                                                    <input type="date" class="form-control" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Dharmado Amount (Default: <?php echo htmlspecialchars($userDetails['dharmadoamount'] ?? '0'); ?>)</label>
                                                    <input type="number" class="form-control" name="dharmadoamount" value="<?php echo htmlspecialchars($userDetails['dharmadoamount'] ?? ''); ?>" placeholder="Enter Dharmado Amount" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Book/Receipt No <span style="color: red;">*</span></label>
                                                    <input type="text" class="form-control" name="book_receipt" placeholder="Enter Book/Receipt No" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Payment Method <span style="color: red;">*</span></label>
                                                    <select class="form-control" name="payment_method" required>
                                                        <option value="Online">Online</option>
                                                        <option value="Cash">Cash</option>
                                                        <option value="Check">Check</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Dharmado Process <span style="color: red;">*</span></label>
                                                    <input type="checkbox" name="dharmado_process" value="1" style="width: 20px; height: 20px;" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                <button type="submit" name="add_dharmado" class="btn btn-primary">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    Monthly Report
                                </div>
                                <div class="panel-body" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Year</th>
                                                <?php
                                                // Display all months as table headers
                                                $months = [
                                                    'January', 'February', 'March', 'April', 'May', 'June',
                                                    'July', 'August', 'September', 'October', 'November', 'December'
                                                ];
                                                foreach ($months as $month) {
                                                    echo "<th>$month</th>";
                                                }
                                                ?>
                                                <th>Total Completed</th>
                                                <th>Total Submitted Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Group monthly reports by year
                                            $reportsByYear = [];
                                            foreach ($monthlyReports as $report) {
                                                $year = date('Y', strtotime($report['date']));
                                                $month = date('F', strtotime($report['date']));
                                                $reportsByYear[$year][$month] = $report;
                                            }

                                            // Sort years in descending order
                                            krsort($reportsByYear);

                                            foreach ($reportsByYear as $year => $reports) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($year) . "</td>";

                                                $totalSubmittedAmount = 0;
                                                $totalCompleted = 0;

                                                foreach ($months as $month) {
                                                    if (isset($reports[$month])) {
                                                        $report = $reports[$month];
                                                        $totalSubmittedAmount += $report['dharmadoamount'];
                                                        $totalCompleted++;

                                                        echo "<td>
                                                                <div style='text-align: center;'>
                                                                    <div>" . htmlspecialchars($report['dharmadoamount']) . "</div>
                                                                    <div><i class='fa fa-check' style='color: green;'></i></div>
                                                                    <div>
                                                                        <a href='#' data-toggle='modal' data-target='#dharmadoDetailsModal" . $report['id'] . "'>
                                                                            <i class='fa fa-eye' style='color: blue;'></i>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                              </td>";

                                                        // Modal for showing details
                                                        echo "<div class='modal fade' id='dharmadoDetailsModal" . $report['id'] . "' tabindex='-1' role='dialog' aria-labelledby='dharmadoDetailsModalLabel" . $report['id'] . "' aria-hidden='true'>
                                                                <div class='modal-dialog' role='document'>
                                                                    <div class='modal-content'>
                                                                        <div class='modal-header'>
                                                                            <h5 class='modal-title' id='dharmadoDetailsModalLabel" . $report['id'] . "'>Dharmado Details</h5>
                                                                            <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                                                <span aria-hidden='true'>&times;</span>
                                                                            </button>
                                                                        </div>
                                                                        <div class='modal-body'>
                                                                            <p><strong>Date:</strong> " . htmlspecialchars(date('d-m-Y', strtotime($report['date']))) . "</p>
                                                                            <p><strong>Amount:</strong> " . htmlspecialchars($report['dharmadoamount']) . "</p>
                                                                            <p><strong>Receipt No:</strong> " . htmlspecialchars($report['book_receipt']) . "</p>
                                                                            <p><strong>Payment Method:</strong> " . htmlspecialchars($report['payment_method']) . "</p>
                                                                        </div>
                                                                        <div class='modal-footer'>
                                                                            <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                              </div>";
                                                    } else {
                                                        echo "<td></td>";
                                                    }
                                                }

                                                // Calculate the expected total amount
                                                $defaultAmount = $userDetails['dharmadoamount'] ?? 0;
                                                $expectedTotalAmount = 12 * $defaultAmount;

                                                // Display total completed and progress bar
                                                $progressPercentage = ($totalCompleted / 12) * 100;
                                                echo "<td>
                                                        <div class='progress-container'>
                                                            <div class='progress-text'>$totalCompleted / 12</div>
                                                            <div class='progress'>
                                                                <div class='progress-bar progress-bar-success' role='progressbar' aria-valuenow='$progressPercentage' aria-valuemin='0' aria-valuemax='100' style='width: $progressPercentage%;'></div>
                                                            </div>
                                                        </div>
                                                      </td>";

                                                // Display total submitted amount
                                                echo "<td>$totalSubmittedAmount / $expectedTotalAmount</td>";
                                                echo "</tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
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
                placeholder: "Select Username",
                allowClear: true
            });

            <?php if (isset($showModal) && $showModal): ?>
                $('#addDharmadoModal').modal('show');
            <?php endif; ?>
        });

        function showThankYouPopup(message) {
            const popup = document.createElement('div');
            popup.style.position = 'fixed';
            popup.style.top = '50%';
            popup.style.left = '50%';
            popup.style.transform = 'translate(-50%, -50%)';
            popup.style.backgroundColor = '#28a745';
            popup.style.color = '#fff';
            popup.style.padding = '20px';
            popup.style.borderRadius = '10px';
            popup.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.2)';
            popup.style.zIndex = '1000';
            popup.style.fontSize = '18px';
            popup.style.textAlign = 'center';
            popup.innerText = message;

            document.body.appendChild(popup);

            setTimeout(() => {
                popup.remove();
            }, 2000); // Show the popup for 2 seconds
        }
    </script>
</body>

</html>
