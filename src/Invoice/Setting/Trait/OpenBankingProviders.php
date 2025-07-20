<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Trait;

/**
 * Trait OpenBankingProviders
 *
 * Provides arrays of endpoints, scopes, OIDC info, documentation URLs, and continent for global Open Banking Third-Party Providers (TPPs).
 * Includes at least one example TPP per continent where available.
 * Each TPP includes OIDC security info and the date the information is relevant to.
 * The array is ordered by estimated fees, with open-source/zero-fee providers at the top.
 * Always verify fee structures and OIDC support with the provider—fees and features may change.
 */
trait OpenBankingProviders
{
    /**
     * List of Open Banking TPP endpoints, scopes, OIDC support, documentation links, and continent.
     * Ordered by lowest to highest fees (as of 2025-07-12, to the best of our knowledge).
     *
     * Structure:
     *   [provider_name] => [
     *      'authUrl'         => string,
     *      'tokenUrl'        => string,
     *      'apiBaseUrl'      => string,
     *      'scope'           => string,
     *      'userinfoUrl'     => string,
     *      'documentationUrl'=> string,
     *      'furtherSecuredWithOIDC' => [
     *          'value' => bool,
     *          'date'  => string (YYYY-MM-DD)
     *      ],
     *      'continent'       => string,
     *      'notes'           => string (optional),
     *   ]
     */
    protected array $openBankingProviders = [
        // EUROPE
        'wonderful' => [
            // No authUrl, tokenUrl, or userinfoUrl (authentication is via static API token)
            'apiBaseUrl' => 'https://api.wonderful.one/',
            'scope' => '', // Not required
            'documentationUrl' => 'https://api.wonderful.one/', // Official API docs
            'furtherSecuredWithOIDC' => [
                'value' => false, // No OIDC/OAuth2
                'date' => '2025-07-12',
            ],
            'continent' => 'europe',
            'notes' => 'Authentication is via dashboard-issued API token. OAuth2 endpoints are not available.',
        ],
        'tink' => [
            'authUrl' => 'https://oauth.tink.com/authorize/',
            'tokenUrl' => 'https://oauth.tink.com/api/v1/oauth/token',
            'apiBaseUrl' => 'https://api.tink.com/data/v2/',
            'scope' => 'accounts:read transactions:read',
            'userinfoUrl' => 'https://api.tink.com/api/v1/user-info',
            'documentationUrl' => 'https://docs.tink.com/resources/api-reference/open-banking-apis',
            'furtherSecuredWithOIDC' => [
                'value' => true,
                'date' => '2025-07-12',
            ],
            'continent' => 'europe',
            'notes' => 'Free tier available, pay-as-you-go for production.',
        ],
        'truelayer' => [
            'authUrl' => 'https://auth.truelayer.com/',
            'tokenUrl' => 'https://auth.truelayer.com/connect/token',
            'apiBaseUrl' => 'https://api.truelayer.com/data/v1/',
            'scope' => 'openid info accounts balance transactions cards products direct_debits standing_orders offline_access',
            'userinfoUrl' => 'https://auth.truelayer.com/userinfo',
            'documentationUrl' => 'https://docs.truelayer.com/docs/authentication',
            'furtherSecuredWithOIDC' => [
                'value' => true,
                'date' => '2025-07-12',
            ],
            'continent' => 'europe',
            'notes' => 'Free sandbox. Production: fees per API call and/or by volume.',
        ],
        'fintecture' => [
            'authUrl' => 'https://api.fintecture.com/authorize',
            'tokenUrl' => 'https://api.fintecture.com/token',
            'apiBaseUrl' => 'https://api.fintecture.com/',
            'scope' => 'AIS PIS',
            'userinfoUrl' => '', // Not standard OIDC
            'documentationUrl' => 'https://docs.fintecture.com/docs/authentication',
            'furtherSecuredWithOIDC' => [
                'value' => false,
                'date' => '2025-07-12',
            ],
            'continent' => 'europe',
            'notes' => 'No setup fees, free sandbox. Live usage requires contract—fees apply per transaction.',
        ],
        'saltedge' => [
            'authUrl' => 'https://www.saltedge.com/api/openbanking/v1/authorize',
            'tokenUrl' => 'https://www.saltedge.com/api/openbanking/v1/token',
            'apiBaseUrl' => 'https://www.saltedge.com/api/openbanking/v1/',
            'scope' => 'openid accounts transactions',
            'userinfoUrl' => '', // Not standard OIDC
            'documentationUrl' => 'https://docs.saltedge.com/',
            'furtherSecuredWithOIDC' => [
                'value' => false,
                'date' => '2025-07-12',
            ],
            'continent' => 'europe',
            'notes' => 'Contact for pricing, sandbox available.',
        ],
        'yapily' => [
            'authUrl' => 'https://auth.yapily.com/oauth2/authorize',
            'tokenUrl' => 'https://auth.yapily.com/oauth2/token',
            'apiBaseUrl' => 'https://api.yapily.com/',
            'scope' => 'accounts payments',
            'userinfoUrl' => '',
            'documentationUrl' => 'https://docs.yapily.com/guides/authentication/',
            'furtherSecuredWithOIDC' => [
                'value' => false,
                'date' => '2025-07-12',
            ],
            'continent' => 'europe',
            'notes' => 'Free sandbox. Live services require contract and fees.',
        ],
        'nordigen' => [
            'authUrl' => 'https://ob.nordigen.com/api/v2/token/new/',
            'tokenUrl' => 'https://ob.nordigen.com/api/v2/token/new/',
            'apiBaseUrl' => 'https://ob.nordigen.com/api/v2/',
            'scope' => '', // Nordigen uses a different model, see docs
            'userinfoUrl' => '',
            'documentationUrl' => 'https://nordigen.com/en/account_information_documenation/integration/',
            'furtherSecuredWithOIDC' => [
                'value' => false,
                'date' => '2025-07-12',
            ],
            'continent' => 'europe',
            'notes' => 'Open source / free for account info. Premium features may require fees.',
        ],
        'tokenio' => [
            'authUrl' => 'https://id.sandbox.token.io/oauth/authorize',
            'tokenUrl' => 'https://id.sandbox.token.io/oauth/token',
            'apiBaseUrl' => 'https://api.sandbox.token.io/',
            'scope' => 'openid accounts payments',
            'userinfoUrl' => 'https://id.sandbox.token.io/userinfo',
            'documentationUrl' => 'https://developer.token.io/docs/authentication',
            'furtherSecuredWithOIDC' => [
                'value' => true,
                'date' => '2025-07-12',
            ],
            'continent' => 'europe',
            'notes' => 'Free sandbox. Production pricing by volume.',
        ],
        // NORTH AMERICA
        'plaid' => [
            'authUrl' => 'https://sandbox.plaid.com/oauth/authorize',
            'tokenUrl' => 'https://sandbox.plaid.com/oauth/token',
            'apiBaseUrl' => 'https://sandbox.plaid.com/',
            'scope' => '', // Scopes not always explicit, see docs
            'userinfoUrl' => '',
            'documentationUrl' => 'https://plaid.com/docs/open-banking/',
            'furtherSecuredWithOIDC' => [
                'value' => false,
                'date' => '2025-07-12',
            ],
            'continent' => 'north america',
            'notes' => 'Free sandbox. Production: monthly & per-user fees.',
        ],
        'mx' => [
            'authUrl' => 'https://int-api.mx.com/oauth2/authorize',
            'tokenUrl' => 'https://int-api.mx.com/oauth2/token',
            'apiBaseUrl' => 'https://int-api.mx.com/',
            'scope' => 'accounts transactions users',
            'userinfoUrl' => '',
            'documentationUrl' => 'https://developer.mx.com/docs/api',
            'furtherSecuredWithOIDC' => [
                'value' => false,
                'date' => '2025-07-12',
            ],
            'continent' => 'north america',
            'notes' => 'Contact for pricing. US focus.',
        ],
        // SOUTH AMERICA
        'belvo' => [
            'authUrl' => 'https://sandbox.belvo.com/oauth/authorize',
            'tokenUrl' => 'https://sandbox.belvo.com/oauth/token',
            'apiBaseUrl' => 'https://sandbox.belvo.com/api/',
            'scope' => 'read write',
            'userinfoUrl' => '',
            'documentationUrl' => 'https://developers.belvo.com/docs/api-reference',
            'furtherSecuredWithOIDC' => [
                'value' => false,
                'date' => '2025-07-12',
            ],
            'continent' => 'south america',
            'notes' => 'Free sandbox. Production: tiered pricing. Covers Brazil, Mexico, Colombia, Chile.',
        ],
        // AFRICA
        'okra' => [
            'authUrl' => 'https://okra.ng/oauth/authorize',
            'tokenUrl' => 'https://okra.ng/oauth/token',
            'apiBaseUrl' => 'https://api.okra.ng/v2/',
            'scope' => 'accounts transactions identity balance',
            'userinfoUrl' => '',
            'documentationUrl' => 'https://docs.okra.ng/docs/okra-api-reference',
            'furtherSecuredWithOIDC' => [
                'value' => false,
                'date' => '2025-07-12',
            ],
            'continent' => 'africa',
            'notes' => 'Nigerian open finance provider. Commercial, with a free sandbox.',
        ],
        // ASIA
        'finbox' => [
            'authUrl' => 'https://api.finbox.com/oauth/authorize',
            'tokenUrl' => 'https://api.finbox.com/oauth/token',
            'apiBaseUrl' => 'https://api.finbox.com/v1/',
            'scope' => 'openid accounts',
            'userinfoUrl' => '',
            'documentationUrl' => 'https://finbox.com/docs/api',
            'furtherSecuredWithOIDC' => [
                'value' => false,
                'date' => '2025-07-12',
            ],
            'continent' => 'asia',
            'notes' => 'India-focused. Contact for pricing.',
        ],
        // OCEANIA
        'basiq' => [
            'authUrl' => 'https://auth.basiq.io/authorize',
            'tokenUrl' => 'https://auth.basiq.io/token',
            'apiBaseUrl' => 'https://au-api.basiq.io/',
            'scope' => 'accounts transactions',
            'userinfoUrl' => '',
            'documentationUrl' => 'https://api.basiq.io/reference',
            'furtherSecuredWithOIDC' => [
                'value' => false,
                'date' => '2025-07-12',
            ],
            'continent' => 'oceania',
            'notes' => 'Australia Consumer Data Right (CDR) provider. Free sandbox, paid production.',
        ],
    ];


    public function getOpenBankingProvidersWithAuthUrl(): array
    {
        $names = array_keys(
            array_filter(
                $this->openBankingProviders,
                /**
                 * @param array<string, mixed> $provider
                 */
                function (array $provider): bool {
                    return isset($provider['authUrl']) && is_string($provider['authUrl']) && $provider['authUrl'] !== '';
                },
            ),
        );
        return $names;
    }

    /**
     * Get an array of all available Open Banking provider names.
     * @psalm-return list<array-key>
     * @return string[]
     */
    public function getOpenBankingProviderNames(): array
    {
        return array_keys($this->openBankingProviders);
    }

    /**
     * Get the full endpoint config for a given provider.
     *
     * @param string $providerName
     * @return array|null
     */
    public function getOpenBankingProviderConfig(string $providerName): ?array
    {
        return $this->openBankingProviders[$providerName] ?? null;
    }
}
