<?php

declare(strict_types=1);

// Clients specific to the UK and EU
use App\Auth\Client\DeveloperSandboxHmrc;
use App\Auth\Client\GovUk;
use App\Auth\Client\OpenBanking;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Definitions\Reference;
use Yiisoft\Factory\Factory;
use Yiisoft\Session\Session;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Yii\AuthClient\StateStorage\StateStorageInterface;
use Yiisoft\Yii\AuthClient\StateStorage\SessionStateStorage;
use Yiisoft\Yii\AuthClient\Client\Facebook;
use Yiisoft\Yii\AuthClient\Client\GitHub;
use Yiisoft\Yii\AuthClient\Client\Google;
use Yiisoft\Yii\AuthClient\Client\LinkedIn;
use Yiisoft\Yii\AuthClient\Client\MicrosoftOnline;
use Yiisoft\Yii\AuthClient\Client\OpenIdConnect;
use Yiisoft\Yii\AuthClient\Client\VKontakte;
use Yiisoft\Yii\AuthClient\Client\X;
use Yiisoft\Yii\AuthClient\Client\Yandex;
use Yiisoft\Yii\AuthClient\AuthAction;
use Yiisoft\Yii\AuthClient\Widget\AuthChoice;
use Yiisoft\Yii\AuthClient\Collection;

/**
 * @var array $params
 * @var array $params['yiisoft/yii-auth-client']
 * @var array $paramsYiisoft['clients']
 */
$paramsYiisoft = $params['yiisoft/yii-auth-client'];
$paramsClients = $paramsYiisoft['clients'];

/** @var array $paramsClients['developersandboxhmrc'] **/
$developersandboxhmrcClient = $paramsClients['developersandboxhmrc'];

/** @var array $paramsClients['facebook'] **/
$facebookClient = $paramsClients['facebook'];

/** @var array $paramsClients['github'] **/
$githubClient = $paramsClients['github'];

/** @var array $paramsClients['google'] **/
$googleClient = $paramsClients['google'];

/** @var array $paramsClients['govuk'] **/
$govukClient = $paramsClients['govuk'];

/** @var array $paramsClients['linkedin'] **/
$linkedinClient = $paramsClients['linkedin'];

/** @var array $paramsClients['microsoftonline'] **/
$microsoftonlineClient = $paramsClients['microsoftonline'];

/** @var array $paramsClients['openbanking'] **/
$openbankingClient = $paramsClients['openbanking'];

/** @var array $paramsClients['oidc'] **/
$openidconnectClient = $paramsClients['oidc'];

/** @var array $paramsClients['vkontakte'] **/
$vkontakteClient = $paramsClients['vkontakte'];

/** @var array $paramsClients['x'] **/
$xClient = $paramsClients['x'];

/** @var array $paramsClients['yandex'] **/
$yandexClient = $paramsClients['yandex'];

$constructArray = [
    'httpClient'     => Reference::to(ClientInterface::class),
    'requestFactory' => Reference::to(RequestFactoryInterface::class),
    'stateStorage'   => Reference::to(StateStorageInterface::class),
    'factory'        => Reference::to(Factory::class),
    'session'        => Reference::to(Session::class),
];

$construct = '__construct()';
$setClientId = 'setClientId()';
$setClientSecret = 'setClientSecret()';
$setReturnUrl = 'setReturnUrl()';
$setIssuerUrl = 'setIssuerUrl()';
$setTenant = 'setTenant()';

return [
    Session::class => [
        $construct => [
            'options' => [
                'cookie_secure' => 0,
            ],
        ],
    ],
    SessionInterface::class => Session::class,
    SessionStateStorage::class => [
        $construct => [
            'session' => Reference::to(SessionInterface::class),
        ],
    ],
    StateStorageInterface::class => Reference::to(SessionStateStorage::class),
    DeveloperSandboxHmrc::class => [
        $construct => $constructArray,
        $setClientId => [$developersandboxhmrcClient['clientId']],
        $setClientSecret => [$developersandboxhmrcClient['clientSecret']],
        $setReturnUrl => [$developersandboxhmrcClient['returnUrl']],
    ],
    Facebook::class => [
        $construct => $constructArray,
        $setClientId => [$facebookClient['clientId']],
        $setClientSecret => [$facebookClient['clientSecret']],
        $setReturnUrl => [$facebookClient['returnUrl']],
    ],
    GitHub::class => [
        $construct => $constructArray,
        $setClientId => [$githubClient['clientId']],
        $setClientSecret => [$githubClient['clientSecret']],
        $setReturnUrl => [$githubClient['returnUrl']],
    ],
    Google::class => [
        $construct => $constructArray,
        $setClientId => [$googleClient['clientId']],
        $setClientSecret => [$googleClient['clientSecret']],
        $setReturnUrl => [$googleClient['returnUrl']],
    ],
    GovUk::class => [
        $construct => $constructArray,
        $setClientId => [$govukClient['clientId']],
        $setClientSecret => [$govukClient['clientSecret']],
        $setReturnUrl => [$govukClient['returnUrl']],
    ],
    LinkedIn::class => [
        $construct => $constructArray,
        $setClientId => [$linkedinClient['clientId']],
        $setClientSecret => [$linkedinClient['clientSecret']],
        $setReturnUrl => [$linkedinClient['returnUrl']],
    ],
    MicrosoftOnline::class => [
        $construct => $constructArray,
        $setClientId => [$microsoftonlineClient['clientId']],
        $setClientSecret => [$microsoftonlineClient['clientSecret']],
        $setReturnUrl => [$microsoftonlineClient['returnUrl']],
        $setTenant => [$microsoftonlineClient['tenant']],
    ],
    OpenBanking::class => [
        $construct => $constructArray,
        $setClientId => [$openbankingClient['clientId']],
        $setClientSecret => [$openbankingClient['clientSecret']],
        $setReturnUrl => [$openbankingClient['returnUrl']],
    ],
    OpenIdConnect::class => [
        $construct => array_merge($constructArray, [
            'cache' => Reference::to(CacheInterface::class),
            'name' => 'oidc',
            'title' => 'Open Id Connect',
        ]),
        $setIssuerUrl => [$openidconnectClient['issuerUrl']],
        $setClientId => [$openidconnectClient['clientId']],
        $setClientSecret => [$openidconnectClient['clientSecret']],
        $setReturnUrl => [$openidconnectClient['returnUrl']],
    ],
    VKontakte::class => [
        $construct => $constructArray,
        $setClientId => [$vkontakteClient['clientId']],
        $setClientSecret => [$vkontakteClient['clientSecret']],
        $setReturnUrl => [$vkontakteClient['returnUrl']],
    ],
    X::class => [
        $construct => $constructArray,
        $setClientId => [$xClient['clientId']],
        $setClientSecret => [$xClient['clientSecret']],
        $setReturnUrl => [$xClient['returnUrl']],
    ],
    Yandex::class => [
        $construct => $constructArray,
        $setClientId => [$yandexClient['clientId']],
        $setClientSecret => [$yandexClient['clientSecret']],
        $setReturnUrl => [$yandexClient['returnUrl']],
    ],
    Collection::class => [
        $construct => [
            'clients' => [
                'developersandboxhmrc' => Reference::to(DeveloperSandboxHmrc::class),
                'facebook' => Reference::to(Facebook::class),
                'github' => Reference::to(GitHub::class),
                'google' => Reference::to(Google::class),
                'govuk' => Reference::to(GovUk::class),
                'linkedin' => Reference::to(LinkedIn::class),
                'microsoftonline' => Reference::to(MicrosoftOnline::class),
                'openbanking' => Reference::to(OpenBanking::class),
                //'oidc' => Reference::to(OpenIdConnect::class),
                'vkontakte' => Reference::to(VKontakte::class),
                'x' => Reference::to(X::class),
                'yandex' => Reference::to(Yandex::class),
            ],
        ],
    ],
    // Applied in: resources/views/auth/login
    AuthChoice::class => [
        $construct => [
            // $this->clients = Collection's clients
            'clientCollection' => Reference::to(Collection::class),
            'webView' => Reference::to(WebView::class),
            'assetManager' => Reference::to(AssetManager::class),
            'translator' => Reference::to(TranslatorInterface::class),
        ],
    ],
    AuthAction::class => [
        $construct => [
            'clientCollection' => Reference::to(Collection::class),
            'aliases' => Reference::to(Aliases::class),
            'webView' => Reference::to(WebView::class),
            'responseFactory' => Reference::to(ResponseFactoryInterface::class),
        ],
    ],
];
