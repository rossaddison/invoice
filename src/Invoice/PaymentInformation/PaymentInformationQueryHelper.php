<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Invoice\Setting\SettingRepository as sR;
use Mollie\Api\MollieApiClient as MollieClient;
use Yiisoft\Translator\TranslatorInterface as Translator;

final class PaymentInformationQueryHelper
{
    public static function extractProviderLower(string $gateway): ?string
    {
        if (preg_match('/Open_Banking_With_([A-Za-z0-9]+)/', $gateway, $matches)) {
            return strtolower($matches[1]);
        }
        return null;
    }

    public static function mollieClientVersionString(): string
    {
        $array_version = new MollieClient()->getVersionStrings();
        return implode($array_version);
    }

    public static function mollieSetTestOrLiveApiKey(sR $sR, MollieClient $mollieClient): bool
    {
        /** @var string $testOrLiveApiKey */
        $testOrLiveApiKey = !empty($sR->getSetting('gateway_mollie_testOrLiveApiKey')) ?
                $sR->decode($sR->getSetting('gateway_mollie_testOrLiveApiKey')) : '';
        !empty($sR->getSetting('gateway_mollie_testOrLiveApiKey')) ?
                $mollieClient->setApiKey($testOrLiveApiKey) : '';
        return !empty($sR->getSetting('gateway_mollie_testOrLiveApiKey')) ? true : false;
    }

    public static function stripeCompleteHeading(
        Translator $translator,
        array $result,
        string $invoiceNumber,
        string $redirectStatus,
    ): string {
        $message = (string) ($result['message'] ?? '');
        if ($redirectStatus === 'succeeded') {
            return sprintf(
                $translator->translate('online.payment.payment.successful'),
                $invoiceNumber,
            );
        }
        return sprintf(
            $translator->translate('online.payment.payment.failed'),
            trim($invoiceNumber . ' ' . $message),
        );
    }

    public static function getStripePciClientSecret(array $yii_invoice): ?string
    {
        $payment_intent = \Stripe\PaymentIntent::create([
            'amount'   => (int) round(((float) $yii_invoice['balance'] ?: 0.00) * 100),
            'currency' => (string) $yii_invoice['currency'],
            'automatic_payment_methods' => ['enabled' => true],
            'receipt_email' => (string) $yii_invoice['customer_email'],
            'metadata' => [
                'invoice_id'             => (string) $yii_invoice['id'],
                'invoice_customer_id'    => (string) $yii_invoice['customer_id'],
                'invoice_number'         => (string) $yii_invoice['number'] ?: '',
                'invoice_payment_method' => '',
                'invoice_url_key'        => (string) $yii_invoice['url_key'],
            ],
        ]);
        return $payment_intent->client_secret;
    }
}
