<?php
// /ragging-complaint-site/api/submitComplaint.php

// --- Includes ---
// NOTE: Order matters if files depend on each other (e.g., mailer needs config)
// It's generally safe to include helpers first.
require_once '../includes/db.php';       // Needs to be early for $conn
require_once '../includes/mailer.php';    // Needs config/mail_config.php loaded (mailer.php includes it)
require_once '../includes/sms_sender.php'; // Needs config/sms_config.php loaded (sms_sender.php includes it)

// --- Configuration ---
$uploadDir = '../uploads/'; // Relative path from this script to the uploads folder
$allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'mp3', 'wav', 'mp4', 'mov', 'avi'];
$maxFileSize = 5 * 1024 * 1024; // 5 MB

// --- Input Validation & Processing ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Basic check for required fields (add more robust checks if needed)
    $requiredFields = ['department', 'year_semester', 'incident_datetime', 'location', 'description'];
    foreach ($requiredFields as $field) {
        if (empty(trim($_POST[$field]))) {
            if ($conn instanceof mysqli) {
                mysqli_close($conn);
            } // Close connection before exit
            header("Location: ../complaint.html?error=" . urlencode("Missing required field: " . str_replace('_', ' ', $field)));
            exit;
        }
    }

    // Get data, using null coalescing operator ?? for optional fields
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    $full_name = !$is_anonymous ? trim($_POST['full_name'] ?? '') : null;
    $contact_info = !$is_anonymous ? trim($_POST['contact_info'] ?? '') : null;
    $department = trim($_POST['department']);
    $year_semester = trim($_POST['year_semester']);
    $incident_datetime = trim($_POST['incident_datetime']);
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);
    $evidence_path_relative_to_root = null; // Path to store in DB (relative to project root)

    // --- File Upload Handling ---
    $evidence_error_msg = '';
    if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['evidence']['tmp_name'];
        $fileName = basename($_FILES['evidence']['name']); // Use basename for security
        $fileSize = $_FILES['evidence']['size'];
        $fileType = $_FILES['evidence']['type']; // MIME type
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Validate file type and size
        if (!in_array($fileExtension, $allowedTypes)) {
            $evidence_error_msg = "Invalid file type ('{$fileExtension}'). Allowed types: " . implode(', ', $allowedTypes);
        } elseif ($fileSize > $maxFileSize) {
            $evidence_error_msg = 'File size exceeds the limit (' . ($maxFileSize / 1024 / 1024) . ' MB).';
        } else {
            // Create a unique filename to prevent overwrites and sanitize
            $newFileName = uniqid('evidence_', true) . '.' . $fileExtension;
            $dest_path = $uploadDir . $newFileName; // Full path for move_uploaded_file

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // File moved successfully, store the path relative to the *site root*
                $evidence_path_relative_to_root = 'uploads/' . $newFileName;
            } else {
                error_log("Failed to move uploaded file: " . $fileName . " to " . $dest_path);
                $evidence_error_msg = 'Server error processing uploaded file.';
                // Consider how critical failure here is. Maybe proceed without evidence?
            }
        }
    } elseif (isset($_FILES['evidence']) && $_FILES['evidence']['error'] != UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors more gracefully
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE directive specified in the HTML form.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
        ];
        $errorCode = $_FILES['evidence']['error'];
        $evidence_error_msg = $uploadErrors[$errorCode] ?? 'Unknown file upload error.';
        error_log("File upload error code: " . $errorCode . " - " . $evidence_error_msg);
    }

    // If there was a file upload error, redirect back
    if (!empty($evidence_error_msg)) {
        if ($conn instanceof mysqli) {
            mysqli_close($conn);
        }
        header("Location: ../complaint.html?error=" . urlencode($evidence_error_msg));
        exit;
    }


    // --- *** FIX: Database Insertion with Correct SQL *** ---
    // Specify the columns explicitly
    $sql = "INSERT INTO complaints (full_name, contact_info, department, year_semester, incident_datetime, location, description, evidence_path, is_anonymous) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql); // This is line 19 (or near it) where the error occurred

    if ($stmt) {
        // Bind variables to the prepared statement as parameters
        // Types: s = string, i = integer, d = double, b = blob
        mysqli_stmt_bind_param(
            $stmt,
            "ssssssssi",
            $full_name,
            $contact_info,
            $department,
            $year_semester,
            $incident_datetime,
            $location,
            $description,
            $evidence_path_relative_to_root, // Use the relative path stored
            $is_anonymous
        );

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            $newComplaintId = mysqli_insert_id($conn); // Get the ID of the new complaint

            // --- SEND ADMIN EMAIL NOTIFICATION ---
            if ($newComplaintId > 0 && defined('ADMIN_EMAIL_RECIPIENTS') && !empty(ADMIN_EMAIL_RECIPIENTS)) {
                $subject = "New Ragging Complaint Submitted - ID: " . $newComplaintId;
                $bodyHTML = "<h2>New Ragging Complaint Received</h2>" .
                    "<p>A new complaint (ID: {$newComplaintId}) has been submitted.</p>" .
                    "<ul>" .
                    "<li><strong>Department:</strong> " . htmlspecialchars($department) . "</li>" .
                    "<li><strong>Location:</strong> " . htmlspecialchars($location) . "</li>" .
                    "<li><strong>Incident Time:</strong> " . htmlspecialchars(date('Y-m-d H:i', strtotime($incident_datetime))) . "</li>" .
                    "<li><strong>Anonymous:</strong> " . ($is_anonymous ? 'Yes' : 'No') . "</li>" .
                    "</ul>" .
                    "<p>Please log in to view full details.</p>";
                $emailSent = sendNotificationEmail(ADMIN_EMAIL_RECIPIENTS, $subject, $bodyHTML);
                if (!$emailSent) {
                    error_log("Failed to send new complaint EMAIL notification for ID: " . $newComplaintId);
                }
            }

            // --- SEND ADMIN SMS NOTIFICATION ---
            if ($newComplaintId > 0 && defined('ADMIN_SMS_RECIPIENTS') && !empty(ADMIN_SMS_RECIPIENTS)) {
                $smsMessageBody = "New Ragging Complaint Alert! ID: {$newComplaintId}. Dept: " . substr($department, 0, 20) . ". Location: " . substr($location, 0, 20) . ". Login to view.";
                $smsMessageBody = substr($smsMessageBody, 0, 155); // Limit length

                foreach (ADMIN_SMS_RECIPIENTS as $adminPhoneNumber) {
                    if (!empty($adminPhoneNumber)) { // Basic check
                        $smsSent = sendSmsNotification($adminPhoneNumber, $smsMessageBody);
                        if (!$smsSent) {
                            error_log("Failed to send new complaint SMS notification for ID {$newComplaintId} to {$adminPhoneNumber}");
                        }
                    }
                }
            }

            // Close resources & Redirect on success
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            header("Location: ../thank-you.html");
            exit;
        } else {
            // Handle DB execution error
            $db_error = mysqli_error($conn);
            error_log("Database Error: Could not execute prepared statement: " . $db_error);
            mysqli_stmt_close($stmt); // Close statement even on error
            mysqli_close($conn);      // Close connection
            header("Location: ../complaint.html?error=" . urlencode("Database error submitting complaint. Please contact support if this persists."));
            exit;
        }
    } else {
        // Handle DB preparation error
        $db_error = mysqli_error($conn);
        error_log("Database Error: Could not prepare statement: " . $db_error);
        mysqli_close($conn); // Close connection
        header("Location: ../complaint.html?error=" . urlencode("Database preparation error. Please contact support."));
        exit;
    }
} else {
    // Handle non-POST request
    if (isset($conn) && $conn instanceof mysqli) {
        mysqli_close($conn);
    } // Close conn if open
    header("Location: ../complaint.html"); // Redirect back
    exit;
}

// Fallback connection close (should not be reached ideally)
if (isset($conn) && $conn instanceof mysqli) {
    mysqli_close($conn);
}
