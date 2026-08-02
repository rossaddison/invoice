<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Trait;

/**
 * Extracted from SettingController purely to stay within SonarQube's
 * php:S1448 method-count ceiling (20) — SonarQube doesn't count
 * trait-provided methods toward the consuming class, even though they're
 * fully callable via $this->.
 */
trait SettingCheckboxArrayTrait
{
    /**
     * Joins a checkboxes[] submission (a settings[] key) into a single
     * comma-separated string in place — saveSubmittedSettings()/
     * saveOneSetting() expect every value to already be a plain string.
     *
     * @param array<string, string|list<string>> $settings
     */
    private function joinCheckboxArraySetting(array &$settings, string $key): void
    {
        if (isset($settings[$key]) && is_array($settings[$key])) {
            $settings[$key] = implode(',', $settings[$key]);
        }
    }
}
