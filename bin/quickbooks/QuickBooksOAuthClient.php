<?php

declare(strict_types=1);

namespace App\Bin\QuickBooks;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

/**
 * Small standalone helper for manually validating the Intuit OAuth 2.0
 * authorization_code flow against the QuickBooks sandbox, ahead of
 * building the real App\Auth\Client\Intuit (following
 * App\Auth\Client\DeveloperSandboxHmrc's own Yiisoft\Yii\AuthClient\OAuth2
 * pattern) for the Bookkeeping module's QuickBooksGateway.
 *
 * Not wired into the app's own Yii3 DI container -- these are one-off
 * `php bin/quickbooks/N-*.php` scripts you run by hand while testing.
 *
 * Endpoints and field names below are taken from Intuit's own
 * authentication-and-authorization/oauth-2.0 docs and this project's
 * README template exactly as given -- nothing invented.
 *
 * @see https://developer.intuit.com/app/developer/qbo/docs/develop/authentication-and-authorization/oauth-2.0
 */
final class QuickBooksOAuthClient
{
    private const string AUTHORIZE_URL = 'https://appcenter.intuit.com/connect/oauth2';
    private const string TOKEN_URL = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
    public const string SANDBOX_ACCOUNTING_BASE_URL = 'https://sandbox-quickbooks.api.intuit.com';
    public const string SANDBOX_OPENID_USERINFO_URL = 'https://sandbox-accounts.platform.intuit.com/v1/openid_connect/userinfo';
    public const string SANDBOX_PAYMENTS_CHARGES_URL = 'https://sandbox.api.intuit.com/quickbooks/v4/payments/charges';

    private readonly string $clientId;
    private readonly string $clientSecret;
    private readonly string $redirectUri;
    private readonly string $scope;
    private readonly string $stateFile;
    private readonly string $tokenFile;
    private readonly Client $http;

    public function __construct()
    {
        $root = dirname(__DIR__, 2);
        Dotenv::createImmutable($root)->safeLoad();

        $this->clientId = $this->requireEnv('QUICKBOOKS_API_CLIENT_ID');
        $this->clientSecret = $this->requireEnv('QUICKBOOKS_API_CLIENT_SECRET');
        $this->redirectUri = $this->requireEnv('QUICKBOOKS_API_REDIRECT_URI');
        $scope = $_ENV['QUICKBOOKS_API_SCOPE'] ?? null;
        $this->scope = (is_string($scope) && $scope !== '') ? $scope : 'com.intuit.quickbooks.accounting';

        $this->stateFile = $root . '/runtime/quickbooks-oauth-state.txt';
        $this->tokenFile = $root . '/runtime/quickbooks-oauth-tokens.json';
        $this->http = new Client(['timeout' => 30]);
    }

    /**
     * Reads from $_ENV, not getenv() -- confirmed live that this
     * environment's php.ini doesn't have putenv() active (or
     * variables_order excludes E), so Dotenv::safeLoad() populates
     * $_ENV/$_SERVER correctly but getenv() sees nothing. $_ENV is the
     * more reliable read with phpdotenv v5 in general, not just a
     * workaround for this one machine.
     */
    private function requireEnv(string $name): string
    {
        $value = $_ENV[$name] ?? null;
        if (!is_string($value) || $value === '') {
            throw new RuntimeException(
                "Missing required environment variable: {$name}. Copy .env.example's QUICKBOOKS_* entries into your own .env first."
            );
        }
        return $value;
    }

    /**
     * Step 1 — builds the authorize URL and persists the random $state
     * locally so exchangeCode() can verify it (CSRF protection on the
     * redirect back) -- never skip this check just because the sandbox
     * redirect_uri happens to be Intuit's own Quick Start page rather
     * than a URL this app controls.
     */
    public function buildAuthorizeUrl(): string
    {
        $state = bin2hex(random_bytes(16));
        file_put_contents($this->stateFile, $state);

        $query = http_build_query([
            'client_id' => $this->clientId,
            'scope' => $this->scope,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'state' => $state,
        ]);

        return self::AUTHORIZE_URL . '?' . $query;
    }

    /**
     * Step 2 — exchanges the code Intuit's redirect handed back for an
     * access/refresh token pair, after verifying $state matches what
     * buildAuthorizeUrl() generated.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int, expires_at: int, realmId: string}
     */
    public function exchangeCode(string $code, string $realmId, string $state): array
    {
        $expectedState = trim((string) @file_get_contents($this->stateFile));
        if ($expectedState === '' || !hash_equals($expectedState, $state)) {
            throw new RuntimeException(
                'State mismatch -- got a different state back than buildAuthorizeUrl() generated. ' .
                'Re-run 1-authorize-url.php and use a fresh link.'
            );
        }

        try {
            $response = $this->http->post(self::TOKEN_URL, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException('Token exchange failed: ' . $e->getMessage(), previous: $e);
        }

        /** @var array{access_token?: string, refresh_token?: string, expires_in?: int} $body */
        $body = json_decode((string) $response->getBody(), true) ?? [];
        if (!isset($body['access_token'], $body['refresh_token'], $body['expires_in'])) {
            throw new RuntimeException('Unexpected token response: ' . $response->getBody());
        }

        $tokens = [
            'access_token' => $body['access_token'],
            'refresh_token' => $body['refresh_token'],
            'expires_in' => $body['expires_in'],
            'expires_at' => time() + $body['expires_in'],
            'realmId' => $realmId,
        ];
        $this->saveTokens($tokens);
        @unlink($this->stateFile);

        return $tokens;
    }

    /**
     * Step 4 — refreshes using the stored refresh_token, overwriting the
     * token file with the new pair. QuickBooks refresh tokens rotate on
     * every use (the old one stops working), so the file is always the
     * only valid copy after this runs.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int, expires_at: int, realmId: string}
     */
    public function refreshToken(): array
    {
        $stored = $this->loadTokens();

        try {
            $response = $this->http->post(self::TOKEN_URL, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $stored['refresh_token'],
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException('Token refresh failed: ' . $e->getMessage(), previous: $e);
        }

        /** @var array{access_token?: string, refresh_token?: string, expires_in?: int} $body */
        $body = json_decode((string) $response->getBody(), true) ?? [];
        if (!isset($body['access_token'], $body['refresh_token'], $body['expires_in'])) {
            throw new RuntimeException('Unexpected refresh response: ' . $response->getBody());
        }

        $tokens = [
            'access_token' => $body['access_token'],
            'refresh_token' => $body['refresh_token'],
            'expires_in' => $body['expires_in'],
            'expires_at' => time() + $body['expires_in'],
            'realmId' => $stored['realmId'],
        ];
        $this->saveTokens($tokens);

        return $tokens;
    }

    /**
     * @param array{access_token: string, refresh_token: string, expires_in: int, expires_at: int, realmId: string} $tokens
     */
    private function saveTokens(array $tokens): void
    {
        $json = json_encode($tokens, JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new RuntimeException('Failed to encode tokens as JSON.');
        }
        if (file_put_contents($this->tokenFile, $json) === false) {
            throw new RuntimeException("Failed to write {$this->tokenFile}.");
        }
    }

    /**
     * @return array{access_token: string, refresh_token: string, expires_in: int, expires_at: int, realmId: string}
     */
    public function loadTokens(): array
    {
        if (!file_exists($this->tokenFile)) {
            throw new RuntimeException('No stored tokens -- run 2-get-tokens.php first.');
        }

        /** @var array{access_token?: string, refresh_token?: string, expires_in?: int, expires_at?: int, realmId?: string}|null $tokens */
        $tokens = json_decode((string) file_get_contents($this->tokenFile), true);
        if (
            $tokens === null
            || !isset($tokens['access_token'], $tokens['refresh_token'], $tokens['expires_in'], $tokens['expires_at'], $tokens['realmId'])
        ) {
            throw new RuntimeException('Token file is corrupt -- delete runtime/quickbooks-oauth-tokens.json and re-run 2-get-tokens.php.');
        }

        return $tokens;
    }

    /**
     * Returns a valid access token, refreshing first if the stored one
     * has expired (or is within 60s of expiring).
     */
    public function requireFreshAccessToken(): string
    {
        $tokens = $this->loadTokens();
        if ($tokens['expires_at'] - time() > 60) {
            return $tokens['access_token'];
        }

        return $this->refreshToken()['access_token'];
    }

    public function realmId(): string
    {
        return $this->loadTokens()['realmId'];
    }

    /**
     * @param array<string, mixed>|null $jsonBody
     */
    public function authenticatedRequest(string $method, string $url, ?array $jsonBody = null): string
    {
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->requireFreshAccessToken(),
                'Accept' => 'application/json',
            ],
        ];
        if ($jsonBody !== null) {
            $options['json'] = $jsonBody;
        }

        try {
            $response = $this->http->request($method, $url, $options);
        } catch (GuzzleException $e) {
            throw new RuntimeException("Request to {$url} failed: " . $e->getMessage(), previous: $e);
        }

        return (string) $response->getBody();
    }
}
