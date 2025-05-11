<?php
// /ragging-complaint-site/api/emailComplaint.php

// --- Includes ---
// Ensure session is started BEFORE requireLogin is called
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/functions.php'; // Includes requireLogin
require_once '../includes/db.php';       // Database connection
require_once '../includes/mailer.php';    // Mailer function

// --- Authentication & Authorization ---
requireLogin(); // Ensure only logged-in admins can use this

// --- Validate Request Method ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // It's better to redirect back to the specific complaint if possible
    $redirect_id = isset($_POST['complaint_id']) ? (int)$_POST['complaint_id'] : null;
    $redirect_url = $redirect_id ? "../admin/view_complaint.php?id={$redirect_id}&error=" : "../admin/dashboard.php?error=";
    header("Location: " . $redirect_url . urlencode("Invalid request method."));
    exit;
}

// --- Validate Inputs ---
if (!isset($_POST['complaint_id']) || !filter_var($_POST['complaint_id'], FILTER_VALIDATE_INT) || $_POST['complaint_id'] <= 0) {
    header("Location: ../admin/dashboard.php?error=" . urlencode("Invalid or missing Complaint ID."));
    exit;
}
$complaint_id = (int)$_POST['complaint_id']; // Store validated ID

if (!isset($_POST['recipient_email']) || !filter_var(trim($_POST['recipient_email']), FILTER_VALIDATE_EMAIL)) {
    // Redirect back to the specific complaint page with an error
    header("Location: ../admin/view_complaint.php?id=" . $complaint_id . "&error=" . urlencode("Invalid or missing recipient email address."));
    exit;
}
$recipient_email = trim($_POST['recipient_email']);
$admin_email = $_SESSION['admin_email'] ?? 'Unknown Admin'; // Get admin email for logging/display

// --- Fetch Full Complaint Details ---
$complaint = null;
$sql = "SELECT * FROM complaints WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $complaint_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $complaint = mysqli_fetch_assoc($result); // Fetch as associative array
    } else {
        error_log("EmailComplaint Error: Failed to execute statement for ID {$complaint_id} - " . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("EmailComplaint Error: Failed to prepare statement for ID: $complaint_id - " . mysqli_error($conn));
}

// --- Check if Complaint Found ---
if (!$complaint) {
    if ($conn instanceof mysqli) {
        mysqli_close($conn);
    }
    header("Location: ../admin/dashboard.php?error=" . urlencode("Complaint with ID {$complaint_id} not found."));
    exit;
}

// --- Format Email Content ---
$subject = "Ragging Complaint Details - ID: " . htmlspecialchars($complaint['id']);
// Build HTML Body (ensure all accesses use ?? '' or similar for safety)
$bodyHTML = "<h1>Ragging Complaint Details (ID: " . htmlspecialchars($complaint['id'] ?? 'N/A') . ")</h1>";
$bodyHTML .= "<p><em>Forwarded by Admin: " . htmlspecialchars($admin_email) . "</em></p>";
$bodyHTML .= "<hr>";
$bodyHTML .= "<table border='0' cellpadding='5' style='border-collapse: collapse; width: 100%;'>"; // Use table for better layout in some clients
$bodyHTML .= "<tr><td style='width: 150px; font-weight: bold;'>Status:</td><td>" . htmlspecialchars($complaint['status'] ?? 'N/A') . "</td></tr>";
$bodyHTML .= "<tr><td style='font-weight: bold;'>Submitted At:</td><td>" . htmlspecialchars(date('Y-m-d H:i:s', strtotime($complaint['created_at'] ?? ''))) . "</td></tr>";
$bodyHTML .= "<tr><td style='font-weight: bold;'>Anonymous:</td><td>" . (isset($complaint['is_anonymous']) && $complaint['is_anonymous'] ? 'Yes' : 'No') . "</td></tr>";

if (!($complaint['is_anonymous'] ?? true)) { // Check if NOT anonymous
    $bodyHTML .= "<tr><td style='font-weight: bold;'>Complainant Name:</td><td>" . (!empty($complaint['full_name']) ? htmlspecialchars($complaint['full_name']) : '<em>Not Provided</em>') . "</td></tr>";
    $bodyHTML .= "<tr><td style='font-weight: bold;'>Contact Info:</td><td>" . (!empty($complaint['contact_info']) ? htmlspecialchars($complaint['contact_info']) : '<em>Not Provided</em>') . "</td></tr>";
}

$bodyHTML .= "<tr><td style='font-weight: bold;'>Department:</td><td>" . htmlspecialchars($complaint['department'] ?? 'N/A') . "</td></tr>";
$bodyHTML .= "<tr><td style='font-weight: bold;'>Year/Semester:</td><td>" . htmlspecialchars($complaint['year_semester'] ?? 'N/A') . "</td></tr>";
$bodyHTML .= "<tr><td style='font-weight: bold;'>Incident Time:</td><td>" . htmlspecialchars(date('Y-m-d H:i', strtotime($complaint['incident_datetime'] ?? ''))) . "</td></tr>";
$bodyHTML .= "<tr><td style='font-weight: bold;'>Location:</td><td>" . htmlspecialchars($complaint['location'] ?? 'N/A') . "</td></tr>";
$bodyHTML .= "<tr><td style='font-weight: bold; vertical-align: top;'>Description:</td><td><pre style='white-space: pre-wrap; word-wrap: break-word; margin:0; padding: 5px; background-color: #f8f9fa; border: 1px solid #eee;'>" . htmlspecialchars($complaint['description'] ?? '') . "</pre></td></tr>";

$bodyHTML .= "<tr><td style='font-weight: bold;'>Evidence File:</td><td>";
if (!empty($complaint['evidence_path'])) {
    // Construct full URL (adjust if needed based on your domain/setup)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost'; // Default to localhost if host isn't set
    // Calculate base path relative to document root
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']); // e.g., /ragging-complaint-site/api
    $basePath = dirname($scriptDir, 1); // e.g., /ragging-complaint-site
    $evidenceUrl = $protocol . $host . rtrim($basePath, '/') . '/' . ltrim($complaint['evidence_path'], '/');

    $bodyHTML .= "<a href='" . htmlspecialchars($evidenceUrl) . "' target='_blank'>" . htmlspecialchars(basename($complaint['evidence_path'])) . "</a>";
} else {
    $bodyHTML .= "<em>No evidence uploaded.</em>";
}
$bodyHTML .= "</td></tr>";
$bodyHTML .= "</table>"; // End table
$bodyHTML .= "<hr>";
$bodyHTML .= "<p><em>End of Complaint Details</em></p>";

// --- Send Email ---
$emailSent = sendNotificationEmail($recipient_email, $subject, $bodyHTML);

// --- Close Database Connection ---
if ($conn instanceof mysqli) {
    mysqli_close($conn);
}

// --- Redirect Based on Outcome ---
if ($emailSent) {
    $success_msg = "Complaint ID " . $complaint_id . " successfully emailed to " . htmlspecialchars($recipient_email);
    header("Location: ../admin/view_complaint.php?id=" . $complaint_id . "&success=" . urlencode($success_msg));
    exit;
} else {
    // CRUCIAL: Check error logs for details from sendNotificationEmail function
    error_log("Failed attempt by admin {$admin_email} to email complaint ID {$complaint_id} to {$recipient_email}. Check mailer logs/config.");
    $error_msg = "Failed to send email. Please check server logs or mail configuration/credentials.";
    header("Location: ../admin/view_complaint.php?id=" . $complaint_id . "&error=" . urlencode($error_msg));
    exit;
}
