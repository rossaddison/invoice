<?php

declare(strict_types=1);

namespace App\Bookkeeping\Infrastructure\QuickBooks;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Setting\SettingRepository;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Everything QuickBooksGateway needs to reach an authenticated QBO company
 * endpoint: credentials, the cached/refreshed OAuth2 access token, and the
 * resolved base/company URL. Split out of QuickBooksGateway purely to keep
 * that class under SonarCloud's 20-method ceiling (php:S1448) — this is a
 * genuinely separate concern (how to connect) from the gateway's own
 * (what to send once connected), not a cosmetic split.
 *
 * OAuth2 endpoint and token-rotation behaviour match
 * `bin/quickbooks/QuickBooksOAuthClient.php` (PR #1339, live-verified
 * against the real Intuit sandbox) exactly — refresh tokens rotate on
 * every use, so every refresh here overwrites the stored access/refresh
 * token pair.
 */
final class QuickBooksConnection
{
    private const string TOKEN_URL = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
    private const string SANDBOX_BASE_URL = 'https://sandbox-quickbooks.api.intuit.com';
    private const string PRODUCTION_BASE_URL = 'https://quickbooks.api.intuit.com';

    private const string KEY_CLIENT_ID = 'bookkeeping_quickbooks_client_id';
    private const string KEY_CLIENT_SECRET = 'bookkeeping_quickbooks_client_secret';
    private const string KEY_REALM_ID = 'bookkeeping_quickbooks_realm_id';
    private const string KEY_REFRESH_TOKEN = 'bookkeeping_quickbooks_refresh_token';
    private const string KEY_ACCESS_TOKEN = 'bookkeeping_quickbooks_access_token';
    private const string KEY_ACCESS_TOKEN_EXPIRES_AT = 'bookkeeping_quickbooks_access_token_expires_at';
    private const string KEY_SANDBOX = 'bookkeeping_quickbooks_sandbox';

    private const string CONTENT_TYPE_JSON = 'application/json';

    public function __construct(
        private readonly SettingRepository $settings,
        private readonly LoggerInterface $logger,
        private readonly HttpClient $httpClient,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->clientId() !== ''
            && $this->clientSecret() !== ''
            && $this->realmId() !== ''
            && $this->storedRefreshToken() !== '';
    }

    public function companyUrl(): string
    {
        return $this->baseUrl() . '/v3/company/' . rawurlencode($this->realmId());
    }

    /**
     * @return array<string, string>
     */
    public function authHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => self::CONTENT_TYPE_JSON,
            'Accept' => self::CONTENT_TYPE_JSON,
        ];
    }

    /**
     * Returns a cached access token when still valid for more than 60s,
     * otherwise refreshes (and persists the rotated pair) first.
     */
    public function accessToken(): ?string
    {
        $cached = $this->settings->getSetting(self::KEY_ACCESS_TOKEN);
        $expiresAt = (int) $this->settings->getSetting(self::KEY_ACCESS_TOKEN_EXPIRES_AT);
        if ($cached !== '' && $expiresAt - time() > 60) {
            return $cached;
        }

        return $this->refreshAccessToken();
    }

    private function baseUrl(): string
    {
        return $this->settings->getSetting(self::KEY_SANDBOX) === '1'
            ? self::SANDBOX_BASE_URL
            : self::PRODUCTION_BASE_URL;
    }

    private function clientId(): string
    {
        return $this->settings->getSetting(self::KEY_CLIENT_ID);
    }

    private function clientSecret(): string
    {
        $encoded = $this->settings->getSetting(self::KEY_CLIENT_SECRET);
        return $encoded !== '' ? (string) $this->settings->decode($encoded) : '';
    }

    private function realmId(): string
    {
        return $this->settings->getSetting(self::KEY_REALM_ID);
    }

    private function storedRefreshToken(): string
    {
        $encoded = $this->settings->getSetting(self::KEY_REFRESH_TOKEN);
        return $encoded !== '' ? (string) $this->settings->decode($encoded) : '';
    }

    private function refreshAccessToken(): ?string
    {
        $refreshToken = $this->storedRefreshToken();
        if ($refreshToken === '') {
            return null;
        }

        return $this->completeTokenRefresh($refreshToken);
    }

    /**
     * Split out of refreshAccessToken() purely to keep its own return
     * count within SonarCloud's php:S1142 limit (3) — the stored-refresh-
     * token guard above already covers the "nothing to refresh with" case,
     * so this only needs to cover the HTTP round-trip's own outcomes.
     */
    private function completeTokenRefresh(string $refreshToken): ?string
    {
        try {
            $response = $this->httpClient->post(self::TOKEN_URL, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->clientId() . ':' . $this->clientSecret()),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => self::CONTENT_TYPE_JSON,
                ],
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ],
            ]);
            /** @var array{access_token?: string, refresh_token?: string, expires_in?: int} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $accessToken = $data['access_token'] ?? null;
            $newRefreshToken = $data['refresh_token'] ?? null;
            $expiresIn = $data['expires_in'] ?? null;
            if ($accessToken === null || $newRefreshToken === null || $expiresIn === null) {
                $this->logger->error('QuickBooks token refresh: unexpected response shape.');
                return null;
            }

            $this->persistTokens($accessToken, $newRefreshToken, $expiresIn);
            return $accessToken;
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('QuickBooks token refresh failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function persistTokens(string $accessToken, string $refreshToken, int $expiresIn): void
    {
        $this->persistSetting(self::KEY_ACCESS_TOKEN, $accessToken);
        $this->persistSetting(self::KEY_ACCESS_TOKEN_EXPIRES_AT, (string) (time() + $expiresIn));
        $this->persistSetting(self::KEY_REFRESH_TOKEN, (string) $this->settings->encode($refreshToken));
    }

    private function persistSetting(string $key, string $value): void
    {
        $setting = $this->settings->withKey($key) ?? new Setting(setting_key: $key);
        $setting->setSettingValue($value);
        $this->settings->save($setting);
    }
}
