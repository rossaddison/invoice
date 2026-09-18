<?php

declare(strict_types=1);

// Step 1 — prints the Intuit OAuth2 authorize URL to visit in a browser.
// Run via:  php bin/quickbooks/1-authorize-url.php
//
// After you sign in and consent (picking a sandbox company), Intuit
// redirects to your QUICKBOOKS_API_REDIRECT_URI with ?code=&realmId=&state=
// in the query string. Since redirect_uri here is Intuit's own Quick
// Start page (not a URL this app controls), that page will display those
// values for you to copy -- paste them into 2-get-tokens.php next.

require __DIR__ . '/QuickBooksOAuthClient.php';

use App\Bin\QuickBooks\QuickBooksOAuthClient;

$client = new QuickBooksOAuthClient();
$url = $client->buildAuthorizeUrl();

echo "Visit this URL in a browser, sign in, and consent:\n\n{$url}\n\n";
echo "Then copy the code, realmId and state from the redirect and run:\n";
echo "  php bin/quickbooks/2-get-tokens.php <code> <realmId> <state>\n";
