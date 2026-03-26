<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use Amazon\Pay\API\Client;
use App\Invoice\Entity\Inv;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\Setting\SettingRepository as sR;
use Yiisoft\Json\Json;
use Yiisoft\Security\Random;

class AmazonPayPaymentService
{
    public function __construct(
        private readonly sR $sR,
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

    public function handleCallback(array $payload): array
    {
        $sessionId = (string) $payload['amazonCheckoutSessionId'];
        /** @var Inv $invoice */
        $invoice = $payload['invoice'];
        /** @var InvRepository */
        $invoiceRepository = $payload['iR'];
        /** @var InvAmountRepository */
        $invoiceAmountRepository = $payload['iaR'];

        if (!$sessionId) {
            return [
                'success' => false,
                'message' => 'Amazon Checkout Session ID missing.',
                'details' => null,
            ];
        }
        
        $sandboxOrLive = '';
        try {
            if ($this->sR->getSetting('gateway_amazon_pay_sandbox') === '1') {
                $sandboxOrLive = 'SANDBOX-';
            } else {
                $sandboxOrLive = 'LIVE-';
            }
            $amazonpayConfig = [
                'public_key_id' => $sandboxOrLive
                . (string) $this->sR->decode($this->sR->getSetting(
                        'gateway_amazon_pay_publicKeyId')),
                'private_key' => $this->getAmazonPrivateKeyFile(),
                'region' => $this->getAmazonRegion(),
                'algorithm' => 'AMZN-PAY-RSASSA-PSS-V2'
            ];
            $client = new Client($amazonpayConfig);

            $apiResponse = (array) $client->getCheckoutSession(
                    ['checkoutSessionId' => $sessionId]);
            $responseData = (array) $apiResponse['response'];
            $statusDetails = (array) $responseData['statusDetails'];
            /** @var string|null $paymentState */
            $paymentState = $statusDetails['state'] ?? null;

            if ($paymentState !== 'Completed') {
                return [
                    'success' => false,
                    'message' => 'Amazon Pay session not completed.',
                    'details' => $responseData,
                ];
            }

            if (!($invoice instanceof Inv)) {
                return [
                    'success' => false,
                    'message' => 'Invoice not found.',
                    'details' => $responseData,
                ];
            }

            $invoice->setPaymentMethod(4); // 4 = Card/Direct Debit
            $invoice->setStatusId(4);      // 4 = Paid
            $invoiceRepository->save($invoice);

            $invoiceAmountRecord =
                $invoiceAmountRepository->repoInvquery((int) $invoice->getId());
            if ($invoiceAmountRecord) {
                $balance = $invoiceAmountRecord->getBalance();
                if (null !== $balance) {
                    $total = $invoiceAmountRecord->getTotal();
                    $invoiceAmountRecord->setBalance(0);
                    if ($total !== null) {
                        $invoiceAmountRecord->setPaid($total);
                    }
                    $invoiceAmountRepository->save($invoiceAmountRecord);
                }
            }

            return [
                'success' => true,
                'message' => 'Amazon Pay session completed and invoice updated.',
                'details' => $responseData,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Amazon Pay callback error: ' . $e->getMessage(),
                'details' => null,
            ];
        }
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

        /**
         * @psalm-suppress MixedReturnStatement $this->generateButtonSignature($payloadJSON)
         */
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

        /**
         * @psalm-suppress MixedReturnStatement $this->generateButtonSignature($payloadJSON)
         */
        return $client->generateButtonSignature($payloadJSON);
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
