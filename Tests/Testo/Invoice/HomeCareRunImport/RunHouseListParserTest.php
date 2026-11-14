<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\HomeCareRunImport;

use App\Invoice\HomeCareRunImport\RunHouse;
use App\Invoice\HomeCareRunImport\RunHouseListParser;
use Testo\Assert;
use Testo\Test;

/**
 * A run as it is kept in the lead's document, read into streets, houses and flags.
 */
#[Test]
final class RunHouseListParserTest
{
    private const string SAMPLE = <<<'TEXT'
LOCHNAGAR (Monthlies) 15th October 26
BERRYHILL 3 MAN RUN CDR
People are complaining on the Facebook page

BERRYHILL CRESCENT   ML2 0NE
3  Bi Monthly Leave in October
GEORGE VIEW ( on your right just behind you.)   FPS CARD PUT THROUGH DOOR EACH TIME
3 MUST BE CLEANED !!!!!!!!!!!card through Door
14
18  fronts only
41 CANCELLED
......................................
SOUTH LINE VIEW  ML2 0NH (4)
3
13
17
21 BLADE WINDOWS BACK AND FRONT PAYING EXTRA FOR THIS
BERRYHILL CONTINUES
DO NOT CLEAN 83
81
114 new
132 KNOCK DOOR TO OPEN GATE SHE WORKS FROM HOME!!!!
Wind in drive out of the estate - turn right.
MONTGOMERY CRESCENT ML2 0DH    (1)
10 NEW START IN October FIRST CLEAN
SHIELD RD ML1 2LP
462
LAMH RECYCLNG
TOUL GARDENS   ML1 2FE (8)
2
6
TEXT;

    public function theTitleAndHeaderNotesComeBeforeTheFirstStreet(): void
    {
        $run = (new RunHouseListParser())->parse(self::SAMPLE);

        Assert::same($run->title, 'LOCHNAGAR (Monthlies) 15th October 26');
        Assert::same($run->headerNotes, ['BERRYHILL 3 MAN RUN CDR', 'People are complaining on the Facebook page']);
    }

    public function streetsAreReadInDrivingOrderWithTheirPostcodes(): void
    {
        $run = (new RunHouseListParser())->parse(self::SAMPLE);

        Assert::same($run->streets(), ['Berryhill Crescent', 'George View', 'South Line View', 'Montgomery Crescent', 'Shield Rd', 'Toul Gardens']);
        $segments = $run->segments;
        Assert::same($segments[0]->postcode, 'ML2 0NE');
        Assert::same($segments[2]->postcode, 'ML2 0NH');
        Assert::same($segments[2]->declaredCount, 4);
    }

    public function aStreetComingBackLaterIsAContinuationOfTheSameStreet(): void
    {
        $run = (new RunHouseListParser())->parse(self::SAMPLE);

        $later = $run->segments[3];
        Assert::same($later->street, 'Berryhill Crescent');
        Assert::true($later->continues);
        Assert::same($later->postcode, 'ML2 0NE');
        Assert::same(array_map(static fn (RunHouse $h): string => $h->number, $later->houses), ['83', '81', '114', '132']);
    }

    public function whatIsWrittenBesideAHouseBecomesFlagsAndKeepsTheNote(): void
    {
        $run = (new RunHouseListParser())->parse(self::SAMPLE);
        $berryhill = $run->segments[0]->houses;
        $george = $run->segments[1]->houses;

        Assert::same($berryhill[0]->number, '3');
        Assert::true($berryhill[0]->has(RunHouse::FLAG_BI_MONTHLY));
        Assert::true($george[0]->has(RunHouse::FLAG_CARD));
        Assert::same($george[1]->note, '');
        Assert::same($george[2]->flags, [RunHouse::FLAG_FRONTS_ONLY]);
        Assert::true($george[3]->has(RunHouse::FLAG_CANCELLED));
        Assert::true($george[3]->isInactive());
        Assert::true($run->segments[3]->houses[0]->has(RunHouse::FLAG_DO_NOT_CLEAN));
        Assert::true($run->segments[3]->houses[2]->has(RunHouse::FLAG_NEW));
        Assert::true($run->segments[3]->houses[3]->has(RunHouse::FLAG_KNOCK));
    }

    public function instructionLinesAreDrivingNotesOnTheStreetNotHouses(): void
    {
        $run = (new RunHouseListParser())->parse(self::SAMPLE);

        Assert::true(in_array('Wind in drive out of the estate - turn right.', $run->segments[3]->notes, true));
        Assert::true(in_array('LAMH RECYCLNG', $run->segments[5]->notes, true));
    }

    public function aDeclaredCountThatDisagreesWithTheHousesFoundIsAWarning(): void
    {
        $run = (new RunHouseListParser())->parse(self::SAMPLE);

        Assert::same(count($run->warnings), 1);
        Assert::true(str_contains($run->warnings[0], 'Toul Gardens says (8) but 2 houses were found'));
    }

    public function theCanonicalFormNumbersEveryHouseInDrivingOrder(): void
    {
        $canonical = (new RunHouseListParser())->parse(self::SAMPLE)->toCanonical();

        Assert::same($canonical['title'], 'LOCHNAGAR (Monthlies) 15th October 26');
        Assert::same($canonical['houses'][0]['sequence'], 1);
        Assert::same($canonical['houses'][0]['street'], 'Berryhill Crescent');
        Assert::same($canonical['houses'][0]['postcode'], 'ML2 0NE');
        Assert::same(array_column($canonical['houses'], 'sequence'), range(1, count($canonical['houses'])));
    }
    public function aMixedCaseStreetWithAPostcodeIsAStreetAndAHouseBeforeAnyStreetIsAHeaderNote(): void
    {
        $run = (new RunHouseListParser())->parse("\u{FEFF}GLENEAGLES RUN\n\n6 SKYE REQUEST BY CUSTOMER\n\nSkye G74 2BU\n\n2\n\n4\n");

        Assert::same($run->title, 'GLENEAGLES RUN');
        Assert::same($run->headerNotes, ['6 SKYE REQUEST BY CUSTOMER']);
        Assert::same($run->segments[0]->street, 'Skye');
        Assert::same($run->segments[0]->postcode, 'G74 2BU');
        Assert::same(count($run->segments[0]->houses), 2);
        Assert::same($run->warnings, []);
    }
}