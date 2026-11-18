<?php
/**
 * VAYUMA AI configuration.
 * Never store your actual API key in this file.
 */

return [
    'api_key' => getenv('OPENAI_API_KEY') ?: '',
    'model' => 'gpt-4.1-mini',
    'api_url' => 'https://api.openai.com/v1/responses',
    'timeout' => 45,
];