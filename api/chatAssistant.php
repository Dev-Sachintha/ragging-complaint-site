<?php

/**
 * /ragging-complaint-site/api/chatAssistant.php
 * Backend proxy to securely call the OpenAI API.
 */

// --- Configuration & Includes ---

// IMPORTANT: Set your OpenAI API Key securely. Load it BEFORE use.
$apiKey = null; // Initialize

// Method 1: Environment Variable (Recommended for Production)
$apiKey = getenv('OPENAI_API_KEY');

// Method 2: Config File (Use if Env Vars aren't set up - less secure for public repo)
// Ensure this check happens ONLY if the environment variable wasn't found.
if (!$apiKey && file_exists(__DIR__ . '/../config/openai_config.php')) {
    // Use error suppression potentially, or check file readability first
    @include_once __DIR__ . '/../config/openai_config.php'; // Include config
    if (defined('OPENAI_API_KEY')) {
        $apiKey = OPENAI_API_KEY;
    }
}

// Include Composer autoloader - Check this path carefully!
// This needs to load classes like the one for OpenAI if you use their PHP library,
// or cURL functions if you use cURL directly.
// Even if only using cURL, other includes might need the autoloader.
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // Log a critical error if composer autoloader is missing
    error_log("FATAL ERROR in chatAssistant.php: Composer autoload file not found at " . __DIR__ . '/../vendor/autoload.php');
    http_response_code(500);
    // Send JSON error even if autoloader failed, helps frontend debugging
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server configuration error: Autoloader missing.']);
    exit;
}

// Include other necessary files AFTER autoloader if they use Composer packages
// require_once __DIR__ . '/../includes/db.php'; // Include if needed
// require_once __DIR__ . '/../includes/functions.php'; // Include if needed

// --- Security & Setup ---

// Ensure API Key was loaded successfully
if (empty($apiKey)) {
    error_log("FATAL ERROR in chatAssistant.php: OpenAI API Key is missing or could not be loaded.");
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'AI Assistant configuration error on server. API Key missing.']);
    exit;
}

// Set headers for JSON response *early*
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Invalid request method. Only POST is allowed.']);
    exit;
}

// --- Process Request ---

// Get the user's message from the POST request body (sent as JSON)
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE); // Convert JSON to associative array

// Check if JSON decoding worked
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    error_log("chatAssistant.php - Invalid JSON received: " . json_last_error_msg());
    echo json_encode(['error' => 'Invalid request format.']);
    exit;
}

$userMessage = $input['message'] ?? '';
$conversationHistory = $input['history'] ?? []; // Get optional history

// Basic validation of the incoming message
if (empty(trim($userMessage))) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Message cannot be empty.']);
    exit;
}

// --- Prepare data for OpenAI API ---
$apiUrl = 'https://api.openai.com/v1/chat/completions';
$model = 'gpt-3.5-turbo'; // Cheaper default, switch to gpt-4 if preferred/available

// Construct the messages array
$messages = [];
// System message to guide the AI
$messages[] = ['role' => 'system', 'content' => 'You are a helpful assistant for a university anti-ragging website. Answer questions briefly about the website purpose, ragging definitions/consequences (general knowledge), and how to use the site to report incidents. Emphasize confidentiality and the anonymous option. Do not provide legal advice or opinions on specific cases. If asked unrelated questions, politely state you can only assist with topics related to the anti-ragging portal.'];

// Add previous history (limit its size)
$maxHistory = 5; // Keep last 5 pairs (user+assistant)
$historyToAdd = array_slice($conversationHistory, -$maxHistory * 2);
foreach ($historyToAdd as $entry) {
    if (isset($entry['role']) && isset($entry['content'])) {
        $messages[] = ['role' => $entry['role'], 'content' => $entry['content']];
    }
}
// Add the new user message (sanitize)
$messages[] = ['role' => 'user', 'content' => htmlspecialchars(strip_tags($userMessage))];


$data = [
    'model' => $model,
    'messages' => $messages,
    'max_tokens' => 150, // Limit response length
    'temperature' => 0.6, // Slightly less creative, more factual
    'n' => 1, // Get one response choice
    'stop' => null, // Let the model decide when to stop
];

// --- Use cURL to make the API request ---
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey // Use the securely loaded API key
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 45); // Increase timeout slightly
// Optional: Add User Agent
// curl_setopt($ch, CURLOPT_USERAGENT, 'RaggingComplaintWebsite/1.0');

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErrorNo = curl_errno($ch); // Get cURL error number
$curlError = curl_error($ch);   // Get cURL error message
curl_close($ch);

// --- Process the response ---
if ($curlErrorNo > 0) { // Check if cURL itself had an error
    http_response_code(500); // Internal Server Error
    error_log("cURL Error calling OpenAI (#{$curlErrorNo}): " . $curlError);
    echo json_encode(['error' => 'Failed to connect to the AI assistant service. Please try again later. (cURL Error)']);
} elseif ($httpcode >= 400) { // Check if OpenAI returned an HTTP error (4xx or 5xx)
    http_response_code($httpcode); // Pass OpenAI's error code back
    error_log("OpenAI API Error: HTTP Code {$httpcode}, Response: {$response}");
    $responseData = json_decode($response, true);
    // Try to extract a meaningful message from OpenAI's error structure
    $errorMessage = $responseData['error']['message'] ?? 'Failed to get a valid response from the AI assistant service. (HTTP Error)';
    echo json_encode(['error' => "AI Assistant Error: " . htmlspecialchars($errorMessage)]); // Sanitize OpenAI error

} elseif ($response === false || $response === null || $response === '') {
    http_response_code(500);
    error_log("OpenAI API Error: Empty or invalid response received. HTTP Code: {$httpcode}");
    echo json_encode(['error' => 'Received an invalid or empty response from the AI assistant.']);
} else { // Successful response (HTTP 200 range)
    $responseData = json_decode($response, true);
    // Check if JSON decoding failed after getting a response
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(500);
        error_log("OpenAI API Error: Failed to decode JSON response. Response: " . $response);
        echo json_encode(['error' => 'Failed to understand the response from the AI assistant.']);
    }
    // Check for expected structure in OpenAI response
    elseif (isset($responseData['choices'][0]['message']['content'])) {
        $assistantReply = $responseData['choices'][0]['message']['content'];
        // Send the reply back to the frontend
        echo json_encode(['reply' => trim($assistantReply)]);
    } else {
        // Response structure was unexpected
        http_response_code(500);
        error_log("OpenAI API Error: Unexpected response structure. Response: " . $response);
        echo json_encode(['error' => 'Received an unexpected response format from the AI assistant.']);
    }
}

exit; // Ensure script termination
