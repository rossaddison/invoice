<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Security\Random;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\AuthClient\Client\OpenBanking;

final class OpenBankingPaymentService
{
    private OpenBanking $openBanking;
    private SessionInterface $session;
    private UrlGenerator $urlGenerator;

    public function __construct(
        OpenBanking $openBanking,
        SessionInterface $session,
        UrlGenerator $urlGenerator,
    ) {
        $this->openBanking  = $openBanking;
        $this->session      = $session;
        $this->urlGenerator = $urlGenerator;
    }

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

        return $this->openBanking->getAuthUrl().'?'.http_build_query([
            'response_type'         => 'code',
            'scope'                 => $this->openBanking->getScope(),
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256',
            // 'redirect_uri' => $this->urlGenerator->generateAbsolute('paymentinformation/openbanking_complete', ['url_key' => $url_key]),
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
}
