<?php

declare(strict_types=1);

// Step 4 — manually forces a refresh of the stored tokens (the 3-*.php
// scripts already do this automatically once the access token is within
// 60s of expiring, via requireFreshAccessToken() -- this script exists to
// let you exercise/watch the refresh call directly).
// Run via:  php bin/quickbooks/4-refresh-token.php

require __DIR__ . '/QuickBooksOAuthClient.php';

use App\Bin\QuickBooks\QuickBooksOAuthClient;

$client = new QuickBooksOAuthClient();
$tokens = $client->refreshToken();

echo "Refreshed. New tokens stored at runtime/quickbooks-oauth-tokens.json\n";
echo "  expires_in: {$tokens['expires_in']}s\n";
