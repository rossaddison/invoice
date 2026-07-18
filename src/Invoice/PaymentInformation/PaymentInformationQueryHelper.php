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

    /**
     * True when Stripe's client-side redirect says the payment worked (or is
     * still working) but our own webhook hasn't confirmed it server-side yet
     * — a race between the browser redirect and the async webhook, not a
     * failure. Only redirect statuses that genuinely mean "not paid"
     * (requires_payment_method, canceled, etc.) fall outside this.
     */
    public static function isStripeStillProcessing(bool $isPaid, string $redirectStatus): bool
    {
        return !$isPaid && in_array($redirectStatus, ['processing', 'succeeded'], true);
    }

    public static function stripeCompleteHeading(
        Translator $translator,
        bool $isPaid,
        string $invoiceNumber,
        string $redirectStatus,
    ): string {
        if ($isPaid) {
            return sprintf(
                $translator->translate('online.payment.payment.successful'),
                $invoiceNumber,
            );
        }
        if (self::isStripeStillProcessing($isPaid, $redirectStatus)) {
            return sprintf(
                $translator->translate('online.payment.payment.processing'),
                $invoiceNumber,
            );
        }
        return sprintf(
            $translator->translate('online.payment.payment.failed'),
            $invoiceNumber,
        );
    }
}
