<?php

declare(strict_types=1);

// Step 3c — creates a test charge via the Payments API.
//
// CAVEAT: unlike the OAuth/token endpoints and the Accounting API's
// companyinfo call (which I'm confident about), I have not independently
// verified this exact request body shape against Intuit's current API
// Explorer/docs in this session -- Payments API v4 field names are the
// one part of this scaffold you should double-check yourself at
// https://developer.intuit.com/app/developer/qbpayments/docs/api/resources/all-entities/charges
// before relying on it. It's also not needed for the actual Bookkeeping
// module integration (that uses the Accounting API's JournalEntry, not
// Payments charges) -- included only because it was asked for.
// Run via:  php bin/quickbooks/3-create-test-charge.php

require __DIR__ . '/QuickBooksOAuthClient.php';

use App\Bin\QuickBooks\QuickBooksOAuthClient;

$client = new QuickBooksOAuthClient();

// Intuit publishes dedicated sandbox-only test card numbers for the
// Payments API (never real card numbers) -- verify the current set at
// the docs link above before using this value.
$body = [
    'amount' => '10.00',
    'currency' => 'USD',
    'card' => [
        'number' => '4111111111111111',
        'expMonth' => '12',
        'expYear' => (string) ((int) date('Y') + 1),
        'cvc' => '123',
        'name' => 'Sandbox Test',
    ],
    'context' => [
        'mobile' => false,
        'isEcommerce' => true,
    ],
];

echo $client->authenticatedRequest('POST', QuickBooksOAuthClient::SANDBOX_PAYMENTS_CHARGES_URL, $body) . "\n";
