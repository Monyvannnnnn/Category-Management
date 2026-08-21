<?php
$server = "sql310.infinityfree.com";
$user = "if0_42693065";
$password = "Munyvann3103094";
$dbname = "if0_42693065_inventory";

// Create connection
$conn = new mysqli($server, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>