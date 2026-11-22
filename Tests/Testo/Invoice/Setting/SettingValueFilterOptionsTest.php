<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Setting;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Setting\Trait\SettingOptionsDataTrait;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * The "Value" filter on setting/debug_index lists every distinct setting value. A select is as wide as its widest
 * option, so one long value (an API key, a URL, a template) stretched the filter and then the whole table past the
 * window, and the page clips what does not fit, taking each row's view, edit and delete buttons with it. The labels
 * are now short; the key of each option stays the full value, which is what the filter matches on.
 */
#[Test]
final class SettingValueFilterOptionsTest
{
    private const int LIMIT = 40;

    /**
     * @param array<string, string> $valuesByKey setting key => stored value
     * @return array<array-key, string> the value filter's options: full value => label
     */
    private function options(array $valuesByKey): array
    {
        $settings = [];
        foreach ($valuesByKey as $key => $value) {
            $setting = new Setting();
            $setting->setSettingKey($key);
            $setting->setSettingValue($value);
            $settings[] = $setting;
        }
        // findAllPreloaded() returns an EntityReader, which is iterated: a Generator is what its getIterator() gives
        $reader = TypedMock::of(EntityReader::class);
        $reader->shouldReceive('getIterator')->andReturnUsing(static function () use ($settings): \Generator {
            yield from $settings;
        });
        $repository = TypedMock::of(SettingRepository::class);
        $repository->shouldReceive('findAllPreloaded')->andReturn($reader);

        $holder = new class {
            use SettingOptionsDataTrait;
        };
        return $holder->optionsDataSettingsValue($repository);
    }

    public function theLimitTheTestsAssumeIsTheOneTheCodeUses(): void
    {
        $holder = new class {
            use SettingOptionsDataTrait;
        };

        Assert::same((new \ReflectionClass($holder))->getConstant('VALUE_FILTER_LABEL_WIDTH'), self::LIMIT);
    }

    public function aLongValueGivesAShortLabelAndKeepsTheFullValueAsTheKey(): void
    {
        $long = str_repeat('abcdefghij', 30);

        $options = $this->options(['storecove_api_key' => $long]);

        Assert::same(array_keys($options), [$long]);
        Assert::true(mb_strwidth($options[$long]) <= self::LIMIT);
        Assert::true(str_ends_with($options[$long], '…'));
        Assert::same(mb_substr($options[$long], 0, 10), 'abcdefghij');
    }

    public function noLabelIsWiderThanTheLimitHoweverLongTheValues(): void
    {
        $options = $this->options([
            'a' => str_repeat('x', 5000),
            'b' => 'https://example.test/' . str_repeat('segment/', 200),
            'c' => '短い' . str_repeat('日本語', 500),
        ]);

        Assert::same(count($options), 3);
        foreach ($options as $label) {
            Assert::true(mb_strwidth($label) <= self::LIMIT);
        }
    }

    public function shortValuesAreShownInFull(): void
    {
        $options = $this->options(['a' => '0', 'b' => 'Arial', 'c' => 'Europe/London']);

        Assert::same($options['Arial'], 'Arial');
        Assert::same($options['Europe/London'], 'Europe/London');
        Assert::same($options[0], '0');
    }

    public function aValueSharedByManySettingsIsListedOnce(): void
    {
        $options = $this->options(['a' => '1', 'b' => '1', 'c' => '1', 'd' => 'Arial', 'e' => 'Arial']);

        Assert::same(count($options), 2);
    }

    public function twoLongValuesWithTheSameStartStayTwoSeparateOptions(): void
    {
        $prefix = str_repeat('same-start-', 20);

        $options = $this->options(['a' => $prefix . 'one', 'b' => $prefix . 'two']);

        Assert::same(count($options), 2);
        Assert::same(array_keys($options), [$prefix . 'one', $prefix . 'two']);
    }
}
