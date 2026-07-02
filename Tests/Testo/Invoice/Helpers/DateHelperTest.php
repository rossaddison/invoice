<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Invoice\Helpers\DateHelper;
use App\Invoice\Setting\SettingRepository;
use DateTimeImmutable;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;

#[Test]
final class DateHelperTest
{
    private readonly DateHelper $dh;
    private readonly SettingRepository $sRepo;

    public function __construct()
    {
        $sRepo = (new ReflectionClass(SettingRepository::class))->newInstanceWithoutConstructor(); // NOSONAR: php:S3011
        $sRepo->settingsArray = [
            'date_format'   => 'Y-m-d',
            'first_day_of_week' => 'monday',
            'time_zone'     => 'Europe/London',
        ];
        $this->sRepo = $sRepo;
        $this->dh    = new DateHelper($sRepo);
    }

    // ── Pure methods (no SRepo call) ─────────────────────────────────────────

    public function addToImmutableAddsOneDayToDate(): void
    {
        $date   = new DateTimeImmutable('2026-07-15');
        $result = $this->dh->addToImmutable($date, '1D');

        Assert::same($result, '2026-07-16');
    }

    public function addToImmutableAddsOneMonth(): void
    {
        $date   = new DateTimeImmutable('2026-01-31');
        $result = $this->dh->addToImmutable($date, '1M');

        Assert::same($result, '2026-03-03');
    }

    public function addToImmutableAddsOneYear(): void
    {
        $date   = new DateTimeImmutable('2026-07-15');
        $result = $this->dh->addToImmutable($date, '1Y');

        Assert::same($result, '2027-07-15');
    }

    public function incrementDateAddsSevenDays(): void
    {
        $result = $this->dh->incrementDate('2026-07-01', '7D');

        Assert::same($result, '2026-07-08');
    }

    public function incrementDateCrossesMonthBoundary(): void
    {
        $result = $this->dh->incrementDate('2026-07-28', '7D');

        Assert::same($result, '2026-08-04');
    }

    public function incrementDateStringToDateTimeReturnsDateTimeInstance(): void
    {
        $result = $this->dh->incrementDateStringToDateTime('2026-07-01', '1M');

        Assert::same($result->format('Y-m-d'), '2026-08-01');
    }

    public function ymdToImmutableParsesStandardFormat(): void
    {
        $result = $this->dh->ymdToImmutable('2026-07-15');

        Assert::same($result->format('Y'), '2026');
        Assert::same($result->format('m'), '07');
        Assert::same($result->format('d'), '15');
    }

    public function dateFromMysqlWithoutStyleConvertsImmutableToDateTime(): void
    {
        $immutable = new DateTimeImmutable('2026-07-15 14:30:00');
        $result    = $this->dh->dateFromMysqlWithoutStyle($immutable);

        Assert::same($result->format('Y-m-d'), '2026-07-15');
    }

    public function now01ReturnsDayOne(): void
    {
        $result = $this->dh->now01();

        Assert::same($result->format('d'), '01');
    }

    public function nowTReturnsDayOne(): void
    {
        $result = $this->dh->nowT();

        Assert::same($result->format('d'), '01');
    }

    public function getYearFromDateTimeReturnsYear(): void
    {
        $immutable = new DateTimeImmutable('2026-07-15');
        $result    = $this->dh->getYearFromDateTime($immutable);

        Assert::same($result, '2026');
    }

    // ── SRepo-dependent methods ───────────────────────────────────────────────

    public function styleReturnsConfiguredDateFormat(): void
    {
        Assert::same($this->dh->style(), 'Y-m-d');
    }

    public function separatorReturnsDashForYmd(): void
    {
        Assert::same($this->dh->separator(), '-');
    }

    public function displayReturnsHumanReadableFormat(): void
    {
        Assert::same($this->dh->display(), 'yyyy-mm-dd');
    }

    public function datepickerDateFormatReturnsPickerFormat(): void
    {
        Assert::same($this->dh->datepickerDateFormat(), 'yy-mm-dd');
    }

    public function dateFormatsContainsAllThirteenFormats(): void
    {
        $formats = $this->dh->dateFormats();

        Assert::count($formats, 13);
        Assert::true(isset($formats['d/m/Y']));
        Assert::true(isset($formats['Y-m-d']));
        Assert::true(isset($formats['Y-m-d H:i:s']));
    }

    public function dateFromMysqlFormatsUsingConfiguredStyle(): void
    {
        $immutable = new DateTimeImmutable('2026-07-15');
        $result    = $this->dh->dateFromMysql($immutable);

        Assert::same($result, '2026-07-15');
    }

    public function dateForPaymentFormMatchesDateFromMysql(): void
    {
        $immutable = new DateTimeImmutable('2026-07-15');

        Assert::same(
            $this->dh->dateForPaymentForm($immutable),
            $this->dh->dateFromMysql($immutable),
        );
    }

    public function isDateReturnsTrueForValidDate(): void
    {
        Assert::true($this->dh->isDate('2026-07-15'));
    }

    public function isDateReturnsFalseForInvalidDate(): void
    {
        Assert::false($this->dh->isDate('not-a-date'));
    }

    public function isDateReturnsFalseForWrongFormat(): void
    {
        // date is real but formatted as d/m/Y, not Y-m-d
        Assert::false($this->dh->isDate('15/07/2026'));
    }

    public function dateToMysqlConvertsFormattedDateToIso(): void
    {
        // date_format is Y-m-d, so input in that format converts to the same
        $result = $this->dh->dateToMysql('2026-07-15');

        Assert::same($result, '2026-07-15');
    }

    public function incrementUserDateAddsOneMonthAndReturnsConfiguredFormat(): void
    {
        $date   = new DateTimeImmutable('2026-01-15');
        $result = $this->dh->incrementUserDate($date, '1M');

        Assert::same($result, '2026-02-15');
    }

    public function getOrSetWithStyleReturnsFormattedStringForImmutableInput(): void
    {
        $immutable = new DateTimeImmutable('2026-07-15');
        $result    = $this->dh->getOrSetWithStyle($immutable);

        Assert::same($result, '2026-07-15');
    }

    public function getOrSetWithStyleReturnsNullForNullInput(): void
    {
        $result = $this->dh->getOrSetWithStyle(null);

        Assert::null($result);
    }

    public function slashSeparatedFormatHasSeparatorSlash(): void
    {
        $this->sRepo->settingsArray['date_format'] = 'd/m/Y';

        Assert::same($this->dh->separator(), '/');

        $this->sRepo->settingsArray['date_format'] = 'Y-m-d';
    }

    public function dotSeparatedFormatHasSeparatorDot(): void
    {
        $this->sRepo->settingsArray['date_format'] = 'd.m.Y';

        Assert::same($this->dh->separator(), '.');

        $this->sRepo->settingsArray['date_format'] = 'Y-m-d';
    }
}
