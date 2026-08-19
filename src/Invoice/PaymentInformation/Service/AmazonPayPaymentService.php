<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use Amazon\Pay\API\Client;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Inv\InvPaymentSettlementService;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\PaymentInformation\PaymentGatewayInterface;
use App\Invoice\PaymentInformation\PaymentRefundResult;
use App\Invoice\PaymentInformation\PaymentVerificationResult;
use App\Invoice\Setting\SettingRepository as sR;
use Yiisoft\Json\Json;
use Yiisoft\Security\Random;

class AmazonPayPaymentService implements PaymentGatewayInterface
{
    public function __construct(
        private readonly sR $sR,
        private readonly InvPaymentSettlementService $invPaymentSettlementService,
    ) {
    }

    /**
     * Create a payment request for Amazon Pay.
     * Customize this method as needed for your integration.
     */
    public function createPaymentRequest(float $amount, string $currency): array
    {
        // Integrate with Amazon Pay SDK or API here.
        // Return data required for frontend or further processing.

        return [
            'orderReference' => 'AMZN-' . Random::string(12),
            'amount' => $amount,
            'currency' => $currency,
        ];
    }

    #[\Override]
    public function getDriverKey(): string
    {
        return 'amazon_pay';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        $publicKeyId = (string) $this->sR->decode($this->sR->getSetting('gateway_amazon_pay_publicKeyId') ?: '');
        $merchantId  = (string) $this->sR->decode($this->sR->getSetting('gateway_amazon_pay_merchantId') ?: '');

        return $publicKeyId !== '' && $merchantId !== '' && null === $this->checkPrivatePemFile();
    }

    /**
     * Authoritatively confirms a checkout session's payment state by asking
     * Amazon Pay directly.
     */
    #[\Override]
    public function verifyPayment(string $providerReference): PaymentVerificationResult
    {
        $sandboxOrLive = $this->sR->getSetting('gateway_amazon_pay_sandbox') === '1' ? 'SANDBOX-' : 'LIVE-';
        $client = new Client([
            'public_key_id' => $sandboxOrLive
                . (string) $this->sR->decode($this->sR->getSetting('gateway_amazon_pay_publicKeyId')),
            'private_key' => $this->getAmazonPrivateKeyFile(),
            'region' => $this->getAmazonRegion(),
            'algorithm' => 'AMZN-PAY-RSASSA-PSS-V2',
        ]);
        $apiResponse = (array) $client->getCheckoutSession(['checkoutSessionId' => $providerReference]);
        $responseData = (array) ($apiResponse['response'] ?? []);
        $statusDetails = (array) ($responseData['statusDetails'] ?? []);
        $paymentState = (string) ($statusDetails['state'] ?? '');

        return new PaymentVerificationResult(
            paid: $paymentState === 'Completed',
            providerReference: $providerReference,
            message: $paymentState,
        );
    }

    /**
     * $providerReference must be an Amazon Pay chargeId (not a
     * checkoutSessionId). Refunds are asynchronous on Amazon's side —
     * refunded: true here means the refund was accepted (RefundInitiated),
     * not necessarily settled yet.
     */
    #[\Override]
    public function refund(string $providerReference, float $amount): PaymentRefundResult
    {
        // Unlike checkoutSessionId/getButtonData's raw `sandbox: bool` config,
        // the *stored* publicKeyId already carries Amazon's own "SANDBOX-"/
        // "LIVE-" prefix (that's how Amazon issues these ids) — do not
        // prepend one here, or the request is rejected as InvalidHeaderValue.
        $client = new Client([
            'public_key_id' => (string) $this->sR->decode($this->sR->getSetting('gateway_amazon_pay_publicKeyId')),
            'private_key' => $this->getAmazonPrivateKeyFile(),
            'region' => $this->getAmazonRegion(),
            'algorithm' => 'AMZN-PAY-RSASSA-PSS-V2',
        ]);
        $currencyCode = $this->sR->getSetting('currency_code') ?: 'GBP';
        $payload = [
            'chargeId' => $providerReference,
            'refundReferenceId' => 'REFUND-' . Random::string(16),
            'refundTotal' => [
                'currencyCode' => $currencyCode,
                'amount' => number_format($amount, 2, '.', ''),
            ],
        ];
        $apiResponse = (array) $client->createRefund($payload, [
            'x-amz-pay-idempotency-key' => Random::string(16),
        ]);
        $responseData = (array) ($apiResponse['response'] ?? []);
        $statusDetails = (array) ($responseData['statusDetails'] ?? []);
        $state = (string) ($statusDetails['state'] ?? '');
        $refundId = (string) ($responseData['refundId'] ?? '');
        $errorMessage = (string) ($responseData['message'] ?? '');

        return new PaymentRefundResult(
            refunded: $refundId !== '' && $state !== 'Declined',
            providerReference: $refundId !== '' ? $refundId : $providerReference,
            message: $state !== '' ? $state : $errorMessage,
        );
    }

    public function handleCallback(array $payload): array
    {
        $sessionId = (string) $payload['amazonCheckoutSessionId'];
        if (!$sessionId) {
            return ['success' => false, 'message' => 'Amazon Checkout Session ID missing.', 'details' => null];
        }
        /** @var Inv $invoice */
        $invoice = $payload['invoice'];
        /** @var InvAmountRepository $invoiceAmountRepository */
        $invoiceAmountRepository = $payload['iaR'];
        try {
            return $this->processAmazonCheckoutSession($sessionId, $invoice, $invoiceAmountRepository);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Amazon Pay callback error: ' . $e->getMessage(), 'details' => null];
        }
    }

    private function processAmazonCheckoutSession(
        string $sessionId,
        Inv $invoice,
        InvAmountRepository $invoiceAmountRepository,
    ): array {
        $sandboxOrLive = $this->sR->getSetting('gateway_amazon_pay_sandbox') === '1' ? 'SANDBOX-' : 'LIVE-';
        $amazonpayConfig = [
            'public_key_id' => $sandboxOrLive
                . (string) $this->sR->decode($this->sR->getSetting('gateway_amazon_pay_publicKeyId')),
            'private_key' => $this->getAmazonPrivateKeyFile(),
            'region' => $this->getAmazonRegion(),
            'algorithm' => 'AMZN-PAY-RSASSA-PSS-V2',
        ];
        $client = new Client($amazonpayConfig);
        $apiResponse = (array) $client->getCheckoutSession(['checkoutSessionId' => $sessionId]);
        $responseData = (array) $apiResponse['response'];
        $statusDetails = (array) $responseData['statusDetails'];
        /** @var string|null $paymentState */
        $paymentState = $statusDetails['state'] ?? null;
        if ($paymentState !== 'Completed') {
            return ['success' => false, 'message' => 'Amazon Pay session not completed.', 'details' => $responseData];
        }
        $invoiceAmountRecord = $invoiceAmountRepository->repoInvquery($invoice->reqId());
        if ($invoiceAmountRecord !== null) {
            $this->invPaymentSettlementService->markInvoicePaidAndAdjustStock($invoice, $invoiceAmountRecord);
        }
        return ['success' => true, 'message' => 'Amazon Pay session completed and invoice updated.', 'details' => $responseData];
    }

    public function checkPrivatePemFile(): ?array
    {
        $aliases = $this->sR->getAmazonPemFileFolderAliases();
        if (!file_exists($aliases->get('@pem_file_unique_folder') . '/private.pem')) {
            return [
                'heading' => '',
                'message' => 'Amazon_Pay private.pem File Not Downloaded.'.
                ' from Amazon and saved in Pem_unique_folder as'
                . ' private.pem (Amazon Pay: 29 May 2025) Download at:'
                . 'https://sellercentral-europe.amazon.com/gp/pyop/seller/'
                . 'integrationcentral?ref=py_intcentr_confcard_sboxhome_GB',
                'url' => 'inv/urlKey',
                'url_key' => '', // Set dynamically in controller
                'gateway' => 'Amazon_Pay',
            ];
        }
        return null;
    }

    /**
     * Related logic: see
     * https://developer.amazon.com
     * /docs/amazon-pay-checkout/add-the-amazon-pay-button.html#2-generate-the-create-checkout-session-payload
     * @param Inv $invoice
     * @param string $url_key
     * @param float $amount
     * @return array
     */
    public function getButtonData(Inv $invoice, string $url_key, float $amount): array
    {
        // Get client language and determine Amazon language code
        $client_language = $invoice->getClient()?->getClientLanguage() ?? '';
        $amazon_languages = $this->sR->amazonLanguages();
        $checkoutLanguage = 'en_GB';
        if ($client_language && isset($amazon_languages[$client_language])) {
            $checkoutLanguage = $amazon_languages[$client_language];
        }

        // Get ledger currency
        $ledgerCurrency = $this->sR->getSetting('currency_code') ?: 'GBP';

        // Get merchant and public key id
        $merchantId =
            (string) $this->sR->decode($this->sR->getSetting('gateway_amazon_pay_merchantId'));
        $publicKeyId =
            (string) $this->sR->decode($this->sR->getSetting('gateway_amazon_pay_publicKeyId'));

        // Generate the payload JSON for Amazon Pay
        $checkoutReviewReturnUrl = $this->sR->getSetting('gateway_amazon_pay_returnUrl')
            . '/' . $url_key;
        $storeId =
            (string) $this->sR->decode($this->sR->getSetting('gateway_amazon_pay_storeId'));

        $payloadArray = [
            'webCheckoutDetails' => [
                'checkoutReviewReturnUrl' => $checkoutReviewReturnUrl,
            ],
            'storeId' => $storeId,
            'scopes' => [
                'name',
                'email',
                'phoneNumber',
                'billingAddress',
            ],
        ];
        $payloadJSON = Json::encode($payloadArray);

        $signature = $this->generateButtonSignature($payloadJSON);

        $productType = 'PayOnly';

        $estimatedOrderAmount = [
            'amount' => number_format($amount, 2, '.', ''),
            'currencyCode' => $ledgerCurrency,
        ];

        // Return all required data for the Amazon Pay button
        return [
            'amount' => $amount,
            'checkoutLanguage' => $checkoutLanguage,
            'ledgerCurrency' => $ledgerCurrency,
            'merchantId' => $merchantId,
            'payloadJSON' => $payloadJSON,
            'productType' => $productType,
            'publicKeyId' => $publicKeyId,
            'signature' => $signature ?: '',
            'estimatedOrderAmount' => $estimatedOrderAmount,
        ];
    }

    /**
     * Related logic: see https://developer.amazon.com
     * /docs/amazon-pay-checkout/
     * add-the-amazon-pay-button.html#2-generate-the-create-checkout-session-payload
     * Step 3: Sign the payload
     *
     * @param string $payloadJSON
     * @throws \RuntimeException
     * @return string
     */
    private function generateButtonSignature(string $payloadJSON): string
    {
        $amazonpay_config = [
            'public_key_id' =>
                $this->sR
                     ->decode($this->sR
                                   ->getSetting('gateway_amazon_pay_publicKeyId')),
            'private_key' => $this->getAmazonPrivateKeyFile(),
            'region' => $this->getAmazonRegion(),
            'sandbox' => $this->sR->getSetting('gateway_amazon_pay_sandbox') === '1',
        ];
        $client = new Client($amazonpay_config);

        // Amazon Pay's SDK declares no return type on generateButtonSignature()
        // (untyped legacy code) — it always returns a string on success,
        // throwing \Exception itself if RSA signing fails, so this cast is a
        // real type narrowing, not a guess.
        return (string) $client->generateButtonSignature($payloadJSON);
    }

    private function getAmazonPrivateKeyFile(): string
    {
        $aliases = $this->sR->getAmazonPemFileFolderAliases();
        $targetPath = $aliases->get('@pem_file_unique_folder');
        $original_file_name = 'private.pem';
        return $targetPath . '/' . $original_file_name;
    }

    private function getAmazonRegion(): string
    {
        $regions = $this->sR->amazonRegions();
        $region = $this->sR->getSetting('gateway_amazon_pay_region');
        return (string) $regions[$region] ?: 'eu';
    }
}
