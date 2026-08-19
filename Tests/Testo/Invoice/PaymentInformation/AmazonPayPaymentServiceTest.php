<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Infrastructure\Persistence\Client\Client as ClientEntity;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Inv\InvPaymentSettlementService;
use App\Invoice\PaymentInformation\Service\AmazonPayPaymentService;
use App\Invoice\Setting\SettingRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Aliases\Aliases;

/**
 * Covers AmazonPayPaymentService.
 *
 * verifyPayment() and refund() (and the "session completed" branch of
 * handleCallback()/processAmazonCheckoutSession()) are intentionally not
 * exercised here: they each construct a real `Amazon\Pay\API\Client`
 * internally via `new Client(...)` rather than accepting an injected
 * client, so there is no seam to substitute a mock without touching
 * production code — invoking them for real would perform a genuine network
 * call to Amazon Pay's API. See the POSSIBLE ISSUE note in the task report.
 */
#[Test]
final class AmazonPayPaymentServiceTest
{
    /**
     * A fixed, publicly-published test-only RSA key — Amazon's own official
     * `amzn/amazon-pay-api-sdk-php` SDK's test suite
     * (tests/unit/unit_test_key_private.txt) uses this exact key for its
     * own signing tests, copied verbatim rather than generating a fresh key
     * per test run via openssl_pkey_new(). That call previously failed here
     * with "Unable to generate a test RSA key." — a local PHP/OpenSSL
     * environment issue (this WAMP install's php.ini has no working
     * openssl.cnf configured), not anything about the SDK or this app's own
     * code — so a real dynamically-generated key was never actually
     * necessary for these tests to be meaningful; any valid RSA private key
     * works identically for exercising `getButtonData()`'s signing path.
     * Using this fixture instead makes the suite deterministic and
     * independent of the local machine's OpenSSL configuration entirely.
     */
    private const string TEST_RSA_PRIVATE_KEY = <<<'PEM'
        -----BEGIN RSA PRIVATE KEY-----
        MIIEowIBAAKCAQEA6Eqj050ePXhA39d4FyvSQyIbW9frefsjw9JSPIRHX4XimpAO
        gJ75BmG/wptqXOG79xDyWOXmHHz5alRjvoQAlI7HOdnScWxHO+9esm1koJyvug23
        Pa5KN8j7BYRpFOuYhFS8hxUEsmMYSw7iIsckbqBB7VzxnGfP10kNcAhJ+GSbHN74
        CsqJJkX+66m85Mrb/13QxZVbIsZLwfQ/S12lStvI1rjtyXBEmfEWNOZLQdPdM2QB
        kCm+vp9QjsD2zpINCCazp+DibqjcCtSGN2mSiraFSBKdg1E55K11E56pF55BsZk5
        lo8/aGAMEYzJB4XYwaMV21Fadg8sM0NRlBFluwIDAQABAoIBACqN8fFEaVPNgeT/
        7ioghwZxax2qMqNIFMc88n/Po9umBVtXZLC/btNyeNTH7/ZQDEU4v4z1oPA7HN4T
        06oFOK3+chTxCJJqyan7Mhfx3mtmCPNGq/kKwuHxWbsrBK0mc+xaMad1fETJzpuB
        gH/qh4wUo78+Naz3f6Xq5iFOA28TdcVXBeECoEfHAmBdknsZRMLvF9goVXwSaKuD
        /mWXiIj3dLbU+TS9NZXlLEnMTJVZiiqyhRWT9oLui/7ROftjtMX49zDLSTifrmeT
        Wzji+8N9VHCyRTXDXile/L15mFoh5P41LdhgFwvSGyUq9vGnwFdMNiEYTimOhmsw
        KM2oFZECgYEA/VcPmXAQwbYOyc2XduxKHob17TfHSH97zYAp1OKvayRr7xzYWhmQ
        ezcRDRcGh9YddZXK59a8RZK3r+gipdoUA492/a14w52pKi22drCaqAn4SQ/zd2Jw
        G6CZWOxPwsCT9EsC9eLTXcWC0YzWraNQmf3uRGVQeSgvkM8JlprrlpUCgYEA6rsA
        +5TJh8KfIYORgkywrSztOWo8JeenW+Lu7ARWauz+a7How5V3xIszkuz9b7cmRhdg
        5gk8xTtR4UWo1SR5mTFuezfOTF0RzBBW7romHKTMOXJcLSoJKe9cQ3ZzaGoDHSqq
        pdapbaSLoKB4nCrK40SRmvmZAwojVA1NTgSvhw8CgYEArZPEHWY6JO8/bKdPmuzE
        z+u6fmEUSqkGQ0QH5VO3yxo2VauW1QzlAHc3WJepItLidlk+n+ByON0QvBa5/pbP
        1ayrY55CuwzABiUx+lqAbJgAJNcoAmlQ1K0RxGqNL6vQ87Wdfql+FqaoPjlYMbpP
        FGN2qCgenhSZmocwU58rwY0CgYBF9sNOOYTwMDRaOusOGWm31GJI8L9I1QlvO+7W
        7lwLtuQGmZq1YUG3lX4j1vubZs3Dqog5SJuSdiHrsWWnUh3kaXVyyKl23W7GkkA3
        G8jsVLqCjPGojJT6qNupSA8SGjcfZG5Ey/zoL1lm4S3R7ndW0kNMHAVdgJITJXvb
        O05ORQKBgEGPnMKv7dJA10Un77GMH7+6/ujWfL4JRj+Aa6l0RCnc05rECtkF5V27
        gzjqRXZYIRczgyXa1MpNeZgE0C7MpXSbzTR4L8E9bMH2b+R4EjX8hoY32BReW0uA
        b4k6oZiNnDl1XZgwczfC1sTF/QsOJPe8i70Vzh/ZNG7DGw2dePLA
        -----END RSA PRIVATE KEY-----
        PEM;

    private function makeTempPemDir(bool $withPrivateKey): string
    {
        $dir = sys_get_temp_dir() . '/amazon_pay_test_' . uniqid('', true);
        if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException("Unable to create temp dir: {$dir}");
        }
        if ($withPrivateKey) {
            file_put_contents($dir . '/private.pem', self::TEST_RSA_PRIVATE_KEY);
        }
        return $dir;
    }

    private function removeTempPemDir(string $dir): void
    {
        @unlink($dir . '/private.pem');
        @rmdir($dir);
    }

    /**
     * @return InvPaymentSettlementService&m\MockInterface
     */
    private function makeInvPaymentSettlementService(): InvPaymentSettlementService
    {
        /** @var InvPaymentSettlementService&m\MockInterface $service */
        $service = m::mock(InvPaymentSettlementService::class);
        return $service;
    }

    public function createPaymentRequestReturnsOrderReferenceAmountAndCurrency(): void
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);

        $service = new AmazonPayPaymentService($sR, $this->makeInvPaymentSettlementService());

        $result = $service->createPaymentRequest(99.50, 'GBP');

        /** @var string $orderReference */
        $orderReference = $result['orderReference'];
        Assert::true(str_starts_with($orderReference, 'AMZN-'));
        Assert::same(17, strlen($orderReference));
        Assert::same(99.50, $result['amount']);
        Assert::same('GBP', $result['currency']);
    }

    public function getDriverKeyReturnsAmazonPay(): void
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);

        $service = new AmazonPayPaymentService($sR, $this->makeInvPaymentSettlementService());

        Assert::same('amazon_pay', $service->getDriverKey());
    }

    public function isConfiguredReturnsFalseWhenPublicKeyIdMissing(): void
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $e = $sR->shouldReceive('getSetting');
        $e->once()->with('gateway_amazon_pay_publicKeyId')->andReturn('');
        $e2 = $sR->shouldReceive('decode');
        $e2->once()->with('')->andReturn('');
        $e3 = $sR->shouldReceive('getSetting');
        $e3->once()->with('gateway_amazon_pay_merchantId')->andReturn('enc-merchant');
        $e4 = $sR->shouldReceive('decode');
        $e4->once()->with('enc-merchant')->andReturn('merchant123');
        $sR->shouldNotReceive('getAmazonPemFileFolderAliases');

        $service = new AmazonPayPaymentService($sR, $this->makeInvPaymentSettlementService());

        Assert::false($service->isConfigured());
    }

    public function isConfiguredReturnsFalseWhenPrivatePemFileMissing(): void
    {
        $dir = $this->makeTempPemDir(withPrivateKey: false);
        try {
            /** @var SettingRepository&m\MockInterface $sR */
            $sR = m::mock(SettingRepository::class);
            $e = $sR->shouldReceive('getSetting');
            $e->once()->with('gateway_amazon_pay_publicKeyId')->andReturn('enc-pub');
            $e2 = $sR->shouldReceive('decode');
            $e2->once()->with('enc-pub')->andReturn('pub123');
            $e3 = $sR->shouldReceive('getSetting');
            $e3->once()->with('gateway_amazon_pay_merchantId')->andReturn('enc-merchant');
            $e4 = $sR->shouldReceive('decode');
            $e4->once()->with('enc-merchant')->andReturn('merchant123');
            $e5 = $sR->shouldReceive('getAmazonPemFileFolderAliases');
            $e5->once()->andReturn(new Aliases(['@pem_file_unique_folder' => $dir]));

            $service = new AmazonPayPaymentService($sR, $this->makeInvPaymentSettlementService());

            Assert::false($service->isConfigured());
        } finally {
            $this->removeTempPemDir($dir);
        }
    }

    public function isConfiguredReturnsTrueWhenCredentialsAndPemFilePresent(): void
    {
        $dir = $this->makeTempPemDir(withPrivateKey: true);
        try {
            /** @var SettingRepository&m\MockInterface $sR */
            $sR = m::mock(SettingRepository::class);
            $e = $sR->shouldReceive('getSetting');
            $e->once()->with('gateway_amazon_pay_publicKeyId')->andReturn('enc-pub');
            $e2 = $sR->shouldReceive('decode');
            $e2->once()->with('enc-pub')->andReturn('pub123');
            $e3 = $sR->shouldReceive('getSetting');
            $e3->once()->with('gateway_amazon_pay_merchantId')->andReturn('enc-merchant');
            $e4 = $sR->shouldReceive('decode');
            $e4->once()->with('enc-merchant')->andReturn('merchant123');
            $e5 = $sR->shouldReceive('getAmazonPemFileFolderAliases');
            $e5->once()->andReturn(new Aliases(['@pem_file_unique_folder' => $dir]));

            $service = new AmazonPayPaymentService($sR, $this->makeInvPaymentSettlementService());

            Assert::true($service->isConfigured());
        } finally {
            $this->removeTempPemDir($dir);
        }
    }

    public function checkPrivatePemFileReturnsDetailsWhenFileMissing(): void
    {
        $dir = $this->makeTempPemDir(withPrivateKey: false);
        try {
            /** @var SettingRepository&m\MockInterface $sR */
            $sR = m::mock(SettingRepository::class);
            $e = $sR->shouldReceive('getAmazonPemFileFolderAliases');
            $e->once()->andReturn(new Aliases(['@pem_file_unique_folder' => $dir]));

            $service = new AmazonPayPaymentService($sR, $this->makeInvPaymentSettlementService());

            $expected = [
                'heading' => '',
                'message' => 'Amazon_Pay private.pem File Not Downloaded.'
                    . ' from Amazon and saved in Pem_unique_folder as'
                    . ' private.pem (Amazon Pay: 29 May 2025) Download at:'
                    . 'https://sellercentral-europe.amazon.com/gp/pyop/seller/'
                    . 'integrationcentral?ref=py_intcentr_confcard_sboxhome_GB',
                'url' => 'inv/urlKey',
                'url_key' => '',
                'gateway' => 'Amazon_Pay',
            ];

            Assert::same($expected, $service->checkPrivatePemFile());
        } finally {
            $this->removeTempPemDir($dir);
        }
    }

    public function checkPrivatePemFileReturnsNullWhenFileExists(): void
    {
        $dir = $this->makeTempPemDir(withPrivateKey: true);
        try {
            /** @var SettingRepository&m\MockInterface $sR */
            $sR = m::mock(SettingRepository::class);
            $e = $sR->shouldReceive('getAmazonPemFileFolderAliases');
            $e->once()->andReturn(new Aliases(['@pem_file_unique_folder' => $dir]));

            $service = new AmazonPayPaymentService($sR, $this->makeInvPaymentSettlementService());

            Assert::null($service->checkPrivatePemFile());
        } finally {
            $this->removeTempPemDir($dir);
        }
    }

    public function handleCallbackReturnsErrorWhenSessionIdMissing(): void
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);

        $service = new AmazonPayPaymentService($sR, $this->makeInvPaymentSettlementService());

        $result = $service->handleCallback(['amazonCheckoutSessionId' => '']);

        Assert::same([
            'success' => false,
            'message' => 'Amazon Checkout Session ID missing.',
            'details' => null,
        ], $result);
    }

    public function handleCallbackReturnsErrorWhenPayloadInvoiceIsInvalid(): void
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);

        $service = new AmazonPayPaymentService($sR, $this->makeInvPaymentSettlementService());

        // A malformed payload (wrong types for invoice/repositories) fails
        // the processAmazonCheckoutSession() parameter type check with a
        // \TypeError before any Amazon Pay client is ever built, which
        // handleCallback()'s catch(\Throwable) turns into an error result.
        $result = $service->handleCallback([
            'amazonCheckoutSessionId' => 'sess-123',
            'invoice' => 'not-an-invoice',
            'iR' => 'not-a-repository',
            'iaR' => 'not-a-repository',
        ]);

        /** @var string $message */
        $message = $result['message'];
        Assert::false($result['success']);
        Assert::true(str_starts_with($message, 'Amazon Pay callback error:'));
        Assert::null($result['details']);
    }

    public function getButtonDataReturnsExpectedPayloadAndSignature(): void
    {
        $dir = $this->makeTempPemDir(withPrivateKey: true);
        try {
            /** @var ClientEntity&m\MockInterface $clientEntity */
            $clientEntity = m::mock(ClientEntity::class);
            $eLang = $clientEntity->shouldReceive('getClientLanguage');
            $eLang->once()->andReturn('French');

            /** @var Inv&m\MockInterface $invoice */
            $invoice = m::mock(Inv::class);
            $eClient = $invoice->shouldReceive('getClient');
            $eClient->once()->andReturn($clientEntity);

            /** @var SettingRepository&m\MockInterface $sR */
            $sR = m::mock(SettingRepository::class);
            $e = $sR->shouldReceive('amazonLanguages');
            $e->once()->andReturn([
                'English' => 'en_GB',
                'French' => 'fr_FR',
                'German' => 'de_DE',
                'Japan' => 'jp_JP',
                'Italian' => 'it_IT',
                'Spanish' => 'es_ES',
            ]);
            $e2 = $sR->shouldReceive('getSetting');
            $e2->once()->with('currency_code')->andReturn('GBP');
            $e3 = $sR->shouldReceive('getSetting');
            $e3->once()->with('gateway_amazon_pay_merchantId')->andReturn('enc-merchant');
            $e4 = $sR->shouldReceive('decode');
            $e4->once()->with('enc-merchant')->andReturn('merchant123');
            $e5 = $sR->shouldReceive('getSetting');
            $e5->twice()->with('gateway_amazon_pay_publicKeyId')->andReturn('enc-pub');
            $e6 = $sR->shouldReceive('decode');
            $e6->twice()->with('enc-pub')->andReturn('pub123');
            $e7 = $sR->shouldReceive('getSetting');
            $e7->once()->with('gateway_amazon_pay_returnUrl')->andReturn('https://example.com/return');
            $e8 = $sR->shouldReceive('getSetting');
            $e8->once()->with('gateway_amazon_pay_storeId')->andReturn('enc-store');
            $e9 = $sR->shouldReceive('decode');
            $e9->once()->with('enc-store')->andReturn('store123');
            $e10 = $sR->shouldReceive('getAmazonPemFileFolderAliases');
            $e10->once()->andReturn(new Aliases(['@pem_file_unique_folder' => $dir]));
            $e11 = $sR->shouldReceive('amazonRegions');
            $e11->once()->andReturn(['Europe' => 'eu']);
            $e12 = $sR->shouldReceive('getSetting');
            $e12->once()->with('gateway_amazon_pay_region')->andReturn('Europe');
            $e13 = $sR->shouldReceive('getSetting');
            $e13->once()->with('gateway_amazon_pay_sandbox')->andReturn('0');

            $service = new AmazonPayPaymentService($sR, $this->makeInvPaymentSettlementService());

            $result = $service->getButtonData($invoice, 'urlkey123', 123.45);

            Assert::same(123.45, $result['amount']);
            Assert::same('fr_FR', $result['checkoutLanguage']);
            Assert::same('GBP', $result['ledgerCurrency']);
            Assert::same('merchant123', $result['merchantId']);
            Assert::same('PayOnly', $result['productType']);
            Assert::same('pub123', $result['publicKeyId']);
            Assert::same(['amount' => '123.45', 'currencyCode' => 'GBP'], $result['estimatedOrderAmount']);

            /** @var string $signature */
            $signature = $result['signature'];
            Assert::notSame('', $signature);
            Assert::true(base64_decode($signature, true) !== false);

            /** @var string $payloadJson */
            $payloadJson = $result['payloadJSON'];
            /** @var array{webCheckoutDetails: array{checkoutReviewReturnUrl: string}, storeId: string, scopes: list<string>} $decodedPayload */
            $decodedPayload = json_decode($payloadJson, true);
            Assert::same('https://example.com/return/urlkey123', $decodedPayload['webCheckoutDetails']['checkoutReviewReturnUrl']);
            Assert::same('store123', $decodedPayload['storeId']);
            Assert::same(['name', 'email', 'phoneNumber', 'billingAddress'], $decodedPayload['scopes']);
        } finally {
            $this->removeTempPemDir($dir);
        }
    }
}
