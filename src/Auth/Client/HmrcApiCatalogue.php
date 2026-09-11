<?php

declare(strict_types=1);

namespace App\Auth\Client;

/**
 * Curated catalogue of HMRC MTD APIs relevant to this application.
 *
 * Each entry is keyed by the API context path and carries the OAuth2 scopes,
 * the identifier the API requires (nino / vrn / eori), the serviceName used
 * when creating a sandbox test user, and the current stable version.
 *
 * Scopes are requested at OAuth2 authorization time via getDefaultScope().
 * HMRC silently drops any scope the registered application is not subscribed
 * to, so requesting all catalogue scopes is safe — the token response shows
 * exactly what was granted.
 */
final class HmrcApiCatalogue
{
    public const string NEEDS_NINO = 'nino';
    public const string NEEDS_VRN  = 'vrn';
    public const string NEEDS_EORI = 'eori';

    /**
     * Live-testing fix 2026-09-10: SonarCloud flagged this array as
     * duplicated code -- and correctly so, not a normalization false
     * positive: five of the eight entries below genuinely repeated
     * 'needs' => self::NEEDS_NINO, 'serviceName' => 'self-assessment',
     * AND the same two-scope array verbatim, byte-for-byte. Those five
     * are exactly HMRC's own "ITSA" (Making Tax Digital for Income Tax
     * Self Assessment) API bundle -- Self Assessment, Self Employment
     * Business, Business Details, Individual Calculations, Income
     * Received -- which genuinely share one OAuth scope pair, one
     * "needs a NINO" identifier, and one Create Test User serviceName
     * by HMRC's own design. itsaEntry() names that real shared concept
     * explicitly instead of repeating it five times; only name/version
     * (the two fields that actually differ per API) are passed in.
     *
     * @return array<string, array{
     *     name: string,
     *     scopes: list<string>,
     *     needs: string,
     *     serviceName: string,
     *     version: string,
     * }>
     */
    public static function all(): array
    {
        return [
            'organisations/vat' => self::entry(
                'VAT (MTD)',
                ['read:vat', 'write:vat'],
                self::NEEDS_VRN,
                'mtd-vat',
                '1.0',
            ),
            'individuals/self-assessment' =>
                self::itsaEntry('Self Assessment (Individual)', '3.0'),
            // Live-testing fix 2026-09-10: this entry's own scopes used
            // to be read:self-employment/write:self-employment, which
            // don't exist on HMRC's real self-employment-business-api/5.0
            // OAS spec (fetched live) -- it actually shares the same
            // ITSA-bundle scopes itsaEntry() encodes above. Since HMRC
            // never grants a scope name that isn't real, this entry
            // could never match fromGrantedScopeString() even when the
            // application genuinely was subscribed and the user had
            // granted the real (shared) scopes -- reported live as
            // "Self Employment Business (MTD) 5.0" missing from the
            // "Select API to exercise" dropdown despite "Derived from
            // granted scopes" being shown. Name corrected to HMRC's own
            // official display name too.
            'individuals/business/self-employment' =>
                self::itsaEntry('Self Employment Business (MTD)', '5.0'),
            // Live-testing fix 2026-09-10: business-details-api/2.0's
            // own OAS spec (fetched live) documents write:self-assessment
            // as required for 3 of its 6 operations (amend quarterly
            // period type, disapply/withdraw the late accounting date
            // rule election) -- this entry only ever listed
            // read:self-assessment (so wasn't a real itsaEntry() member
            // yet). Doesn't change dropdown availability today (every
            // other ITSA-family entry already requests
            // write:self-assessment), but the catalogue's own record of
            // what this API needs was incomplete/misleading.
            'individuals/business/details' =>
                self::itsaEntry('Business Details (MTD)', '2.0'),
            'individuals/calculations' =>
                self::itsaEntry('Individual Calculations', '5.0'),
            'individuals/income-received' =>
                self::itsaEntry('Income Received', '2.0'),
            // Added 2026-09-10 per user request to incorporate further
            // relevant HMRC APIs -- confirmed live against HMRC's real
            // obligations-api/3.0 OAS spec that this needs only
            // read:self-assessment (no write), so it's a plain entry()
            // rather than itsaEntry() -- reusing itsaEntry()'s bundled
            // write:self-assessment here would have made the
            // catalogue's own record of what this API needs wrong,
            // the exact mistake #1287 fixed for a different entry.
            'individuals/obligations' => self::entry(
                'Obligations (MTD)',
                ['read:self-assessment'],
                self::NEEDS_NINO,
                'self-assessment',
                '3.0',
            ),
            'individuals/national-insurance' => self::entry(
                'National Insurance Record',
                ['read:national-insurance-record'],
                self::NEEDS_NINO,
                'national-insurance',
                '1.0',
            ),
            'customs/declarations' => self::entry(
                'Customs Declarations',
                ['write:customs-declaration'],
                self::NEEDS_EORI,
                'customs-services',
                '2.0',
            ),
        ];
    }

    /**
     * Shorthand for the five-strong "ITSA" bundle sharing one OAuth
     * scope pair, NEEDS_NINO, and one Create Test User serviceName --
     * see all()'s own docblock for why this exists.
     *
     * @return array{
     *     name: string,
     *     scopes: list<string>,
     *     needs: string,
     *     serviceName: string,
     *     version: string,
     * }
     */
    private static function itsaEntry(string $name, string $version): array
    {
        return self::entry(
            $name,
            ['read:self-assessment', 'write:self-assessment'],
            self::NEEDS_NINO,
            'self-assessment',
            $version,
        );
    }

    /**
     * @param list<string> $scopes
     * @return array{
     *     name: string,
     *     scopes: list<string>,
     *     needs: string,
     *     serviceName: string,
     *     version: string,
     * }
     */
    private static function entry(
        string $name,
        array $scopes,
        string $needs,
        string $serviceName,
        string $version,
    ): array {
        return [
            'name'        => $name,
            'scopes'      => $scopes,
            'needs'       => $needs,
            'serviceName' => $serviceName,
            'version'     => $version,
        ];
    }

    /**
     * Space-separated string of every scope across all catalogue entries.
     * Safe to use as getDefaultScope() — HMRC drops unsubscribed scopes silently.
     */
    public static function allScopes(): string
    {
        $scopes = [];
        foreach (self::all() as $entry) {
            foreach ($entry['scopes'] as $scope) {
                $scopes[$scope] = true;
            }
        }
        return implode(' ', array_keys($scopes));
    }

    /**
     * Filter the catalogue to entries whose scopes overlap with what HMRC
     * actually granted in the token response (the hmrc_scope session value).
     *
     * @return array<string, array{name: string, scopes: list<string>, needs: string, serviceName: string, version: string}>
     */
    public static function fromGrantedScopeString(string $grantedScopes): array
    {
        if ($grantedScopes === '') {
            return [];
        }
        $granted = array_flip(explode(' ', $grantedScopes));
        $result  = [];
        foreach (self::all() as $context => $entry) {
            if (array_intersect_key(array_flip($entry['scopes']), $granted) !== []) {
                $result[$context] = $entry;
            }
        }
        return $result;
    }

    /**
     * Filter the catalogue using the subscriptions array returned by the HMRC
     * Developer Hub endpoint. Falls back gracefully if the context field uses
     * either 'context' or 'apiContext' key naming.
     *
     * @param array<array-key, mixed> $subscriptions
     * @return array<string, array{name: string, scopes: list<string>, needs: string, serviceName: string, version: string}>
     */
    public static function fromSubscriptions(array $subscriptions): array
    {
        $all    = self::all();
        $result = [];
        foreach ($subscriptions as $sub) {
            if (!is_array($sub)) {
                continue;
            }
            /** @var array<string, mixed> $sub */
            $context = (string) ($sub['context'] ?? ($sub['apiContext'] ?? ''));
            if ($context !== '' && isset($all[$context])) {
                $result[$context] = $all[$context];
            }
        }
        return $result;
    }

    /**
     * Returns the unique serviceNames needed to create a sandbox test user
     * that covers the given API contexts.
     *
     * @param list<string> $contexts
     * @return list<string>
     */
    public static function testUserServiceNames(array $contexts): array
    {
        $all          = self::all();
        $serviceNames = [];
        foreach ($contexts as $context) {
            if (isset($all[$context])) {
                $serviceNames[$all[$context]['serviceName']] = true;
            }
        }
        return array_keys($serviceNames);
    }

    /**
     * Route name used by the HMRC controller for each API context.
     * Returns null when no dedicated route exists yet.
     *
     * 'individuals/business/self-employment' and
     * 'individuals/business/details' both point at the same route
     * deliberately (not a copy-paste slip): the self-employment-business
     * API's own "list all businesses" endpoint no longer exists in its
     * current version (self-employmentBusinesses() found this live,
     * 2026-09-10 -- see that action's own docblock) -- business discovery
     * now lives on Business Details' /list endpoint instead, so the one
     * page is genuinely backed by both APIs' scopes now.
     */
    public static function routeFor(string $context): ?string
    {
        return match ($context) {
            'organisations/vat' => 'backend/hmrc/vatObligations',
            'individuals/business/self-employment',
            'individuals/business/details' =>
                'backend/hmrc/selfEmploymentBusinesses',
            'individuals/obligations' => 'backend/hmrc/incomeTaxObligations',
            'individuals/self-assessment' => 'backend/hmrc/itsaStatus',
            default => null,
        };
    }
}
