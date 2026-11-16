<?php

declare(strict_types=1);

namespace App\Auth;

/**
 * Which language name a freshly confirmed signup's Client is stored with.
 *
 * The confirmation link carries a `language` segment (e.g. "English"). Anyone holding the link can
 * rewrite it, so it must never be stored as it came. The language the server recorded for the
 * account at signup is preferred; the link's value is only used when it is one of the known names.
 */
final class SignupLanguage
{
    public const string FALLBACK = 'English';

    /**
     * @param array<array-key, mixed> $known the configured languages (key => name, e.g. 'en' => 'English')
     */
    public static function resolve(string $fromLink, ?string $stored, array $known): string
    {
        $names = array_values(array_filter($known, 'is_string'));
        if (null !== $stored && in_array($stored, $names, true)) {
            return $stored;
        }
        return in_array($fromLink, $names, true) ? $fromLink : self::FALLBACK;
    }
}
