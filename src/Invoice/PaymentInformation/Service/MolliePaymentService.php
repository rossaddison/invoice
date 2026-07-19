<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Invoice\PaymentInformation\PaymentGatewayInterface;
use App\Invoice\PaymentInformation\PaymentInformationQueryHelper;
use App\Invoice\PaymentInformation\PaymentRefundResult;
use App\Invoice\PaymentInformation\PaymentVerificationResult;
use App\Invoice\Setting\SettingRepository;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient as MollieClient;
use Psr\Log\LoggerInterface;

final class MolliePaymentService implements PaymentGatewayInterface
{
    public function __construct(
        private readonly SettingRepository $settings,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function getDriverKey(): string
    {
        return 'mollie';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        return PaymentInformationQueryHelper::mollieSetTestOrLiveApiKey($this->settings, new MollieClient());
    }

    /**
     * Authoritatively confirms a payment's status by asking Mollie directly.
     */
    #[\Override]
    public function verifyPayment(string $providerReference): PaymentVerificationResult
    {
        $mollieClient = new MollieClient();
        PaymentInformationQueryHelper::mollieSetTestOrLiveApiKey($this->settings, $mollieClient);
        try {
            $payment = $mollieClient->payments->get($providerReference);
            return new PaymentVerificationResult(
                paid: $payment->isPaid(),
                providerReference: $providerReference,
                message: $payment->status,
            );
        } catch (ApiException $e) {
            $this->logger->warning('Mollie verifyPayment failed.', ['error' => $e->getMessage()]);
            return new PaymentVerificationResult(paid: false, providerReference: $providerReference, message: $e->getMessage());
        }
    }

    #[\Override]
    public function refund(string $providerReference, float $amount): PaymentRefundResult
    {
        $mollieClient = new MollieClient();
        PaymentInformationQueryHelper::mollieSetTestOrLiveApiKey($this->settings, $mollieClient);
        try {
            $refund = $mollieClient->paymentRefunds->createForId($providerReference, [
                'amount' => [
                    'currency' => strtoupper($this->settings->getSetting('currency_code') ?: 'EUR'),
                    'value' => number_format($amount, 2, '.', ''),
                ],
            ]);

            return new PaymentRefundResult(
                refunded: true,
                providerReference: $refund->id,
                message: $refund->status,
            );
        } catch (ApiException $e) {
            $this->logger->error('Mollie refund failed.', [
                'payment_id' => $providerReference,
                'error' => $e->getMessage(),
            ]);
            return new PaymentRefundResult(refunded: false, providerReference: $providerReference, message: $e->getMessage());
        }
    }
}
