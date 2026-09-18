<?php

declare(strict_types=1);

// Step 2 — trades the authorization code for an access/refresh token
// pair and stores them at runtime/quickbooks-oauth-tokens.json (git-ignored
// -- see runtime/.gitignore).
// Run via:  php bin/quickbooks/2-get-tokens.php <code> <realmId> <state>
// (all three copied from the redirect after 1-authorize-url.php's consent step)

require __DIR__ . '/QuickBooksOAuthClient.php';

use App\Bin\QuickBooks\QuickBooksOAuthClient;

$code = $argv[1] ?? null;
$realmId = $argv[2] ?? null;
$state = $argv[3] ?? null;
if (!is_string($code) || !is_string($realmId) || !is_string($state) || $code === '' || $realmId === '' || $state === '') {
    fwrite(STDERR, "Usage: php bin/quickbooks/2-get-tokens.php <code> <realmId> <state>\n");
    exit(1);
}

$client = new QuickBooksOAuthClient();
$tokens = $client->exchangeCode($code, $realmId, $state);

echo "Tokens stored at runtime/quickbooks-oauth-tokens.json\n";
echo "  realmId:     {$tokens['realmId']}\n";
echo "  expires_in:  {$tokens['expires_in']}s\n";
echo "\nNow try:\n";
echo "  php bin/quickbooks/3-get-company-info.php\n";
echo "  php bin/quickbooks/3-get-user-info.php\n";
