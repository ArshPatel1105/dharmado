<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php'); // Redirect to login if not logged in
    exit();
}

require __DIR__ . '/../vendor/autoload.php'; // Correct path to autoload.php
include '../dbconnect.php';

use Dompdf\Dompdf;

$pdfPreview = ""; // Variable to store the PDF preview

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $month = mysqli_real_escape_string($conn, $_POST['month']);
    $taluko = mysqli_real_escape_string($conn, $_POST['taluko']);

    $query = "SELECT * FROM user WHERE 1=1";

    if (!empty($year)) {
        $query .= " AND id IN (SELECT user_id FROM monthly_report WHERE YEAR(date) = '$year')";
    }

    if (!empty($month)) {
        $query .= " AND id IN (SELECT user_id FROM monthly_report WHERE MONTHNAME(date) = '$month')";
    }

    if (!empty($taluko)) {
        $query .= " AND taluko = '$taluko'";
    }

    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $html = '<h1>User Details Report</h1>';
        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse;">';
        $html .= '<thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Contact</th>
                        <th>Dharmado Amount</th>
                        <th>Occupation</th>
                        <th>Taluko</th>
                        <th>Address</th>
                    </tr>
                  </thead>';
        $html .= '<tbody>';

        while ($row = mysqli_fetch_assoc($result)) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['id']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['gender']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['contact']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['dharmadoamount']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['occupation']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['taluko']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['address']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Generate the PDF preview as a base64-encoded string
        $pdfPreview = base64_encode($dompdf->output());
    } else {
        $error = "No records found for the selected filters.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download PDF - BDMS</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link href="../vendor/metisMenu/metisMenu.min.css" rel="stylesheet">
    <link href="../dist/css/sb-admin-2.css" rel="stylesheet">
    <link href="../vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <style>
        body {
            margin-bottom: 50px; /* Add margin to prevent content overlap with footer */
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
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include 'includes/nav.php'; ?>
        <div id="page-wrapper">
            <div class="container">
                <h1 class="text-center">Download User Details Report</h1>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="year">Select Year:</label>
                        <select name="year" id="year" class="form-control">
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
                        <select name="month" id="month" class="form-control">
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
                        <label for="taluko">Select Taluko:</label>
                        <select name="taluko" id="taluko" class="form-control">
                            <option value="">-- Select Taluko --</option>
                            <option value="Modasa">Modasa</option>
                            <option value="Dhansura">Dhansura</option>
                            <option value="Bayad">Bayad</option>
                            <option value="Malpur">Malpur</option>
                            <option value="Megharaj">Megharaj</option>
                            <option value="Bhiloda">Bhiloda</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Generate Preview</button>
                </form>

                <?php if (!empty($pdfPreview)): ?>
                    <h2 class="text-center">PDF Preview</h2>
                    <iframe src="data:application/pdf;base64,<?php echo $pdfPreview; ?>" width="100%" height="500px"></iframe>
                    <form method="POST" action="downloadpdf.php">
                        <input type="hidden" name="year" value="<?php echo htmlspecialchars($year); ?>">
                        <input type="hidden" name="month" value="<?php echo htmlspecialchars($month); ?>">
                        <input type="hidden" name="taluko" value="<?php echo htmlspecialchars($taluko); ?>">
                        <button type="submit" class="btn btn-success">Download PDF</button>
                    </form>
                <?php endif; ?>
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
