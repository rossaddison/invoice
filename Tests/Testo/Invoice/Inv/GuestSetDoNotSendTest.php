<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Inv\WorkerInvAccess;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use Mockery as m;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Guest::setDoNotSend() is reachable with view.inv, which customer guests
 * hold too -- it must only act on an invoice allocated to the signed-in
 * worker. Uses GuestOfflineTest's harness, which mixes in the real Guest trait.
 */
#[Test]
final class GuestSetDoNotSendTest
{
    private function harness(
        ?User $user,
        WebControllerService&m\MockInterface $webService,
    ): GuestOfflineTestHarness {
        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('getUser')->andReturn($user);
        // BaseController::initializeViewRenderer() asks for both at construction.
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn(true);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn(false);

        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturn('translated');

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add');

        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        /** @var DataResponseFactoryInterface&m\MockInterface $factory */
        $factory = m::mock(DataResponseFactoryInterface::class);

        return new GuestOfflineTestHarness(
            $webService,
            $userService,
            $translator,
            $webViewRenderer,
            $session,
            $sR,
            $flash,
            $factory,
        );
    }

    private function user(int $id): User&m\MockInterface
    {
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $user->shouldReceive('reqId')->andReturn($id);
        return $user;
    }

    private function request(string $reason): ServerRequestInterface&m\MockInterface
    {
        /** @var ServerRequestInterface&m\MockInterface $request */
        $request = m::mock(ServerRequestInterface::class);
        $request->shouldReceive('getParsedBody')->andReturn(['reason' => $reason]);
        return $request;
    }

    /**
     * @return array{WebControllerService&m\MockInterface, Response&m\MockInterface, Response&m\MockInterface}
     */
    private function webService(): array
    {
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        /** @var Response&m\MockInterface $redirect */
        $redirect = m::mock(Response::class);
        $webService->shouldReceive('getNotFoundResponse')->andReturn($notFound);
        $webService->shouldReceive('getRedirectResponse')->with('inv/guest')->andReturn($redirect);
        return [$webService, $notFound, $redirect];
    }

    private function access(bool $allocated): WorkerInvAccess&m\MockInterface
    {
        /** @var WorkerInvAccess&m\MockInterface $access */
        $access = m::mock(WorkerInvAccess::class);
        $access->shouldReceive('isAllocated')->andReturn($allocated);
        return $access;
    }

    public function anInvoiceNotAllocatedToTheCallerIsLeftUntouchedAnd404s(): void
    {
        [$webService, $notFound] = $this->webService();
        $inv = new Inv();
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoInvUnLoadedquery')->with(12)->andReturn($inv);
        $iR->shouldNotReceive('save');

        $result = $this->harness($this->user(55), $webService)
            ->setDoNotSend($this->request('property_inaccessible'), '12', $iR, $this->access(false));

        Assert::same($result, $notFound);
        Assert::false($inv->getDoNotSend());
    }

    public function anUnknownInvoiceIdAlso404s(): void
    {
        [$webService, $notFound] = $this->webService();
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoInvUnLoadedquery')->with(999)->andReturn(null);
        $iR->shouldNotReceive('save');
        /** @var WorkerInvAccess&m\MockInterface $access */
        $access = m::mock(WorkerInvAccess::class);
        $access->shouldNotReceive('isAllocated');

        $result = $this->harness($this->user(55), $webService)
            ->setDoNotSend($this->request('property_inaccessible'), '999', $iR, $access);

        Assert::same($result, $notFound);
    }

    public function aSignedOutCallerOwnsNothing(): void
    {
        [$webService, $notFound] = $this->webService();
        $inv = new Inv();
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoInvUnLoadedquery')->andReturn($inv);
        $iR->shouldNotReceive('save');
        /** @var WorkerInvAccess&m\MockInterface $access */
        $access = m::mock(WorkerInvAccess::class);
        $access->shouldReceive('isAllocated')->with($inv, 0)->andReturn(false);

        $result = $this->harness(null, $webService)
            ->setDoNotSend($this->request('property_inaccessible'), '12', $iR, $access);

        Assert::same($result, $notFound);
    }

    public function theAllocatedWorkerCanFlagTheirInvoice(): void
    {
        [$webService, , $redirect] = $this->webService();
        $inv = new Inv();
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoInvUnLoadedquery')->with(12)->andReturn($inv);
        $iR->shouldReceive('save')->once()->with($inv);

        $result = $this->harness($this->user(55), $webService)
            ->setDoNotSend($this->request('property_inaccessible'), '12', $iR, $this->access(true));

        Assert::same($result, $redirect);
        Assert::true($inv->getDoNotSend());
        Assert::same($inv->getDoNotSendReason(), 'property_inaccessible');
    }

    public function theAllocatedWorkerCanClearTheFlagWithAnEmptyOrInvalidReason(): void
    {
        [$webService, , $redirect] = $this->webService();
        $inv = new Inv();
        $inv->setDoNotSend(true);
        $inv->setDoNotSendReason('job_incomplete');
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoInvUnLoadedquery')->andReturn($inv);
        $iR->shouldReceive('save')->once()->with($inv);

        $result = $this->harness($this->user(55), $webService)
            ->setDoNotSend($this->request('not_a_real_reason'), '12', $iR, $this->access(true));

        Assert::same($result, $redirect);
        Assert::false($inv->getDoNotSend());
        Assert::same($inv->getDoNotSendReason(), '');
    }
}
