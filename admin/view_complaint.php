<?php
// --- Includes ---
require_once '../includes/functions.php'; // Ensures getStatusBadgeClass() is loaded if defined here
require_once '../includes/db.php';

// --- Authentication & Validation ---
requireLogin(); // Check if admin is logged in

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    // Redirect if ID is invalid or missing
    header("Location: dashboard.php?error=Invalid Complaint ID");
    exit;
}
$complaint_id = (int)$_GET['id']; // Cast to integer

// --- Fetch Complaint Data ---
$complaint = null; // Initialize variable
$sql = "SELECT * FROM complaints WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $complaint_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $complaint = mysqli_fetch_assoc($result); // Fetch the data
    } else {
        error_log("View Complaint Error: Failed to execute statement for ID {$complaint_id} - " . mysqli_error($conn));
        // Don't close connection yet, might need it for redirect message
    }
    mysqli_stmt_close($stmt); // Close statement is fine here
} else {
    error_log("View Complaint Error: Failed to prepare statement for ID {$complaint_id} - " . mysqli_error($conn));
}


// --- Check if Complaint Found ---
if (!$complaint) {
    if ($conn instanceof mysqli) {
        mysqli_close($conn);
    } // Close connection if open
    header("Location: dashboard.php?error=Complaint not found");
    exit;
}

// --- *** DO NOT CLOSE CONNECTION HERE *** ---
// mysqli_close($conn); // Removed from here

// --- HTML Output ---
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Complaint #<?php echo htmlspecialchars($complaint['id'] ?? 'N/A'); // Use ?? for safety 
                            ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css"> <!-- Adjust path -->
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['admin_email'])): // Add check for safety 
                    ?>
                        <li class="nav-item"><span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($_SESSION['admin_email']); ?></span></li>
                    <?php else: ?>
                        <li class="nav-item"><span class="navbar-text me-3 text-warning">Admin Email Not Found</span></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">View Complaints</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-4"> <!-- Use main tag -->
        <!-- Display Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Complaint Details - ID: <?php echo htmlspecialchars($complaint['id'] ?? 'N/A'); ?></h2>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <!-- Status Display -->
                <div class="me-auto"> <!-- Push status to left -->
                    <span>Status:
                        <span class="badge bg-<?php echo getStatusBadgeClass($complaint['status'] ?? 'Unknown'); // Use ?? null coalescing 
                                                ?>">
                            <?php echo htmlspecialchars($complaint['status'] ?? 'Unknown'); ?>
                        </span>
                    </span>
                </div>

                <!-- Status Update Form -->
                <form action="../api/updateStatus.php" method="post" class="d-inline-block">
                    <input type="hidden" name="complaint_id" value="<?php echo htmlspecialchars($complaint['id'] ?? ''); ?>">
                    <div class="input-group input-group-sm">
                        <select name="new_status" class="form-select" aria-label="Update Status">
                            <option value="Pending" <?php echo (($complaint['status'] ?? '') == 'Pending' ? 'selected' : ''); ?>>Pending</option>
                            <option value="In Review" <?php echo (($complaint['status'] ?? '') == 'In Review' ? 'selected' : ''); ?>>In Review</option>
                            <option value="Resolved" <?php echo (($complaint['status'] ?? '') == 'Resolved' ? 'selected' : ''); ?>>Resolved</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Update</button> <!-- Shortened text -->
                    </div>
                </form>

                <!-- Email Complaint Form -->
                <form action="../api/emailComplaint.php" method="post" class="d-inline-block">
                    <input type="hidden" name="complaint_id" value="<?php echo htmlspecialchars($complaint['id'] ?? ''); ?>">
                    <div class="input-group input-group-sm">
                        <input type="email" name="recipient_email" class="form-control" placeholder="Recipient Email" required aria-label="Recipient Email">
                        <button type="submit" class="btn btn-outline-secondary" title="Email Complaint Details">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope" viewBox="0 0 16 16">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z" />
                            </svg>
                            Email
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">Submitted At:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($complaint['created_at'] ?? ''))); ?></dd>

                    <dt class="col-sm-3">Submitted Anonymously:</dt>
                    <dd class="col-sm-9"><?php echo isset($complaint['is_anonymous']) && $complaint['is_anonymous'] ? 'Yes' : 'No'; ?></dd>

                    <?php if (isset($complaint['is_anonymous']) && !$complaint['is_anonymous']): ?>
                        <dt class="col-sm-3">Complainant Name:</dt>
                        <dd class="col-sm-9"><?php echo !empty($complaint['full_name']) ? htmlspecialchars($complaint['full_name']) : '<em>Not Provided</em>'; ?></dd>

                        <dt class="col-sm-3">Contact Info:</dt>
                        <dd class="col-sm-9"><?php echo !empty($complaint['contact_info']) ? htmlspecialchars($complaint['contact_info']) : '<em>Not Provided</em>'; ?></dd>
                    <?php endif; ?>

                    <dt class="col-sm-3">Department:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($complaint['department'] ?? 'N/A'); ?></dd>

                    <dt class="col-sm-3">Year/Semester:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($complaint['year_semester'] ?? 'N/A'); ?></dd>

                    <dt class="col-sm-3">Incident Date/Time:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($complaint['incident_datetime'] ?? ''))); ?></dd>

                    <dt class="col-sm-3">Location:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($complaint['location'] ?? 'N/A'); ?></dd>

                    <dt class="col-sm-3">Description:</dt>
                    <dd class="col-sm-9">
                        <pre style="white-space: pre-wrap; word-wrap: break-word; background-color: #f0f0f0; padding: 10px; border-radius: 4px;"><?php echo htmlspecialchars($complaint['description'] ?? ''); ?></pre>
                    </dd>

                    <dt class="col-sm-3">Evidence File:</dt>
                    <dd class="col-sm-9">
                        <?php if (!empty($complaint['evidence_path'])): ?>
                            <a href="../<?php echo htmlspecialchars($complaint['evidence_path']); ?>" target="_blank">
                                <?php echo htmlspecialchars(basename($complaint['evidence_path'])); ?>
                            </a>
                            <!-- Optional: You might not need to show the stored path here -->
                            <!-- (Stored at: <?php // echo htmlspecialchars($complaint['evidence_path']); 
                                                ?>) -->
                        <?php else: ?>
                            <em>No evidence uploaded.</em>
                        <?php endif; ?>
                    </dd>
                </dl>
            </div> <!-- End card-body -->
        </div> <!-- End card -->
    </main> <!-- End main container -->

    <!-- Footer -->
    <footer class="text-center mt-auto py-3 bg-light border-top">
        <p class="mb-0">© <?php echo date("Y"); ?> University Name - Admin Area</p>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
// *** CLOSE CONNECTION AT THE VERY END ***
if (isset($conn) && $conn instanceof mysqli) {
    mysqli_close($conn);
}
?>