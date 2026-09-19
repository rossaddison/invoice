<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Inv\InvVoidController;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Service\WebControllerService;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;

#[Test]
final class InvVoidControllerTest
{
    private function makeController(?Inv $inv, float $paid, string $expectedFlash = ''): InvVoidController
    {
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoInvUnLoadedquery')->andReturn($inv);
        if ($inv !== null && $expectedFlash === 'invoice.void.success') {
            $iR->shouldReceive('save')->once()->with($inv);
        } else {
            $iR->shouldReceive('save')->never();
        }

        /** @var InvAmountRepository&m\MockInterface $iaR */
        $iaR = m::mock(InvAmountRepository::class);
        $iaR->shouldReceive('repoInvquery')->andReturn(new InvAmount(inv_id: 1, paid: $paid, total: 120.00));

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        if ($expectedFlash !== '') {
            $flash->shouldReceive('add')->once()->with(m::any(), $expectedFlash, true);
        } else {
            $flash->shouldReceive('add')->never();
        }

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn(string $key): string => $key);

        /** @var WebControllerService&m\MockInterface $web */
        $web = m::mock(WebControllerService::class);
        $web->shouldReceive('getRedirectResponse')->andReturn(new Psr7Response(302));
        $web->shouldReceive('getNotFoundResponse')->andReturn(new Psr7Response(404));

        return new InvVoidController($flash, $iR, $iaR, $translator, $web);
    }

    private function route(): CurrentRoute
    {
        /** @var CurrentRoute&m\MockInterface $route */
        $route = m::mock(CurrentRoute::class);
        $route->shouldReceive('getArgument')->with('id', '0')->andReturn('1');
        return $route;
    }

    private function inv(int $status): Inv
    {
        $inv = new Inv(status_id: $status);
        $inv->setId(1);
        return $inv;
    }

    public function returnsNotFoundForAnUnknownInvoice(): void
    {
        $response = $this->makeController(null, 0.00)->void($this->route());
        Assert::same($response->getStatusCode(), 404);
    }

    public function voidsAnIssuedUnpaidInvoiceAndMakesItReadOnly(): void
    {
        $inv = $this->inv(2);
        $response = $this->makeController($inv, 0.00, 'invoice.void.success')->void($this->route());
        Assert::same($response->getStatusCode(), 302);
        Assert::same($inv->reqStatusId(), 14);
        Assert::true($inv->isVoid());
    }

    public function voidsAViewedInvoice(): void
    {
        $inv = $this->inv(3);
        $this->makeController($inv, 0.00, 'invoice.void.success')->void($this->route());
        Assert::same($inv->reqStatusId(), 14);
    }

    public function refusesToVoidADraft(): void
    {
        $inv = $this->inv(1);
        $this->makeController($inv, 0.00, 'invoice.void.not.allowed')->void($this->route());
        Assert::same($inv->reqStatusId(), 1);
    }

    public function refusesToVoidAnInvoiceWithPayments(): void
    {
        $inv = $this->inv(2);
        $this->makeController($inv, 30.00, 'invoice.void.not.allowed')->void($this->route());
        Assert::same($inv->reqStatusId(), 2);
    }

    public function aVoidInvoiceCanNeverChangeStatusAgain(): void
    {
        $inv = $this->inv(2);
        $inv->setStatusId(14);
        $inv->setStatusId(2);
        $inv->setStatusId(4);
        Assert::same($inv->reqStatusId(), 14);
    }

    public function draftAndPaidInvoicesCannotBeVoidedDirectlyOnTheEntity(): void
    {
        $draft = $this->inv(1);
        $draft->setStatusId(14);
        Assert::same($draft->reqStatusId(), 1);
        $paid = $this->inv(4);
        $paid->setStatusId(14);
        Assert::same($paid->reqStatusId(), 4);
    }
}
