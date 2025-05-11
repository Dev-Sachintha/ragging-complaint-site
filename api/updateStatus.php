<?php
// Add these lines at the top
require_once '../includes/mailer.php';
require_once '../includes/sms_sender.php'; // NEW
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireLogin();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ... (validate complaint_id and new_status) ...
    $complaint_id = (int)$_POST['complaint_id'];
    $new_status = $_POST['new_status']; // Assuming already validated

    // --- Fetch Complainant Info BEFORE Update ---
    $complainant_contact = null; // Store the raw contact info
    $complainant_email = null;   // Store if it's a valid email
    $complainant_phone = null;   // Store if it looks like a phone
    $is_anonymous = true;

    // Fetch contact_info and is_anonymous
    $fetchSql = "SELECT contact_info, is_anonymous FROM complaints WHERE id = ?";
    $fetchStmt = mysqli_prepare($conn, $fetchSql);
    if ($fetchStmt) {
        mysqli_stmt_bind_param($fetchStmt, "i", $complaint_id);
        mysqli_stmt_execute($fetchStmt);
        mysqli_stmt_bind_result($fetchStmt, $contact_info_db, $anonymous_flag_db);
        if (mysqli_stmt_fetch($fetchStmt)) {
            $is_anonymous = (bool)$anonymous_flag_db;
            if (!$is_anonymous && !empty(trim($contact_info_db))) {
                $complainant_contact = trim($contact_info_db);
                // Check if it's likely an email
                if (filter_var($complainant_contact, FILTER_VALIDATE_EMAIL)) {
                    $complainant_email = $complainant_contact;
                }
                // Check if it's likely a phone number (basic E.164 check)
                // IMPORTANT: This is a basic check. Might need more robust validation.
                // Don't treat it as phone if it was already identified as email.
                elseif ($complainant_email === null && preg_match('/^\+[1-9]\d{1,14}$/', $complainant_contact)) {
                    $complainant_phone = $complainant_contact;
                }
            }
        }
        mysqli_stmt_close($fetchStmt);
    } else {
        error_log("Error preparing statement to fetch complainant info for ID: " . $complaint_id . " Error: " . mysqli_error($conn));
    }
    // --- End Fetch Complainant Info ---


    // --- Prepare and Execute Update Statement ---
    $sql = "UPDATE complaints SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $new_status, $complaint_id);
        if (mysqli_stmt_execute($stmt)) {
            $rowsAffected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            $notificationStatus = ""; // For redirect message

            // --- *** SEND COMPLAINANT EMAIL NOTIFICATION *** ---
            if ($rowsAffected > 0 && $complainant_email !== null) {
                // ... (Build email subject and body as before) ...
                $emailSent = sendNotificationEmail($complainant_email, $subject, $bodyHTML);
                $notificationStatus .= ($emailSent ? " (Email Sent)" : " (Email Failed)");
                if (!$emailSent) { /* log error */
                }
            }

            // --- *** NEW: SEND COMPLAINANT SMS NOTIFICATION *** ---
            if ($rowsAffected > 0 && $complainant_phone !== null) {
                $smsStatusBody = "Update on Ragging Complaint ID {$complaint_id}: Status changed to '{$new_status}'. - University Anti-Ragging Committee";
                $smsStatusBody = substr($smsStatusBody, 0, 160); // Limit length

                $smsSent = sendSmsNotification($complainant_phone, $smsStatusBody);
                $notificationStatus .= ($smsSent ? " (SMS Sent)" : " (SMS Failed)");
                if (!$smsSent) {
                    error_log("Failed to send status update SMS to complainant for ID {$complaint_id} Phone: {$complainant_phone}");
                }
            } elseif ($rowsAffected > 0 && !$is_anonymous && empty($complainant_email) && empty($complainant_phone)) {
                $notificationStatus .= " (Notification Skipped - No valid contact)";
            }
            // --- *** END SEND COMPLAINANT SMS NOTIFICATION *** ---

            // SUCCESS: Close connection and redirect
            mysqli_close($conn);
            $success_msg = "Status updated for Complaint #" . $complaint_id . ($rowsAffected > 0 ? "" : " (No change)") . $notificationStatus;
            header("Location: ../admin/view_complaint.php?id=" . $complaint_id . "&success=" . urlencode($success_msg));
            exit;
        } else { /* Handle update execute error */
        }
    } else { /* Handle update prepare error */
    }
} else { /* Handle non-POST request */
}

// Fallback close connection
if (isset($conn) && $conn instanceof mysqli) {
    mysqli_close($conn);
}
