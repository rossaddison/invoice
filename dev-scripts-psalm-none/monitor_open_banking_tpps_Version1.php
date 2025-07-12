<?php

declare(strict_types=1);

/**
 * Usage: How to use:
 * Save as monitor_open_banking_tpps.php.
 * Run with php monitor_open_banking_tpps.php.
 * The script checks each endpoint for each TPP, printing status:
 * OK (code) if reachable (even if requires auth).
 * FAIL (code or error) if not reachable.
 * Tip:
 * For production, add logging, email alerts, or extend with authentication for deeper checks as needed!
 */

/**
 * Open Banking TPP Endpoint Monitor (PHP Version)
 * Checks availability of known endpoints for each listed TPP.
 * Usage: php monitor_open_banking_tpps.php
 * Requires PHP 7.4+ with curl enabled.
 */

$tpps = [
    [
        'name' => 'Wonderful',
        'documentationUrl' => 'https://wonderful.gitbook.io/open-banking/',
        'endpoints' => [
            'authUrl' => 'https://api.wonderful.one/oauth2/authorize',
            'tokenUrl' => 'https://api.wonderful.one/oauth2/token',
            'apiBaseUrl' => 'https://api.wonderful.one/open-banking/v3.1/',
            'userinfoUrl' => 'https://api.wonderful.one/openid/userinfo',
        ],
    ],
    [
        'name' => 'Tink',
        'documentationUrl' => 'https://docs.tink.com/resources/api-reference/open-banking-apis',
        'endpoints' => [
            'authUrl' => 'https://oauth.tink.com/authorize/',
            'tokenUrl' => 'https://oauth.tink.com/api/v1/oauth/token',
            'apiBaseUrl' => 'https://api.tink.com/data/v2/',
            'userinfoUrl' => 'https://api.tink.com/api/v1/user-info',
        ],
    ],
    [
        'name' => 'TrueLayer',
        'documentationUrl' => 'https://docs.truelayer.com/docs/authentication',
        'endpoints' => [
            'authUrl' => 'https://auth.truelayer.com/',
            'tokenUrl' => 'https://auth.truelayer.com/connect/token',
            'apiBaseUrl' => 'https://api.truelayer.com/data/v1/',
            'userinfoUrl' => 'https://auth.truelayer.com/userinfo',
        ],
    ],
    [
        'name' => 'Fintecture',
        'documentationUrl' => 'https://docs.fintecture.com/docs/authentication',
        'endpoints' => [
            'authUrl' => 'https://api.fintecture.com/authorize',
            'tokenUrl' => 'https://api.fintecture.com/token',
            'apiBaseUrl' => 'https://api.fintecture.com/',
        ],
    ],
    [
        'name' => 'Salt Edge',
        'documentationUrl' => 'https://docs.saltedge.com/',
        'endpoints' => [
            'authUrl' => 'https://www.saltedge.com/api/openbanking/v1/authorize',
            'tokenUrl' => 'https://www.saltedge.com/api/openbanking/v1/token',
            'apiBaseUrl' => 'https://www.saltedge.com/api/openbanking/v1/',
        ],
    ],
    [
        'name' => 'Yapily',
        'documentationUrl' => 'https://docs.yapily.com/guides/authentication/',
        'endpoints' => [
            'authUrl' => 'https://auth.yapily.com/oauth2/authorize',
            'tokenUrl' => 'https://auth.yapily.com/oauth2/token',
            'apiBaseUrl' => 'https://api.yapily.com/',
        ],
    ],
    [
        'name' => 'Nordigen',
        'documentationUrl' => 'https://nordigen.com/en/account_information_documenation/integration/',
        'endpoints' => [
            'authUrl' => 'https://ob.nordigen.com/api/v2/token/new/',
            'tokenUrl' => 'https://ob.nordigen.com/api/v2/token/new/',
            'apiBaseUrl' => 'https://ob.nordigen.com/api/v2/',
        ],
    ],
    [
        'name' => 'Token.io',
        'documentationUrl' => 'https://developer.token.io/docs/authentication',
        'endpoints' => [
            'authUrl' => 'https://id.sandbox.token.io/oauth/authorize',
            'tokenUrl' => 'https://id.sandbox.token.io/oauth/token',
            'apiBaseUrl' => 'https://api.sandbox.token.io/',
            'userinfoUrl' => 'https://id.sandbox.token.io/userinfo',
        ],
    ],
    [
        'name' => 'Plaid',
        'documentationUrl' => 'https://plaid.com/docs/open-banking/',
        'endpoints' => [
            'authUrl' => 'https://sandbox.plaid.com/oauth/authorize',
            'tokenUrl' => 'https://sandbox.plaid.com/oauth/token',
            'apiBaseUrl' => 'https://sandbox.plaid.com/',
        ],
    ],
    [
        'name' => 'MX',
        'documentationUrl' => 'https://developer.mx.com/docs/api',
        'endpoints' => [
            'authUrl' => 'https://int-api.mx.com/oauth2/authorize',
            'tokenUrl' => 'https://int-api.mx.com/oauth2/token',
            'apiBaseUrl' => 'https://int-api.mx.com/',
        ],
    ],
    [
        'name' => 'Belvo',
        'documentationUrl' => 'https://developers.belvo.com/docs/api-reference',
        'endpoints' => [
            'authUrl' => 'https://sandbox.belvo.com/oauth/authorize',
            'tokenUrl' => 'https://sandbox.belvo.com/oauth/token',
            'apiBaseUrl' => 'https://sandbox.belvo.com/api/',
        ],
    ],
    [
        'name' => 'Finbox',
        'documentationUrl' => 'https://finbox.com/docs/api',
        'endpoints' => [
            'authUrl' => 'https://api.finbox.com/oauth/authorize',
            'tokenUrl' => 'https://api.finbox.com/oauth/token',
            'apiBaseUrl' => 'https://api.finbox.com/v1/',
        ],
    ],
    [
        'name' => 'Okra',
        'documentationUrl' => 'https://docs.okra.ng/docs/okra-api-reference',
        'endpoints' => [
            'authUrl' => 'https://okra.ng/oauth/authorize',
            'tokenUrl' => 'https://okra.ng/oauth/token',
            'apiBaseUrl' => 'https://api.okra.ng/v2/',
        ],
    ],
    [
        'name' => 'Basiq',
        'documentationUrl' => 'https://api.basiq.io/reference',
        'endpoints' => [
            'authUrl' => 'https://auth.basiq.io/authorize',
            'tokenUrl' => 'https://auth.basiq.io/token',
            'apiBaseUrl' => 'https://au-api.basiq.io/',
        ],
    ],
];

/**
 * Check endpoint via CURL.
 * Returns [bool $ok, int|string $status]
 */
function check_endpoint($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Consider 200, 204, 401, 403 as "OK" (endpoint exists but may require auth)
    if (in_array($code, [200, 204, 401, 403])) {
        return [true, $code];
    }

    // Some endpoints may not support HEAD/NOBODY, try GET as fallback
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $resp = curl_exec($ch);
    $code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if (in_array($code2, [200, 204, 401, 403])) {
        return [true, $code2];
    }

    if ($code2 == 0 && $err) {
        return [false, $err];
    }

    return [false, $code2];
}

echo 'Open Banking TPP Endpoint Monitor - ' . gmdate('c') . "\n";
foreach ($tpps as $tpp) {
    echo "\nProvider: {$tpp['name']}\nDocs: {$tpp['documentationUrl']}\n";
    foreach ($tpp['endpoints'] as $ep_name => $ep_url) {
        [$ok, $status] = check_endpoint($ep_url);
        $status_str = $ok ? 'OK' : 'FAIL';
        echo "  {$ep_name}: $ep_url ... $status_str ($status)\n";
    }
}
