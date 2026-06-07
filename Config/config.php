<?php
return [
    'audience'           => env('MSTEAMS_AUDIENCE'),
    'jwks_cache_ttl'     => env('MSTEAMS_JWKS_TTL', 3600),

    // License Configuration — all credentials via .env only, never hardcoded
    'license_server_url' => env('MSTEAMS_LICENSE_SERVER_URL', 'https://staging.managedfreescout.com'),
    'consumer_key'       => env('MSTEAMS_CONSUMER_KEY'),
    'consumer_secret'    => env('MSTEAMS_CONSUMER_SECRET'),
    'product_id'         => env('MSTEAMS_PRODUCT_ID', 834),
    'software'           => 2,
];
