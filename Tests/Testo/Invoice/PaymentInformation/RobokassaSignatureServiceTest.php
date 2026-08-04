<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\RobokassaSignatureService;
use Testo\Assert;
use Testo\Test;

/**
 * Covers RobokassaSignatureService: the JWT-signing formula (ground-truthed
 * against the official robokassa/sdk-php source) and the Result URL
 * callback-verification formula (NOT ground-truthed the same way — see the
 * class's own docblock). Pure signature computation, no network I/O.
 */
#[Test]
final class RobokassaSignatureServiceTest
{
    private function service(): RobokassaSignatureService
    {
        return new RobokassaSignatureService();
    }

    public function base64UrlProducesUrlSafeOutputWithNoPadding(): void
    {
        // Raw bytes chosen so the ordinary base64 form contains both '+'
        // and '/' and requires '=' padding, exercising every substitution.
        $raw = "\xff\xff\xbe\xff\xef";

        $encoded = $this->service()->base64Url($raw);

        Assert::false(str_contains($encoded, '+'));
        Assert::false(str_contains($encoded, '/'));
        Assert::false(str_contains($encoded, '='));
        Assert::same(rtrim(strtr(base64_encode($raw), '+/', '-_'), '='), $encoded);
    }

    public function signJwtProducesTheExactHmacMd5FormulaFromTheOfficialSdk(): void
    {
        $header = ['alg' => 'MD5', 'typ' => 'JWT'];
        $payload = ['MerchantLogin' => 'demo', 'InvId' => 123, 'OutSum' => 10.0];

        [$dataToSign, $signature] = $this->service()->signJwt($header, $payload, 'demo', 'password1');

        $expectedHeader = rtrim(strtr(base64_encode(
            json_encode($header, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ), '+/', '-_'), '=');
        $expectedPayload = rtrim(strtr(base64_encode(
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ), '+/', '-_'), '=');
        $expectedDataToSign = $expectedHeader . '.' . $expectedPayload;
        $expectedSignature = rtrim(strtr(base64_encode(
            hash_hmac('md5', $expectedDataToSign, 'demo:password1', true),
        ), '+/', '-_'), '=');

        Assert::same($expectedDataToSign, $dataToSign);
        Assert::same($expectedSignature, $signature);
    }

    public function signJwtProducesADifferentSignatureForADifferentPassword(): void
    {
        $header = ['alg' => 'MD5', 'typ' => 'JWT'];
        $payload = ['MerchantLogin' => 'demo', 'InvId' => 123, 'OutSum' => 10.0];

        [, $signature1] = $this->service()->signJwt($header, $payload, 'demo', 'password1');
        [, $signature2] = $this->service()->signJwt($header, $payload, 'demo', 'a-different-password');

        Assert::notSame($signature1, $signature2);
    }

    public function signOpStateMatchesTheOfficialSdksMd5Formula(): void
    {
        $signature = $this->service()->signOpState('demo', '123', 'password2');

        Assert::same(hash('md5', 'demo:123:password2'), $signature);
    }

    public function verifyResultUrlSignatureAcceptsACorrectlySignedCallback(): void
    {
        $expected = hash('md5', '100.00:456:password2');

        Assert::true($this->service()->verifyResultUrlSignature('100.00', '456', 'password2', $expected));
    }

    public function verifyResultUrlSignatureAcceptsAnUppercaseSignatureValue(): void
    {
        // Robokassa's classic callback formula is documented (across every
        // third-party integration checked) as case-insensitive on the
        // SignatureValue it sends.
        $expected = hash('md5', '100.00:456:password2');

        Assert::true($this->service()->verifyResultUrlSignature('100.00', '456', 'password2', strtoupper($expected)));
    }

    public function verifyResultUrlSignatureRejectsATamperedOutSum(): void
    {
        $expected = hash('md5', '100.00:456:password2');

        Assert::false($this->service()->verifyResultUrlSignature('999.99', '456', 'password2', $expected));
    }

    public function verifyResultUrlSignatureRejectsAWrongPassword(): void
    {
        $expected = hash('md5', '100.00:456:password2');

        Assert::false($this->service()->verifyResultUrlSignature('100.00', '456', 'a-different-password', $expected));
    }

    public function verifyResultUrlSignatureIncludesSortedShpParamsInTheFormula(): void
    {
        // Shp_ params must be sorted before joining — built here in
        // reverse/unsorted order to confirm the service does the sorting,
        // not just relies on caller-supplied order.
        $shpParams = ['Shp_userId' => '42', 'Shp_invoiceUrlKey' => 'abc123'];
        $expected = hash('md5', '100.00:456:password2:Shp_invoiceUrlKey=abc123:Shp_userId=42');

        Assert::true($this->service()->verifyResultUrlSignature('100.00', '456', 'password2', $expected, $shpParams));
    }

    public function verifyResultUrlSignatureRejectsATamperedShpParam(): void
    {
        $expected = hash('md5', '100.00:456:password2:Shp_invoiceUrlKey=abc123');

        Assert::false($this->service()->verifyResultUrlSignature(
            '100.00',
            '456',
            'password2',
            $expected,
            ['Shp_invoiceUrlKey' => 'tampered'],
        ));
    }
}
