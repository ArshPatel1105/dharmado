<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php'); // Redirect to login if not logged in
    exit();
}

include '../dbconnect.php';

// Fetch the next auto-increment ID from the database
$nextIdQuery = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'secyear' AND TABLE_NAME = 'user'";
$nextIdResult = mysqli_query($conn, $nextIdQuery);
$nextId = 1; // Default to 1 if the query fails

if ($nextIdResult && $row = mysqli_fetch_assoc($nextIdResult)) {
    $nextId = $row['AUTO_INCREMENT'];
}

$successMessage = ""; // Variable to store success message

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = strtoupper($_POST['name']);
    $gender = strtoupper($_POST['gender']);
    $dob = strtoupper($_POST['dob']);
    $occupation = strtoupper($_POST['occupation_select'] === 'Other' ? $_POST['occupation_custom'] : $_POST['occupation_select']);
    $address = strtoupper($_POST['address']);
    $taluko = strtoupper($_POST['taluko']);
    $contact = strtoupper($_POST['contact']);
    $dharmadoamount = strtoupper($_POST['dharmadoamount']);
    $startingdate = strtoupper($_POST['startingdate']);

    // Handle photo upload
    $photoData = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $photoData = mysqli_real_escape_string($conn, file_get_contents($_FILES['photo']['tmp_name']));
    }

    // Validate taluko
    if (empty($taluko)) {
        echo "<div class='alert alert-danger'>Error: Taluko is required.</div>";
    } else {
        // Validate contact number
        if (!preg_match('/^\d{10}$/', $contact)) {
            echo "<div class='alert alert-danger'>Error: Contact number must be exactly 10 digits.</div>";
        } else {
            // Insert the new user into the `user` table
            $insertUserQuery = "INSERT INTO user (name, gender, dob, occupation, address, taluko, contact, dharmadoamount, startingdate, photo) 
                                VALUES ('$name', '$gender', '$dob', '$occupation', '$address', '$taluko', '$contact', '$dharmadoamount', '$startingdate', '$photoData')";
            if (mysqli_query($conn, $insertUserQuery)) {
                $user_id = mysqli_insert_id($conn); // Get the ID of the newly inserted user

                // Get the current month and year
                $currentMonth = date('F'); // e.g., "September"
                $currentYear = date('Y');  // e.g., "2023"
                $tableName = "remaining_dharmado_" . strtolower($currentMonth) . "_" . $currentYear; // e.g., "remaining_dharmado_september_2023"

                // Check if the table for the current month and year exists
                $checkTableQuery = "SHOW TABLES LIKE '$tableName'";
                $tableResult = mysqli_query($conn, $checkTableQuery);

                if (mysqli_num_rows($tableResult) > 0) {
                    // Insert the new user into the dynamically created "Remaining Dharmado" table
                    $insertRemainingQuery = "INSERT INTO $tableName (user_id, name, contact, dharmadoamount, occupation, taluko) 
                                              VALUES ('$user_id', '$name', '$contact', '$dharmadoamount', '$occupation', '$taluko')";
                    mysqli_query($conn, $insertRemainingQuery);
                }

                $successMessage = "User details submitted successfully!";
            } else {
                echo "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
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

    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }

        #wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        #page-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .panel-body {
            max-height: calc(100vh - 200px); /* Adjust height to fit within the viewport */
            overflow-y: auto; /* Enable scrolling for the panel body */
        }

        footer {
            background-color: #424558;
            color: #CCC;
            text-align: center;
            padding: 10px;
            position: relative;
            bottom: 0;
            width: 100%;
        }

        footer p {
            margin: 0;
            line-height: 100%;
        }

        .form-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Three columns */
            gap: 20px;
        }

        .form-group label {
            font-weight: bold;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            padding: 8px;
            font-size: 14px;
            width: 100%;
        }

        .form-container .btn {
            grid-column: span 3; /* Make the button span across all columns */
        }
    </style>
    <script>
        function validateForm() {
            const contactInput = document.getElementById('contact');
            const contactValue = contactInput.value;

            if (!/^\d{10}$/.test(contactValue)) {
                alert("Contact number must be exactly 10 digits.");
                contactInput.focus();
                return false;
            }

            return true;
        }

        function disableSubmitButton(form) {
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = "Submitting..."; // Optional: Change button text
            setTimeout(() => {
                submitButton.disabled = false;
                submitButton.textContent = "Submit"; // Reset button text
            }, 3000); // Re-enable the button after 3 seconds
            return true; // Allow form submission
        }
    </script>
</head>

<body>
    <div id="wrapper">
        <?php include 'includes/nav.php'; ?>

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h3 class="page-header">Add User Details</h3>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            Please fill up the form below:
                        </div>
                        <div class="panel-body">
                            <?php if (!empty($successMessage)): ?>
                                <div class="alert alert-success">
                                    <?php echo $successMessage; ?>
                                    <a href="addblood.php" class="btn btn-success" style="margin-left: 10px;">Next</a>
                                </div>
                            <?php endif; ?>
                            <form id="addUserForm" role="form" action="" method="post" enctype="multipart/form-data" onsubmit="return disableSubmitButton(this)">
                                <div class="form-container" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                                    <div class="form-group">
                                        <label>User ID</label>
                                        <input class="form-control" type="text" value="<?php echo $nextId; ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Enter Full Name</label>
                                        <input class="form-control" type="text" placeholder="YASH RITESHBHAI PATEL " name="name" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Gender [ M/F ]</label>
                                        <input class="form-control" placeholder="M or F" name="gender" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Enter Date of Birth</label>
                                        <input class="form-control" type="date" name="dob" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Enter Occupation</label>
                                        <select class="form-control" id="occupation" name="occupation_select" required>
                                            <option value="">-- Select Occupation --</option>
                                            <option value="TEACHER">Teacher</option>
                                            <option value="DOCTOR">Doctor</option>
                                            <option value="FARMER">Farmer</option>
                                            <option value="BUSINESS">Business</option>
                                            <option value="PRIVATE JOB">Private Job</option>
                                            <option value="OTHER">Other</option>
                                        </select>
                                        <input class="form-control" type="text" id="customOccupation" name="occupation_custom" placeholder="Enter Custom Occupation" style="display: none; margin-top: 10px;">
                                    </div>
                                    <div class="form-group">
                                        <label>Enter Address</label>
                                        <input class="form-control" placeholder="Address" type="text" name="address" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Select Taluko</label>
                                        <select class="form-control" name="taluko" required>
                                            <option value="">-- Select Taluko --</option>
                                            <option value="Modasa">Modasa</option>
                                            <option value="Dhansura">Dhansura</option>
                                            <option value="Bayad">Bayad</option>
                                            <option value="Malpur">Malpur</option>
                                            <option value="Megharaj">Megharaj</option>
                                            <option value="Bhiloda">Bhiloda</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Enter Contact Number</label>
                                        <input class="form-control" id="contact" placeholder="Contact Number" type="text" name="contact" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Dharmado Amount</label>
                                        <input class="form-control" placeholder="Dharmado Amount" type="number" name="dharmadoamount" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Dharmado Starting Date</label>
                                        <input class="form-control" type="date" name="startingdate" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Upload Passport-Size Photo (Optional)</label>
                                        <input class="form-control" type="file" name="photo" accept="image/*">
                                    </div>
                                    <div class="form-group" style="grid-column: span 3; text-align: center;">
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer>
        <p>&copy; <?php echo date("Y"); ?>: Developed By BAPS MODASA</p>
    </footer>
    <script>
        document.getElementById('occupation').addEventListener('change', function () {
            const customOccupation = document.getElementById('customOccupation');
            if (this.value === 'OTHER') {
                customOccupation.style.display = 'block';
                customOccupation.required = true;
            } else {
                customOccupation.style.display = 'none';
                customOccupation.required = false;
            }
        });
    </script>
</body>
</html>