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
     * @return array<string, array{name: string, scopes: list<string>, needs: string, serviceName: string, version: string}>
     */
    public static function all(): array
    {
        return [
            'organisations/vat' => [
                'name'        => 'VAT (MTD)',
                'scopes'      => ['read:vat', 'write:vat'],
                'needs'       => self::NEEDS_VRN,
                'serviceName' => 'mtd-vat',
                'version'     => '1.0',
            ],
            'individuals/self-assessment' => [
                'name'        => 'Self Assessment (Individual)',
                'scopes'      => ['read:self-assessment', 'write:self-assessment'],
                'needs'       => self::NEEDS_NINO,
                'serviceName' => 'self-assessment',
                'version'     => '3.0',
            ],
            // Live-testing fix 2026-09-10: this entry's own scopes
            // (read:self-employment/write:self-employment) don't exist
            // on HMRC's real self-employment-business-api/5.0 OAS spec
            // (fetched live) -- it actually uses the same
            // read:self-assessment/write:self-assessment scopes every
            // other ITSA-family entry below already requests. Since
            // HMRC never grants a scope name that isn't real, this
            // entry could never match fromGrantedScopeString() even
            // when the application genuinely was subscribed and the
            // user had granted the real (shared) scopes -- reported
            // live as "Self Employment Business (MTD) 5.0" missing
            // from the "Select API to exercise" dropdown despite
            // "Derived from granted scopes" being shown. Name corrected
            // to HMRC's own official display name too.
            'individuals/business/self-employment' => [
                'name'        => 'Self Employment Business (MTD)',
                'scopes'      => ['read:self-assessment', 'write:self-assessment'],
                'needs'       => self::NEEDS_NINO,
                'serviceName' => 'self-assessment',
                'version'     => '5.0',
            ],
            // Live-testing fix 2026-09-10: business-details-api/2.0's
            // own OAS spec (fetched live) documents write:self-assessment
            // as required for 3 of its 6 operations (amend quarterly
            // period type, disapply/withdraw the late accounting date
            // rule election) -- this catalogue entry only ever listed
            // read:self-assessment. Doesn't change dropdown availability
            // today (every other ITSA-family entry already requests
            // write:self-assessment), but the catalogue's own record of
            // what this API needs was incomplete/misleading.
            'individuals/business/details' => [
                'name'        => 'Business Details (MTD)',
                'scopes'      => ['read:self-assessment', 'write:self-assessment'],
                'needs'       => self::NEEDS_NINO,
                'serviceName' => 'self-assessment',
                'version'     => '2.0',
            ],
            'individuals/calculations' => [
                'name'        => 'Individual Calculations',
                'scopes'      => ['read:self-assessment', 'write:self-assessment'],
                'needs'       => self::NEEDS_NINO,
                'serviceName' => 'self-assessment',
                'version'     => '5.0',
            ],
            'individuals/income-received' => [
                'name'        => 'Income Received',
                'scopes'      => ['read:self-assessment', 'write:self-assessment'],
                'needs'       => self::NEEDS_NINO,
                'serviceName' => 'self-assessment',
                'version'     => '2.0',
            ],
            'individuals/national-insurance' => [
                'name'        => 'National Insurance Record',
                'scopes'      => ['read:national-insurance-record'],
                'needs'       => self::NEEDS_NINO,
                'serviceName' => 'national-insurance',
                'version'     => '1.0',
            ],
            'customs/declarations' => [
                'name'        => 'Customs Declarations',
                'scopes'      => ['write:customs-declaration'],
                'needs'       => self::NEEDS_EORI,
                'serviceName' => 'customs-services',
                'version'     => '2.0',
            ],
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
            default => null,
        };
    }
}
