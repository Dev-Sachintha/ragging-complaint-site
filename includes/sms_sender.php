<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoloader
require_once __DIR__ . '/../config/sms_config.php';

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;

function sendSmsNotification($to, $messageBody)
{
    if (!preg_match('/^\+[1-9]\d{1,14}$/', $to)) {
        error_log("Invalid phone number format: " . $to);
        return false;
    }

    if (empty(trim($messageBody))) {
        error_log("Message body cannot be empty.");
        return false;
    }

    $sid    = TWILIO_ACCOUNT_SID;
    $token  = TWILIO_AUTH_TOKEN;
    $from   = TWILIO_PHONE_NUMBER;

    $client = new Client($sid, $token);

    try {
        $client->messages->create($to, [
            'from' => $from,
            'body' => $messageBody
        ]);
        return true;
    } catch (TwilioException $e) {
        error_log("Twilio Error ({$e->getCode()}): {$e->getMessage()}");
        return false;
    } catch (\Exception $e) {
        error_log("General Error: {$e->getMessage()}");
        return false;
    }
}
