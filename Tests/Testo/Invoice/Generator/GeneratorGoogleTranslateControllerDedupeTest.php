<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Generator;

use App\Invoice\Generator\GeneratorGoogleTranslateController;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;

/**
 * Covers the two pure helpers `performGoogleTranslation()` uses to avoid
 * sending the Cloud Translation API the same string once per key: the
 * Translation API bills every character sent, including exact repeats,
 * and this message file genuinely has keys that share identical English
 * text (e.g. several `*.save` keys all reading "Save"). The network call
 * itself isn't exercised here — these two methods are the deterministic
 * dedup-then-reconstruct logic either side of it. Reflection-constructed
 * via newInstanceWithoutConstructor(), matching
 * RedirectControllerBotDetectionTest's established pattern for testing a
 * private method in isolation on a controller with injected dependencies
 * this test never needs.
 */
#[Test]
final class GeneratorGoogleTranslateControllerDedupeTest
{
    private function controller(): GeneratorGoogleTranslateController
    {
        $reflectionClass = new ReflectionClass(GeneratorGoogleTranslateController::class);

        /** @var GeneratorGoogleTranslateController */
        return $reflectionClass->newInstanceWithoutConstructor();
    }

    /**
     * @param array<array-key, string> $content
     * @return list<string>
     */
    private function uniqueTranslatableValues(array $content): array
    {
        $reflectionClass = new ReflectionClass(GeneratorGoogleTranslateController::class);
        $method = $reflectionClass->getMethod('uniqueTranslatableValues');

        /** @var list<string> */
        return $method->invoke($this->controller(), $content);
    }

    /**
     * @param list<array-key> $keys
     * @param list<string> $values
     * @param array<string, string> $valueTranslationMap
     * @return array<array-key, string>
     */
    private function combineKeysWithTranslatedValues(array $keys, array $values, array $valueTranslationMap): array
    {
        $reflectionClass = new ReflectionClass(GeneratorGoogleTranslateController::class);
        $method = $reflectionClass->getMethod('combineKeysWithTranslatedValues');

        /** @var array<array-key, string> */
        return $method->invoke($this->controller(), $keys, $values, $valueTranslationMap);
    }

    public function collapsesDuplicateValuesToOneEntryEach(): void
    {
        $content = [
            'button.save' => 'Save',
            'form.save' => 'Save',
            'modal.save' => 'Save',
            'button.cancel' => 'Cancel',
        ];

        $unique = $this->uniqueTranslatableValues($content);

        // 4 keys, but only 2 distinct strings -- this is exactly the
        // reduction in what gets billed by the Translation API.
        Assert::same(count($unique), 2);
        Assert::true(in_array('Save', $unique, true));
        Assert::true(in_array('Cancel', $unique, true));
    }

    public function preservesEveryKeyWhenThereAreNoDuplicates(): void
    {
        $content = [
            'a' => 'Alpha',
            'b' => 'Beta',
            'c' => 'Gamma',
        ];

        $unique = $this->uniqueTranslatableValues($content);

        Assert::same(count($unique), 3);
    }

    public function mapsEveryKeySharingAValueBackToTheSameTranslation(): void
    {
        $keys = ['button.save', 'form.save', 'modal.save', 'button.cancel'];
        $values = ['Save', 'Save', 'Save', 'Cancel'];
        $valueTranslationMap = [
            'Save' => 'Guardar',
            'Cancel' => 'Cancelar',
        ];

        $combined = $this->combineKeysWithTranslatedValues($keys, $values, $valueTranslationMap);

        Assert::same($combined, [
            'button.save' => 'Guardar',
            'form.save' => 'Guardar',
            'modal.save' => 'Guardar',
            'button.cancel' => 'Cancelar',
        ]);
    }

    public function roundTripsThroughBothHelpersUnchanged(): void
    {
        // End-to-end (minus the actual network call): dedupe, pretend to
        // translate each unique value, then reconstruct -- every
        // original key must still be present, with duplicate keys
        // ending up with an identical translated value.
        $content = [
            'x' => 'Yes',
            'y' => 'No',
            'z' => 'Yes',
        ];
        $keys = array_keys($content);
        $values = array_values($content);

        $unique = $this->uniqueTranslatableValues($content);
        $fakeTranslated = array_map(static fn (string $v): string => strtoupper($v) . '!', $unique);
        $valueTranslationMap = array_combine($unique, $fakeTranslated);

        $combined = $this->combineKeysWithTranslatedValues($keys, $values, $valueTranslationMap);

        Assert::same($combined, ['x' => 'YES!', 'y' => 'NO!', 'z' => 'YES!']);
    }
}
