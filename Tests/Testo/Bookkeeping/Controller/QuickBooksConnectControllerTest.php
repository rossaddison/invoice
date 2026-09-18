<?php

declare(strict_types=1);

namespace Tests\Testo\Bookkeeping\Controller;

use App\Auth\Client\Intuit;
use App\Auth\Permissions;
use App\Bookkeeping\Controller\QuickBooksConnectController;
use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Psr7\ServerRequest;
use InvalidArgumentException;
use Mockery as m;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Router\UrlGeneratorInterface as UrlGenerator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\AuthClient\OAuthToken;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Covers QuickBooksConnectController -- the one-time interactive "Connect
 * to QuickBooks" flow. Deliberately never asserts against real translated
 * strings (the mocked Translator just echoes the message key back), so
 * these tests only prove the controller's own branching/persistence
 * logic, matching how the rest of this app's controller tests avoid
 * coupling to resources/messages/en/app.php's actual wording.
 */
#[Test]
final class QuickBooksConnectControllerTest
{
    private const string SETTINGS_TAB_ROUTE = 'setting/tabIndex';
    private const string REALM_ID = '12345';

    /**
     * @param array<string, string> $queryParams
     */
    private function makeRequest(array $queryParams = []): Request
    {
        $request = new ServerRequest('GET', 'https://invoice.myhost/bookkeeping/quickbooksCallback');
        return $request->withQueryParams($queryParams);
    }

    /**
     * @return sR&m\MockInterface
     */
    private function makeSettingRepository(string $clientId = 'test-client-id', string $clientSecret = 'enc:test-secret'): sR
    {
        /** @var sR&m\MockInterface $settings */
        $settings = m::mock(sR::class);
        $settings->shouldReceive('getSetting')->andReturnUsing(
            static fn(string $key): string => match ($key) {
                'bookkeeping_quickbooks_client_id' => $clientId,
                'bookkeeping_quickbooks_client_secret' => $clientSecret,
                default => '',
            },
        );
        $settings->shouldReceive('decode')->andReturnUsing(
            static fn(string $data): string => str_starts_with($data, 'enc:') ? substr($data, 4) : $data,
        );
        $settings->shouldReceive('encode')->andReturnUsing(
            static fn(string $data): string => 'enc:' . $data,
        );

        return $settings;
    }

    private function makeController(
        ?Intuit $intuit = null,
        ?sR $settings = null,
        ?WebControllerService $webService = null,
        ?Flash $flash = null,
    ): QuickBooksConnectController {
        if ($intuit === null) {
            /** @var Intuit&m\MockInterface $intuit */
            $intuit = m::mock(Intuit::class);
        }
        if ($settings === null) {
            $settings = $this->makeSettingRepository();
        }
        if ($webService === null) {
            /** @var WebControllerService&m\MockInterface $webService */
            $webService = m::mock(WebControllerService::class);
            $webService->shouldReceive('getRedirectResponse')
                ->with(self::SETTINGS_TAB_ROUTE, [], ['active' => 'online-bookkeeping'])
                ->andReturn(new Psr7Response(302, ['Location' => '/en/setting/tab_index?active=online-bookkeeping']));
        }
        if ($flash === null) {
            /** @var Flash&m\MockInterface $flash */
            $flash = m::mock(Flash::class);
            $flash->shouldReceive('has')->andReturn(false);
            $flash->shouldReceive('add')->byDefault();
        }

        /** @var UrlGenerator&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGenerator::class);
        $urlGenerator->shouldReceive('generateAbsolute')
            ->with('bookkeeping/quickbooksCallback')
            ->andReturn('https://invoice.myhost/en/bookkeeping/quickbooksCallback');

        /** @var ResponseFactoryInterface&m\MockInterface $responseFactory */
        $responseFactory = m::mock(ResponseFactoryInterface::class);
        $responseFactory->shouldReceive('createResponse')->with(302)->andReturn(new Psr7Response(302));

        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn(true);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn(true);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn(string $key): string => $key);

        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();

        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);

        return new QuickBooksConnectController(
            $intuit,
            $urlGenerator,
            $responseFactory,
            $webService,
            $userService,
            $translator,
            $webViewRenderer,
            $session,
            $settings,
            $flash,
        );
    }

    public function connectFlashesAWarningAndRedirectsWhenNotConfigured(): void
    {
        $settings = $this->makeSettingRepository('', '');

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add')->once()->with('warning', 'bookkeeping.quickbooks.not.configured', true);

        $controller = $this->makeController(settings: $settings, flash: $flash);

        $response = $controller->connect($this->makeRequest());

        Assert::same(302, $response->getStatusCode());
        Assert::same('/en/setting/tab_index?active=online-bookkeeping', $response->getHeaderLine('Location'));
    }

    public function connectRedirectsToTheIntuitAuthorizeUrlWhenConfigured(): void
    {
        /** @var Intuit&m\MockInterface $intuit */
        $intuit = m::mock(Intuit::class);
        $intuit->shouldReceive('setClientId')->once()->with('test-client-id');
        $intuit->shouldReceive('setClientSecret')->once()->with('test-secret');
        $intuit->shouldReceive('setOauth2ReturnUrl')->once()->with('https://invoice.myhost/en/bookkeeping/quickbooksCallback');
        $intuit->shouldReceive('buildAuthUrl')->once()->andReturn('https://appcenter.intuit.com/connect/oauth2?client_id=test-client-id');

        $controller = $this->makeController(intuit: $intuit);

        $response = $controller->connect($this->makeRequest());

        Assert::same(302, $response->getStatusCode());
        Assert::same('https://appcenter.intuit.com/connect/oauth2?client_id=test-client-id', $response->getHeaderLine('Location'));
    }

    public function callbackFlashesCancelledAndRedirectsWhenErrorIsPresent(): void
    {
        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add')->once()->with('danger', 'bookkeeping.quickbooks.connect.cancelled', true);

        $controller = $this->makeController(flash: $flash);

        $response = $controller->callback($this->makeRequest(['error' => 'access_denied']));

        Assert::same(302, $response->getStatusCode());
    }

    public function callbackFlashesCancelledAndRedirectsWhenCodeIsMissing(): void
    {
        $controller = $this->makeController();

        $response = $controller->callback($this->makeRequest(['realmId' => self::REALM_ID]));

        Assert::same(302, $response->getStatusCode());
    }

    public function callbackFlashesCancelledAndRedirectsWhenRealmIdIsMissing(): void
    {
        $controller = $this->makeController();

        $response = $controller->callback($this->makeRequest(['code' => 'abc123']));

        Assert::same(302, $response->getStatusCode());
    }

    public function callbackFlashesNotConfiguredWhenCredentialsAreMissing(): void
    {
        $settings = $this->makeSettingRepository('', '');

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add')->once()->with('warning', 'bookkeeping.quickbooks.not.configured', true);

        $controller = $this->makeController(settings: $settings, flash: $flash);

        $response = $controller->callback($this->makeRequest(['code' => 'abc123', 'realmId' => self::REALM_ID]));

        Assert::same(302, $response->getStatusCode());
    }

    public function callbackFlashesInvalidStateWhenFetchAccessTokenThrows(): void
    {
        /** @var Intuit&m\MockInterface $intuit */
        $intuit = m::mock(Intuit::class);
        $intuit->shouldReceive('setClientId')->once();
        $intuit->shouldReceive('setClientSecret')->once();
        $intuit->shouldReceive('setOauth2ReturnUrl')->once();
        $intuit->shouldReceive('fetchAccessToken')->once()->andThrow(new InvalidArgumentException('Invalid auth state parameter.'));

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add')->once()->with('danger', 'bookkeeping.quickbooks.connect.invalid.state', true);

        $controller = $this->makeController(intuit: $intuit, flash: $flash);

        $response = $controller->callback($this->makeRequest(['code' => 'abc123', 'realmId' => self::REALM_ID]));

        Assert::same(302, $response->getStatusCode());
    }

    public function callbackFlashesUnexpectedResponseWhenTheTokenIsMissingFields(): void
    {
        $incompleteToken = new OAuthToken();
        $incompleteToken->setParam('token_type', 'bearer');

        /** @var Intuit&m\MockInterface $intuit */
        $intuit = m::mock(Intuit::class);
        $intuit->shouldReceive('setClientId')->once();
        $intuit->shouldReceive('setClientSecret')->once();
        $intuit->shouldReceive('setOauth2ReturnUrl')->once();
        $intuit->shouldReceive('fetchAccessToken')->once()->andReturn($incompleteToken);

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add')->once()->with('danger', 'bookkeeping.quickbooks.connect.unexpected.response', true);

        $controller = $this->makeController(intuit: $intuit, flash: $flash);

        $response = $controller->callback($this->makeRequest(['code' => 'abc123', 'realmId' => self::REALM_ID]));

        Assert::same(302, $response->getStatusCode());
    }

    public function callbackPersistsSettingsAndFlashesSuccessOnTheHappyPath(): void
    {
        $token = new OAuthToken();
        $token->setParam('access_token', 'new-access-token');
        $token->setParam('refresh_token', 'new-refresh-token');
        $token->setParam('expires_in', 3600);

        /** @var Intuit&m\MockInterface $intuit */
        $intuit = m::mock(Intuit::class);
        $intuit->shouldReceive('setClientId')->once()->with('test-client-id');
        $intuit->shouldReceive('setClientSecret')->once()->with('test-secret');
        $intuit->shouldReceive('setOauth2ReturnUrl')->once();
        $intuit->shouldReceive('fetchAccessToken')->once()->andReturn($token);

        $settings = $this->makeSettingRepository();
        /** @var Setting&m\MockInterface $storedSetting */
        $storedSetting = m::mock(Setting::class);
        $storedSetting->shouldReceive('setSettingValue')->times(4);
        $settings->shouldReceive('withKey')->times(4)->andReturn($storedSetting);
        $settings->shouldReceive('save')->times(4)->with($storedSetting);

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add')->once()->with('info', 'bookkeeping.quickbooks.connect.success', true);

        $controller = $this->makeController(intuit: $intuit, settings: $settings, flash: $flash);

        $response = $controller->callback($this->makeRequest(['code' => 'abc123', 'realmId' => '999888777']));

        Assert::same(302, $response->getStatusCode());
    }
}
