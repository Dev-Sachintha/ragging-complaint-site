<?php
// /includes/functions.php

// Make sure session_start() is at the top if needed by other functions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Add or Uncomment this function ---
// Helper function for status badge colors
function getStatusBadgeClass($status)
{
    switch ($status) {
        case 'Pending':
            return 'warning text-dark';
        case 'In Review':
            return 'primary';
        case 'Resolved':
            return 'success';
        default:
            // Handle null or unexpected status gracefully
            return 'secondary';
    }
}
// --- End of function definition ---


// --- Your other functions (isLoggedIn, requireLogin, sanitizeInput) should also be here ---
function isLoggedIn()
{
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireLogin()
{
    if (!isLoggedIn()) {
        if (headers_sent()) {
            echo "Redirect failed. Please login.";
            exit;
        }
        // Corrected path assuming login.php is at the root
        header("Location: ../login.php?error=" . urlencode("Please login first"));
        exit;
    }
}

function sanitizeInput($input)
{
    return trim(htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8'));
}
// --- End of other functions ---
