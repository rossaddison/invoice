<?php

declare(strict_types=1);

// Step 3a — calls the QBO Accounting API's companyinfo endpoint (the
// endpoint our actual Bookkeeping integration cares about, since it's
// part of the Accounting API that JournalEntry also lives in).
// Run via:  php bin/quickbooks/3-get-company-info.php

require __DIR__ . '/QuickBooksOAuthClient.php';

use App\Bin\QuickBooks\QuickBooksOAuthClient;

$client = new QuickBooksOAuthClient();
$realmId = $client->realmId();

$url = QuickBooksOAuthClient::SANDBOX_ACCOUNTING_BASE_URL . "/v3/company/{$realmId}/companyinfo/{$realmId}";

echo $client->authenticatedRequest('GET', $url) . "\n";
