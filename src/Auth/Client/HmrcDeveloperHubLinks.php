<?php

declare(strict_types=1);

namespace App\Auth\Client;

/**
 * Direct links into the HMRC Developer Hub web UI (the human portal at
 * developer.service.hmrc.gov.uk, not an API) -- account-level pages plus
 * the per-application management pages that need this app's registered
 * Developer Hub application ID to build.
 *
 * That application ID is a separate value from the OAuth client_id
 * (DEVELOPER_GOV_SANDBOX_HMRC_API_CLIENT_ID) already used for the OAuth2
 * flow -- it's the UUID Developer Hub itself uses in its own URLs (visible
 * in the browser address bar on the "Manage application" page), stored
 * here as DEVELOPER_GOV_SANDBOX_HMRC_API_APPLICATION_ID.
 *
 * Each entry's 'labelKey' is a translation key from resources/messages/*
 * /app.php (mtd.hmrc.developer.hub.* group) rather than a literal English
 * string -- this class stays pure/dependency-free so it's easy to Testo
 * test, and the calling view (which already has $translator available as
 * a common view parameter) resolves the actual text.
 *
 * @see https://developer.service.hmrc.gov.uk/developer/applications
 */
final class HmrcDeveloperHubLinks
{
    private const string BASE = 'https://developer.service.hmrc.gov.uk/developer';

    /**
     * Account-level pages -- no application ID needed.
     *
     * @return list<array{labelKey: string, url: string}>
     */
    public static function accountLinks(): array
    {
        return [
            [
                'labelKey' => 'mtd.hmrc.developer.hub.login',
                'url'      => self::BASE . '/login',
            ],
            [
                'labelKey' => 'mtd.hmrc.developer.hub.profile',
                'url'      => self::BASE . '/profile',
            ],
            [
                'labelKey' => 'mtd.hmrc.developer.hub.email.preferences',
                'url'      => self::BASE . '/profile/email-preferences',
            ],
            [
                'labelKey' => 'mtd.hmrc.developer.hub.change.password',
                'url'      => self::BASE . '/profile/password',
            ],
            [
                'labelKey' => 'mtd.hmrc.developer.hub.security.preferences',
                'url'      => self::BASE . '/profile/security-preferences',
            ],
            [
                'labelKey' => 'mtd.hmrc.developer.hub.add.sandbox.application',
                'url'      => self::BASE . '/application/add/sandbox',
            ],
            [
                'labelKey' => 'mtd.hmrc.developer.hub.add.production.application',
                'url'      => self::BASE . '/applications/add/production',
            ],
            [
                'labelKey' => 'mtd.hmrc.developer.hub.logout',
                'url'      => self::BASE . '/logout',
            ],
        ];
    }

    /**
     * Per-application management pages. Empty when no application ID is
     * configured, since every one of these URLs needs it.
     *
     * @return list<array{labelKey: string, url: string}>
     */
    public static function applicationLinks(string $applicationId): array
    {
        if ($applicationId === '') {
            return [];
        }

        $appBase = self::BASE . '/applications/' . urlencode($applicationId);

        return [
            [
                'labelKey' => 'mtd.hmrc.developer.hub.manage.application',
                'url'      => $appBase . '/manage',
            ],
            [
                'labelKey' => 'mtd.hmrc.developer.hub.subscriptions',
                'url'      => $appBase . '/subscriptions',
            ],
            [
                'labelKey' => 'mtd.hmrc.developer.hub.change.name.and.description',
                'url'      => $appBase . '/change-app-name-and-desc',
            ],
            [
                'labelKey' => 'mtd.hmrc.developer.hub.client.secrets',
                'url'      => $appBase . '/client-secrets',
            ],
            [
                'labelKey' => 'mtd.hmrc.developer.hub.redirect.uris',
                'url'      => $appBase . '/redirect-uris',
            ],
            [
                'labelKey' => 'mtd.hmrc.developer.hub.ip.allowlist',
                'url'      => $appBase . '/ip-allowlist',
            ],
            [
                'labelKey' => 'mtd.hmrc.developer.hub.team.members',
                'url'      => $appBase . '/team-members',
            ],
            [
                'labelKey' => 'mtd.hmrc.developer.hub.tc.and.privacy.policy.url',
                'url'      => $appBase . '/change-tc-and-priv-pol-url',
            ],
            [
                'labelKey' => 'mtd.hmrc.developer.hub.delete.application',
                'url'      => $appBase . '/delete',
            ],
        ];
    }
}
