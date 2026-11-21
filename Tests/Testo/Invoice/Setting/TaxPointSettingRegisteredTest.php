<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Setting;

use App\Invoice\Inv\TaxPointNoteVisibility;
use App\Invoice\Setting\SettingRepository;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;

/**
 * A setting is not finished when its checkbox works. This project registers each one in three places, and
 * show_tax_point_note was first added with only the checkbox:
 *
 * 1. its default, in the first-run defaults InvoiceController installs (InvoiceInstallTrait);
 * 2. its tooltip entry (SettingTooltipTrait), which feeds the info icon next to the field;
 * 3. the settings tab itself (partial_settings_vat_registered.php), under the same key.
 *
 * The first-run installer wipes every setting (secret keys included) as part of installing, so it is read as
 * source here and never executed.
 */
#[Test]
final class TaxPointSettingRegisteredTest
{
    private const string KEY = 'show_tax_point_note';

    private function root(): string
    {
        return dirname(__DIR__, 4);
    }

    private function repository(): SettingRepository
    {
        return (new ReflectionClass(SettingRepository::class))->newInstanceWithoutConstructor();
    }

    public function theKeyIsTheOneTheVisibilityRuleReads(): void
    {
        Assert::same(TaxPointNoteVisibility::SETTING_KEY, self::KEY);
    }

    public function itHasADefaultOfOffInTheFirstRunSettingsInvoiceControllerInstalls(): void
    {
        $source = (string) file_get_contents($this->root() . '/src/Invoice/Trait/InvoiceInstallTrait.php');

        Assert::same(preg_match("/'" . self::KEY . "'\\s*=>\\s*0\\s*,/", $source), 1);
    }

    public function itHasATooltipEntryWithAWhyAndAWhere(): void
    {
        $tooltips = $this->repository()->tooltipArray();

        Assert::true(isset($tooltips[self::KEY]));
        /** @var array<string, string> $entry */
        $entry = $tooltips[self::KEY];
        Assert::true(($entry['why'] ?? '') !== '');
        Assert::true(($entry['where'] ?? '') !== '');
    }

    public function theInfoIconNextToTheFieldIsRenderedFromThatEntry(): void
    {
        $icon = $this->repository()->infoIcon(self::KEY, true);

        Assert::true(str_contains($icon, 'bi-info-circle'));
        Assert::true(str_contains($icon, 'Tax Point'));
    }

    public function theVatTabSavesAndLabelsTheSameKeyAndShowsTheIcon(): void
    {
        $view = (string) file_get_contents(
            $this->root() . '/resources/views/invoice/setting/views/partial_settings_vat_registered.php',
        );

        Assert::true(str_contains($view, "settings[" . self::KEY . "]"));
        Assert::true(str_contains($view, "infoIcon('" . self::KEY . "')"));
    }
}
