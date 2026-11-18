<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Telegram;

use Testo\Assert;
use Testo\Test;

/**
 * The profile page and the link page use these English texts; a key missing from either English file shows
 * its raw name to the user.
 */
#[Test]
final class LinkTranslationKeysTest
{
    private const array KEYS = [
        'telegram.link.heading',
        'telegram.linked',
        'telegram.not.linked',
        'telegram.link.button',
        'telegram.unlink.button',
        'telegram.link.intro',
        'telegram.link.open',
        'telegram.link.expires',
        'telegram.link.after',
        'telegram.link.back',
        'telegram.link.unavailable',
        'telegram.unlinked',
        'telegram.bot.api.webhook.secret.token',
        'telegram.bot.api.webhook.secret.token.not.set',
    ];

    /**
     * @return list<string>
     */
    private function missingFrom(string $relativePath): array
    {
        // read as text: src/Invoice/Language/English/app_lang.php is generated and assigns $lang, it returns nothing
        $source = (string) file_get_contents(dirname(__DIR__, 4) . '/' . $relativePath);
        return array_values(array_filter(
            self::KEYS,
            static fn (string $key): bool => preg_match("/^\s*'" . preg_quote($key, '/') . "' => '.+',\$/m", $source) !== 1,
        ));
    }

    public function everyKeyHasEnglishTextInTheMessagesFile(): void
    {
        Assert::same($this->missingFrom('resources/messages/en/app.php'), []);
    }

    public function everyKeyHasEnglishTextInTheLanguageFile(): void
    {
        Assert::same($this->missingFrom('src/Invoice/Language/English/app_lang.php'), []);
    }
}
