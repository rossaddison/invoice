<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Invoice\Inv\TaxPointNoteVisibility;
use Testo\Assert;
use Testo\Test;

/**
 * The Tax Point explanation on inv/edit is optional: UK locale only, and only when ticked on Settings > VAT.
 */
#[Test]
final class TaxPointNoteVisibilityTest
{
    public function isShownForTheUkLocaleWhenTheSettingIsTicked(): void
    {
        Assert::true(TaxPointNoteVisibility::isVisible('en', 'gb', '1'));
    }

    public function isHiddenWhenTheSettingIsUnticked(): void
    {
        Assert::false(TaxPointNoteVisibility::isVisible('en', 'gb', '0'));
    }

    public function isHiddenWhenTheSettingHasNeverBeenSaved(): void
    {
        Assert::false(TaxPointNoteVisibility::isVisible('en', 'gb', null));
        Assert::false(TaxPointNoteVisibility::isVisible('en', 'gb', ''));
    }

    public function isHiddenForANonUkLocaleEvenWhenTicked(): void
    {
        Assert::false(TaxPointNoteVisibility::isVisible('de', 'de', '1'));
    }

    public function isHiddenWhenTheEnglishLocaleFlagIsNotTheUkFlag(): void
    {
        Assert::false(TaxPointNoteVisibility::isVisible('en', 'us', '1'));
        Assert::false(TaxPointNoteVisibility::isVisible('en', null, '1'));
    }

    public function usesTheSettingKeyThatTheVatTabSaves(): void
    {
        Assert::same(TaxPointNoteVisibility::SETTING_KEY, 'show_tax_point_note');
    }
}
