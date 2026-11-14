<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Testing;

use App\Invoice\Testing\VirtualizeSensitiveTestDataCheck;
use Testo\Assert;
use Testo\Test;

use function array_unique;
use function count;
use function explode;
use function implode;
use function str_contains;

/**
 * Test data must be virtual. These tests pin what counts as real-looking and what counts as virtual,
 * and that virtualizing turns the first into the second without disturbing anything else.
 *
 * The values below that LOOK real are invented and cannot belong to anybody (QQ is not a postcode
 * area, 0123 is not an area code, the surnames are generic); each such line carries the ignore marker,
 * which is the designed way to say "this real-looking value is on purpose".
 */
#[Test]
final class VirtualizeSensitiveTestDataCheckTest
{
    private function check(): VirtualizeSensitiveTestDataCheck
    {
        return new VirtualizeSensitiveTestDataCheck();
    }

    public function anEmailOnAnOrdinaryDomainIsRealLooking(): void
    {
        $findings = $this->check()->check('Contact someone@mail-provider.co.uk today'); // sensitive-data-check: ignore

        Assert::count($findings, 1);
        Assert::same(VirtualizeSensitiveTestDataCheck::RULE_EMAIL, $findings[0]['rule']);
        Assert::same(1, $findings[0]['line']);
    }

    public function emailsOnReservedDomainsAreVirtual(): void
    {
        foreach (['a@example.com', 'a@example.org', 'a@example.net', 'a@host.test', 'a@shop.example', 'a@mail.invalid', 'a@box.localhost'] as $email) {
            Assert::true($this->check()->isVirtual($email));
        }
    }

    public function telephoneNumbersInTheOfcomDramaRangesAreVirtual(): void
    {
        foreach (['01632 960123', '07700 900123', '+44 20 7946 0958', '020 7946 0958', '+44 7700 900001', '0113 496 0123'] as $number) {
            Assert::true($this->check()->isVirtual($number));
        }
    }

    public function aTelephoneNumberOutsideThoseRangesIsRealLooking(): void
    {
        $findings = $this->check()->check('Ring 0123 456 7890 after nine'); // sensitive-data-check: ignore

        Assert::count($findings, 1);
        Assert::same(VirtualizeSensitiveTestDataCheck::RULE_PHONE, $findings[0]['rule']);
    }

    public function aPostcodeStartingZzIsVirtualAndAnyOtherIsRealLooking(): void
    {
        Assert::true($this->check()->isVirtual('ZZ1 1AA'));
        Assert::true($this->check()->isVirtual('zz9 9aa'));

        $findings = $this->check()->check('QQ1 1QQ'); // sensitive-data-check: ignore
        Assert::count($findings, 1);
        Assert::same(VirtualizeSensitiveTestDataCheck::RULE_POSTCODE, $findings[0]['rule']);
        Assert::false($this->check()->isVirtual('qq1 1qq')); // sensitive-data-check: ignore
    }

    public function aTitledNameIsRealLookingUnlessTheSurnameIsVirtual(): void
    {
        Assert::false($this->check()->isVirtual('Mrs Brown was in')); // sensitive-data-check: ignore
        Assert::false($this->check()->isVirtual('Dr. Okafor-Smith rang')); // sensitive-data-check: ignore
        Assert::true($this->check()->isVirtual('Mr Virtual1'));
        Assert::true($this->check()->isVirtual('Ms Test7'));
        Assert::true($this->check()->isVirtual('Dr Example3'));
        Assert::true($this->check()->isVirtual('Miss Sample9'));
    }

    public function aLineCarryingTheIgnoreMarkerIsSkipped(): void
    {
        $text = "someone@mail-provider.co.uk // sensitive-data-check: ignore\nother@mail-provider.co.uk"; // sensitive-data-check: ignore

        $findings = $this->check()->check($text);

        Assert::count($findings, 1);
        Assert::same(2, $findings[0]['line']);
    }

    public function findingsNameTheKindAndLineButNeverTheValue(): void
    {
        $findings = $this->check()->check("fine\nsomeone@mail-provider.co.uk"); // sensitive-data-check: ignore

        Assert::same(2, $findings[0]['line']);
        Assert::same('27 characters', $findings[0]['hint']);
        Assert::false(str_contains($findings[0]['hint'], 'someone'));
    }

    public function severalValuesOnOneLineAreReportedInTextOrder(): void
    {
        $findings = $this->check()->check('QQ1 1QQ then someone@mail-provider.co.uk then 0123 456 7890'); // sensitive-data-check: ignore

        Assert::count($findings, 3);
        Assert::same(VirtualizeSensitiveTestDataCheck::RULE_POSTCODE, $findings[0]['rule']);
        Assert::same(VirtualizeSensitiveTestDataCheck::RULE_EMAIL, $findings[1]['rule']);
        Assert::same(VirtualizeSensitiveTestDataCheck::RULE_PHONE, $findings[2]['rule']);
    }

    public function textWithNoRealLookingDataPasses(): void
    {
        Assert::true($this->check()->isVirtual(''));
        Assert::true($this->check()->isVirtual('Run 3, Virtual Street, house 12A, pull point, price 45.00'));
    }

    public function virtualizingTurnsRealLookingValuesIntoVirtualOnes(): void
    {
        $text = "Client Mrs Brown, someone@mail-provider.co.uk, 0123 456 7890, QQ1 1QQ"; // sensitive-data-check: ignore

        $virtual = $this->check()->virtualize($text);

        Assert::true($this->check()->isVirtual($virtual));
        Assert::false(str_contains($virtual, 'Brown'));
        Assert::false(str_contains($virtual, 'someone'));
        Assert::true(str_contains($virtual, '@example.test'));
        Assert::true(str_contains($virtual, '01632 960'));
        Assert::true(str_contains($virtual, 'ZZ'));
        Assert::true(str_contains($virtual, 'Client Mrs Virtual'));
    }

    public function theSameRealValueBecomesTheSameVirtualValueAndDifferentOnesStayDifferent(): void
    {
        $text = "a@mail-provider.co.uk\nb@mail-provider.co.uk\nA@mail-provider.co.uk"; // sensitive-data-check: ignore

        $lines = explode("\n", $this->check()->virtualize($text));

        Assert::same($lines[0], $lines[2]);
        Assert::false($lines[0] === $lines[1]);
    }

    public function manyDistinctRealValuesGetDistinctVirtualValues(): void
    {
        $postcodes = [];
        $phones = [];
        for ($i = 1; $i <= 30; $i++) {
            $postcodes[] = 'QQ' . $i . ' 1QQ'; // sensitive-data-check: ignore
            $phones[] = '0123 456 ' . (7000 + $i); // sensitive-data-check: ignore
        }

        $virtualPostcodes = explode("\n", $this->check()->virtualize(implode("\n", $postcodes)));
        $virtualPhones = explode("\n", $this->check()->virtualize(implode("\n", $phones)));

        Assert::same(30, count(array_unique($virtualPostcodes)));
        Assert::same(30, count(array_unique($virtualPhones)));
    }

    public function virtualizingVirtualTextChangesNothing(): void
    {
        $text = "Mr Virtual1, virtual1@example.test, 01632 960001, ZZ1 1AA\nrun 4";

        Assert::same($text, $this->check()->virtualize($text));
    }

    public function virtualizingLeavesLinesWithTheIgnoreMarkerAlone(): void
    {
        $text = "keep someone@mail-provider.co.uk // sensitive-data-check: ignore\nchange other@mail-provider.co.uk"; // sensitive-data-check: ignore

        $lines = explode("\n", $this->check()->virtualize($text));

        Assert::same("keep someone@mail-provider.co.uk // sensitive-data-check: ignore", $lines[0]); // sensitive-data-check: ignore
        Assert::true(str_contains($lines[1], '@example.test'));
    }

    public function aPlusFortyFourNumberIsRecognisedAndVirtualizedToo(): void
    {
        $virtual = $this->check()->virtualize('Call +44 1234 567890'); // sensitive-data-check: ignore

        Assert::true($this->check()->isVirtual($virtual));
        Assert::true(str_contains($virtual, '01632 960'));
    }
}
