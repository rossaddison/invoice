<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\InvRecurring;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvRecurring\InvRecurring;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Inv\InvService as IS;
use App\Invoice\InvAmount\InvAmountService;
use App\Invoice\InvCustom\InvCustomService;
use App\Invoice\InvRecurring\InvRecurringController;
use App\Invoice\InvRecurring\InvRecurringService;
use App\Invoice\InvTaxRate\InvTaxRateService;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use Mockery as m;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Testo\Assert;
use Testo\Test;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Covers InvRecurringController::add() -- specifically the fix for a real
 * bug found while auditing InvService: this action's own $form is a bare
 * `new InvRecurringForm()`, never InvRecurringForm::show()'d with the
 * $inv_id this method already resolved and validated at its own top (via
 * the route argument) -- unlike start()'s equivalent flow, which does call
 * show(). _form.php's hidden inv_id field therefore always submitted
 * empty, so InvRecurringService::saveInvRecurring() could never resolve a
 * real base invoice and silently never saved the new schedule, while this
 * action still redirected to the index as if it had. Fixed by writing the
 * already-validated $inv_id directly onto $body before calling
 * saveInvRecurring(), regardless of what (if anything) the client
 * actually submitted for that key.
 *
 * makeController() returns $sR itself as part of the tuple rather than
 * accepting it as a parameter, matching SettingToggleControllerTest's own
 * documented reason: a `Type&m\MockInterface` parameter type triggers
 * Psalm's full-project-scope-only artifact (see that file's own docblock).
 */
#[Test]
final class InvRecurringControllerTest
{
    /**
     * @return array{
     *     0: InvRecurringController,
     *     1: InvRecurringService&m\MockInterface,
     *     2: WebControllerService&m\MockInterface,
     *     3: TranslatorInterface&m\MockInterface
     * }
     */
    private function makeController(): array
    {
        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn(true);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn(true);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);

        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();

        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);

        // flashMessage()'s own has()/add() pair -- no ->with() constraint,
        // so any level/message this test class happens to trigger is
        // accepted; only the redirect targets and saveInvRecurring() calls
        // below are what each test actually asserts on.
        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add');

        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);

        /** @var InvRecurringService&m\MockInterface $invrecurringService */
        $invrecurringService = m::mock(InvRecurringService::class);

        // Never exercised by add() -- bare mocks, but each still needs its
        // own @var-annotated variable rather than an inline m::mock(...)
        // call: Psalm can only infer the X&MockInterface intersection type
        // from that annotation, not from a bare call passed straight as a
        // constructor argument (it falls back to plain MockInterface,
        // which then fails the real constructor's own parameter types).
        /** @var DataResponseFactoryInterface&m\MockInterface $factory */
        $factory = m::mock(DataResponseFactoryInterface::class);
        /** @var InvCustomService&m\MockInterface $invCustomService */
        $invCustomService = m::mock(InvCustomService::class);
        /** @var InvAmountService&m\MockInterface $invAmountService */
        $invAmountService = m::mock(InvAmountService::class);
        /** @var InvTaxRateService&m\MockInterface $invTaxRateService */
        $invTaxRateService = m::mock(InvTaxRateService::class);
        /** @var IS&m\MockInterface $iS */
        $iS = m::mock(IS::class);
        /** @var MailerInterface&m\MockInterface $mailer */
        $mailer = m::mock(MailerInterface::class);

        $controller = new InvRecurringController(
            $factory,
            $invCustomService,
            $invAmountService,
            $invrecurringService,
            $invTaxRateService,
            $iS,
            $mailer,
            $session,
            $sR,
            $translator,
            $userService,
            $webViewRenderer,
            $webService,
            $flash,
        );

        return [$controller, $invrecurringService, $webService, $translator];
    }

    private function makeBaseInvoice(int $statusId): Inv
    {
        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        $inv->shouldReceive('reqStatusId')->andReturn($statusId);
        $inv->shouldReceive('getDateCreated')->andReturn(new \DateTimeImmutable('2026-01-01'));
        return $inv;
    }

    private function makeCurrentRoute(string $invId): CurrentRoute
    {
        /** @var CurrentRoute&m\MockInterface $currentRoute */
        $currentRoute = m::mock(CurrentRoute::class);
        $currentRoute->shouldReceive('getArgument')->with('inv_id')->andReturn($invId);
        return $currentRoute;
    }

    /** @param array<string, mixed> $parsedBody */
    private function makePostRequest(array $parsedBody): Request
    {
        /** @var Request&m\MockInterface $request */
        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn(Method::POST);
        $request->shouldReceive('getParsedBody')->andReturn($parsedBody);
        return $request;
    }

    private function makeFormHydrator(bool $validates): FormHydrator
    {
        /** @var FormHydrator&m\MockInterface $formHydrator */
        $formHydrator = m::mock(FormHydrator::class);
        $formHydrator->shouldReceive('populateFromPostAndValidate')->andReturn($validates);
        return $formHydrator;
    }

    public function addOverridesTheSubmittedBodysInvIdWithTheRouteResolvedOne(): void
    {
        [$controller, $invrecurringService, $webService, $translator] = $this->makeController();
        $translator->shouldReceive('translate')->with('add')->andReturn('Add');

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoInvUnloadedquery')->with(42)->andReturn($this->makeBaseInvoice(2));

        /** @var Response&m\MockInterface $redirect */
        $redirect = m::mock(Response::class);
        $webService->shouldReceive('getRedirectResponse')->once()
            ->with('invrecurring/index')->andReturn($redirect);

        // The exact bug: a submitted body that never carries inv_id at
        // all -- exactly what _form.php's hidden field produced before
        // the fix, since the form was never InvRecurringForm::show()'n.
        $invrecurringService->shouldReceive('saveInvRecurring')->once()->with(
            m::type(InvRecurring::class),
            m::on(static fn (array $body): bool => ($body['inv_id'] ?? null) === 42),
        );

        $result = $controller->add(
            $this->makePostRequest(['frequency' => '1M']),
            $this->makeCurrentRoute('42'),
            $this->makeFormHydrator(true),
            $iR,
        );

        Assert::same($redirect, $result);
    }

    public function addOverridesAMismatchedInvIdSubmittedByTheClientToo(): void
    {
        // Not just "absent" -- a tampered/stale hidden field carrying a
        // DIFFERENT invoice's id must not redirect this schedule onto it.
        [$controller, $invrecurringService, $webService, $translator] = $this->makeController();
        $translator->shouldReceive('translate')->with('add')->andReturn('Add');

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoInvUnloadedquery')->with(42)->andReturn($this->makeBaseInvoice(2));

        /** @var Response&m\MockInterface $redirect */
        $redirect = m::mock(Response::class);
        $webService->shouldReceive('getRedirectResponse')->once()
            ->with('invrecurring/index')->andReturn($redirect);

        $invrecurringService->shouldReceive('saveInvRecurring')->once()->with(
            m::type(InvRecurring::class),
            m::on(static fn (array $body): bool => ($body['inv_id'] ?? null) === 42),
        );

        $result = $controller->add(
            $this->makePostRequest(['frequency' => '1M', 'inv_id' => 999]),
            $this->makeCurrentRoute('42'),
            $this->makeFormHydrator(true),
            $iR,
        );

        Assert::same($redirect, $result);
    }

    public function addReturnsNotFoundWhenTheRouteInvoiceDoesNotExist(): void
    {
        [$controller, $invrecurringService, $webService] = $this->makeController();

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoInvUnloadedquery')->with(42)->andReturn(null);

        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        $webService->shouldReceive('getNotFoundResponse')->once()->andReturn($notFound);

        $invrecurringService->shouldNotReceive('saveInvRecurring');

        $result = $controller->add(
            $this->makePostRequest(['frequency' => '1M']),
            $this->makeCurrentRoute('42'),
            $this->makeFormHydrator(true),
            $iR,
        );

        Assert::same($notFound, $result);
    }

    public function addRedirectsWithAFlashWhenTheRouteInvoiceIsNotSentYet(): void
    {
        [$controller, $invrecurringService, $webService, $translator] = $this->makeController();
        $translator->shouldReceive('translate')->with('recurring.status.sent.only')->andReturn('Only sent invoices.');

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoInvUnloadedquery')->with(42)->andReturn($this->makeBaseInvoice(1));

        /** @var Response&m\MockInterface $redirect */
        $redirect = m::mock(Response::class);
        $webService->shouldReceive('getRedirectResponse')->once()
            ->with('inv/view', ['id' => 42])->andReturn($redirect);

        $invrecurringService->shouldNotReceive('saveInvRecurring');

        $result = $controller->add(
            $this->makePostRequest(['frequency' => '1M']),
            $this->makeCurrentRoute('42'),
            $this->makeFormHydrator(true),
            $iR,
        );

        Assert::same($redirect, $result);
    }
}
