<?php
require_once __DIR__ . '/bootstrap.php';

define('TWILIO_ACCOUNT_SID', $_ENV['TWILIO_ACCOUNT_SID']);
define('TWILIO_AUTH_TOKEN', $_ENV['TWILIO_AUTH_TOKEN']);
define('TWILIO_PHONE_NUMBER', $_ENV['TWILIO_PHONE_NUMBER']);

define('ADMIN_SMS_RECIPIENTS', [
    $_ENV['ADMIN_SMS_RECIPIENT_1'],
    // $_ENV['ADMIN_SMS_RECIPIENT_2'],
]);
