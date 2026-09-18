<?php

declare(strict_types=1);

// Step 3b — calls the OpenID Connect userinfo endpoint. Requires the
// openid/profile/email/phone/address scopes to have been requested in
// Step 1 (QUICKBOOKS_API_SCOPE in .env).
// Run via:  php bin/quickbooks/3-get-user-info.php

require __DIR__ . '/QuickBooksOAuthClient.php';

use App\Bin\QuickBooks\QuickBooksOAuthClient;

$client = new QuickBooksOAuthClient();

echo $client->authenticatedRequest('GET', QuickBooksOAuthClient::SANDBOX_OPENID_USERINFO_URL) . "\n";
