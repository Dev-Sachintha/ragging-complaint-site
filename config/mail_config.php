<?php
// /ragging-complaint-site/config/mail_config.php

// ** IMPORTANT: Replace placeholders with your actual SMTP settings **
// ** Consider using environment variables for better security! **

// SMTP server (e.g., smtp.gmail.com) -> Corresponds to this line:
define('SMTP_HOST', 'smtp.gmail.com'); // E.g., 'smtp.gmail.com' or 'smtp.office365.com' or your provider's host

// Port (usually 587 for TLS, 465 for SSL, or 25 for non-secure) -> Corresponds to this line:
define('SMTP_PORT', 587); // CHANGE TO 465 if using SSL, or 25 if needed (uncommon & insecure)

// Username (your email) -> Corresponds to this line:
define('SMTP_USERNAME', 'chamikara24sachintha@gmail.com'); // E.g., 'my.email@gmail.com'

// Password (or app-specific password) -> Corresponds to this line:
define('SMTP_PASSWORD', 'uzkyjzhjpbrvvxqx'); // Your SMTP login password (Use App Password for Gmail)

// Encryption method (TLS or SSL) -> Corresponds to this line:
define('SMTP_SECURE', 'tls'); // CHANGE TO 'ssl' if using Port 465, set to false if using Port 25 (not recommended)

// --- Other settings (usually fine as default) ---
define('SMTP_AUTH', true);      // Almost always true when using username/password

define('MAIL_FROM_ADDRESS', 's.chamikara24@gmail.com'); // The "From" address shown to recipients
define('MAIL_FROM_NAME', 'Anti-Ragging Portal');   // The "From" name shown to recipients

define('ADMIN_EMAIL_RECIPIENTS', [
    'christmassachi@gmail.com',
    // 'admin2@your-university-domain.edu',
]);
