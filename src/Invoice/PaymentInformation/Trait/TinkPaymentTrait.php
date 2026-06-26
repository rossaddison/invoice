<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Trait;

use App\Invoice\PaymentInformation\PaymentInformationGatewayContext;

trait TinkPaymentTrait
{
    /**
     * @param array<string, mixed> $viewData
     * @param array<array-key, mixed> $inv
     * @return array<string, mixed>
     */
    private function applyTinkProviderData(
        array $viewData,
        float $amount,
        PaymentInformationGatewayContext $ctx,
        array $inv,
    ): array {
        $company = $this->compR->repoCompanyActivequery();
        $recipientName = $company?->getName() ?? 'Unknown';
        if (null === $company || null === $company->getName()) {
            return $viewData;
        }
        $clientId     = $this->sR->getSetting('gateway_open_banking_with_tink_client_id');
        $clientSecret = $this->sR->getSetting('gateway_open_banking_with_tink_client_secret');
        $invCurrency  = strtoupper((string) $inv['currency']);
        if (strlen($clientId) === 0 || strlen($clientSecret) === 0) {
            $viewData['alert'] = 'Missing Credentials Client Id and Client Secret';
        } elseif (!in_array($invCurrency, $this->sR->tinkSupportedCurrencies(), true)) {
            $viewData['alert'] = 'Currency not supported.';
        } else {
            $details = $this->openBankingPaymentService->initiateTinkPayment(
                $amount, $ctx->invoice, $company, $invCurrency,
                $recipientName, (int) $clientId, (int) $clientSecret,
            );
            $singleKeyArray   = (array) ($details['data'] ?? []);
            $data             = (array) ($singleKeyArray['data'] ?? []);
            $paymentRequestId = (string) ($data['id'] ?? '');
            $market           = (string) ($data['market'] ?? '');
            $locale           = 'en_GB';
            $redirectUri      = urlencode($this->urlGenerator->generate(
                'paymentinformation/tinkCcomplete',
                ['url_key' => $ctx->url_key, 'payment_request_id' => $paymentRequestId],
            ));
            $viewData['authToken'] = false;
            $viewData['authUrl']   = "https://link.tink.com/1.0/pay/direct"
                . "/?client_id={$clientId}&redirect_uri={$redirectUri}"
                . "&market={$market}&locale={$locale}&payment_request_id={$paymentRequestId}";
        }
        return $viewData;
    }
}
