<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Telegram;

use Testo\Assert;
use Testo\Test;

/**
 * The Telegram tab of Settings, rendered for real: its eleven steps come in order, every setting it saves is
 * still a field of the form, the pages its buttons open exist, and the one control that did nothing (the
 * payment notifications checkbox, which no code reads) is gone.
 *
 * @see resources/views/invoice/setting/views/partial_settings_telegram.php
 */
#[Test]
final class TelegramSettingsTabTest
{
    private const string PARTIAL = '/resources/views/invoice/setting/views/partial_settings_telegram.php';

    /** every setting the tab saves: the settings save reads them by these names */
    private const array FIELDS = [
        'enable_telegram',
        'telegram_token',
        'telegram_webhook_secret_token',
        'telegram_chat_id',
        'telegram_test_message_use',
        'telegram_provider_token',
        'telegram_payment_method_id',
    ];

    /**
     * @param array<string, string> $settings
     */
    private function render(array $settings = []): string
    {
        $s = new class ($settings) {
            /** @param array<string, string> $settings */
            public function __construct(private readonly array $settings)
            {
            }

            public function getSetting(string $key): string
            {
                return $this->settings[$key] ?? '';
            }
        };
        $translator = new class {
            public function translate(string $id): string
            {
                return '[' . $id . ']';
            }
        };
        $urlGenerator = new class {
            public function generate(string $name): string
            {
                return '/invoice/' . $name;
            }

            public function generateAbsolute(string $name): string
            {
                return 'https://invoice.example.test/' . $name;
            }
        };

        // the view reads these variables itself, which Psalm cannot see, and its path is built at run time
        /** @psalm-suppress UnusedVariable, UnresolvableInclude */
        $render = static function (object $s, object $translator, object $urlGenerator, string $file): string {
            $body = [];
            $payment_methods = [];
            ob_start();
            require $file;
            return (string) ob_get_clean();
        };

        return $render($s, $translator, $urlGenerator, dirname(__DIR__, 4) . self::PARTIAL);
    }

    public function theStepsAreNumberedOneToElevenInOrder(): void
    {
        preg_match_all('#<b>(\d+)\.</b>#', $this->render(), $m);

        Assert::same($m[1], ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11']);
    }

    public function everySettingTheTabSavesIsStillAFieldOfTheForm(): void
    {
        $html = $this->render();

        foreach (self::FIELDS as $field) {
            Assert::true(str_contains($html, 'name="settings[' . $field . ']"'), $field);
        }
    }

    public function theControlThatDidNothingIsGone(): void
    {
        Assert::false(str_contains($this->render(), 'telegram_payment_notifications'));
    }

    public function theSecretsAreShownAsPasswordFieldsThatAreNotAutofilled(): void
    {
        $html = $this->render(['telegram_token' => 'abc']);

        foreach (['telegram_token', 'telegram_webhook_secret_token', 'telegram_chat_id', 'telegram_provider_token'] as $field) {
            Assert::true(
                preg_match('#<input [^>]*type="password"[^>]*name="settings\[' . $field . '\]"|<input [^>]*name="settings\[' . $field . '\]"[^>]*type="password"#', $html) === 1,
                $field,
            );
        }
        Assert::true(substr_count($html, 'autocomplete="off"') >= 4);
    }

    public function aSavedValueIsEncodedInTheField(): void
    {
        $html = $this->render(['telegram_token' => '"><script>alert(1)</script>']);

        Assert::false(str_contains($html, '<script>alert(1)</script>'));
        Assert::true(str_contains($html, '&lt;script&gt;'));
    }

    public function theButtonsOpenThePagesTheStepsTalkAbout(): void
    {
        $html = $this->render();

        foreach ([
            '/invoice/telegram/index',
            '/invoice/telegram/getWebhookinfo',
            '/invoice/telegram/deleteWebhook',
            '/invoice/telegram/getUpdates',
            '/invoice/paymentmethod/index',
        ] as $url) {
            Assert::true(str_contains($html, 'href="' . $url . '"'), $url);
        }
    }

    public function theWebhookInfoButtonOpensInANewTab(): void
    {
        Assert::true(preg_match('#<a [^>]*target="_blank"[^>]*href="/invoice/telegram/getWebhookinfo"|<a [^>]*href="/invoice/telegram/getWebhookinfo"[^>]*target="_blank"#', $this->render()) === 1);
    }

    public function theWebhookAddressOfThisSiteIsShown(): void
    {
        Assert::true(str_contains($this->render(), '<code>https://invoice.example.test/telegram/webhook</code>'));
    }

    public function theProvidersModalTheProviderLinkOpensIsStillThere(): void
    {
        $html = $this->render();

        Assert::true(str_contains($html, 'id="telegram-providers"'));
        Assert::true(str_contains($html, 'href="#telegram-providers"'));
    }

    public function everyTranslationKeyTheTabUsesHasEnglishTextInBothFiles(): void
    {
        $partial = (string) file_get_contents(dirname(__DIR__, 4) . self::PARTIAL);
        preg_match_all("/'(telegram\\.[a-z0-9.]+)'/", $partial, $m);
        $keys = array_values(array_unique($m[1]));
        Assert::true(count($keys) >= 30, 'the scan found the keys');

        foreach (['resources/messages/en/app.php', 'src/Invoice/Language/English/app_lang.php'] as $file) {
            $source = (string) file_get_contents(dirname(__DIR__, 4) . '/' . $file);
            $missing = array_values(array_filter(
                $keys,
                static fn (string $key): bool => preg_match("/^\\s*'" . preg_quote($key, '/') . "'\\s*=>\\s*(\\n\\s*)?'/m", $source) !== 1,
            ));
            Assert::same($missing, [], $file);
        }
    }
}
