<?php
// Basic configuration for database connection and site settings

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // change if different
define('DB_PASS', '');           // change if you have a password
define('DB_NAME', 'foodie_db');  // make sure to create this database

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Start session for login system
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>


