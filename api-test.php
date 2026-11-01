<?php

require_once 'vendor/autoload.php';

// استفاده از کتابخانه کلاینت PHP OpenAI (https://github.com/openai-php/client)
$apiKey = getenv('aa-wqzZ1hPijiIjrv9LJhGLp2MMULmvN6ZOkfWF0oAfs99s7MF2');

if (!$apiKey) {
    die("AvalAI API key not found. Please set the AVALAI_API_KEY environment variable.");
}

// Your custom base URL
$customBaseUrl = 'https://api.avalai.ir/v1';

// Create a custom client instance using the factory
$client = OpenAI::factory()
    ->withApiKey($apiKey)
    ->withBaseUri($customBaseUrl)
    ->make();