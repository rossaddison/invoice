<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\Worker\Worker;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Inv\InvIndexFilter;
use App\Invoice\Inv\InvIndexListDeps;
use App\Invoice\Inv\InvIndexNavDeps;
use App\Invoice\Inv\RunSheetPdfDeps;
use App\Invoice\Inv\RunSheetPdfRowBuilder;
use App\Invoice\InvWorkerAllocation\AllocationSidesSummary;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Worker\WorkerRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use Mockery as m;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * The staff run sheet PDF (inv/runsheetpdf) carries client names, phones and balances for every
 * invoice. A field worker holds VIEW_INV, so they could open it by address; they are sent to their
 * own scoped sheet (inv/guest/runsheetpdf) instead.
 */
#[Test]
final class RunSheetPdfWorkerTest
{
    public function aFieldWorkerIsSentToTheirOwnScopedSheet(): void
    {
        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $user = m::mock(User::class);
        $user->shouldReceive('reqId')->andReturn(55);
        $userService->shouldReceive('getUser')->andReturn($user);
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn(true);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn(false);
        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        /** @var WebViewRenderer&m\MockInterface $views */
        $views = m::mock(WebViewRenderer::class);
        $views->shouldReceive('withControllerName')->andReturnSelf();
        $views->shouldReceive('withLayout')->andReturnSelf();
        /** @var Response&m\MockInterface $redirect */
        $redirect = m::mock(Response::class);
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        $webService->shouldReceive('getRedirectResponse')->once()->with('inv/guest/runsheetpdf')->andReturn($redirect);
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        /** @var WorkerRepository&m\MockInterface $wR */
        $wR = m::mock(WorkerRepository::class);
        $wR->shouldReceive('findByUserId')->andReturn(new Worker('Dave', 'Smith', true));
        /** @var ServerRequestInterface&m\MockInterface $request */
        $request = m::mock(ServerRequestInterface::class);

        /** @var InvIndexFilter&m\MockInterface $filter */
        $filter = m::mock(InvIndexFilter::class);
        /** @var InvIndexListDeps&m\MockInterface $list */
        $list = m::mock(InvIndexListDeps::class);
        /** @var InvIndexNavDeps&m\MockInterface $nav */
        $nav = m::mock(InvIndexNavDeps::class);
        /** @var FamilyRepository<\App\Infrastructure\Persistence\Family\Family>&m\MockInterface $fR */
        $fR = m::mock(FamilyRepository::class);
        /** @var RunSheetPdfRowBuilder&m\MockInterface $builder */
        $builder = m::mock(RunSheetPdfRowBuilder::class);
        /** @var AllocationSidesSummary&m\MockInterface $sides */
        $sides = m::mock(AllocationSidesSummary::class);

        $harness = new RunSheetPdfTestHarness($webService, $userService, $translator, $views, $session, $sR, $flash);
        $result = $harness->runSheetPdf(
            $request,
            $filter,
            $list,
            $nav,
            new RunSheetPdfDeps($fR, $builder, $sides),
            $wR,
        );

        Assert::same($result, $redirect);
    }
}
