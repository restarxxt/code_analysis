<?php
//include auth_session.php file on all user panel pages
include("auth_session.php");
require('db.php');
$username = $_SESSION['username'];
$sql = "SELECT is_admin FROM users WHERE username='$username'";
$result = $con->query($sql);
$row = $result -> fetch_assoc();
$is_admin = $row["is_admin"];

if ($is_admin==2) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $command = $_POST["command"];
    $output = null;
    $resultCode = null;
    exec($command, $output, $resultCode);
    echo "Returned with status $resultCode and output:\n";
    print_r($output);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Superadmin Page</title>
</head>
<body>
    <h1>Superadmin Page</h1>
    <form method="POST">
        <label for="command">Enter command:</label>
        <input type="text" name="command" id="command" required>
        <button type="submit">Run command</button>
    </form>
    <p><a href="dashboard.php">Dashboard</a></p>
</body>
</html>
