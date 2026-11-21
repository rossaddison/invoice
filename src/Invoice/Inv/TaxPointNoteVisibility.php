<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

/**
 * Decides whether the Tax Point explanation is shown on the inv/edit form.
 *
 * Tax point is a UK VAT concept (it references UK VAT reporting rules), so it is only relevant for the
 * GBP/UK locale: the session locale is 'en' and that locale's own flag is 'gb' (belt-and-braces against the
 * flag mapping drifting from the locale code). It is also optional: it appears only when the
 * "Show Tax Point information" box is ticked on the Settings > VAT tab, so an install that has not ticked it
 * (the default) shows no tax point information.
 */
final class TaxPointNoteVisibility
{
    public const string SETTING_KEY = 'show_tax_point_note';

    public static function isVisible(string $locale, ?string $localeFlag, ?string $setting): bool
    {
        return $locale === 'en' && $localeFlag === 'gb' && $setting === '1';
    }
}
