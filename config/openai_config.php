<?php

/**
 * /ragging-complaint-site/config/openai_config.php
 *
 * Configuration for OpenAI API Key.
 *
 * !!! IMPORTANT SECURITY WARNING !!!
 * This method of storing the API key is simple but less secure than
 * environment variables, especially if this 'config' directory is
 * accessible via the web or if the code is committed to public repositories.
 * Consider using environment variables for production.
 */

// --- OpenAI API Key ---
// Replace 'sk-YourSecretAPIKeyGoesHere' with your actual OpenAI Secret API Key.
// Get your key from: https://platform.openai.com/account/api-keys


require_once __DIR__ . '/bootstrap.php';

define('OPENAI_API_KEY', $_ENV['OPENAI_API_KEY']);

