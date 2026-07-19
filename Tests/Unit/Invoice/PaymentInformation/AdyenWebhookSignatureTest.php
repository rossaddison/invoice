<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\PaymentInformation;

use Adyen\Util\HmacSignature;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the exact signature-verification primitive
 * AdyenPaymentService::verifyWebhookNotification() delegates to
 * (Adyen\Util\HmacSignature::isValidNotificationHMAC()). Pure HMAC-SHA256
 * computation, no network I/O.
 *
 * Note: AdyenPaymentService itself isn't instantiated here because its
 * constructor requires the concrete `final` SettingRepository class (not
 * SettingRepositoryInterface), which PHPUnit cannot generate a test double
 * for — the same pre-existing constructor-design constraint documented in
 * StripeWebhookSignatureTest, unrelated to this change.
 */
final class AdyenWebhookSignatureTest extends TestCase
{
    private const string HMAC_KEY = '44782D6D6F636B2D6B65792D666F722D74657374696E672D6F6E6C79';
    private const string OTHER_HMAC_KEY = '00112233445566778899AABBCCDDEEFF0011223';

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function signedNotificationItem(array $overrides = []): array
    {
        $item = array_merge([
            'pspReference'        => 'psp_test_123',
            'originalReference'   => '',
            'merchantAccountCode' => 'TestMerchant',
            'merchantReference'   => 'test-invoice-url-key',
            'amount'              => ['value' => 1000, 'currency' => 'EUR'],
            'eventCode'           => 'AUTHORISATION',
            'success'             => 'true',
        ], $overrides);

        $hmac = new HmacSignature();
        $item['additionalData'] = [
            'hmacSignature' => $hmac->calculateNotificationHMAC(self::HMAC_KEY, $item),
        ];

        return $item;
    }

    public function testValidSignatureIsAccepted(): void
    {
        $item = $this->signedNotificationItem();

        self::assertTrue(new HmacSignature()->isValidNotificationHMAC(self::HMAC_KEY, $item));
    }

    public function testTamperedPayloadIsRejected(): void
    {
        $item = $this->signedNotificationItem();
        $item['amount']['value'] = 999999;

        self::assertFalse(new HmacSignature()->isValidNotificationHMAC(self::HMAC_KEY, $item));
    }

    public function testWrongKeyIsRejected(): void
    {
        $item = $this->signedNotificationItem();

        self::assertFalse(new HmacSignature()->isValidNotificationHMAC(self::OTHER_HMAC_KEY, $item));
    }
}
