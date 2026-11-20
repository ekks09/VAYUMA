<?php
// Secrets come from the server environment, never from the browser or repository.
return [
    'provider' => 'nvidia',
    'api_key' => getenv('NVIDIA_API_KEY') ?: '',
    'model' => getenv('NVIDIA_MODEL') ?: '',
    'api_url' => 'https://integrate.api.nvidia.com/v1/chat/completions',
    'timeout' => 30,
];
