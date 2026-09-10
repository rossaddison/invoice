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
 * The two path tables below are the only per-link data; buildLinks() is
 * the single place that turns a table into the {labelKey, url} shape both
 * public methods return, so there's one copy of that shape rather than
 * seventeen near-identical array literals.
 *
 * @see https://developer.service.hmrc.gov.uk/developer/applications
 */
final class HmrcDeveloperHubLinks
{
    private const string BASE = 'https://developer.service.hmrc.gov.uk/developer';

    /** @var array<string, string> Translation key => path relative to BASE. */
    private const array ACCOUNT_PATHS_BY_LABEL_KEY = [
        'mtd.hmrc.developer.hub.login' => '/login',
        'mtd.hmrc.developer.hub.profile' => '/profile',
        'mtd.hmrc.developer.hub.email.preferences' => '/profile/email-preferences',
        'mtd.hmrc.developer.hub.change.password' => '/profile/password',
        'mtd.hmrc.developer.hub.security.preferences' =>
            '/profile/security-preferences',
        'mtd.hmrc.developer.hub.add.sandbox.application' =>
            '/application/add/sandbox',
        'mtd.hmrc.developer.hub.add.production.application' =>
            '/applications/add/production',
        'mtd.hmrc.developer.hub.logout' => '/logout',
    ];

    /**
     * @var array<string, string> Translation key => path relative to
     * BASE . '/applications/{applicationId}'.
     */
    private const array APPLICATION_PATHS_BY_LABEL_KEY = [
        'mtd.hmrc.developer.hub.manage.application' => '/manage',
        'mtd.hmrc.developer.hub.subscriptions' => '/subscriptions',
        'mtd.hmrc.developer.hub.change.name.and.description' =>
            '/change-app-name-and-desc',
        'mtd.hmrc.developer.hub.client.secrets' => '/client-secrets',
        'mtd.hmrc.developer.hub.redirect.uris' => '/redirect-uris',
        'mtd.hmrc.developer.hub.ip.allowlist' => '/ip-allowlist',
        'mtd.hmrc.developer.hub.team.members' => '/team-members',
        'mtd.hmrc.developer.hub.tc.and.privacy.policy.url' =>
            '/change-tc-and-priv-pol-url',
        'mtd.hmrc.developer.hub.delete.application' => '/delete',
    ];

    /**
     * Account-level pages -- no application ID needed.
     *
     * @return list<array{labelKey: string, url: string}>
     */
    public static function accountLinks(): array
    {
        return self::buildLinks(self::BASE, self::ACCOUNT_PATHS_BY_LABEL_KEY);
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

        return self::buildLinks($appBase, self::APPLICATION_PATHS_BY_LABEL_KEY);
    }

    /**
     * @param array<string, string> $pathsByLabelKey
     * @return list<array{labelKey: string, url: string}>
     */
    private static function buildLinks(string $base, array $pathsByLabelKey): array
    {
        $links = [];
        foreach ($pathsByLabelKey as $labelKey => $path) {
            $links[] = ['labelKey' => $labelKey, 'url' => $base . $path];
        }

        return $links;
    }
}
