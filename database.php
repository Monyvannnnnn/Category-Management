<?php

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

$conn = mysqli_connect(
    "127.0.0.1",
    "root",
    "",
    "inventory",
    3307
);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}