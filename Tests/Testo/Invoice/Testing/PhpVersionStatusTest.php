<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Testing;

use App\Invoice\Testing\PhpVersionStatus;
use Testo\Assert;
use Testo\Test;

/**
 * @see PhpVersionStatus
 */
#[Test]
final class PhpVersionStatusTest
{
    private const array LATEST = ['8.4' => '8.4.28', '8.5' => '8.5.13', '8.6' => '8.6.0'];

    /** @return list<array{where: string, branch: string, version: string|null}> */
    private function runs(?string $windows85): array
    {
        return [
            ['where' => 'ubuntu', 'branch' => '8.5', 'version' => '8.5.13'],
            ['where' => 'windows', 'branch' => '8.5', 'version' => $windows85],
        ];
    }

    public function theVersionIsTheOnlyKeyInPhpNetsPerBranchResponse(): void
    {
        Assert::same(PhpVersionStatus::latestFromReleaseJson('{"8.5.13":{"tags":["security"],"date":"19 Nov 2026"}}'), '8.5.13');
        Assert::same(PhpVersionStatus::latestFromReleaseJson('{"8.6.0":{}}'), '8.6.0');
    }

    public function anUnusableResponseGivesNoVersionRatherThanAGuess(): void
    {
        Assert::null(PhpVersionStatus::latestFromReleaseJson(''));
        Assert::null(PhpVersionStatus::latestFromReleaseJson('not json'));
        Assert::null(PhpVersionStatus::latestFromReleaseJson('[]'));
        Assert::null(PhpVersionStatus::latestFromReleaseJson('{"latest":{}}'));
    }

    public function aLegOnAnOlderPatchIsBehindAndOnesOnTheLatestAreCurrent(): void
    {
        $rows = PhpVersionStatus::rows(self::LATEST, [['ubuntu', '8.5'], ['windows', '8.5']], $this->runs('8.5.12'));

        Assert::same($rows[0]['state'], PhpVersionStatus::CURRENT);
        Assert::same($rows[1]['state'], PhpVersionStatus::BEHIND);
        Assert::same($rows[1]['running'], '8.5.12');
        Assert::same($rows[1]['latest'], '8.5.13');
    }

    public function aPreReleaseOfTheLatestIsBehindIt(): void
    {
        $rows = PhpVersionStatus::rows(self::LATEST, [['ubuntu', '8.6']], [
            ['where' => 'ubuntu', 'branch' => '8.6', 'version' => '8.6.0RC7'],
        ]);

        Assert::same($rows[0]['state'], PhpVersionStatus::BEHIND);
    }

    public function aLegThatNeverReportedOrWhoseLatestIsUnknownIsUnknownNotCurrent(): void
    {
        $missing = PhpVersionStatus::rows(self::LATEST, [['alpine', '8.5']], []);
        $noLatest = PhpVersionStatus::rows(['8.5' => null], [['ubuntu', '8.5']], $this->runs('8.5.13'));
        $emptyVersion = PhpVersionStatus::rows(self::LATEST, [['windows', '8.5']], $this->runs(null));

        Assert::same($missing[0]['state'], PhpVersionStatus::UNKNOWN);
        Assert::same($noLatest[0]['state'], PhpVersionStatus::UNKNOWN);
        Assert::same($emptyVersion[0]['state'], PhpVersionStatus::UNKNOWN);
    }

    public function theBadgeIsRedAndNamesWhatIsBehind(): void
    {
        $rows = PhpVersionStatus::rows(self::LATEST, [['ubuntu', '8.5'], ['windows', '8.5']], $this->runs('8.5.12'));
        $badge = PhpVersionStatus::badge($rows, '2026-11-22');

        Assert::same($badge['color'], 'red');
        Assert::same($badge['message'], 'BEHIND: windows 8.5 (8.5.12 < 8.5.13)');
        Assert::same($badge['label'], 'PHP in CI (2026-11-22)');
        Assert::same($badge['schemaVersion'], 1);
    }

    public function theBadgeIsGreenOnlyWhenEveryLegIsCurrent(): void
    {
        $rows = PhpVersionStatus::rows(self::LATEST, [['ubuntu', '8.5'], ['windows', '8.5']], $this->runs('8.5.13'));

        Assert::same(PhpVersionStatus::badge($rows, '2026-11-22')['color'], 'brightgreen');
        Assert::same(PhpVersionStatus::badge($rows, '2026-11-22')['message'], 'all on latest');
    }

    public function theBadgeIsYellowNeverGreenWhenALegIsUnchecked(): void
    {
        $rows = PhpVersionStatus::rows(self::LATEST, [['ubuntu', '8.5'], ['alpine', '8.5']], $this->runs('8.5.13'));
        $badge = PhpVersionStatus::badge($rows, '2026-11-22');

        Assert::same($badge['color'], 'yellow');
        Assert::same($badge['message'], 'unverified: 1 of 2 not checked');
        Assert::same(PhpVersionStatus::badge([], '2026-11-22')['color'], 'yellow');
    }

    public function behindOutranksUnknownBecauseItIsTheActionableOne(): void
    {
        $rows = PhpVersionStatus::rows(self::LATEST, [['windows', '8.5'], ['alpine', '8.5']], $this->runs('8.5.12'));

        Assert::same(PhpVersionStatus::badge($rows, '2026-11-22')['color'], 'red');
    }

    public function theSummaryListsEveryLegIncludingThoseNotReported(): void
    {
        $rows = PhpVersionStatus::rows(self::LATEST, [['windows', '8.5'], ['alpine', '8.5']], $this->runs('8.5.12'));
        $markdown = PhpVersionStatus::summaryMarkdown($rows, '2026-11-22');

        Assert::true(str_contains($markdown, '| windows | 8.5 | 8.5.12 | 8.5.13 | behind |'));
        Assert::true(str_contains($markdown, '| alpine | 8.5 | not reported | 8.5.13 | unknown |'));
    }
}
