<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Invoice\Entity\Inv;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Setting\Trait\OpenBankingProviders;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Security\Random;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\AuthClient\Client\OpenBanking;

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

        return $this->openBanking->fetchAccessTokenWithCurlAndCodeVerifier(
            $request,
            $code,
            [
                'redirect_uri'  => $this->urlGenerator->generateAbsolute('paymentinformation/openbanking_complete', ['url_key' => $url_key]),
                'code_verifier' => $codeVerifier,
            ],
        );
    }

    public function paymentStatusAndDetails(string $urlKey, float $balance, float $total, Inv $invoice, array $items_array): array
    {
        $amount = ((($balance > 0) && ($total > 0)) ? $balance : 0);
        $merchant_payment_reference = 'won-' . ($invoice->getNumber() ?? '#') . '-' . ($invoice->getClient()?->getClient_full_name() ?? 'No Client Full Name');
        $payment_description = '';
        /**
         * @var string $item
         */
        foreach ($items_array as $item) {
            $payment_description .= $item . ', ';
        }
        $customer_email_address = $invoice->getClient()?->getClient_email();
        $apiKey               = $this->sR->getSetting('gateway_openbanking_apiToken');
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
