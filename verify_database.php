<?php
include 'config.php';

echo "<style>
    body { font-family: Arial; background: #1a1a1a; color: #fff; padding: 20px; }
    .container { max-width: 1200px; margin: 0 auto; }
    h1 { color: #137fec; }
    h2 { margin-top: 30px; border-bottom: 1px solid #444; padding-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 10px; text-align: left; border-bottom: 1px solid #444; }
    th { background: #333; font-weight: bold; }
    tr:hover { background: #2a2a2a; }
    .success { color: #10b981; }
    .error { color: #ef4444; }
    .warning { color: #f59e0b; }
</style>";

echo "<div class='container'>";
echo "<h1>✓ Database Structure Verification</h1>";

// Test users table
echo "<h2>Users Table</h2>";
$result = $conn->query("SELECT * FROM users LIMIT 1");
if ($result) {
    echo "<span class='success'>✓ Users table exists</span><br>";
    echo "Columns: " . implode(", ", array_keys($result->fetch_assoc())) . "<br>";
} else {
    echo "<span class='error'>✗ Error accessing users table</span>";
}

// Test assets table
echo "<h2>Assets Table</h2>";
$result = $conn->query("SELECT * FROM assets LIMIT 1");
if ($result) {
    echo "<span class='success'>✓ Assets table exists</span><br>";
    echo "Columns: " . implode(", ", array_keys($result->fetch_assoc())) . "<br>";
} else {
    echo "<span class='error'>✗ Error accessing assets table</span>";
}

// Test maintenance_logs table
echo "<h2>Maintenance Logs Table</h2>";
$result = $conn->query("SELECT * FROM maintenance_logs LIMIT 1");
if ($result) {
    echo "<span class='success'>✓ Maintenance logs table exists</span><br>";
} else {
    echo "<span class='warning'>⚠ Warning: Maintenance logs table issue or empty</span>";
}

// Count records
echo "<h2>Record Counts</h2>";
$users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$assets = $conn->query("SELECT COUNT(*) as count FROM assets")->fetch_assoc()['count'];
$maintenance = $conn->query("SELECT COUNT(*) as count FROM maintenance_logs")->fetch_assoc()['count'];
$audit = $conn->query("SELECT COUNT(*) as count FROM audit_logs")->fetch_assoc()['count'];

echo "<table>";
echo "<tr><th>Table</th><th>Record Count</th></tr>";
echo "<tr><td>Users</td><td>$users</td></tr>";
echo "<tr><td>Assets</td><td>$assets</td></tr>";
echo "<tr><td>Maintenance Logs</td><td>$maintenance</td></tr>";
echo "<tr><td>Audit Logs</td><td>$audit</td></tr>";
echo "</table>";

// List users
echo "<h2>All Users</h2>";
$result = $conn->query("SELECT id, username, email, role FROM users");
if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td><strong>" . strtoupper($row['role']) . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<span class='warning'>No users found. Please create a user first.</span>";
}

echo "</div>";
?>