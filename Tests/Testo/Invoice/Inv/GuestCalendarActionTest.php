<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Infrastructure\Persistence\Worker\Worker;
use App\Invoice\CategorySecondary\CategorySecondaryRepository as CSR;
use App\Invoice\Inv\InvController;
use App\Invoice\Inv\InvGuestDeps;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\Invoice\Worker\WorkerRepository as WR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvRecurring\InvRecurringRepository as IRR;
use App\Invoice\PaymentInformation\Service\BacsPaymentService;
use App\User\UserService;
use Mockery as m;
use Psr\Http\Message\ResponseInterface as Response;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;
use App\Service\WebControllerService;

/**
 * Covers Trait\GuestCalendar::guestCalendar() -- the worker-vs-client
 * scoping this route exists for. Reflection technique matches
 * CalendarActionTest.php/HomeCareRunContextResolutionTest.php exactly;
 * InvController already composes Guest (resolveGuestAccess()) and
 * GuestCalendar together, so no dedicated harness subclass is needed the
 * way GuestOfflineTest.php's was (that test predates GuestCalendar and
 * targets Guest.php's own offline actions, on a lighter-weight harness
 * rather than the full InvController).
 */
#[Test]
final class GuestCalendarActionTest
{
    private function emptyEntityReader(): EntityReader&m\MockInterface
    {
        /** @var EntityReader&m\MockInterface $entityReader */
        $entityReader = m::mock(EntityReader::class);
        $entityReader->shouldReceive('getIterator')->andReturn((static function (): \Generator {
            yield from [];
        })());
        return $entityReader;
    }

    /**
     * @return array{0: InvController, 1: UserService&m\MockInterface, 2: WebControllerService&m\MockInterface, 3: WebViewRenderer&m\MockInterface, 4: SettingRepository&m\MockInterface}
     */
    private function makeController(): array
    {
        $reflectionClass = new ReflectionClass(InvController::class);
        $controller = $reflectionClass->newInstanceWithoutConstructor();

        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('renderPartialAsString')->andReturn('');
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->andReturn('primary');
        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add');
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturn('translated');

        $reflectionClass->getProperty('userService')->setValue($controller, $userService);
        $reflectionClass->getProperty('webService')->setValue($controller, $webService);
        $reflectionClass->getProperty('webViewRenderer')->setValue($controller, $webViewRenderer);
        $reflectionClass->getProperty('sR')->setValue($controller, $sR);
        $reflectionClass->getProperty('flash')->setValue($controller, $flash);
        $reflectionClass->getProperty('translator')->setValue($controller, $translator);

        return [$controller, $userService, $webService, $webViewRenderer, $sR];
    }

    private function makeDeps(?UserInv $userInv, ?Worker $worker, IR&m\MockInterface $iR): InvGuestDeps
    {
        /** @var UIR&m\MockInterface $uiR */
        $uiR = m::mock(UIR::class);
        $uiR->shouldReceive('repoUserInvUserIdcount')->andReturn(null !== $userInv ? 1 : 0);
        $uiR->shouldReceive('repoUserInvUserIdquery')->andReturn($userInv);

        /** @var WR&m\MockInterface $wR */
        $wR = m::mock(WR::class);
        $wR->shouldReceive('findByUserId')->andReturn($worker);

        /** @var UCR&m\MockInterface $ucR */
        $ucR = m::mock(UCR::class);
        $ucR->shouldReceive('getAssignedToUser')->andReturn(null === $worker ? [12] : []);

        /** @var IAR&m\MockInterface $iaR */
        $iaR = m::mock(IAR::class);
        /** @var IRR&m\MockInterface $irR */
        $irR = m::mock(IRR::class);
        /** @var BacsPaymentService&m\MockInterface $bacsPaymentService */
        $bacsPaymentService = m::mock(BacsPaymentService::class);

        return new InvGuestDeps($iaR, $irR, $iR, $ucR, $uiR, $bacsPaymentService, $wR);
    }

    private function makeUser(int $id): User&m\MockInterface
    {
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $user->shouldReceive('reqId')->andReturn($id);
        return $user;
    }

    private function makeUserInv(): UserInv&m\MockInterface
    {
        /** @var UserInv&m\MockInterface $userInv */
        $userInv = m::mock(UserInv::class);
        $userInv->shouldReceive('getActive')->andReturn(true);
        $userInv->shouldReceive('getListLimit')->andReturn(10);
        return $userInv;
    }

    public function returns404WhenNotLoggedIn(): void
    {
        [$controller, $userService, $webService] = $this->makeController();
        $userService->shouldReceive('getUser')->andReturn(null);
        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        $webService->shouldReceive('getNotFoundResponse')->andReturn($notFound);

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        /** @var CSR&m\MockInterface $csR */
        $csR = m::mock(CSR::class);
        $result = $controller->guestCalendar($this->makeDeps(null, null, $iR), $csR);

        Assert::same($notFound, $result);
    }

    public function redirectsToTheStorefrontWhenAccessCannotBeResolved(): void
    {
        [$controller, $userService, $webService] = $this->makeController();
        $userService->shouldReceive('getUser')->andReturn($this->makeUser(7));
        /** @var Response&m\MockInterface $redirect */
        $redirect = m::mock(Response::class);
        $webService->shouldReceive('getRedirectResponse')->with('shop/catalog/index')->andReturn($redirect);

        // No UserInv at all -- resolveGuestAccess() returns null (same
        // not-eligible path GuestOfflineTest.php already covers for the
        // sibling offline actions).
        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        /** @var CSR&m\MockInterface $csR */
        $csR = m::mock(CSR::class);
        $result = $controller->guestCalendar($this->makeDeps(null, null, $iR), $csR);

        Assert::same($redirect, $result);
    }

    public function aWorkerGetsWorkerScopedInvoicesAcrossTheWindow(): void
    {
        [$controller, $userService, , $webViewRenderer] = $this->makeController();
        $userService->shouldReceive('getUser')->andReturn($this->makeUser(9));

        /** @var Worker&m\MockInterface $worker */
        $worker = m::mock(Worker::class);
        $worker->shouldReceive('reqId')->andReturn(55);

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoWorkerDateRangeQuery')
            ->withArgs(function (\DateTimeImmutable $from, \DateTimeImmutable $to, int $workerId): bool {
                return $workerId === 55 && $from < $to;
            })
            ->once()
            ->andReturn($this->emptyEntityReader());
        $iR->shouldReceive('repoGuestClientsDateRangeQuery')->never();

        /** @var Response&m\MockInterface $response */
        $response = m::mock(Response::class);
        /** @var array<string, mixed>|null $captured */
        $captured = null;
        $webViewRenderer->shouldReceive('render')
            ->withArgs(function (string $view, array $parameters) use (&$captured): bool {
                $captured = $parameters;
                return $view === 'guest_calendar';
            })
            ->andReturn($response);

        /** @var CSR&m\MockInterface $csR */
        $csR = m::mock(CSR::class);
        $csR->shouldReceive('optionsDataCategorySecondaries')->andReturn([]);

        $result = $controller->guestCalendar(
            $this->makeDeps($this->makeUserInv(), $worker, $iR),
            $csR,
            '2026',
            '9',
        );

        Assert::same($response, $result);
        Assert::notNull($captured);
        /** @psalm-var array{months: list<mixed>} $captured */
        Assert::same(7, count($captured['months']));
    }

    public function anOrdinaryClientGuestGetsClientScopedInvoices(): void
    {
        [$controller, $userService, , $webViewRenderer] = $this->makeController();
        $userService->shouldReceive('getUser')->andReturn($this->makeUser(9));

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoGuestClientsDateRangeQuery')
            ->withArgs(function (\DateTimeImmutable $from, \DateTimeImmutable $to, array $clientIds): bool {
                return $clientIds === [12] && $from < $to;
            })
            ->once()
            ->andReturn($this->emptyEntityReader());
        $iR->shouldReceive('repoWorkerDateRangeQuery')->never();

        /** @var Response&m\MockInterface $response */
        $response = m::mock(Response::class);
        $webViewRenderer->shouldReceive('render')->andReturn($response);

        /** @var CSR&m\MockInterface $csR */
        $csR = m::mock(CSR::class);
        $csR->shouldReceive('optionsDataCategorySecondaries')->andReturn([]);

        // worker=null -- resolveGuestAccess() falls back to the ordinary
        // client-assignment gate, scoped by the UserClient ids makeDeps()
        // stubs to [12] on this branch.
        $result = $controller->guestCalendar(
            $this->makeDeps($this->makeUserInv(), null, $iR),
            $csR,
        );

        Assert::same($response, $result);
    }
}
