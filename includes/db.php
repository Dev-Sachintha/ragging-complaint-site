<?php
// Database Configuration
define('DB_SERVER', 'localhost'); // Usually 'localhost'
define('DB_USERNAME', 'root');    // Your MySQL username (default is often 'root')
define('DB_PASSWORD', '');        // Your MySQL password (default is often empty for XAMPP)
define('DB_NAME', 'ragging_complaints_db'); // Your database name

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn === false) {
    // Don't show detailed errors in production
    // Log error instead
    error_log("Database connection failed: " . mysqli_connect_error());
    die("ERROR: Could not connect to database. Please try again later.");
}

// Optional: Set character set (recommended)
mysqli_set_charset($conn, "utf8mb4");

// Optional: Start session here if used across multiple includes
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }
