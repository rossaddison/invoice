<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Trait;

use App\Invoice\Enum\ServiceType;
use Psr\Http\Message\ServerRequestInterface as Request;

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

    /**
     * The settings posted from the settings form, with the checkboxes[] ones (hidden HomeCare columns
     * and the services offered) joined into plain strings; null when the post carried no settings.
     *
     * @return array<string, string>|null
     */
    private function postedSettingsWithCheckboxesJoined(Request $request): ?array
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            return null;
        }
        /** @var array<string, string|list<string>> $settings */
        $settings = (array) $body['settings'];
        foreach (['homecare_hidden_inv_columns', 'homecare_hidden_inv_guest_columns', ServiceType::SETTING_KEY] as $key) {
            $this->joinCheckboxArraySetting($settings, $key);
        }
        /** @var array<string, string> $settings */
        return $settings;
    }
}
