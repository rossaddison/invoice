<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Testing;

use App\Invoice\Testing\PhpVersionStatusReport;
use Testo\Assert;
use Testo\Test;

/**
 * @see PhpVersionStatusReport
 *
 * Reads files only; the workflow does the fetching. Each test works in its own temporary folder, removed afterwards.
 */
#[Test]
final class PhpVersionStatusReportTest
{
    private const string EXPECT = 'ubuntu:8.5,windows:8.5,alpine:8.5';

    private function folder(): string
    {
        $folder = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-version-report-' . bin2hex(random_bytes(6));
        mkdir($folder . DIRECTORY_SEPARATOR . 'runs', 0777, true);
        mkdir($folder . DIRECTORY_SEPARATOR . 'latest', 0777, true);
        return $folder;
    }

    private function remove(string $folder): void
    {
        foreach (['runs', 'latest', ''] as $sub) {
            $directory = $sub === '' ? $folder : $folder . DIRECTORY_SEPARATOR . $sub;
            $files = glob($directory . DIRECTORY_SEPARATOR . '*');
            foreach ($files === false ? [] : $files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($directory);
        }
    }

    private function probe(string $folder, string $where, string $version): void
    {
        file_put_contents(
            $folder . '/runs/' . $where . '-8.5.json',
            '{"where":"' . $where . '","branch":"8.5","version":"' . $version . '"}',
        );
    }

    /** @return array<string, mixed> */
    private function badge(string $folder): array
    {
        /** @var array<string, mixed> $badge */
        $badge = json_decode((string) file_get_contents($folder . '/badge.json'), true);
        return $badge;
    }

    public function itWritesARedBadgeNamingTheLegThatIsBehind(): void
    {
        $folder = $this->folder();
        file_put_contents($folder . '/latest/8.5.json', '{"8.5.13":{"tags":["security"]}}');
        $this->probe($folder, 'ubuntu', '8.5.13');
        $this->probe($folder, 'windows', '8.5.12');
        $this->probe($folder, 'alpine', '8.5.13');

        $markdown = PhpVersionStatusReport::run(
            $folder . '/runs',
            $folder . '/latest',
            self::EXPECT,
            $folder . '/badge.json',
            '',
            '2026-11-22',
        );
        $badge = $this->badge($folder);
        $this->remove($folder);

        Assert::same($badge['color'], 'red');
        Assert::same($badge['message'], 'BEHIND: windows 8.5 (8.5.12 < 8.5.13)');
        Assert::true(str_contains($markdown, '| windows | 8.5 | 8.5.12 | 8.5.13 | behind |'));
    }

    public function itWritesAGreenBadgeWhenEveryLegIsOnTheLatest(): void
    {
        $folder = $this->folder();
        file_put_contents($folder . '/latest/8.5.json', '{"8.5.13":{}}');
        foreach (['ubuntu', 'windows', 'alpine'] as $where) {
            $this->probe($folder, $where, '8.5.13');
        }

        PhpVersionStatusReport::run($folder . '/runs', $folder . '/latest', self::EXPECT, $folder . '/badge.json');
        $badge = $this->badge($folder);
        $this->remove($folder);

        Assert::same($badge['color'], 'brightgreen');
        Assert::same($badge['message'], 'all on latest');
    }

    public function aLegThatNeverReportedMakesTheBadgeYellowNotGreen(): void
    {
        $folder = $this->folder();
        file_put_contents($folder . '/latest/8.5.json', '{"8.5.13":{}}');
        $this->probe($folder, 'ubuntu', '8.5.13');
        $this->probe($folder, 'windows', '8.5.13');

        PhpVersionStatusReport::run($folder . '/runs', $folder . '/latest', self::EXPECT, $folder . '/badge.json');
        $badge = $this->badge($folder);
        $this->remove($folder);

        Assert::same($badge['color'], 'yellow');
        Assert::same($badge['message'], 'unverified: 1 of 3 not checked');
    }

    public function aFailedPhpNetFetchMeansUnknownNotCurrent(): void
    {
        $folder = $this->folder();
        foreach (['ubuntu', 'windows', 'alpine'] as $where) {
            $this->probe($folder, $where, '8.5.13');
        }

        PhpVersionStatusReport::run($folder . '/runs', $folder . '/latest', self::EXPECT, $folder . '/badge.json');
        $badge = $this->badge($folder);
        $this->remove($folder);

        Assert::same($badge['color'], 'yellow');
        Assert::same($badge['message'], 'unverified: 3 of 3 not checked');
    }

    public function anEmptyVersionFromAFailedProbeCountsAsNotReported(): void
    {
        $folder = $this->folder();
        file_put_contents($folder . '/latest/8.5.json', '{"8.5.13":{}}');
        $this->probe($folder, 'ubuntu', '8.5.13');
        $this->probe($folder, 'windows', '8.5.13');
        $this->probe($folder, 'alpine', '');

        PhpVersionStatusReport::run($folder . '/runs', $folder . '/latest', self::EXPECT, $folder . '/badge.json');
        $badge = $this->badge($folder);
        $this->remove($folder);

        Assert::same($badge['color'], 'yellow');
    }

    public function theSummaryIsAppendedToTheFileTheWorkflowPointsAt(): void
    {
        $folder = $this->folder();
        file_put_contents($folder . '/latest/8.5.json', '{"8.5.13":{}}');
        file_put_contents($folder . '/summary.md', "earlier step\n");
        $this->probe($folder, 'ubuntu', '8.5.13');

        PhpVersionStatusReport::run(
            $folder . '/runs',
            $folder . '/latest',
            'ubuntu:8.5',
            $folder . '/badge.json',
            $folder . '/summary.md',
        );
        $summary = (string) file_get_contents($folder . '/summary.md');
        unlink($folder . '/summary.md');
        $this->remove($folder);

        Assert::true(str_starts_with($summary, "earlier step\n"));
        Assert::true(str_contains($summary, '| ubuntu | 8.5 | 8.5.13 | 8.5.13 | current |'));
    }
}
