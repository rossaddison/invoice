<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Client;

use App\Auth\Client\GovUk;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for GovUk OAuth2 / OIDC client.
 *
 * The class is instantiated without a constructor (via reflection) because the
 * OAuth2 parent requires HTTP infrastructure we don't need for pure-logic tests.
 *
 * All protected methods are exercised via a shared call() helper that uses
 * ReflectionMethod::invoke() — this is the same pattern used across the
 * existing test suite for classes with complex constructors.
 *
 * Bugs confirmed fixed and regression-tested here:
 *   - decodeJwtHeaderIntoArray: now calls base64UrlToBase64() before base64_decode()
 *   - supportedScopes: 'open_id' → 'openid' (no underscore)
 */
final class GovUkTest extends TestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private GovUk $govUk;

    #[\Override]
    protected function setUp(): void
    {
        $instance      = (new \ReflectionClass(GovUk::class))->newInstanceWithoutConstructor();
        $this->govUk   = $instance;
    }

    /**
     * Invoke a protected method on the GovUk instance via reflection.
     *
     * @param mixed[] $args
     */
    private function call(string $method, mixed ...$args): mixed
    {
        return (new \ReflectionMethod(GovUk::class, $method))->invoke($this->govUk, ...$args);
    }

    /**
     * Build a base64url-encoded JWT header segment from a plain PHP array.
     *
     * @param array<string, mixed> $header
     */
    private function encodeHeader(array $header): string
    {
        return rtrim(strtr(base64_encode((string) json_encode($header)), '+/', '-_'), '=');
    }

    // -------------------------------------------------------------------------
    // Public identity methods
    // -------------------------------------------------------------------------

    public function testGetNameReturnsGovuk(): void
    {
        $this->assertSame('govuk', $this->govUk->getName());
    }

    public function testGetTitleReturnsGovUk(): void
    {
        $this->assertSame('GovUk', $this->govUk->getTitle());
    }

    public function testGetButtonClassReturnsBtnBtnInfo(): void
    {
        $this->assertSame('btn btn-info', $this->govUk->getButtonClass());
    }

    // -------------------------------------------------------------------------
    // base64UrlToBase64 — converts URL-safe chars back to standard base64
    // -------------------------------------------------------------------------

    public function testBase64UrlToBase64ReplacesDashWithPlus(): void
    {
        $this->assertSame('+', $this->call('base64UrlToBase64', '-'));
    }

    public function testBase64UrlToBase64ReplacesUnderscoreWithSlash(): void
    {
        $this->assertSame('/', $this->call('base64UrlToBase64', '_'));
    }

    public function testBase64UrlToBase64LeavesAlphanumericUnchanged(): void
    {
        $this->assertSame('abc123=', $this->call('base64UrlToBase64', 'abc123='));
    }

    public function testBase64UrlToBase64ConvertsMultipleSpecialChars(): void
    {
        $this->assertSame('a+b/c+d/', $this->call('base64UrlToBase64', 'a-b_c-d_'));
    }

    public function testBase64UrlToBase64RoundTrip(): void
    {
        $original  = 'fn5+/abc';
        $urlSafe   = strtr($original, '+/', '-_');
        $converted = $this->call('base64UrlToBase64', $urlSafe);
        $this->assertSame($original, $converted);
    }

    // -------------------------------------------------------------------------
    // splitIdTokenIntoJwtHeader — extracts the first dot-separated segment
    // -------------------------------------------------------------------------

    public function testSplitIdTokenReturnsFirstSegment(): void
    {
        $this->assertSame('header', $this->call('splitIdTokenIntoJwtHeader', 'header.payload.signature'));
    }

    public function testSplitIdTokenWithBase64UrlEncodedHeader(): void
    {
        $encoded = $this->encodeHeader(['alg' => 'RS256', 'kid' => 'k1', 'typ' => 'JWT']);
        $token   = $encoded . '.payload.signature';

        $this->assertSame($encoded, $this->call('splitIdTokenIntoJwtHeader', $token));
    }

    // -------------------------------------------------------------------------
    // decodeJwtHeaderIntoArray — bug fix: must call base64UrlToBase64() first
    // -------------------------------------------------------------------------

    public function testDecodeJwtHeaderDecodesStandardHeader(): void
    {
        $header  = ['alg' => 'RS256', 'kid' => 'abc123', 'typ' => 'JWT'];
        $encoded = $this->encodeHeader($header);

        /** @var array<string, mixed> $result */
        $result = $this->call('decodeJwtHeaderIntoArray', $encoded);

        $this->assertSame($header, $result);
    }

    /**
     * Regression test for the base64url fix.
     *
     * The JSON `{"alg":"RS256","kid":"~~~","typ":"JWT"}` contains byte 0x7E (tilde)
     * three times. At byte offset 22–24, the 6-bit base64 group alignment produces
     * the value 62 (0x3E), which standard base64 encodes as '+'. The base64url
     * equivalent is '-'. Before the fix, base64_decode() received '-' and failed.
     */
    public function testDecodeJwtHeaderCorrectlyHandlesUrlSafeChars(): void
    {
        $json   = '{"alg":"RS256","kid":"~~~","typ":"JWT"}';
        $b64url = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');

        // Verify the fixture actually exercises the conversion (contains - or _)
        $this->assertTrue(
            str_contains($b64url, '-') || str_contains($b64url, '_'),
            'Fixture must contain URL-safe base64 chars to be a meaningful regression test',
        );

        /** @var array<string, mixed> $result */
        $result = $this->call('decodeJwtHeaderIntoArray', $b64url);

        $this->assertSame(['alg' => 'RS256', 'kid' => '~~~', 'typ' => 'JWT'], $result);
    }

    public function testDecodeJwtHeaderThrowsOnInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Base64-encoded JWT header.');
        $this->call('decodeJwtHeaderIntoArray', '!!not-base64!!');
    }

    public function testDecodeJwtHeaderThrowsWhenDecodedIsNotJson(): void
    {
        $notJson = base64_encode('this is not json');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON in JWT header.');
        $this->call('decodeJwtHeaderIntoArray', $notJson);
    }

    public function testDecodeJwtHeaderThrowsWhenDecodedJsonIsNotArray(): void
    {
        $scalar = base64_encode('"just a string"');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON in JWT header.');
        $this->call('decodeJwtHeaderIntoArray', $scalar);
    }

    // -------------------------------------------------------------------------
    // getKidFromJwtHeader — extracts the 'kid' claim
    // -------------------------------------------------------------------------

    public function testGetKidFromJwtHeaderReturnsKidValue(): void
    {
        $header  = ['alg' => 'RS256', 'kid' => 'my-signing-key-2025', 'typ' => 'JWT'];
        $encoded = $this->encodeHeader($header);

        /** @var string $kid */
        $kid = $this->call('getKidFromJwtHeader', $encoded);

        $this->assertSame('my-signing-key-2025', $kid);
    }

    public function testGetKidFromJwtHeaderThrowsWhenKidKeyMissing(): void
    {
        $header  = ['alg' => 'RS256', 'typ' => 'JWT']; // no 'kid'
        $encoded = $this->encodeHeader($header);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"kid"');
        $this->call('getKidFromJwtHeader', $encoded);
    }

    public function testGetKidFromJwtHeaderThrowsWhenKidIsNotAString(): void
    {
        $header  = ['alg' => 'RS256', 'kid' => 42, 'typ' => 'JWT']; // int kid
        $encoded = $this->encodeHeader($header);

        $this->expectException(InvalidArgumentException::class);
        $this->call('getKidFromJwtHeader', $encoded);
    }

    // -------------------------------------------------------------------------
    // supportedScopes — regression test for 'open_id' → 'openid' typo fix
    // -------------------------------------------------------------------------

    public function testSupportedScopesContainsOpenid(): void
    {
        /** @var list<string> $scopes */
        $scopes = $this->call('supportedScopes');
        $this->assertContains('openid', $scopes);
    }

    public function testSupportedScopesDoesNotContainOpenUnderscoreId(): void
    {
        /** @var list<string> $scopes */
        $scopes = $this->call('supportedScopes');
        $this->assertNotContains('open_id', $scopes, "'open_id' was the pre-fix typo; must be 'openid'");
    }

    public function testSupportedScopesReturnsExpectedSet(): void
    {
        $this->assertSame(
            ['openid', 'email', 'phone', 'offline_access'],
            $this->call('supportedScopes'),
        );
    }

    // -------------------------------------------------------------------------
    // OIDC capability metadata — all return correct values
    // -------------------------------------------------------------------------

    public function testGetDefaultScopeReturnsOpenid(): void
    {
        $this->assertSame('openid', $this->call('getDefaultScope'));
    }

    public function testGetDefaultScopesReturnsOpenidEmailPhone(): void
    {
        $this->assertSame('openid email phone', $this->call('getDefaultScopes'));
    }

    public function testDefaultViewOptionsReturnsPopupDimensions(): void
    {
        $this->assertSame(
            ['popupWidth' => 860, 'popupHeight' => 480],
            $this->call('defaultViewOptions'),
        );
    }

    public function testSupportedBackChannelLogoutReturnsFalse(): void
    {
        $this->assertFalse($this->call('supportedBackChannelLogout'));
    }

    public function testSupportedBackChannelLogoutSessionReturnsTrue(): void
    {
        $this->assertTrue($this->call('supportedBackChannelLogoutSession'));
    }

    public function testSupportedCodeChallengeMethodsReturnsS256(): void
    {
        $this->assertSame(['S256'], $this->call('supportedCodeChallengeMethods'));
    }

    public function testSupportedGrantTypesReturnsAuthorizationCode(): void
    {
        $this->assertSame(['authorization_code'], $this->call('supportedGrantTypes'));
    }

    public function testSupportedTokenEndpointAuthMethodsReturnsExpected(): void
    {
        $this->assertSame(
            ['private_key_jwt', 'client_secret_post'],
            $this->call('supportedTokenEndpointAuthMethods'),
        );
    }

    public function testSupportedRequestUriParameterReturnsFalse(): void
    {
        $this->assertFalse($this->call('supportedRequestUriParameter'));
    }

    public function testSupportedResponseTypesReturnsCode(): void
    {
        $this->assertSame(['code'], $this->call('supportedResponseTypes'));
    }

    public function testSupportedTokenEndpointAuthSigningAlgValuesContainsRs256AndPs256(): void
    {
        /** @var list<string> $algs */
        $algs = $this->call('supportedTokenEndpointAuthSigningAlgValues');
        $this->assertContains('RS256', $algs);
        $this->assertContains('PS256', $algs);
    }

    public function testSupportedUserInterfaceLocalesReturnsEnAndCy(): void
    {
        $this->assertSame(['en', 'cy'], $this->call('supportedUserInterfaceLocales'));
    }

    public function testGetClaimTypesSupportedReturnsNormal(): void
    {
        $this->assertSame(['normal'], $this->call('getClaimTypesSupported'));
    }

    public function testGetClaimsSupportedContainsCoreOidcClaims(): void
    {
        /** @var list<string> $claims */
        $claims = $this->call('getClaimsSupported');
        $this->assertContains('sub', $claims);
        $this->assertContains('email', $claims);
        $this->assertContains('email_verified', $claims);
        $this->assertContains('phone_number', $claims);
    }

    public function testGetClaimsSupportedContainsGovUkVocabClaims(): void
    {
        /** @var list<string> $claims */
        $claims = $this->call('getClaimsSupported');
        $this->assertContains('https://vocab.account.gov.uk/v1/passport', $claims);
        $this->assertContains('https://vocab.account.gov.uk/v1/coreIdentityJWT', $claims);
    }

    public function testGetJsonTrustMarkUriPointsToIntegrationEnvironment(): void
    {
        /** @var string $uri */
        $uri = $this->call('getJsonTrustMarkUri');
        $this->assertStringStartsWith('https://', $uri);
        $this->assertStringContainsString('trustmark', $uri);
    }

    public function testGetPolicyUriPointsToPrivacyNotice(): void
    {
        /** @var string $uri */
        $uri = $this->call('getPolicyUri');
        $this->assertStringStartsWith('https://', $uri);
        $this->assertStringContainsString('privacy-notice', $uri);
    }

    public function testGetTermsAndConditionsUriPointsToTerms(): void
    {
        /** @var string $uri */
        $uri = $this->call('getTermsAndConditionsUri');
        $this->assertStringStartsWith('https://', $uri);
        $this->assertStringContainsString('terms-and-conditions', $uri);
    }
}
