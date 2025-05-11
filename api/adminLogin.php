<?php
require_once '../includes/db.php'; // Ensure DB connection is included

// --- Start Session ---
// It's crucial session_start() is called BEFORE any output
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start the session to store login state
}

// Initialize variables
$error = '';
$stmt = null; // Keep track of the statement resource

// Check DB connection immediately after include (assuming db.php sets $conn)
if ($conn === false) {
    error_log("Admin Login DB Connection Error at start: " . mysqli_connect_error());
    $error = "Database connection error. Please try again later.";
    // If connection failed, redirect immediately
    header("location: ../login.php?error=" . urlencode($error));
    exit;
}

// --- Process Login ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Prepare SQL to prevent SQL injection
        $sql = "SELECT id, email, password_hash FROM admins WHERE email = ?";

        $stmt = mysqli_prepare($conn, $sql); // Assign to $stmt
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                // Check if email exists
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $db_email, $hashed_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        // Verify password
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, regenerate session ID for security FIRST
                            session_regenerate_id(true);

                            // Store data in session variables
                            $_SESSION["admin_logged_in"] = true;
                            $_SESSION["admin_id"] = $id;
                            $_SESSION["admin_email"] = $db_email;

                            // SUCCESS: Close resources and redirect
                            mysqli_stmt_close($stmt);
                            mysqli_close($conn); // Close connection ONLY on success exit path here
                            header("location: ../admin/dashboard.php");
                            exit; // IMPORTANT: exit after redirect header

                        } else {
                            // Invalid password
                            $error = 'Invalid email or password.';
                        }
                    } else {
                        // Error fetching result
                        error_log("Admin Login Error: Failed to fetch row after finding user.");
                        $error = "An internal error occurred during fetch. Please try again.";
                    }
                } else {
                    // Email doesn't exist
                    $error = 'Invalid email or password.'; // Keep error message generic
                }
            } else {
                // Statement execution failed
                error_log("Admin Login Error: Statement execution failed - " . mysqli_error($conn));
                $error = "Oops! Something went wrong executing. Please try again later.";
            }
            // Close statement ONLY if it was successfully prepared
            // mysqli_stmt_close($stmt); // Moved this cleanup lower

        } else {
            // Statement preparation failed
            error_log("Admin Login Error: Statement preparation failed - " . mysqli_error($conn));
            $error = "Oops! Something went wrong preparing. Please try again later.";
        }
    } // End basic validation else

    // --- Cleanup Statement if Prepared ---
    // Close the statement if it exists (was successfully prepared)
    if ($stmt instanceof mysqli_stmt) {
        mysqli_stmt_close($stmt);
    }

    // --- Redirect on Error (if any occurred above) ---
    if (!empty($error)) {
        // Close connection ONLY if an error occurred (and wasn't closed on success)
        if ($conn instanceof mysqli) { // Check if connection variable is valid
            mysqli_close($conn);
        }
        header("location: ../login.php?error=" . urlencode($error));
        exit; // IMPORTANT: exit after redirect header
    }
    // If we somehow reach here without error or success exit (shouldn't happen in POST)
    // Fall through just in case, connection will be closed below if still open.

} else {
    // --- Not a POST request ---
    // Close connection if open
    if ($conn instanceof mysqli) {
        mysqli_close($conn);
    }
    header("location: ../login.php"); // Redirect non-POST requests
    exit; // IMPORTANT: exit after redirect header
}

// --- Final Cleanup (Shouldn't be reached if logic is correct, but safe fallback) ---
if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
