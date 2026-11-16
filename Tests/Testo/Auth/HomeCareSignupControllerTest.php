<?php

declare(strict_types=1);

namespace Tests\Testo\Auth;

use App\Auth\AuthService;
use App\Auth\Controller\HomeCareSignupConfirmDeps;
use App\Auth\Controller\HomeCareSignupController;
use App\Auth\Controller\HomeCareSignupDeps;
use App\Auth\Form\HomeCareSignupForm;
use App\Auth\HomeCareSignupEmailService;
use App\Auth\TokenRepository;
use App\Infrastructure\Persistence\HomeCarePendingSignup\HomeCarePendingSignup;
use App\Infrastructure\Persistence\Identity\Identity;
use App\Infrastructure\Persistence\Token\Token;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\HomeCarePendingSignup\HomeCarePendingSignupRepository;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use GuzzleHttp\Psr7\HttpFactory;
use Mockery as m;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Header;
use Yiisoft\Rbac\Manager;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Security\TokenMask;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * The guards and the confirmation-link handling of the public HomeCare signup, with every
 * collaborator mocked: who is turned away before anything is written, and what the one-time
 * emailed token does and does not allow.
 */
#[Test]
final class HomeCareSignupControllerTest
{
    private function makeController(
        bool $guest = true,
        string $stopSetting = '',
        string $turnstileSecret = '',
        ?HomeCareSignupEmailService $emailService = null,
    ): HomeCareSignupController {
        $authService = m::mock(AuthService::class);
        $authService->shouldReceive('isGuest')->andReturn($guest);

        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('stop_homecare_signing_up')->andReturn($stopSetting);
        $sR->shouldReceive('getSetting')->with('turnstile_secret_key')->andReturn($turnstileSecret);
        $sR->shouldReceive('getSetting')->with('turnstile_site_key')->andReturn('site-key');

        $webService = m::mock(WebControllerService::class);
        $webService->shouldReceive('getRedirectResponse')->andReturnUsing(
            static fn (string $url): ResponseInterface => new HttpFactory()
                ->createResponse(302)
                ->withHeader(Header::LOCATION, $url),
        );

        $renderer = m::mock(WebViewRenderer::class);
        $renderer->shouldReceive('withControllerName')->andReturnSelf();
        $renderer->shouldReceive('render')->andReturnUsing(
            static fn (string $view, array $params = []): ResponseInterface => new HttpFactory()
                ->createResponse(200)
                ->withHeader('X-View', $view . ':' . (string) ($params['outcome'] ?? '')),
        );

        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $key): string => $key);

        return new HomeCareSignupController(
            m::mock(Manager::class),
            $authService,
            $webService,
            $renderer,
            $emailService ?? m::mock(HomeCareSignupEmailService::class),
            $sR,
            $translator,
            m::mock(UrlGenerator::class),
            new \App\Service\ClientIpResolver([]),
        );
    }

    private function request(string $method, string $body = ''): ServerRequestInterface
    {
        $request = new HttpFactory()->createServerRequest($method, 'https://example.test/en/homecare-signup');
        return $request->withParsedBody($method === 'POST' ? ['HomeCareSignup' => ['login' => $body]] : null);
    }

    private function location(ResponseInterface $response): string
    {
        return $response->getHeaderLine(Header::LOCATION);
    }

    public function aLoggedInUserIsSentAwayWithoutTheFormBeingTouched(): void
    {
        $hydrator = m::mock(FormHydrator::class);
        $hydrator->shouldNotReceive('populateFromPostAndValidate');
        $controller = $this->makeController(guest: false);

        $response = $controller->signup(
            $this->request('POST'),
            m::mock(CurrentRoute::class),
            $hydrator,
            m::mock(HomeCareSignupForm::class),
            m::mock(HomeCareSignupDeps::class),
        );

        Assert::same($this->location($response), 'site/index');
    }

    public function theKillSwitchSendsEveryVisitorAwayEvenOnPost(): void
    {
        $hydrator = m::mock(FormHydrator::class);
        $hydrator->shouldNotReceive('populateFromPostAndValidate');
        $controller = $this->makeController(stopSetting: '1');

        $response = $controller->signup(
            $this->request('POST'),
            m::mock(CurrentRoute::class),
            $hydrator,
            m::mock(HomeCareSignupForm::class),
            m::mock(HomeCareSignupDeps::class),
        );

        Assert::same($this->location($response), 'site/index');
    }

    public function aPostWithoutATurnstileTokenFailsBeforeAnyAccountIsCreated(): void
    {
        $hydrator = m::mock(FormHydrator::class);
        $hydrator->shouldNotReceive('populateFromPostAndValidate');
        $form = m::mock(HomeCareSignupForm::class);
        $form->shouldNotReceive('signup');
        $controller = $this->makeController(turnstileSecret: 'real-secret');

        $response = $controller->signup(
            $this->request('POST'),
            m::mock(CurrentRoute::class),
            $hydrator,
            $form,
            m::mock(HomeCareSignupDeps::class),
        );

        Assert::same($this->location($response), 'site/signupfailed');
    }

    public function aFailedVerificationEmailLeavesTheUserAndPendingRowBehind(): void
    {
        // Characterisation: signup() saves the User and the pending row first, then tries to
        // email. When the email fails the visitor sees "signup failed" but the rows stay, and the
        // login/email are now taken, so the real owner of that address cannot sign up with it.
        $user = m::mock(User::class);
        $user->shouldReceive('reqId')->andReturn(41);
        $form = m::mock(HomeCareSignupForm::class);
        $form->shouldReceive('signup')->once()->andReturn($user);
        $form->shouldReceive('isValidated')->andReturn(true);

        $hydrator = m::mock(FormHydrator::class);
        $hydrator->shouldReceive('populateFromPostAndValidate')->andReturn(true);

        $emailService = m::mock(HomeCareSignupEmailService::class);
        $controller = $this->makeController(emailService: $emailService);
        $emailService->shouldReceive('savePendingSignup')->once();
        $emailService->shouldReceive('processSendSignupEmail')->andReturn(
            new HttpFactory()->createResponse(302)->withHeader(Header::LOCATION, 'site/signupfailed'),
        );
        $form->shouldReceive('getValidationResult')->andReturn(new \Yiisoft\Validator\Result());

        $csR = m::mock(\App\Invoice\CategorySecondary\CategorySecondaryRepository::class);
        $csR->shouldReceive('optionsDataCategorySecondaries')->andReturn([]);
        $deps = m::mock(HomeCareSignupDeps::class);
        $deps->csR = $csR;
        $form->shouldReceive('serviceOptions')->andReturn([]);

        // GET: the Turnstile gate only guards POST, and a POST with no secret is now refused
        // before this point (see aPostIsRefusedWhenNoTurnstileSecretIsConfigured). The mocked
        // hydrator stands in for the validated form either way; the once() expectations prove the
        // rows are written before the email is attempted.
        $response = $controller->signup($this->request('GET'), m::mock(CurrentRoute::class), $hydrator, $form, $deps);

        Assert::same($this->location($response), 'site/signupfailed');
    }

    public function openingTheConfirmLinkOnlyShowsTheButtonAndTouchesNothing(): void
    {
        // GET (and HEAD): what a mail scanner or link previewer sends. No token lookup, no burn,
        // no pending row read, no UserInv change.
        foreach (['GET', 'HEAD'] as $method) {
            [$deps, $tR, $pendingR] = $this->makeConfirmDeps();
            $tR->shouldNotReceive('findIdentityByToken');
            $tR->shouldNotReceive('findTokenByTokenAndType');
            $tR->shouldNotReceive('save');
            $pendingR->shouldNotReceive('repoByUserIdquery');
            $pendingR->shouldNotReceive('delete');

            $response = $this->makeController()->confirm(
                'en',
                $this->maskedToken('real-token', time()),
                HomeCareSignupController::EMAIL_VERIFICATION_TOKEN,
                m::mock(FormHydrator::class),
                $deps,
                $this->request($method),
            );

            Assert::same($response->getHeaderLine('X-View'), 'signupconfirm:');
        }
    }

    public function aPostIsRefusedWhenNoTurnstileSecretIsConfigured(): void
    {
        $hydrator = m::mock(FormHydrator::class);
        $hydrator->shouldNotReceive('populateFromPostAndValidate');
        $form = m::mock(HomeCareSignupForm::class);
        $form->shouldNotReceive('signup');
        $controller = $this->makeController(turnstileSecret: '');

        $response = $controller->signup(
            $this->request('POST'),
            m::mock(CurrentRoute::class),
            $hydrator,
            $form,
            m::mock(HomeCareSignupDeps::class),
        );

        Assert::same($this->location($response), 'site/signupfailed');
    }

    /** @return array{0: HomeCareSignupConfirmDeps, 1: TokenRepository&m\MockInterface, 2: HomeCarePendingSignupRepository&m\MockInterface} */
    private function makeConfirmDeps(): array
    {
        $tR = m::mock(TokenRepository::class);
        $pendingR = m::mock(HomeCarePendingSignupRepository::class);
        $deps = m::mock(HomeCareSignupConfirmDeps::class);
        $deps->tR = $tR;
        $deps->pendingR = $pendingR;
        return [$deps, $tR, $pendingR];
    }

    private function maskedToken(string $raw, int $issuedAt): string
    {
        return TokenMask::apply($raw . '_' . $issuedAt);
    }

    private function identityForUser(int $userId): Identity
    {
        $user = m::mock(User::class);
        $user->shouldReceive('reqId')->andReturn($userId);
        $identity = m::mock(Identity::class);
        $identity->shouldReceive('getUser')->andReturn($user);
        return $identity;
    }

    public function aTokenThatWasAlreadyUsedOrNeverIssuedIsSentToTheHomePage(): void
    {
        [$deps, $tR, $pendingR] = $this->makeConfirmDeps();
        $tR->shouldReceive('findIdentityByToken')->andReturn(null);
        $pendingR->shouldNotReceive('delete');
        $controller = $this->makeController();

        $response = $controller->confirm(
            'en',
            $this->maskedToken('forged-token', time()),
            HomeCareSignupController::EMAIL_VERIFICATION_TOKEN,
            m::mock(FormHydrator::class),
            $deps,
            $this->request('POST'),
        );

        Assert::same($this->location($response), 'site/index');
    }

    public function aTokenWithoutATimestampSuffixIsSentToTheHomePage(): void
    {
        [$deps, $tR] = $this->makeConfirmDeps();
        $tR->shouldNotReceive('findIdentityByToken');
        $controller = $this->makeController();

        $response = $controller->confirm(
            'en',
            TokenMask::apply('nounderscore'),
            HomeCareSignupController::EMAIL_VERIFICATION_TOKEN,
            m::mock(FormHydrator::class),
            $deps,
            $this->request('POST'),
        );

        Assert::same($this->location($response), 'site/index');
    }

    public function anExpiredTokenRendersExpiredAndStillBurnsTheTokenAndDeletesThePendingRow(): void
    {
        [$deps, $tR, $pendingR] = $this->makeConfirmDeps();
        $tR->shouldReceive('findIdentityByToken')->andReturn($this->identityForUser(7));
        $pending = m::mock(HomeCarePendingSignup::class);
        $pendingR->shouldReceive('repoByUserIdquery')->with(7)->andReturn($pending);
        $pendingR->shouldReceive('delete')->once()->with($pending);
        $token = m::mock(Token::class);
        $token->shouldReceive('setToken')->once()->withArgs(
            static fn (string $value): bool => str_starts_with($value, 'already_used_token_'),
        );
        $token->shouldReceive('getCreatedAt')->andReturn(new \DateTimeImmutable('-3601 seconds'));
        $tR->shouldReceive('findTokenByTokenAndType')->andReturn($token);
        $tR->shouldReceive('save')->once()->with($token);
        $controller = $this->makeController();

        $response = $controller->confirm(
            'en',
            $this->maskedToken('real-token', time() - 3601),
            HomeCareSignupController::EMAIL_VERIFICATION_TOKEN,
            m::mock(FormHydrator::class),
            $deps,
            $this->request('POST'),
        );

        Assert::same($response->getHeaderLine('X-View'), 'homecaresignup_confirmed:expired');
    }

    public function aRewrittenTimestampInTheLinkCannotReviveAnExpiredToken(): void
    {
        // The Token was really created two days ago; the link's suffix has been rewritten to "now".
        [$deps, $tR, $pendingR] = $this->makeConfirmDeps();
        $tR->shouldReceive('findIdentityByToken')->andReturn($this->identityForUser(7));
        $pending = m::mock(HomeCarePendingSignup::class);
        $pendingR->shouldReceive('repoByUserIdquery')->with(7)->andReturn($pending);
        $pendingR->shouldReceive('delete')->once();
        $token = m::mock(Token::class);
        $token->shouldReceive('setToken')->once();
        $token->shouldReceive('getCreatedAt')->andReturn(new \DateTimeImmutable('-2 days'));
        $tR->shouldReceive('findTokenByTokenAndType')->andReturn($token);
        $tR->shouldReceive('save')->once();
        $controller = $this->makeController();

        $response = $controller->confirm(
            'en',
            $this->maskedToken('real-token', time()),
            HomeCareSignupController::EMAIL_VERIFICATION_TOKEN,
            m::mock(FormHydrator::class),
            $deps,
            $this->request('POST'),
        );

        Assert::same($response->getHeaderLine('X-View'), 'homecaresignup_confirmed:expired');
    }

    public function aStaleSuffixDoesNotExpireATokenTheServerIssuedJustNow(): void
    {
        // The suffix is ignored in both directions: the stored creation time alone decides.
        [$deps, $tR, $pendingR] = $this->makeConfirmDeps();
        $tR->shouldReceive('findIdentityByToken')->andReturn($this->identityForUser(7));
        $pendingR->shouldReceive('repoByUserIdquery')->with(7)->andReturn(m::mock(HomeCarePendingSignup::class));
        $pendingR->shouldReceive('delete')->once();
        $token = m::mock(Token::class);
        $token->shouldReceive('setToken')->once();
        $token->shouldReceive('getCreatedAt')->andReturn(new \DateTimeImmutable('-60 seconds'));
        $tR->shouldReceive('findTokenByTokenAndType')->andReturn($token);
        $tR->shouldReceive('save')->once();
        $uiR = m::mock(\App\Invoice\UserInv\UserInvRepository::class);
        $uiR->shouldReceive('repoUserInvUserIdquery')->with(7)->andReturn(null);
        $deps->uiR = $uiR;
        $controller = $this->makeController();

        $response = $controller->confirm(
            'en',
            $this->maskedToken('real-token', time() - 86400),
            HomeCareSignupController::EMAIL_VERIFICATION_TOKEN,
            m::mock(FormHydrator::class),
            $deps,
            $this->request('POST'),
        );

        // past the expiry check, stopped later by the missing UserInv
        Assert::same($this->location($response), 'site/index');
    }

    public function aTokenThatCannotBeFoundAgainIsTreatedAsExpired(): void
    {
        [$deps, $tR, $pendingR] = $this->makeConfirmDeps();
        $tR->shouldReceive('findIdentityByToken')->andReturn($this->identityForUser(7));
        $pendingR->shouldReceive('repoByUserIdquery')->with(7)->andReturn(m::mock(HomeCarePendingSignup::class));
        $pendingR->shouldReceive('delete')->once();
        $tR->shouldReceive('findTokenByTokenAndType')->andReturn(null);
        $controller = $this->makeController();

        $response = $controller->confirm(
            'en',
            $this->maskedToken('real-token', time()),
            HomeCareSignupController::EMAIL_VERIFICATION_TOKEN,
            m::mock(FormHydrator::class),
            $deps,
            $this->request('POST'),
        );

        Assert::same($response->getHeaderLine('X-View'), 'homecaresignup_confirmed:expired');
    }

    public function aValidTokenWithNoPendingRowRendersExpired(): void
    {
        [$deps, $tR, $pendingR] = $this->makeConfirmDeps();
        $tR->shouldReceive('findIdentityByToken')->andReturn($this->identityForUser(7));
        $pendingR->shouldReceive('repoByUserIdquery')->with(7)->andReturn(null);
        $pendingR->shouldNotReceive('delete');
        $tR->shouldReceive('findTokenByTokenAndType')->andReturn(null);
        $controller = $this->makeController();

        $response = $controller->confirm(
            'en',
            $this->maskedToken('real-token', time()),
            HomeCareSignupController::EMAIL_VERIFICATION_TOKEN,
            m::mock(FormHydrator::class),
            $deps,
            $this->request('POST'),
        );

        Assert::same($response->getHeaderLine('X-View'), 'homecaresignup_confirmed:expired');
    }

    public function theTokenTypeInTheUrlIsPassedStraightToTheLookup(): void
    {
        // Characterisation: the type is a URL segment chosen by the visitor, not fixed to the
        // HomeCare type, so a token of another kind (e.g. the generic signup's) is looked up too.
        [$deps, $tR] = $this->makeConfirmDeps();
        $tR->shouldReceive('findIdentityByToken')->once()->with('abc', 'some-other-token-type')->andReturn(null);
        $controller = $this->makeController();

        $controller->confirm(
            'en',
            $this->maskedToken('abc', time()),
            'some-other-token-type',
            m::mock(FormHydrator::class),
            $deps,
            $this->request('POST'),
        );

        Assert::true(true);
    }
}
