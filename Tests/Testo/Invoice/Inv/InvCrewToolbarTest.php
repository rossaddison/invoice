<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Invoice\Inv\Widget\InvCrewToolbar;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * The lead's "today's crew" form above the inv/guest grid.
 */
#[Test]
final class InvCrewToolbarTest
{
    /** @var array<int, string> */
    private const array NAMES = [1 => 'Lee Lead', 7 => 'Dave Smith', 8 => 'Sam <b>Jones</b>'];

    /**
     * @param list<int> $crew
     */
    private function html(array $crew, bool $frontBack): string
    {
        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        $urlGenerator->shouldReceive('generate')->with('inv/setcrew')->andReturn('/inv/setcrew');
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);

        return (new InvCrewToolbar($urlGenerator, $translator, 'csrf-token', self::NAMES, $crew, $frontBack))->render();
    }

    public function postsToTheSetCrewRouteWithTheCsrfToken(): void
    {
        $html = $this->html([], false);

        Assert::true(str_contains($html, 'action="/inv/setcrew"'));
        Assert::true(str_contains($html, 'method="POST"') || str_contains($html, 'method="post"'));
        Assert::true(str_contains($html, 'name="_csrf" value="csrf-token"'));
        Assert::true(str_contains($html, 'homecare.crew.save'));
    }

    public function listsEveryActiveWorkerAsACrewCheckboxWithTheNameEscaped(): void
    {
        $html = $this->html([], false);

        foreach ([1, 7, 8] as $id) {
            Assert::true(str_contains($html, 'name="crew[]" value="' . $id . '"'));
            Assert::true(str_contains($html, 'id="crew_worker_' . $id . '"'));
        }
        Assert::true(str_contains($html, 'Sam &lt;b&gt;Jones&lt;/b&gt;'));
        Assert::false(str_contains($html, '<b>Jones</b>'));
    }

    public function ticksTheSavedCrewAndFrontBackAndNothingElse(): void
    {
        $html = $this->html([1, 8], true);

        Assert::same(substr_count($html, ' checked'), 3);
        Assert::true((bool) preg_match('/value="1"[^>]*checked|checked[^>]*value="1"/', $html));
        Assert::true((bool) preg_match('/name="front_back"[^>]*checked|checked[^>]*name="front_back"/', $html));
        Assert::false((bool) preg_match('/value="7"[^>]*checked|checked[^>]*value="7"/', $html));
    }

    public function noCrewAndNoFrontBackLeavesEverythingUnticked(): void
    {
        Assert::same(substr_count($this->html([], false), ' checked'), 0);
    }
}