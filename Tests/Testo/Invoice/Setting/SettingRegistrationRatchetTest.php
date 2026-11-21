<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Setting;

use App\Invoice\Setting\SettingRepository;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;

/**
 * A setting is registered in three places: a field on a settings tab, a default in the first-run settings that
 * InvoiceController installs (InvoiceInstallTrait), and a tooltip entry (SettingTooltipTrait). Most of the settings
 * that already existed lack one or both, and show_tax_point_note was first added with only the field.
 *
 * This is a ratchet. The settings that lack a default or a tooltip today are frozen in two text files, and the tests
 * fail when
 *  - a setting NOT in a file is on a tab without a default / a tooltip (a new setting has been added unregistered);
 *  - a setting IS in a file but has since been given one (delete it from the file so the list only ever shrinks).
 *
 * The first-run installer wipes every setting as part of installing, so its defaults are read as source, never run.
 */
#[Test]
final class SettingRegistrationRatchetTest
{
    private const string WITHOUT_DEFAULT = __DIR__ . '/Fixtures/settings-without-default.txt';
    private const string WITHOUT_TOOLTIP = __DIR__ . '/Fixtures/settings-without-tooltip.txt';

    private function root(): string
    {
        return dirname(__DIR__, 4);
    }

    /** @return list<string> every setting key that has a field on a settings tab */
    private function tabKeys(): array
    {
        $keys = [];
        $files = glob($this->root() . '/resources/views/invoice/setting/views/*.php');
        foreach ($files === false ? [] : $files as $file) {
            preg_match_all('/settings\[([a-z0-9_]+)\]/', (string) file_get_contents($file), $matches);
            foreach ($matches[1] as $key) {
                $keys[$key] = true;
            }
        }
        return array_map('strval', array_keys($keys));
    }

    /** @return array<string, true> the keys in the first-run defaults array */
    private function defaults(): array
    {
        $source = (string) file_get_contents($this->root() . '/src/Invoice/Trait/InvoiceInstallTrait.php');
        $start = strpos($source, '$default_settings = [');
        $end = $start === false ? false : strpos($source, "\n        ];", $start);
        Assert::true($start !== false && $end !== false);
        preg_match_all("/^\\s*'([a-z0-9_]+)'\\s*=>/m", substr($source, $start, $end - $start), $matches);
        return array_fill_keys($matches[1], true);
    }

    /** @return array<string, true> */
    private function tooltips(): array
    {
        $repository = (new ReflectionClass(SettingRepository::class))->newInstanceWithoutConstructor();
        return array_fill_keys(array_map('strval', array_keys($repository->tooltipArray())), true);
    }

    /** @return list<string> */
    private function baseline(string $file): array
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return $lines === false ? [] : $lines;
    }

    /**
     * @param array<string, true> $registered
     * @return list<string> keys on a tab that are not registered
     */
    private function unregistered(array $registered): array
    {
        return array_values(array_filter($this->tabKeys(), static fn (string $key): bool => !isset($registered[$key])));
    }

    public function aSettingAddedToATabHasADefaultInTheFirstRunSettings(): void
    {
        $new = array_values(array_diff($this->unregistered($this->defaults()), $this->baseline(self::WITHOUT_DEFAULT)));

        Assert::same($new, [], 'Add these to the defaults in InvoiceInstallTrait (installDefaultSettingsOnFirstRun)');
    }

    public function aSettingAddedToATabHasATooltipEntry(): void
    {
        $new = array_values(array_diff($this->unregistered($this->tooltips()), $this->baseline(self::WITHOUT_TOOLTIP)));

        Assert::same($new, [], 'Add these to the tooltip entries in SettingTooltipTrait');
    }

    public function theDefaultBaselineHoldsOnlySettingsThatStillLackADefault(): void
    {
        $stale = array_values(array_diff($this->baseline(self::WITHOUT_DEFAULT), $this->unregistered($this->defaults())));

        Assert::same($stale, [], 'These now have a default: delete them from settings-without-default.txt');
    }

    public function theTooltipBaselineHoldsOnlySettingsThatStillLackATooltip(): void
    {
        $stale = array_values(array_diff($this->baseline(self::WITHOUT_TOOLTIP), $this->unregistered($this->tooltips())));

        Assert::same($stale, [], 'These now have a tooltip: delete them from settings-without-tooltip.txt');
    }

    public function theTabScanFindsTheSettingsItIsMeantToGuard(): void
    {
        Assert::true(count($this->tabKeys()) > 150);
        Assert::true(in_array('show_tax_point_note', $this->tabKeys(), true));
        Assert::true(isset($this->defaults()['enable_vat_registration']));
        Assert::true(isset($this->tooltips()['enable_vat_registration']));
    }
}
