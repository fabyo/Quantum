<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],
    'supports_credentials' => true,
    'allowed_origins' => [env('SANCTUM_STATEFUL_DOMAINS', 'http://localhost:5173')]
];
