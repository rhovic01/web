<?php
$servername = "localhost";
$username = "root"; // Change if needed
$password = ""; // Change if needed
$dbname = "cict_rms";

$conn = new mysqli($servername, $username, $password, $dbname, 4306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
