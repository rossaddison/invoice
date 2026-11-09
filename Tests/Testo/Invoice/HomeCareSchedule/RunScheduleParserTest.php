<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\HomeCareSchedule;

use App\Invoice\HomeCareSchedule\ParsedSchedule;
use App\Invoice\HomeCareSchedule\RunScheduleParser;
use App\Invoice\HomeCareSchedule\ScheduleEntry;
use Testo\Assert;
use Testo\Test;

/**
 * The schedules exactly as they were sent round (WhatsApp text), not a tidy format.
 */
#[Test]
final class RunScheduleParserTest
{
    /** October, as sent on WhatsApp: crews in brackets, ditto marks, a crew on its own line, divider lines. */
    private const string OCTOBER = <<<'TXT'
OCTOBER SCHEDULE

5 - Bellshill 2 (Andy & Dan)
6 - Dunfermline 1  (“ & “)

8 - Bellshill 1 (Ross.Dan.Callum)
9 - Craig’s Edinburgh (Ross.Dan.Callum)
……………………………………..
12 - Gleneagles (Ross.Andy.Dan)
13 - Bute
(Ross.Andy.Dan)

15 - Lochnagar (Ross.Dan.Callum)
16 - Inchkeith (Ross.Dan.Callum)
……………………………………..
19 - E. Kilbride (Ross.Dan.Andy)
20 - Dunf 2 (Ross.Dan.Andy)

22 - Bellshill F (Ross.Dan)
23 - Anniesland (Ross.Dan)
……………………………………..
26 - Milngavie (Ross.Dan.Andy)
27 - Blantyre (Ross.Dan.Andy)

29 - Clydebank (Ross.Dan)
30 - Mansions (Ross.Dan)
TXT;

    /** November, as typed: no dashes, no crews, a run glued to its day, bare days for the open Wednesdays, a standing rule. */
    private const string NOVEMBER = <<<'TXT'
Here is the schedule for the month of November:Andrew is every Monday and Tuesday

2 Bellshill 1
3 Gleneagles
4
5 Bellshill 2
6 Dunfermline 1

9 Edinburgh
10 Bute
11
12Lochnagar
13 Inchkeith

16 E. Kilbride
17 Dunfer 2
18
19 Anniesland
20 Bellshill F

23 Bearsden
24 Blantyre
25
26 Clydebank
27  Mansions
TXT;

    private function october(): ParsedSchedule
    {
        return (new RunScheduleParser())->parse(self::OCTOBER, 2026);
    }

    private function november(): ParsedSchedule
    {
        return (new RunScheduleParser())->parse(self::NOVEMBER, 2026);
    }

    private function entry(ParsedSchedule $schedule, string $date): ScheduleEntry
    {
        foreach ($schedule->entries as $entry) {
            if ($entry->date === $date) {
                return $entry;
            }
        }
        Assert::true(false, 'no entry for ' . $date);
        throw new \LogicException('unreachable');
    }

    public function theMonthComesFromTheHeadingInEitherWording(): void
    {
        Assert::same($this->october()->month, 10);
        Assert::same($this->november()->month, 11);
        Assert::same($this->october()->year, 2026);
    }

    public function octoberReadsAsSixteenRunsWithTheirCrews(): void
    {
        $schedule = $this->october();

        Assert::same(count($schedule->entries), 16);
        Assert::same(count($schedule->runDays()), 16);
        Assert::same($schedule->warnings, []);
        $first = $this->entry($schedule, '2026-10-05');
        Assert::same($first->runName, 'Bellshill 2');
        Assert::same($first->crewNames, ['Andy', 'Dan']);
        Assert::same($first->weekday, 1);
        $last = $this->entry($schedule, '2026-10-30');
        Assert::same($last->runName, 'Mansions');
        Assert::same($last->crewNames, ['Ross', 'Dan']);
    }

    public function dittoMarksMeanTheSameCrewAsTheDayAbove(): void
    {
        $day = $this->entry($this->october(), '2026-10-06');

        Assert::same($day->runName, 'Dunfermline 1');
        Assert::same($day->crewNames, ['Andy', 'Dan']);
        Assert::true($day->crewFromDitto);
        Assert::false($this->entry($this->october(), '2026-10-05')->crewFromDitto);
    }

    public function aCrewOnTheLineBelowBelongsToTheDayAbove(): void
    {
        $day = $this->entry($this->october(), '2026-10-13');

        Assert::same($day->runName, 'Bute');
        Assert::same($day->crewNames, ['Ross', 'Andy', 'Dan']);
    }

    public function aSmartApostropheAndAnAbbreviationAreKeptAsWritten(): void
    {
        $schedule = $this->october();

        Assert::same($this->entry($schedule, '2026-10-09')->runName, "Craig's Edinburgh");
        Assert::same($this->entry($schedule, '2026-10-19')->runName, 'E. Kilbride');
        Assert::same($this->entry($schedule, '2026-10-20')->runName, 'Dunf 2');
        Assert::same($this->entry($schedule, '2026-10-22')->runName, 'Bellshill F');
    }

    public function novemberReadsWithoutDashesOrSpacesAndKeepsTheOpenWednesdays(): void
    {
        $schedule = $this->november();

        Assert::same(count($schedule->entries), 20);
        Assert::same(count($schedule->runDays()), 16);
        Assert::same($schedule->warnings, []);
        Assert::same($this->entry($schedule, '2026-11-12')->runName, 'Lochnagar');
        Assert::same($this->entry($schedule, '2026-11-27')->runName, 'Mansions');
        foreach (['2026-11-04', '2026-11-11', '2026-11-18', '2026-11-25'] as $wednesday) {
            $day = $this->entry($schedule, $wednesday);
            Assert::true($day->isOpenDay());
            Assert::same($day->weekday, 3);
            Assert::same($day->allCrewNames(), []);
        }
    }

    public function aStandingRuleAddsThatWorkerToEveryRunOnThoseWeekdays(): void
    {
        $schedule = $this->november();

        Assert::same(count($schedule->rules), 1);
        Assert::same($schedule->rules[0]->name, 'Andrew');
        Assert::same($schedule->rules[0]->weekdays, [1, 2]);
        // every Monday and Tuesday run has Andrew; no other day does
        $mondayOrTuesday = ['2026-11-02', '2026-11-03', '2026-11-09', '2026-11-10', '2026-11-16', '2026-11-17', '2026-11-23', '2026-11-24'];
        foreach ($schedule->runDays() as $day) {
            Assert::same(in_array($day->date, $mondayOrTuesday, true), ['Andrew'] === $day->ruleCrewNames);
        }
        Assert::same($this->entry($schedule, '2026-11-05')->ruleCrewNames, []);
    }

    public function aRuleDoesNotDuplicateSomeoneAlreadyOnTheWrittenCrew(): void
    {
        $text = "November schedule\nAndy is every Monday\n2 - Bellshill 1 (Andy & Dan)\n9 - Bute (Ross & Dan)";

        $schedule = (new RunScheduleParser())->parse($text, 2026);

        Assert::same($this->entry($schedule, '2026-11-02')->ruleCrewNames, []);
        Assert::same($this->entry($schedule, '2026-11-09')->ruleCrewNames, ['Andy']);
        Assert::same($this->entry($schedule, '2026-11-09')->allCrewNames(), ['Ross', 'Dan', 'Andy']);
    }

    public function theRunAndCrewNamesAreListedOnceInOrderOfAppearance(): void
    {
        $schedule = $this->october();

        Assert::same(array_slice($schedule->runNames(), 0, 4), ['Bellshill 2', 'Dunfermline 1', 'Bellshill 1', "Craig's Edinburgh"]);
        Assert::same($schedule->crewNames(), ['Andy', 'Dan', 'Ross', 'Callum']);
    }

    public function noMonthInTheTextFallsBackToTheMonthGivenOrAsksForOne(): void
    {
        $text = "5 - Bellshill 2 (Andy & Dan)";

        $given = (new RunScheduleParser())->parse($text, 2026, 3);
        $none = (new RunScheduleParser())->parse($text, 2026);

        Assert::same($given->month, 3);
        Assert::same($given->entries[0]->date, '2026-03-05');
        Assert::same($none->month, 0);
        Assert::same($none->entries, []);
        Assert::same(count($none->warnings), 1);
    }

    public function aDayThatDoesNotExistIsSkippedWithAWarning(): void
    {
        $schedule = (new RunScheduleParser())->parse("November schedule\n31 - Bute (Ross)\n30 - Mansions (Ross)", 2026);

        Assert::same(count($schedule->entries), 1);
        Assert::same($schedule->entries[0]->date, '2026-11-30');
        Assert::same(count($schedule->warnings), 1);
    }

    public function aLineItCannotReadIsReportedNotGuessed(): void
    {
        $schedule = (new RunScheduleParser())->parse("November schedule\n2 - Bellshill 1 (Ross)\nweather permitting!", 2026);

        Assert::same(count($schedule->entries), 1);
        Assert::same(count($schedule->warnings), 1);
        Assert::true(str_contains($schedule->warnings[0], 'weather permitting'));
    }

    public function theSameDateTwiceIsFlagged(): void
    {
        $schedule = (new RunScheduleParser())->parse("November schedule\n2 - Bellshill 1 (Ross)\n2 - Bute (Dan)", 2026);

        Assert::same(count($schedule->entries), 2);
        Assert::same(count($schedule->warnings), 1);
    }

    public function crewSeparatorsOfAnyKindAreUnderstood(): void
    {
        $schedule = (new RunScheduleParser())->parse(
            "November schedule\n2 - A (ross.dan)\n3 - B (Ross, Dan and Callum)\n4 - C (Ross & Dan)\n5 - D (ROSS/DAN)",
            2026,
        );

        Assert::same($schedule->entries[0]->crewNames, ['Ross', 'Dan']);
        Assert::same($schedule->entries[1]->crewNames, ['Ross', 'Dan', 'Callum']);
        Assert::same($schedule->entries[2]->crewNames, ['Ross', 'Dan']);
        Assert::same($schedule->entries[3]->crewNames, ['Ross', 'Dan']);
    }

    public function aDayWithNoCrewIsLeftWithNoCrewNotBorrowed(): void
    {
        $schedule = (new RunScheduleParser())->parse("November schedule\n2 - A (Ross & Dan)\n3 - B", 2026);

        Assert::same($schedule->entries[1]->crewNames, []);
        Assert::false($schedule->entries[1]->crewFromDitto);
    }

    public function aMonthOutsideOneToTwelveIsNotAMonth(): void
    {
        foreach ([0, 13] as $month) {
            $schedule = (new RunScheduleParser())->parse("2 - A (Ross)\n3 - B", 2026, $month);

            Assert::same($schedule->entries, []);
            Assert::same(count($schedule->warnings), 1);
        }
    }}
