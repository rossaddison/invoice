<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\HomeCareRunImport;

use App\Invoice\HomeCareRunImport\RunHouse;
use App\Invoice\HomeCareRunImport\RunHouseListParser;
use App\Invoice\HomeCareRunImport\RunSheetCsv;
use Testo\Assert;
use Testo\Test;

#[Test]
final class RunSheetCsvTest
{
    private const string SHEET = "street,postcode,number,client_name,phone,price,note,flags\r\n"
        . "Berryhill Crescent,ML2 0NE,14,Mrs Smith,07700 900123,12.5,gate sticks,fronts_only new\r\n"
        . "Berryhill Crescent,,16,,,,,\r\n"
        . "Shield Rd,ml12lp,462,\"O'Neil, J\",,abc,,bogus\r\n"
        . ",ML1 2LP,5,,,,,\r\n";

    public function aSheetIsRecognisedByItsHeader(): void
    {
        $csv = new RunSheetCsv();
        Assert::true($csv->isSheet(self::SHEET));
        Assert::true($csv->isSheet("\xEF\xBB\xBFStreet;postcode;number\n"));
        Assert::false($csv->isSheet("RUN ONE\nBERRYHILL CRESCENT ML2 0NE\n14\n"));
    }

    public function rowsBecomeSegmentsWithClientDetails(): void
    {
        $run = (new RunSheetCsv())->parse(self::SHEET);

        Assert::same(count($run->segments), 2);
        Assert::same($run->segments[0]->street, 'Berryhill Crescent');
        Assert::same($run->segments[0]->postcode, 'ML2 0NE');
        Assert::same(count($run->segments[0]->houses), 2);
        $house = $run->segments[0]->houses[0];
        Assert::same($house->clientName, 'Mrs Smith');
        Assert::same($house->phone, '07700 900123');
        Assert::same($house->price, 12.5);
        Assert::true($house->has(RunHouse::FLAG_FRONTS_ONLY));
        Assert::true($house->has(RunHouse::FLAG_NEW));
        Assert::same($run->segments[1]->postcode, 'ML1 2LP');
        Assert::same($run->segments[1]->houses[0]->clientName, "O'Neil, J");
    }

    public function problemsAreWarnedAbout(): void
    {
        $run = (new RunSheetCsv())->parse(self::SHEET);
        $all = implode("\n", $run->warnings);

        Assert::true(str_contains($all, 'Line 5 skipped'));
        Assert::true(str_contains($all, '"abc" is not a price'));
        Assert::true(str_contains($all, 'unknown flag "bogus"'));
        Assert::same($run->houseCount(), 3);
    }

    public function theTemplateForARunReadsBackTheSame(): void
    {
        $parsed = (new RunHouseListParser())->parse("RUN ONE\nBERRYHILL CRESCENT ML2 0NE (2)\n14\n16 fronts only\n");
        $csv = new RunSheetCsv();
        $sheet = $csv->template($parsed);
        $back = $csv->parse($sheet);

        Assert::true($csv->isSheet($sheet));
        Assert::same($back->houseCount(), 2);
        Assert::same($back->segments[0]->houses[1]->number, '16');
        Assert::true($back->segments[0]->houses[1]->has(RunHouse::FLAG_FRONTS_ONLY));
        Assert::same($back->segments[0]->postcode, 'ML2 0NE');
    }

    public function theBlankTemplateHasTheHeaderAndAnExample(): void
    {
        $sheet = (new RunSheetCsv())->template();

        Assert::true(str_contains($sheet, '"street","postcode","number","client_name","phone","price","note","flags"'));
        Assert::true((new RunSheetCsv())->isSheet(ltrim($sheet, "\xEF\xBB\xBF")) || (new RunSheetCsv())->isSheet($sheet));
    }

    public function formulaCellsAreNeutralised(): void
    {
        $parsed = (new RunSheetCsv())->parse("street,postcode,number,client_name\n\"A Road\",,1,=HYPERLINK(\"x\")\n");
        $sheet = (new RunSheetCsv())->template($parsed);

        Assert::true(str_contains($sheet, "\"'=HYPERLINK"));
    }

    public function aHouseCanBeMarkedAsAPullPointInTheFlagsColumn(): void
    {
        $run = (new RunSheetCsv())->parse("street,postcode,number,flags
\"A Road\",,1,pull_point
\"A Road\",,2,
");

        Assert::true($run->segments[0]->houses[0]->has(RunHouse::FLAG_PULL_POINT));
        Assert::false($run->segments[0]->houses[1]->has(RunHouse::FLAG_PULL_POINT));
        Assert::same($run->warnings, []);
    }

    public function aCellTheTemplateGuardedAgainstFormulasReadsBackWithoutTheApostrophe(): void
    {
        $run = (new RunSheetCsv())->parse("street,postcode,number,phone,note\n\"A Road\",,1,'+44 7700 900123,'-careful\n\"A Road\",,2,'plain,\n");

        Assert::same($run->segments[0]->houses[0]->phone, '+44 7700 900123');
        Assert::same($run->segments[0]->houses[0]->note, '-careful');
        // an apostrophe that is not a guard stays
        Assert::same($run->segments[0]->houses[1]->phone, "'plain");
    }

    public function aHouseNumberThatIsNotANumberIsSkippedWithAWarning(): void
    {
        $run = (new RunSheetCsv())->parse("street,postcode,number\n\"A Road\",,Flat 1\n\"A Road\",,12-14\n\"A Road\",,12a\n");

        Assert::same($run->houseCount(), 1);
        Assert::same($run->segments[0]->houses[0]->number, '12A');
        Assert::same(count($run->warnings), 2);
        Assert::true(str_contains($run->warnings[0], 'house number such as 14 or 12A'));
    }

    public function aPostcodeThatIsNotOneIsLeftBlankWithAWarning(): void
    {
        $run = (new RunSheetCsv())->parse("street,postcode,number\n\"A Road\",NOT A POSTCODE AT ALL,1\n");

        Assert::null($run->segments[0]->postcode);
        Assert::same(count($run->warnings), 1);
        Assert::true(str_contains($run->warnings[0], 'is not a postcode'));
    }}
