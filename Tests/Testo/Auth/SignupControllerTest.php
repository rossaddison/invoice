<?php

declare(strict_types=1);

namespace Tests\Testo\Auth;

use App\Auth\AuthService;
use App\Auth\Controller\SignupController;
use App\Auth\Controller\SignupDeps;
use App\Auth\Form\SignupForm;
use App\Auth\TokenRepository;
use App\Infrastructure\Persistence\Identity\Identity;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\AppConstants;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserInv\UserRbacLinkRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\Service\WebControllerService;
use App\User\UserRepository;
use GuzzleHttp\Psr7\HttpFactory;
use Mockery as m;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Header;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Mailer\MessageInterface;
use Yiisoft\Rbac\Manager;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * The generic /signup action: who is turned away, what happens before the email is confirmed.
 * Built without its constructor (which reads OAuth settings) and with only the collaborators the
 * tested path touches.
 */
#[Test]
final class SignupControllerTest
{
    /** @param array<string, object> $extra properties to set besides the setting repository and web service */
    private function makeController(string $stopSigningUp = '', string $turnstileSecret = '', array $extra = []): SignupController
    {
        $sR = TypedMock::of(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('stop_signing_up')->andReturn($stopSigningUp);
        $sR->shouldReceive('getSetting')->with('turnstile_secret_key')->andReturn($turnstileSecret);
        $sR->shouldReceive('getSetting')->andReturn('');

        $webService = TypedMock::of(WebControllerService::class);
        $webService->shouldReceive('getRedirectResponse')->andReturnUsing(
            static fn (string $url): ResponseInterface => new HttpFactory()
                ->createResponse(302)
                ->withHeader(Header::LOCATION, $url),
        );

        $reflection = new \ReflectionClass(SignupController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $properties = ['sR' => $sR, 'webService' => $webService, 'clientIp' => new \App\Service\ClientIpResolver([]), ...$extra];
        foreach ($properties as $property => $value) {
            $reflection->getProperty($property)->setValue($controller, $value);
        }
        return $controller;
    }

    private function authService(bool $guest): AuthService
    {
        $authService = TypedMock::of(AuthService::class);
        $authService->shouldReceive('isGuest')->andReturn($guest);
        return $authService;
    }

    private function post(): ServerRequestInterface
    {
        return new HttpFactory()
            ->createServerRequest('POST', 'https://example.test/en/signup')
            ->withParsedBody(['Signup' => ['login' => 'bot']]);
    }

    private function signup(SignupController $controller, bool $guest = true, ?FormHydrator $hydrator = null): ResponseInterface
    {
        if (null === $hydrator) {
            $hydrator = TypedMock::of(FormHydrator::class);
            $hydrator->shouldReceive('populateFromPostAndValidate')->never();
        }
        return $controller->signup(
            $this->authService($guest),
            TypedMock::of(CurrentRoute::class),
            $hydrator,
            $this->post(),
            TypedMock::of(SignupForm::class),
            TypedMock::build(SignupDeps::class),
        );
    }

    public function aLoggedInUserIsSentAway(): void
    {
        Assert::same($this->signup($this->makeController(), guest: false)->getHeaderLine(Header::LOCATION), 'site/index');
    }

    public function stopSigningUpClosesTheRouteItselfNotJustTheNavbarLink(): void
    {
        Assert::same($this->signup($this->makeController(stopSigningUp: '1'))->getHeaderLine(Header::LOCATION), 'site/index');
    }

    public function aPostWithoutATurnstileTokenFailsBeforeAnyAccountIsCreated(): void
    {
        $controller = $this->makeController(turnstileSecret: 'real-secret');
        // open_banking_provider is read first and is '' here, so no provider URL is built

        Assert::same($this->signup($controller)->getHeaderLine(Header::LOCATION), 'site/signupfailed');
    }

    public function aPostIsRefusedWhenNoTurnstileSecretIsConfigured(): void
    {
        // open_banking_provider is read first and is '' here, so no provider URL is built
        Assert::same(
            $this->signup($this->makeController(turnstileSecret: ''))->getHeaderLine(Header::LOCATION),
            'site/signupfailed',
        );
    }

    private function assignRoleAndVerify(SignupController $controller, int $userId, string $role): bool
    {
        return (bool) new \ReflectionMethod($controller, 'assignRoleAndVerify')->invoke($controller, $userId, $role);
    }

    public function theExtensionRowIsCreatedInactiveBeforeTheRoleIsAssigned(): void
    {
        // assignRoleAndVerify() links the user's extension row to RBAC, so the row has to exist by
        // then: it is created by signup() itself, not as a side effect of building the email.
        $user = TypedMock::of(User::class);
        $user->shouldReceive('reqId')->andReturn(42);
        $form = TypedMock::of(SignupForm::class);
        $form->shouldReceive('signup')->andReturn($user);
        $form->shouldReceive('getConsentPeriodicInvoice')->andReturn(true);
        $form->shouldReceive('getConsentTelegramOutstanding')->andReturn(false);
        $form->shouldReceive('isValidated')->andReturn(false);

        $uiR = TypedMock::of(UserInvRepository::class);
        $uiR->shouldReceive('createInactiveFor')->once()->globally()->ordered()->with($user, 'English', true, false);
        $manager = TypedMock::of(Manager::class);
        $manager->shouldReceive('revokeAll')->once()->globally()->ordered()->with('42');
        $manager->shouldReceive('assign')->once()->globally()->ordered();
        // no role persisted: assignRoleAndVerify() fails, so the flow stops before any email
        $manager->shouldReceive('getRolesByUserId')->andReturn([]);
        $uR = TypedMock::of(UserRepository::class);
        $uR->shouldReceive('repoCount')->andReturn(5);

        $sR = TypedMock::of(SettingRepository::class);
        $sR->shouldReceive('getSetting')->andReturn('');
        $sR->shouldReceive('localeLanguageArray')->andReturn(['en' => 'English']);
        $route = TypedMock::of(CurrentRoute::class);
        $route->shouldReceive('getArgument')->with('_language')->andReturn('en');
        $session = TypedMock::of(SessionInterface::class);
        $session->shouldReceive('set');
        $hydrator = TypedMock::of(FormHydrator::class);
        $hydrator->shouldReceive('populateFromPostAndValidate')->andReturn(true);

        $controller = $this->makeController(extra: [
            'sR' => $sR,
            'manager' => $manager,
            'uiR' => $uiR,
            'urlR' => TypedMock::of(UserRbacLinkRepository::class),
            'logger' => new NullLogger(),
            'session' => $session,
        ]);
        // GET: the Turnstile gate only guards POST, and the hydrator is mocked
        $request = new HttpFactory()->createServerRequest('GET', 'https://example.test/en/signup');

        $response = $controller->signup(
            $this->authService(true),
            $route,
            $hydrator,
            $request,
            $form,
            TypedMock::build(SignupDeps::class, ['uiR' => $uiR, 'uR' => $uR]),
        );

        Assert::same($response->getHeaderLine(Header::LOCATION), 'site/signupfailed');
    }

    public function theConfirmationEmailCarriesTheLinkInBothTheHtmlAndThePlainTextPart(): void
    {
        $confirmUrl = 'https://example.test/en/userinv/signup/English/MASKED-TOKEN/email-verification';
        $identity = TypedMock::of(Identity::class);
        $identity->shouldReceive('getId')->andReturn('5');
        $user = TypedMock::of(User::class);
        $user->shouldReceive('getEmail')->andReturn('new.user@example.test');
        $user->shouldReceive('getLogin')->andReturn('virtualtest');
        $user->shouldReceive('getIdentity')->andReturn($identity);
        $tR = TypedMock::of(TokenRepository::class);
        $tR->shouldReceive('save');

        $sR = TypedMock::of(SettingRepository::class);
        $sR->shouldReceive('getSetting')->andReturnUsing(
            static fn (string $key): string => $key === 'email_send_method' ? 'symfony' : '',
        );
        $sR->shouldReceive('getConfigAdminEmail')->andReturn('admin@example.test');
        $sR->shouldReceive('localeLanguageArray')->andReturn(['en' => 'English']);
        $translator = TypedMock::of(Translator::class);
        $translator->shouldReceive('translate')->andReturnUsing(
            static fn (string $key): string => $key === 'email.link.click.confirm' ? 'Please confirm' : $key,
        );
        $urlGenerator = TypedMock::of(UrlGenerator::class);
        $urlGenerator->shouldReceive('generateAbsolute')
            ->with('userinv/signup', m::on(static fn (array $arguments): bool => $arguments['tokenType'] === 'email-verification'
                && $arguments['language'] === 'English' && $arguments['_language'] === 'en'))
            ->andReturn($confirmUrl);
        $sent = [];
        $mailer = TypedMock::of(MailerInterface::class);
        $mailer->shouldReceive('send')->once()->andReturnUsing(function (MessageInterface $message) use (&$sent): void {
            $sent[] = $message;
        });
        $route = TypedMock::of(CurrentRoute::class);
        $route->shouldReceive('getArgument')->with('_language')->andReturn('en');

        $controller = $this->makeController(extra: [
            'sR' => $sR,
            'translator' => $translator,
            'urlGenerator' => $urlGenerator,
            'mailer' => $mailer,
        ]);
        $result = new \ReflectionMethod($controller, 'processSendSignupEmail')
            ->invoke($controller, $user, $route, TypedMock::build(SignupDeps::class, ['tR' => $tR]));

        Assert::null($result);
        Assert::same(count($sent), 1);
        Assert::true(str_contains((string) $sent[0]->getHtmlBody(), '<a href="' . $confirmUrl . '">Please confirm</a>'));
        Assert::true(str_contains((string) $sent[0]->getHtmlBody(), '<p>' . $confirmUrl . '</p>'));
        Assert::true(str_contains((string) $sent[0]->getTextBody(), $confirmUrl));
    }

    public function theRoleIsAssignedAtSignupBeforeTheEmailIsConfirmed(): void
    {
        // Characterisation: signup() assigns the RBAC role straight away; only userinv.active is
        // held back until the link is clicked. Anything that checks the role (not userinv.active)
        // already treats the unconfirmed account as an Observer.
        $manager = TypedMock::of(Manager::class);
        $manager->shouldReceive('revokeAll')->once()->with('42');
        $manager->shouldReceive('assign')->once()->with(AppConstants::ROLE_OBSERVER, '42');
        $manager->shouldReceive('getRolesByUserId')->with('42')->andReturn(['observer' => new \stdClass()]);
        $uiR = TypedMock::of(UserInvRepository::class);
        $uiR->shouldReceive('repoUserInvUserIdquery')->with(42)->andReturn(null);

        $controller = $this->makeController(extra: [
            'manager' => $manager,
            'uiR' => $uiR,
            'urlR' => TypedMock::of(UserRbacLinkRepository::class),
            'logger' => TypedMock::of(LoggerInterface::class)->shouldIgnoreMissing(),
        ]);

        Assert::true($this->assignRoleAndVerify($controller, 42, AppConstants::ROLE_OBSERVER));
    }
}
