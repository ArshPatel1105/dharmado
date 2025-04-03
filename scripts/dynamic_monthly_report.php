<?php
include '../dbconnect.php';

// Get the current month and year
$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('F');
$currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Table names
$completeTableName = "complete_dharmado_" . strtolower($currentMonth) . "_" . $currentYear;
$remainingTableName = "remaining_dharmado_" . strtolower($currentMonth) . "_" . $currentYear;

// Only transfer users who have submitted dharmado from remaining to complete table
$transferToCompleteQuery = "
    INSERT INTO $completeTableName (user_id, name, contact, dharmadoamount, occupation, taluko, dharmado_process)
    SELECT r.user_id, r.name, r.contact, r.dharmadoamount, r.occupation, r.taluko, 1
    FROM $remainingTableName r
    INNER JOIN monthly_report m ON r.user_id = m.user_id
    WHERE m.dharmado_process = 1
    AND MONTHNAME(m.date) = '$currentMonth' 
    AND YEAR(m.date) = '$currentYear'
    AND r.user_id NOT IN (
        SELECT user_id FROM $completeTableName
    )
";
if (!mysqli_query($conn, $transferToCompleteQuery)) {
    error_log("Error transferring users to $completeTableName: " . mysqli_error($conn));
}

// Remove transferred users from remaining_dharmado table
$removeFromRemainingQuery = "
    DELETE r FROM $remainingTableName r
    INNER JOIN monthly_report m ON r.user_id = m.user_id
    WHERE m.dharmado_process = 1
    AND MONTHNAME(m.date) = '$currentMonth' 
    AND YEAR(m.date) = '$currentYear'
";
if (!mysqli_query($conn, $removeFromRemainingQuery)) {
    error_log("Error removing users from $remainingTableName: " . mysqli_error($conn));
}

echo "Users have been transferred between tables successfully.";
?>