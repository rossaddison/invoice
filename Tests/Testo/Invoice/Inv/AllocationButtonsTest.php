<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\Inv\Widget\AllocationButtons;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * The reusable allocation buttons, used on their own (the cell composes them
 * into a layout; InvAllocationCellTest covers that).
 */
#[Test]
final class AllocationButtonsTest
{
    private function buttons(): AllocationButtons
    {
        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        $urlGenerator->shouldReceive('generate')->andReturnUsing(
            static fn (string $route, array $args = []): string => '/' . $route . '/' . (string) array_values($args)[0]
        );
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);

        return new AllocationButtons($urlGenerator, $translator, 'tok', [7 => 'Dave Smith', 8 => 'Sam <b>']);
    }

    private function inv(): Inv
    {
        $inv = new Inv();
        $inv->setId(12);
        $inv->setNumber('INV-9');
        return $inv;
    }

    public function aWorkerNameFallsBackToTheIdAndIsNotEscapedHereTheCallerEscapesIt(): void
    {
        Assert::same($this->buttons()->workerName(7), 'Dave Smith');
        Assert::same($this->buttons()->workerName(99), '#99');
    }

    public function anUnassignedChipGivesTheSideAndAnAssignedOneClearsIt(): void
    {
        $give = $this->buttons()->chip($this->inv(), AllocationSide::Front, 7, false);
        $clear = $this->buttons()->chip($this->inv(), AllocationSide::Front, 7, true);

        Assert::true(str_contains($give, 'action="/inv/allocateside/12"'));
        Assert::true(str_contains($give, 'name="worker_id" value="7"'));
        Assert::true(str_contains($give, 'aria-pressed="false"'));
        Assert::true(str_contains($clear, 'name="worker_id" value>'));
        Assert::true(str_contains($clear, 'aria-pressed="true"'));
        Assert::true(str_contains($give, 'aria-label="allocation.side.front Dave Smith #INV-9"'));
    }

    public function settleControlsAreDonePostponeDropdownAndCancelInThatOrder(): void
    {
        $allocation = new InvWorkerAllocation(12, AllocationSide::Front->value, 7);
        $allocation->setId(3);
        $html = $this->buttons()->settleControls($allocation, $this->inv());

        Assert::same(substr_count($html, 'action="/inv/settleallocation/3"'), 3);
        $done = strpos($html, 'value="completed"');
        $pause = strpos($html, 'data-bs-toggle="dropdown"');
        $cancel = strpos($html, 'value="cancelled"');
        Assert::true($done !== false && $pause !== false && $cancel !== false);
        Assert::true($done < $pause && $pause < $cancel);
    }

    public function everyStatusHasABadgeWithATooltip(): void
    {
        foreach (AllocationStatus::cases() as $status) {
            $html = $this->buttons()->statusBadge($status);
            Assert::true(str_contains($html, 'allocation.status.' . $status->value));
            Assert::true(str_contains($html, 'title="allocation.status.' . $status->value . ': allocation.tip.status.' . $status->value . '"'));
        }
    }

    public function theAllocateFormOffersEverySideAndEveryWorkerWithEscapedNames(): void
    {
        $html = $this->buttons()->allocateForm($this->inv());

        Assert::true(str_contains($html, 'name="side"'));
        Assert::true(str_contains($html, 'name="worker_id"'));
        // the sides and two workers carry a value; the "unassigned" option's empty value renders bare
        Assert::same(substr_count($html, '<option value="'), count(AllocationSide::cases()) + 2);
        Assert::true(str_contains($html, '<option value>worker.unassigned'));
        Assert::true(str_contains($html, 'Sam &lt;b&gt;'));
    }
}
