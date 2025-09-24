<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Invoice\Entity\Company;
use App\Invoice\Entity\CompanyPrivate;
use App\Invoice\Entity\Inv;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Setting\Trait\OpenBankingProviders;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Security\Random;
use Yiisoft\Session\SessionInterface;
use App\Auth\Client\OpenBanking;

final class OpenBankingPaymentService
{
    use OpenBankingProviders;

    public function __construct(
        private readonly OpenBanking $openBanking,
        private readonly SessionInterface $session,
        private readonly sR $sR,
        private readonly UrlGenerator $urlGenerator,
    ) {}

    /**
     * Get the Open Banking authentication URL with PKCE.
     * Returns '' if not configured.
     */
    public function getAuthUrlForProvider(?array $providerConfig, string $url_key): string
    {
        if (null === $providerConfig) {
            return '';
        }

        $this->openBanking->setAuthUrl((string) $providerConfig['authUrl']);
        $this->openBanking->setTokenUrl((string) $providerConfig['tokenUrl']);
        $this->openBanking->setScope(isset($providerConfig['scope']) ? (string) $providerConfig['scope'] : null);

        $codeVerifier  = Random::string(128);
        $codeChallenge = strtr(
            rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='),
            '+/',
            '-_',
        );
        $this->session->set('code_verifier', $codeVerifier);

        return $this->openBanking->getAuthUrl() . '?' . http_build_query([
            'response_type'         => 'code',
            'scope'                 => $this->openBanking->getScope(),
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256',
            'redirect_uri' => $this->urlGenerator->generate('paymentinformation/openbanking_oauth_complete', ['url_key' => $url_key, '_language' => 'en'], [], null),
        ]);
    }

    /**
     * Exchange the authorization code for an access token.
     *
     * @param non-empty-string                                                             $code
     * @param array{authUrl:non-empty-string,tokenUrl:non-empty-string,scope?:string}|null $providerConfig
     * @param non-empty-string                                                             $url_key
     *
     * @throws \RuntimeException
     */
    public function fetchToken(
        ServerRequestInterface $request,
        string $code,
        ?array $providerConfig,
        string $url_key,
    ): \Yiisoft\Yii\AuthClient\OAuthToken {
        if (null === $providerConfig) {
            throw new \RuntimeException('Open Banking provider is not configured.');
        }
        $this->openBanking->setAuthUrl($providerConfig['authUrl']);
        $this->openBanking->setTokenUrl($providerConfig['tokenUrl']);
        $this->openBanking->setScope($providerConfig['scope'] ?? null);

        $codeVerifier = $this->session->get('code_verifier');
        if (!is_string($codeVerifier) || '' === $codeVerifier) {
            throw new \RuntimeException('Missing code verifier in session.');
        }

        return $this->openBanking->fetchAccessTokenWithCodeVerifier(
            $request,
            $code,
            [
                'redirect_uri'  => $this->urlGenerator->generateAbsolute('paymentinformation/openbanking_complete', ['url_key' => $url_key]),
                'code_verifier' => $codeVerifier,
            ],
        );
    }

    public function initiateTinkPayment(float $amount, Inv $invoice, Company $company, string $currency, string $recipientName, int $clientId, int $clientSecret): array
    {
        $providerConfig = $this->getOpenBankingProviderConfig('tink');
        if (null !== $providerConfig) {
            $activeCompanyPrivate = null;
            /**
             * @var CompanyPrivate $companyPrivate
             */
            foreach ($company->getCompanyPrivates() as $companyPrivate) {
                if ($companyPrivate->isActiveToday()) {
                    /**
                     * @var CompanyPrivate $activeCompanyPrivate
                     */
                    $activeCompanyPrivate = $companyPrivate;
                    break; // Stop at the first active one
                }
            }
            if (null !== $activeCompanyPrivate) {
                if (null !== ($iban = $activeCompanyPrivate->getIban())) {
                    $market = strtoupper(substr($iban, 0, 2));
                    try {
                        // Step 1: Get access token
                        $client = new \GuzzleHttp\Client();
                        $tokenResponse = $client->post((string) $providerConfig['token_url'], [
                            'form_params' => [
                                'client_id' => $clientId,
                                'client_secret' => $clientSecret,
                                'grant_type' => 'client_credentials',
                                'scope' => 'payment:read payment:write',
                            ],
                        ]);
                        $tokenData = (array) json_decode($tokenResponse->getBody()->getContents(), true);
                        $accessToken = (string) ($tokenData['access_token'] ?? '');
                        if (strlen($accessToken) == 0) {
                            return ['success' => false, 'error' => 'Unable to fetch access token'];
                        }

                        // Step 2: Create payment request
                        $response = $client->post((string) $providerConfig['paymentRequestUrl'], [
                            'headers' => [
                                'Authorization' => "Bearer $accessToken",
                                'Content-Type' => 'application/json',
                            ],
                            'json' => [
                                'recipient' => [
                                    'accountNumber' => $iban,
                                    'accountType' => 'iban',
                                ],
                                'amount' => $amount,
                                'currency' => $currency,
                                'market' => $market,
                                'recipientName' => $recipientName,
                                'sourceMessage' => 'Payment for Invoice ' . ($invoice->getNumber() ?? 'No Number'),
                                'remittanceInformation' => [
                                    'type' => 'UNSTRUCTURED',
                                    'value' => 'CREDITOR REFERENCE',
                                ],
                                'paymentScheme' => 'SEPA_CREDIT_TRANSFER',
                            ],
                        ]);
                        $body = (array) json_decode($response->getBody()->getContents(), true);
                        return ['success' => true, 'data' => $body];
                    } catch (\Throwable $e) {
                        return ['success' => false, 'error' => $e->getMessage()];
                    }
                }
                return ['success' => false, 'data' => []];
            }
            return ['success' => false, 'error' => 'CompanyPrivate: Today\'s date does not fall within Start Date and End Date'];
        }
        return ['success' => false, 'data' => []];
    }

    /**
     * @param string $urlKey
     * @param float $amount
     * @param Inv $invoice
     * @param array $items_array
     * @return array
     * Related logic: see src/Invoice/Setting/Trait/OpenBankingProviders.php
     */
    public function paymentStatusAndDetails(string $urlKey, float $amount, Inv $invoice, array $items_array): array
    {

        $merchant_payment_reference = 'won-' . ($invoice->getNumber() ?? '#') . '-' . ($invoice->getClient()?->getClient_full_name() ?? 'No Client Full Name');
        $payment_description = '';
        /**
         * @var string $item
         */
        foreach ($items_array as $item) {
            $payment_description .= $item . ', ';
        }
        $customer_email_address = $invoice->getClient()?->getClient_email();
        $apiKey               = $this->sR->getSetting('gateway_open_banking_with_wonderful_apiToken');
        $providerConfig       = $this->getOpenBankingProviderConfig('wonderful');
        if (null !== $providerConfig) {
            try {
                $client = new GuzzleClient();
                $apiWonderfulOneV2QuickPay = (string) $providerConfig['quickPayUrl'];
                $response = $client->post($apiWonderfulOneV2QuickPay, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json',
                    ],
                    'json' => [
                        'amount'                     => $amount,
                        'merchant_payment_reference' => $merchant_payment_reference,
                        'payment_description'        => $payment_description,
                        'customer_email_address'     => $customer_email_address,
                        'redirect_url'               => $this->urlGenerator->generateAbsolute(
                            'paymentinformation/wonderful_complete',
                            [
                                'url_key' => $urlKey,
                                'ref' => $merchant_payment_reference,
                            ],
                            [],
                        ),
                    ],
                ]);
                $body = (array) json_decode((string) $response->getBody(), true);

                if (isset($body['status']) && $body['status'] === 'paid') {
                    // Mark invoice as paid, update DB, etc.
                    return ['success' => true, 'data' => $body];
                }
                return ['success' => false, 'data' => $body ?: []];
            } catch (\Throwable $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }
        return ['success' => false, 'data' => []];
    }
}
