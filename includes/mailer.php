<?php

/**
 * /ragging-complaint-site/includes/mailer.php
 *
 * Helper function to send emails using PHPMailer and SMTP configuration.
 */

// --- Prerequisites ---
// 1. Make sure you have run `composer require phpmailer/phpmailer` in your project root.
// 2. Make sure `/vendor/autoload.php` exists.
// 3. Make sure `/config/mail_config.php` exists and contains your SMTP defines.

// Include Composer's autoloader FIRST - this makes PHPMailer classes available
require_once __DIR__ . '/../vendor/autoload.php';
// Include your configuration file which defines SMTP constants
require_once __DIR__ . '/../config/mail_config.php';

// Import PHPMailer classes into the global namespace for easier use
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException; // Alias Exception to avoid conflicts
use PHPMailer\PHPMailer\SMTP;

/**
 * Sends an email notification using PHPMailer.
 *
 * Reads SMTP configuration from constants defined in mail_config.php.
 * Logs errors using error_log().
 *
 * @param string|array $to Recipient email address or an array of email addresses.
 * @param string $subject The subject line of the email.
 * @param string $bodyHTML The main content of the email in HTML format.
 * @param string $bodyText Optional plain text version of the email body for non-HTML clients. If empty, it will be auto-generated from HTML.
 * @param string|null $replyToEmail Optional email address to set as the Reply-To address.
 * @param string $replyToName Optional name associated with the Reply-To address.
 * @return bool Returns true if the email was sent successfully, false otherwise.
 */
function sendNotificationEmail($to, $subject, $bodyHTML, $bodyText = '', $replyToEmail = null, $replyToName = '')
{
    // Verify essential config constants are defined
    if (!defined('SMTP_HOST') || !defined('SMTP_PORT') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD') || !defined('MAIL_FROM_ADDRESS')) {
        error_log("Mailer Error: Required SMTP configuration constants (SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD, MAIL_FROM_ADDRESS) are not defined in mail_config.php.");
        return false;
    }

    // Create a new PHPMailer instance; passing `true` enables exceptions
    $mail = new PHPMailer(true);

    try {
        // --- Server Settings ---
        // Enable verbose debug output (Uncomment ONLY for local testing/debugging)
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Shows client/server messages
        // $mail->Debugoutput = 'error_log';   // Send debug output to PHP's error log instead of echo

        // Configure SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = defined('SMTP_AUTH') ? SMTP_AUTH : true; // Default to true if AUTH is expected
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;

        // Set encryption based on config ('tls', 'ssl')
        $smtpSecureLower = defined('SMTP_SECURE') ? strtolower(SMTP_SECURE) : 'tls'; // Default to tls
        if ($smtpSecureLower === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use 'ssl' constant equivalent
        } elseif ($smtpSecureLower === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use 'tls' constant equivalent
        } else {
            $mail->SMTPSecure = false; // Or handle as error if encryption is mandatory
        }

        $mail->Port       = (int)SMTP_PORT; // Ensure port is an integer

        // --- Recipients ---
        $fromAddr = MAIL_FROM_ADDRESS;
        $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Web Server';
        $mail->setFrom($fromAddr, $fromName);

        // Add recipient(s)
        $validRecipientFound = false;
        if (is_array($to)) {
            foreach ($to as $recipient) {
                $recipient = trim($recipient);
                if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    $mail->addAddress($recipient);
                    $validRecipientFound = true;
                } else {
                    error_log("Mailer Warning: Invalid recipient address skipped in array: " . $recipient);
                }
            }
        } elseif (is_string($to) && filter_var(trim($to), FILTER_VALIDATE_EMAIL)) {
            $mail->addAddress(trim($to));
            $validRecipientFound = true;
        }

        // If no valid recipients were added, stop and return false
        if (!$validRecipientFound) {
            error_log("Mailer Error: No valid recipient addresses provided for email with subject: " . $subject);
            return false;
        }

        // --- Optional Reply-To ---
        if ($replyToEmail && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyToEmail, $replyToName ?? ''); // Add Reply-To if provided
        }

        // --- Content ---
        $mail->isHTML(true); // Set email format to HTML
        $mail->CharSet = PHPMailer::CHARSET_UTF8; // Use UTF-8 characters
        $mail->Subject = $subject;
        $mail->Body    = $bodyHTML;
        // Create plain text version if not provided
        $mail->AltBody = !empty($bodyText) ? $bodyText : strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $bodyHTML));

        // --- Send ---
        $mail->send();
        return true; // Email sent successfully

    } catch (PHPMailerException $e) {
        // PHPMailer exceptions (e.g., connection, authentication)
        error_log("Mailer Error [PHPMailer Exception]: Message could not be sent. Mailer Error: {$mail->ErrorInfo} --- SMTP Host: " . SMTP_HOST . ", Port: " . SMTP_PORT . ", Username: " . SMTP_USERNAME);
        return false; // Email sending failed
    } catch (Exception $e) {
        // Catch other potential general exceptions during setup
        error_log("Mailer Error [General Exception]: Failed to set up or send email. Error: {$e->getMessage()}");
        return false; // Email sending failed
    }
} // End of sendNotificationEmail function
