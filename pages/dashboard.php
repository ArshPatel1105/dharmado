<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php'); // Redirect to login if not logged in
    exit();
}

include '../dbconnect.php';

// Get the current month and year
$currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('F');

// Table names
$completeTableName = "complete_dharmado_" . strtolower($currentMonth) . "_" . $currentYear;
$remainingTableName = "remaining_dharmado_" . strtolower($currentMonth) . "_" . $currentYear;

// Function to create tables if they don't exist
function createMonthlyTables($conn, $completeTableName, $remainingTableName) {
    $createTablesQuery = "
        CREATE TABLE IF NOT EXISTS $completeTableName (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            contact VARCHAR(15) NOT NULL,
            dharmadoamount DECIMAL(10, 2) NOT NULL,
            occupation VARCHAR(255),
            taluko VARCHAR(255),
            dharmado_process TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS $remainingTableName (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            contact VARCHAR(15) NOT NULL,
            dharmadoamount DECIMAL(10, 2) NOT NULL,
            occupation VARCHAR(255),
            taluko VARCHAR(255),
            dharmado_process TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

    $queries = explode(';', $createTablesQuery);
    foreach ($queries as $query) {
        if (trim($query) != '') {
            if (!mysqli_query($conn, $query)) {
                error_log("Error creating table: " . mysqli_error($conn));
                return false;
            }
        }
    }
    return true;
}

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
     $ch = curl_init();
     curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
     curl_close($ch);
     error_log("WhatsApp API Response: $response");
}

// Create tables and populate them when searching
if (isset($_GET['month']) || isset($_GET['year'])) {
    if (createMonthlyTables($conn, $completeTableName, $remainingTableName)) {
        // Populate remaining_dharmado table with all users
        $populateRemainingQuery = "
            INSERT IGNORE INTO $remainingTableName (user_id, name, contact, dharmadoamount, occupation, taluko, dharmado_process)
            SELECT id, name, contact, dharmadoamount, occupation, taluko, 0
            FROM user
            WHERE id NOT IN (
                SELECT user_id FROM monthly_report 
                WHERE MONTHNAME(date) = '$currentMonth' 
                AND YEAR(date) = '$currentYear'
            )
        ";
        if (!mysqli_query($conn, $populateRemainingQuery)) {
            error_log("Error populating remaining table: " . mysqli_error($conn));
        }

        // Move users who have submitted dharmado to complete_dharmado table
        $populateCompleteQuery = "
            INSERT INTO $completeTableName (user_id, name, contact, dharmadoamount, occupation, taluko, dharmado_process)
            SELECT u.id, u.name, u.contact, u.dharmadoamount, u.occupation, u.taluko, 1
            FROM user u
            INNER JOIN monthly_report mr ON u.id = mr.user_id
            WHERE MONTHNAME(mr.date) = '$currentMonth'
            AND YEAR(mr.date) = '$currentYear'
            AND mr.dharmado_process = 1
            AND u.id NOT IN (
                SELECT user_id FROM $completeTableName
            )";
        if (mysqli_query($conn, $populateCompleteQuery)) {
            // Fetch users who were just added to the complete_dharmado table
            $newCompleteUsersQuery = "
                SELECT u.contact, u.name 
                FROM user u
                INNER JOIN $completeTableName c ON u.id = c.user_id
                WHERE c.dharmado_process = 1
            ";
            $newCompleteUsersResult = mysqli_query($conn, $newCompleteUsersQuery);
            while ($user = mysqli_fetch_assoc($newCompleteUsersResult)) {
                $contact = $user['contact'];
                $name = $user['name'];
                $message = "Dear $name, thank you for submitting your Dharmado. Your contribution is greatly appreciated!";
                sendWhatsAppMessage($contact, $message);
            }
        } else {
            error_log("Error populating complete table: " . mysqli_error($conn));
        }
    }
}

// Check if the dynamic tables exist
$checkCompleteTableQuery = "SHOW TABLES LIKE '$completeTableName'";
$checkRemainingTableQuery = "SHOW TABLES LIKE '$remainingTableName'";

$completeTableExists = mysqli_num_rows(mysqli_query($conn, $checkCompleteTableQuery)) > 0;
$remainingTableExists = mysqli_num_rows(mysqli_query($conn, $checkRemainingTableQuery)) > 0;

// Fetch data for "Complete Dharmado" if the table exists
$completeResult = [];
if ($completeTableExists) {
    $completeQuery = "SELECT user_id, name, contact, dharmadoamount, taluko FROM $completeTableName";
    $completeResult = mysqli_query($conn, $completeQuery);
    if (!$completeResult) {
        error_log("Error fetching data from $completeTableName: " . mysqli_error($conn));
    }
}

// Fetch data for "Remaining Dharmado" if the table exists
$remainingResult = [];
if ($remainingTableExists) {
    $remainingQuery = "SELECT user_id, name, contact, dharmadoamount, taluko FROM $remainingTableName";
    $remainingResult = mysqli_query($conn, $remainingQuery);
    if (!$remainingResult) {
        error_log("Error fetching data from $remainingTableName: " . mysqli_error($conn));
    }
}

// Fetch the total number of users
$totalUsersQuery = "SELECT COUNT(*) AS total_users FROM user";
$totalUsersResult = mysqli_query($conn, $totalUsersQuery);
$totalUsers = 0;
if ($totalUsersResult && $row = mysqli_fetch_assoc($totalUsersResult)) {
    $totalUsers = $row['total_users'];
} else {
    error_log("Error fetching total users: " . mysqli_error($conn));
}

// Fetch data for pie charts
$completeDharmadoData = [];
$remainingDharmadoData = [];

if ($completeTableExists) {
    $completeDharmadoQuery = "SELECT taluko, COUNT(*) AS user_count FROM $completeTableName GROUP BY taluko";
    $completeDharmadoResult = mysqli_query($conn, $completeDharmadoQuery);
    while ($row = mysqli_fetch_assoc($completeDharmadoResult)) {
        $completeDharmadoData[] = [
            'taluko' => $row['taluko'],
            'user_count' => $row['user_count']
        ];
    }
}

if ($remainingTableExists) {
    $remainingDharmadoQuery = "SELECT taluko, COUNT(*) AS user_count FROM $remainingTableName GROUP BY taluko";
    $remainingDharmadoResult = mysqli_query($conn, $remainingDharmadoQuery);
    while ($row = mysqli_fetch_assoc($remainingDharmadoResult)) {
        $remainingDharmadoData[] = [
            'taluko' => $row['taluko'],
            'user_count' => $row['user_count']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - BDMS</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link href="../vendor/metisMenu/metisMenu.min.css" rel="stylesheet">
    <link href="../dist/css/sb-admin-2.css" rel="stylesheet">
    <link href="../vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <style>
        .total-users-box {
            margin-top: 20px;
        }

        .scrollable-table {
            max-height: 300px;
            overflow-y: auto;
        }

        .scrollable-table thead th {
            position: sticky;
            top: 0;
            background-color: #f9f9f9;
            z-index: 1;
        }
    </style>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script>
        google.charts.load('current', { packages: ['corechart'] });
        google.charts.setOnLoadCallback(drawCharts);

        function drawCharts() {
            // Complete Dharmado Pie Chart
            var completeData = google.visualization.arrayToDataTable([
                ['Taluko', 'User Count'],
                <?php
                foreach ($completeDharmadoData as $data) {
                    echo "['" . $data['taluko'] . "', " . $data['user_count'] . "],";
                }
                ?>
            ]);

            var completeOptions = {
                title: 'Complete Dharmado',
                pieHole: 0.4
            };

            var completeChart = new google.visualization.PieChart(document.getElementById('completeDharmadoChart'));
            completeChart.draw(completeData, completeOptions);

            // Remaining Dharmado Pie Chart
            var remainingData = google.visualization.arrayToDataTable([
                ['Taluko', 'User Count'],
                <?php
                foreach ($remainingDharmadoData as $data) {
                    echo "['" . $data['taluko'] . "', " . $data['user_count'] . "],";
                }
                ?>
            ]);

            var remainingOptions = {
                title: 'Remaining Dharmado',
                pieHole: 0.4
            };

            var remainingChart = new google.visualization.PieChart(document.getElementById('remainingDharmadoChart'));
            remainingChart.draw(remainingData, remainingOptions);
        }

        function filterTable(tableId, columnIndex, filterValue) {
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName('tr');
            for (let i = 1; i < rows.length; i++) {
                const cell = rows[i].getElementsByTagName('td')[columnIndex];
                if (cell) {
                    const cellValue = cell.textContent || cell.innerText;
                    rows[i].style.display = filterValue === '' || cellValue === filterValue ? '' : 'none';
                }
            }
        }
    </script>
</head>

<body>
    <div id="wrapper">
        <?php include 'includes/nav.php'; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row total-users-box">
                    <div class="col-lg-4">
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <div class="row">
                                    <div class="col-xs-3">
                                        <i class="fa fa-users fa-5x"></i>
                                    </div>
                                    <div class="col-xs-9 text-right">
                                        <div class="huge"><?php echo $totalUsers; ?></div>
                                        <div>Total Users</div>
                                    </div>
                                </div>
                            </div>
                            <a href="manageblood.php">
                                <div class="panel-footer">
                                    <span class="pull-left">View Details</span>
                                    <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                                    <div class="clearfix"></div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="panel panel-success">
                            <div class="panel-heading">
                                <div class="row">
                                    <div class="col-xs-3">
                                        <i class="fa fa-check fa-5x"></i>
                                    </div>
                                    <div class="col-xs-9 text-right">
                                        <div class="huge">
                                            <?php
                                            $completeCount = $completeTableExists ? mysqli_num_rows($completeResult) : 0;
                                            echo $completeCount;
                                            ?>
                                        </div>
                                        <div>Complete Dharmado Users</div>
                                    </div>
                                </div>
                            </div>
                            
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="panel panel-danger">
                            <div class="panel-heading">
                                <div class="row">
                                    <div class="col-xs-3">
                                        <i class="fa fa-times fa-5x"></i>
                                    </div>
                                    <div class="col-xs-9 text-right">
                                        <div class="huge">
                                            <?php
                                            $remainingCount = $remainingTableExists ? mysqli_num_rows($remainingResult) : 0;
                                            echo $remainingCount;
                                            ?>
                                        </div>
                                        <div>Remaining Dharmado Users</div>
                                    </div>
                                </div>
                            </div>
                           
                            </a>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h1 class="page-header" style="margin: 0;">Monthly Report</h1>
                            <form method="GET" action="" class="form-inline">
                                <div class="form-group">
                                    <label for="month" class="sr-only">Month:</label>
                                    <select name="month" id="month" class="form-control">
                                        <?php
                                        for ($m = 1; $m <= 12; $m++) {
                                            $monthName = date('F', mktime(0, 0, 0, $m, 1));
                                            $selected = ($monthName == $currentMonth) ? 'selected' : '';
                                            echo "<option value='$monthName' $selected>$monthName</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="year" class="sr-only">Year:</label>
                                    <select name="year" id="year" class="form-control">
                                        <?php
                                        $currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y'); // Ensure the selected year is used
                                        for ($y = date('Y'); $y >= date('Y') - 10; $y--) {
                                            $selected = ($y == $currentYear) ? 'selected' : '';
                                            echo "<option value='$y' $selected>$y</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Search</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <!-- Complete Dharmado Table -->
                    <div class="col-lg-6">
                        <div class="panel panel-success">
                            <div class="panel-heading">
                                Complete Dharmado for <?php echo htmlspecialchars($currentMonth) . " " . htmlspecialchars($currentYear); ?>
                            </div>
                            <div class="panel-body scrollable-table">
                                <table class="table table-bordered table-hover" id="completeDharmadoTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Contact</th>
                                            <th>Dharmado Amount</th>
                                            <th>
                                                Taluka
                                                <select class="form-control" onchange="filterTable('completeDharmadoTable', 4, this.value)">
                                                    <option value="">All</option>
                                                    <?php
                                                    $talukaQuery = "SELECT DISTINCT taluko FROM $completeTableName";
                                                    $talukaResult = mysqli_query($conn, $talukaQuery);
                                                    while ($row = mysqli_fetch_assoc($talukaResult)) {
                                                        echo "<option value='" . htmlspecialchars($row['taluko']) . "'>" . htmlspecialchars($row['taluko']) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($completeResult && mysqli_num_rows($completeResult) > 0) {
                                            while ($row = mysqli_fetch_assoc($completeResult)) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['contact']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['dharmadoamount']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['taluko']) . "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='5' class='text-center'>No data found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Remaining Dharmado Table -->
                    <div class="col-lg-6">
                        <div class="panel panel-danger">
                            <div class="panel-heading">
                                Remaining Dharmado for <?php echo htmlspecialchars($currentMonth) . " " . htmlspecialchars($currentYear); ?>
                            </div>
                            <div class="panel-body scrollable-table">
                                <table class="table table-bordered table-hover" id="remainingDharmadoTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Contact</th>
                                            <th>Dharmado Amount</th>
                                            <th>
                                                Taluka
                                                <select class="form-control" onchange="filterTable('remainingDharmadoTable', 4, this.value)">
                                                    <option value="">All</option>
                                                    <?php
                                                    $talukaQuery = "SELECT DISTINCT taluko FROM $remainingTableName";
                                                    $talukaResult = mysqli_query($conn, $talukaQuery);
                                                    while ($row = mysqli_fetch_assoc($talukaResult)) {
                                                        echo "<option value='" . htmlspecialchars($row['taluko']) . "'>" . htmlspecialchars($row['taluko']) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($remainingResult && mysqli_num_rows($remainingResult) > 0) {
                                            while ($row = mysqli_fetch_assoc($remainingResult)) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['contact']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['dharmadoamount']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['taluko']) . "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='5' class='text-center'>No data found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <!-- Complete Dharmado Pie Chart -->
                    <div class="col-lg-6">
                        <div class="panel panel-success">
                            <div class="panel-heading">Complete Dharmado</div>
                            <div class="panel-body">
                                <div id="completeDharmadoChart" style="width: 100%; height: 400px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Remaining Dharmado Pie Chart -->
                    <div class="col-lg-6">
                        <div class="panel panel-danger">
                            <div class="panel-heading">Remaining Dharmado</div>
                            <div class="panel-body">
                                <div id="remainingDharmadoChart" style="width: 100%; height: 400px;"></div>
                            </div>
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
</body>

</html>
