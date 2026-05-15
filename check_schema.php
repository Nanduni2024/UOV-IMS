<?php
include 'config.php';

// Get maintenance_logs table structure
$result = $conn->query("DESCRIBE maintenance_logs");
if ($result) {
    echo "Maintenance Logs Table Columns:<br>";
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    echo "</pre>";
} else {
    echo "Error: " . $conn->error;
}
?>
