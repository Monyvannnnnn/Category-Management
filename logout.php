<?php
// logout.php
require_once __DIR__ . "/includes/auth_helper.php";

logoutUser();

header("Location: login.php");
exit;


