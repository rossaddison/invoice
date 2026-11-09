<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Dwelling\Dwelling;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Dwelling\DwellingRepository;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Inv\GuestPullPointOrder;
use App\Invoice\Inv\RunSheetPdfRowBuilder;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * The inv/guest grid for a worker: invoices come out under their pull points.
 */
#[Test]
final class GuestPullPointOrderTest
{
    public function invoicesComeOutUnderTheirPullPoints(): void
    {
        // one street; houses 1..4, house 2 is a pull point and 4 is one, so 3 follows 2 and 1 is leading
        $family = new Family();
        $family->setId(1);
        $family->setStreetSortOrder(1);
        $dwellings = [];
        $invs = [];
        foreach ([1, 2, 3, 4] as $number) {
            $dwelling = new Dwelling(family_id: 1, house_number_numeric: $number, postcode: 'AB12CD');
            $dwelling->setId($number);
            $dwelling->setPullPoint(in_array($number, [2, 4], true));
            $dwellings[$number] = $dwelling;
            $client = new Client();
            $client->setDwellingId($number);
            $inv = new Inv();
            $inv->setId(100 + $number);
            $inv->setClient($client);
            $invs[] = $inv;
        }
        /** @var DwellingRepository<Dwelling>&m\MockInterface $dwR */
        $dwR = m::mock(DwellingRepository::class);
        $dwR->shouldReceive('repoDwellingQuery')->andReturnUsing(static fn (int $id): ?Dwelling => $dwellings[$id] ?? null);
        /** @var FamilyRepository<Family>&m\MockInterface $fR */
        $fR = m::mock(FamilyRepository::class);
        $fR->shouldReceive('repoFamilyquery')->andReturn($family);

        $reader = (new GuestPullPointOrder($dwR, $fR, new RunSheetPdfRowBuilder()))->order(array_reverse($invs));

        $ids = array_map(static fn (Inv $i): int => $i->reqId(), iterator_to_array($reader->read(), false));
        Assert::same($ids, [102, 103, 104, 101]);
    }
}
