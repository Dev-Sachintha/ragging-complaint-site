<?php // Add this opening tag if your server requires it, though often not needed at the very start ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Optional: Keep inline styles or move them to style.css */
        body { display: flex; min-height: 100vh; flex-direction: column; background-color: #f8f9fa;}
        main { flex: 1; }
        .login-container {
            max-width: 450px; /* Consistent with style.css */
            margin: 3rem auto; /* Consistent with style.css */
            background-color: #ffffff; /* Consistent with style.css */
            padding: 2rem; /* Adjusted padding */
            border-radius: 0.375rem; /* Standard Bootstrap border-radius */
        }
         .login-container h2 {
            color: #495057;
            margin-bottom: 1.5rem; /* More space below heading */
         }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-secondary shadow-sm">
         <div class="container">
            <a class="navbar-brand" href="index.html">Anti-Ragging Portal - Admin</a>
            <ul class="navbar-nav ms-auto">
                 <li class="nav-item">
                     <a class="nav-link" href="index.html">Back to Main Site</a>
                 </li>
             </ul>
         </div>
    </nav>

    <main class="container d-flex align-items-center justify-content-center">
        <div class="login-container p-4 border rounded shadow-sm">
            <h2 class="text-center">Admin Login</h2>

            <!-- Alert for login errors -->
            <?php
            // This PHP block will now execute because the file is login.php
            if (isset($_GET['error'])):
            ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($_GET['error']); // Sanitize output ?>
                </div>
            <?php
            endif;
            ?>

            <form action="api/adminLogin.php" method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" required autocomplete="email">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
             <!-- Optional: Keep the back link here or rely on navbar -->
             <!-- <p class="mt-3 text-center"><a href="index.html">Back to Main Site</a></p> -->
        </div>
    </main>

    <footer class="text-center py-3 mt-auto" style="background-color: #e9ecef; border-top: 1px solid #dee2e6; color: #6c757d; font-size: 0.9em;">
         <p class="mb-0">© <?php echo date("Y"); ?> University Name. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>