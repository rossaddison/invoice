<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Dwelling;

use App\Infrastructure\Persistence\Dwelling\Dwelling;
use App\Invoice\Client\ClientDwellingRepository;
use App\Invoice\Dwelling\DwellingForm;
use App\Invoice\Dwelling\DwellingPostcodeLink;
use App\Invoice\Dwelling\DwellingRepository;
use App\Invoice\Dwelling\DwellingService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * A dwelling can be marked as a pull point: the house the crew pulls the surrounding houses from.
 */
#[Test]
final class DwellingPullPointTest
{
    private function service(): DwellingService
    {
        /** @var DwellingRepository<Dwelling>&m\MockInterface $repository */
        $repository = m::mock(DwellingRepository::class);
        $repository->shouldReceive('save');
        /** @var ClientDwellingRepository<\App\Infrastructure\Persistence\Client\Client>&m\MockInterface $clients */
        $clients = m::mock(ClientDwellingRepository::class);
        return new DwellingService($repository, $clients);
    }

    public function aDwellingIsNotAPullPointUnlessMarked(): void
    {
        $dwelling = new Dwelling();

        Assert::false($dwelling->isPullPoint());
        $dwelling->setPullPoint(true);
        Assert::true($dwelling->isPullPoint());
    }

    public function theTickedBoxMarksItAndTheHiddenZeroClearsIt(): void
    {
        $service = $this->service();
        $dwelling = new Dwelling();

        $service->saveDwelling($dwelling, ['pull_point' => '1']);
        Assert::true($dwelling->isPullPoint());
        $service->saveDwelling($dwelling, ['pull_point' => '0']);
        Assert::false($dwelling->isPullPoint());
    }

    public function aSaveThatDoesNotMentionItLeavesItAlone(): void
    {
        $dwelling = new Dwelling();
        $dwelling->setPullPoint(true);

        $this->service()->saveDwelling($dwelling, ['postcode' => 'ab1 2cd']);

        Assert::true($dwelling->isPullPoint());
    }

    public function theFormShowsTheStoredValue(): void
    {
        $dwelling = new Dwelling();
        $dwelling->setPullPoint(true);

        Assert::true(DwellingForm::show($dwelling)->isPullPoint());
    }

    public function thePostcodeCellCarriesAPullPointBadgeOnlyForAPullPoint(): void
    {
        $plain = new Dwelling(family_id: 1, house_number_numeric: 12, postcode: 'AB12CD');
        $pull = new Dwelling(family_id: 1, house_number_numeric: 14, postcode: 'AB12CD');
        $pull->setPullPoint(true);

        /** @var DwellingRepository<Dwelling>&m\MockInterface $dwR */
        $dwR = m::mock(DwellingRepository::class);
        $dwR->shouldReceive('repoDwellingQuery')->with(1)->andReturn($plain);
        $dwR->shouldReceive('repoDwellingQuery')->with(2)->andReturn($pull);

        Assert::false(str_contains(DwellingPostcodeLink::html(1, $dwR, '', 'Pull point'), DwellingPostcodeLink::PULL_POINT_ICON));
        $badged = DwellingPostcodeLink::html(2, $dwR, '', 'Pull point');
        Assert::true(str_contains($badged, DwellingPostcodeLink::PULL_POINT_ICON));
        Assert::true(str_contains($badged, 'title="Pull point"'));
        Assert::true(str_contains($badged, 'https://www.google.com/maps/dir/?api=1&amp;destination=AB12CD'));
    }

    public function theRunSheetRowKeepsItThroughTheWorkerAndSideCopies(): void
    {
        $row = new \App\Invoice\Inv\RunSheetPdfRow('14 Example Crescent', null, 'Mrs A', '07', 'INV1', 5.0, 1, 3, '', true);

        Assert::true($row->withoutClientDetails()->pullPoint);
        Assert::true($row->withSides('Front: Dave')->pullPoint);
    }

    public function onlyAPullPointCanBeStampedAsDelegatedAndUntickingClearsIt(): void
    {
        $dwelling = new Dwelling();
        $at = new \DateTimeImmutable('2026-11-09 10:30:00');

        Assert::false($dwelling->markDelegated($at));
        Assert::null($dwelling->getPullPointSortOrder());
        $dwelling->setPullPoint(true);
        Assert::true($dwelling->markDelegated($at));
        Assert::same($dwelling->getPullPointSortOrder(), $at);
        $dwelling->setPullPoint(false);
        Assert::null($dwelling->getPullPointSortOrder());
    }

    public function theDelegatedButtonShowsOnlyOnAPullPointWithTheTimeOfTheLastTap(): void
    {
        $pull = new Dwelling(family_id: 1, house_number_numeric: 14, postcode: 'AB12CD');
        $pull->setPullPoint(true);
        $pull->markDelegated(new \DateTimeImmutable('today 10:30'));
        $plain = new Dwelling(family_id: 1, house_number_numeric: 12, postcode: 'AB12CD');

        /** @var DwellingRepository<Dwelling>&m\MockInterface $dwR */
        $dwR = m::mock(DwellingRepository::class);
        $dwR->shouldReceive('repoDwellingQuery')->with(1)->andReturn($plain);
        $dwR->shouldReceive('repoDwellingQuery')->with(2)->andReturn($pull);
        /** @var \Yiisoft\Router\UrlGeneratorInterface&m\MockInterface $urls */
        $urls = m::mock(\Yiisoft\Router\UrlGeneratorInterface::class);
        $urls->shouldReceive('generate')->andReturn('/inv/delegatepullpoint/9');
        /** @var \Yiisoft\Translator\TranslatorInterface&m\MockInterface $t */
        $t = m::mock(\Yiisoft\Translator\TranslatorInterface::class);
        $t->shouldReceive('translate')->andReturnUsing(static fn (string $k): string => $k === 'dwelling.pull.point.delegated.at' ? 'Delegated at %s' : $k);
        $cell = new \App\Invoice\Dwelling\PullPointDelegateCell($dwR, $urls, $t, 'tok');

        $inv = static function (int $dwellingId): \App\Infrastructure\Persistence\Inv\Inv {
            $client = new \App\Infrastructure\Persistence\Client\Client();
            $client->setDwellingId($dwellingId);
            $i = new \App\Infrastructure\Persistence\Inv\Inv();
            $i->setId(9);
            $i->setClient($client);
            return $i;
        };

        Assert::same($cell->render($inv(1)), '');
        $html = $cell->render($inv(2));
        Assert::true(str_contains($html, '/inv/delegatepullpoint/9'));
        Assert::true(str_contains($html, 'Delegated at 10:30'));
    }

    public function aStampFromAnEarlierDayShowsItsDateSoItIsNotTakenForToday(): void
    {
        $pull = new Dwelling(family_id: 1, house_number_numeric: 14, postcode: 'AB12CD');
        $pull->setPullPoint(true);
        $pull->markDelegated(new \DateTimeImmutable('2026-11-02 10:30:00'));
        /** @var DwellingRepository<Dwelling>&m\MockInterface $dwR */
        $dwR = m::mock(DwellingRepository::class);
        $dwR->shouldReceive('repoDwellingQuery')->andReturn($pull);
        /** @var \Yiisoft\Router\UrlGeneratorInterface&m\MockInterface $urls */
        $urls = m::mock(\Yiisoft\Router\UrlGeneratorInterface::class);
        $urls->shouldReceive('generate')->andReturn('/inv/delegatepullpoint/9');
        /** @var \Yiisoft\Translator\TranslatorInterface&m\MockInterface $t */
        $t = m::mock(\Yiisoft\Translator\TranslatorInterface::class);
        $t->shouldReceive('translate')->andReturnUsing(static fn (string $k): string => $k === 'dwelling.pull.point.delegated.at' ? 'Delegated at %s' : $k);
        $client = new \App\Infrastructure\Persistence\Client\Client();
        $client->setDwellingId(2);
        $inv = new \App\Infrastructure\Persistence\Inv\Inv();
        $inv->setId(9);
        $inv->setClient($client);

        $html = (new \App\Invoice\Dwelling\PullPointDelegateCell($dwR, $urls, $t, 'tok'))->render($inv);

        Assert::true(str_contains($html, 'Delegated at 2 Nov 10:30'));
    }}
