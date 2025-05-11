<?php
// /ragging-complaint-site/admin/dashboard.php

require_once '../includes/functions.php'; // Includes helper functions like requireLogin() & getStatusBadgeClass()
require_once '../includes/db.php';       // Database connection

requireLogin(); // Make sure the admin is logged in

// --- Fetch complaints data ---
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$validStatuses = ['Pending', 'In Review', 'Resolved']; // Define allowed statuses

// Base SQL query - selecting necessary columns
$sql = "SELECT id, department, incident_datetime, location, status, created_at, description, evidence_path
        FROM complaints";

// Apply status filter if valid
if (!empty($statusFilter) && in_array($statusFilter, $validStatuses)) {
    // Using prepared statement for safety, even for status filtering
    $sql .= " WHERE status = ?";
    $stmt = mysqli_prepare($conn, $sql . " ORDER BY created_at DESC"); // Prepare with ordering
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $statusFilter);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        error_log("Dashboard Error: Failed to prepare filtered query - " . mysqli_error($conn));
        $result = false; // Ensure result is false on prepare error
    }
} else {
    // No filter or invalid filter, fetch all
    $sql .= " ORDER BY created_at DESC";
    $stmt = null; // No prepared statement needed here if query is static
    $result = mysqli_query($conn, $sql);
}

// Check for query execution errors (for both filtered and non-filtered cases)
if ($result === false) {
    error_log("Dashboard Error: Failed to execute query - " . mysqli_error($conn));
    // Display a user-friendly error or die gracefully
    if ($conn instanceof mysqli) {
        mysqli_close($conn);
    } // Close connection before dying
    die("Error fetching complaints. Please check server logs or contact support.");
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Complaints</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Optional: Style for truncated description column */
        .description-snippet {
            max-width: 250px;
            /* Adjust width as needed */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: help;
            /* Indicate hover for tooltip */
            display: inline-block;
            /* Needed for max-width and ellipsis */
            vertical-align: middle;
            /* Align text nicely */
        }

        /* Ensure DataTables controls fit well */
        .dataTables_wrapper .row {
            margin-bottom: 1rem;
        }

        /* Adjust vertical alignment for table content */
        #complaintsTable td,
        #complaintsTable th {
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['admin_email'])): ?>
                        <li class="nav-item"><span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($_SESSION['admin_email']); ?></span></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="dashboard.php">View Complaints</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-4"> <!-- Added main tag -->
        <h2 class="mb-3">Ragging Complaints</h2>

        <!-- Filter Controls -->
        <form method="get" action="dashboard.php" class="row g-3 mb-4 align-items-end bg-light p-3 rounded border shadow-sm">
            <div class="col-md-4 col-lg-3">
                <label for="statusFilter" class="form-label fw-bold">Filter by Status:</label>
                <select name="status" id="statusFilter" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?php echo ($statusFilter == 'Pending' ? 'selected' : ''); ?>>Pending</option>
                    <option value="In Review" <?php echo ($statusFilter == 'In Review' ? 'selected' : ''); ?>>In Review</option>
                    <option value="Resolved" <?php echo ($statusFilter == 'Resolved' ? 'selected' : ''); ?>>Resolved</option>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="dashboard.php" class="btn btn-secondary ms-2">Clear Filter</a> <!-- Added Clear Filter Button -->
            </div>
            <!-- Optional Export Button Placeholder -->
            <!-- <div class="col-md-auto ms-auto"> -->
            <!-- <a href="export_complaints.php?status=<?php // echo urlencode($statusFilter); 
                                                        ?>" class="btn btn-success"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a> -->
            <!--</div> -->
        </form>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>


        <!-- Complaints Table -->
        <div class="table-responsive card shadow-sm"> <!-- Added card for better look -->
            <div class="card-body"> <!-- Added card-body -->
                <table id="complaintsTable" class="table table-striped table-bordered table-hover" style="width:100%">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Department</th>
                            <th>Incident Dt/Tm</th> <!-- Shortened -->
                            <th>Location</th>
                            <th>Description Snippet</th>
                            <th>Evidence</th>
                            <th>Status</th>
                            <th>Received At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                // Prepare description snippet and tooltip
                                $fullDescription = htmlspecialchars($row['description'] ?? ''); // Ensure description exists and sanitize
                                $descriptionSnippet = mb_strimwidth($fullDescription, 0, 75, "..."); // Truncate multi-byte safe

                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['id'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($row['department'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars(date('Y-m-d H:i', strtotime($row['incident_datetime'] ?? ''))) . "</td>";
                                echo "<td>" . htmlspecialchars($row['location'] ?? 'N/A') . "</td>";

                                // Display Truncated Description with Tooltip
                                echo "<td data-bs-toggle='tooltip' data-bs-placement='top' title='" . $fullDescription . "'><span class='description-snippet'>" . $descriptionSnippet . "</span></td>";

                                // Display Evidence Link
                                echo "<td class='text-center'>"; // Center align content
                                if (!empty($row['evidence_path'])) {
                                    $evidenceUrl = "../" . htmlspecialchars($row['evidence_path']);
                                    echo "<a href='" . $evidenceUrl . "' target='_blank' class='btn btn-sm btn-outline-secondary' title='View Evidence'><svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-paperclip' viewBox='0 0 16 16'><path d='M4.5 3a2.5 2.5 0 0 1 5 0v9a1.5 1.5 0 0 1-3 0V5a.5.5 0 0 1 1 0v7a.5.5 0 0 0 1 0V3a1.5 1.5 0 1 0-3 0v9a2.5 2.5 0 0 0 5 0V5A.5.5 0 0 1 11 5v7a.5.5 0 0 0 1 0V3a2.5 2.5 0 0 1-5 0v9a1.5 1.5 0 0 1-3 0V5a.5.5 0 0 1 1 0v7a.5.5 0 0 0 1 0z'/></svg></a>";
                                } else {
                                    echo "<span class='text-muted fst-italic'>None</span>";
                                }
                                echo "</td>";

                                // Status Badge
                                echo "<td class='text-center'><span class='badge bg-" . getStatusBadgeClass($row['status'] ?? 'Unknown') . "'>" . htmlspecialchars($row['status'] ?? 'Unknown') . "</span></td>";
                                echo "<td>" . htmlspecialchars(date('Y-m-d H:i', strtotime($row['created_at'] ?? ''))) . "</td>";
                                echo "<td class='text-center'><a href='view_complaint.php?id=" . htmlspecialchars($row['id'] ?? '') . "' class='btn btn-sm btn-info'>View Details</a></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9' class='text-center fst-italic'>No complaints found matching the filter.</td></tr>"; // Colspan is 9
                        }
                        ?>
                    </tbody>
                </table>
            </div> <!-- End card-body -->
        </div> <!-- End table-responsive/card -->
    </main> <!-- End main container -->

    <!-- Footer -->
    <footer class="text-center mt-auto py-3 bg-light border-top">
        <p class="mb-0">© <?php echo date("Y"); ?> University Name - Admin Area</p>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Initialize DataTables & Tooltips after the document is ready
        $(document).ready(function() {
            // Initialize DataTables
            $('#complaintsTable').DataTable({
                // "order": [[ 7, "desc" ]], // Default sort by 8th column (Received At) descending
                "columnDefs": [{
                        "orderable": false,
                        "targets": [4, 5, 8]
                    }, // Disable sorting for Description, Evidence, Actions
                    {
                        "searchable": false,
                        "targets": [5, 8]
                    } // Disable searching for Evidence, Actions
                ],
                "language": { // Optional: Customize text
                    "zeroRecords": "No complaints found matching your search",
                    "infoEmpty": "No complaints available"
                }
            });

            // Enable Bootstrap tooltips (for description snippets)
            // Select tooltips specifically within the main container to avoid navbar conflicts
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('main [data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        });
    </script>
</body>

</html>
<?php
// Close resources
if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    mysqli_stmt_close($stmt);
} // Close statement if it was used
if (isset($result) && $result instanceof mysqli_result) {
    mysqli_free_result($result);
} // Free result set if applicable
if (isset($conn) && $conn instanceof mysqli) {
    mysqli_close($conn);
} // Close connection
?>