<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Infrastructure\Persistence\CustomField\CustomField;
use App\Infrastructure\Persistence\CustomValue\CustomValue;
use App\Invoice\CustomValue\CustomValueRepository;
use App\Invoice\Helpers\CustomValuesHelper;
use App\Invoice\Setting\SettingRepository;
use DateTimeImmutable;
use Mockery as m;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Custom fields on forms, read-only views and PDFs. The print methods echo HTML, so each test captures the
 * output and checks the markup that matters: the field name the form posts back, the input type, the
 * disabled state of the read-only view, and the stored choices.
 */
#[Test]
final class CustomValuesHelperTest
{
    private const int FIELD_ID = 5;

    private function settings(): SettingRepository
    {
        $settings = (new ReflectionClass(SettingRepository::class))->newInstanceWithoutConstructor(); // NOSONAR: php:S3011
        $settings->settingsArray = ['date_format' => 'Y-m-d'];
        return $settings;
    }

    private function helper(?CustomValueRepository $cvR = null): CustomValuesHelper
    {
        return new CustomValuesHelper($this->settings(), $cvR ?? TypedMock::of(CustomValueRepository::class));
    }

    private function translator(): TranslatorInterface
    {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);
        return $translator;
    }

    private function urlGenerator(): UrlGeneratorInterface
    {
        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        $urlGenerator->shouldReceive('generate')
            ->with('customfield/edit', ['id' => self::FIELD_ID])
            ->andReturn('/customfield/edit/5');
        return $urlGenerator;
    }

    private function formModel(): FormModel
    {
        return new class extends FormModel {
            public string $custom_field_id = '';
        };
    }

    private function field(string $type, bool $required = false, ?string $label = 'My label'): CustomField
    {
        return new CustomField(id: self::FIELD_ID, label: $label, type: $type, required: $required);
    }

    private function value(int $id, string $value, int $fieldId = self::FIELD_ID): CustomValue
    {
        return new CustomValue(id: $id, custom_field_id: $fieldId, value: $value);
    }

    /** @param list<CustomValue> $values */
    private function reader(array $values): EntityReader
    {
        $reader = TypedMock::of(EntityReader::class);
        $reader->shouldReceive('getIterator')->andReturnUsing(static function () use ($values): \Generator {
            yield from $values;
        });
        return $reader;
    }

    private function has(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }

    /** @param callable(): void $print */
    private function capture(callable $print): string
    {
        ob_start();
        $print();
        return (string) ob_get_clean();
    }

    /**
     * @param list<CustomValue> $entityValues
     * @param array<int, list<CustomValue>> $choices
     */
    private function form(
        CustomField $field,
        array $entityValues = [],
        array $choices = [],
        ?CustomValueRepository $cvR = null,
    ): string {
        return $this->capture(fn () => $this->helper($cvR)->printFieldForForm(
            $field,
            $this->formModel(),
            $this->translator(),
            $this->urlGenerator(),
            $entityValues,
            $choices,
        ));
    }

    /** @param list<CustomValue> $entityValues */
    private function view(CustomField $field, array $entityValues = [], ?CustomValueRepository $cvR = null): string
    {
        return $this->capture(fn () => $this->helper($cvR)->printFieldForView($field, $this->formModel(), $entityValues));
    }

    /** @param list<CustomValue> $entityValues */
    private function pdf(CustomField $field, array $entityValues = [], ?CustomValueRepository $cvR = null): string
    {
        return $this->capture(
            fn () => $this->helper($cvR)->printFieldForPdf($this->translator(), $entityValues, $field),
        );
    }

    // ── formatters ───────────────────────────────────────────────────────────

    public function formatDateIsEmptyForNullAndFormatsADate(): void
    {
        Assert::same($this->helper()->formatDate(null), '');
        Assert::same($this->helper()->formatDate(new DateTimeImmutable('2026-11-21')), '2026-11-21');
    }

    public function formatTextReturnsTheTextOrEmptyForNull(): void
    {
        Assert::same($this->helper()->formatText(null), '');
        Assert::same($this->helper()->formatText('hello'), 'hello');
        Assert::same($this->helper()->formatFallback('fallback'), 'fallback');
    }

    public function formatBooleanTranslatesOneAsTrueAndEverythingElseAsFalse(): void
    {
        $translator = $this->translator();

        Assert::same($this->helper()->formatBoolean($translator, '1'), 'true');
        Assert::same($this->helper()->formatBoolean($translator, '0'), 'false');
        Assert::same($this->helper()->formatBoolean($translator, 'maybe'), 'false');
    }

    public function formatAvsGroupsAThirteenDigitNumberAndLeavesOtherTextAlone(): void
    {
        Assert::same($this->helper()->formatAvs('7561234567897'), '756.1234.5678.97');
        Assert::same($this->helper()->formatAvs('not a number'), 'not a number');
    }

    // ── isSerialized ─────────────────────────────────────────────────────────

    public function isSerializedRejectsAnythingThatIsNotAString(): void
    {
        Assert::false($this->helper()->isSerialized(['a']));
        Assert::false($this->helper()->isSerialized(5));
        Assert::false($this->helper()->isSerialized(null));
    }

    public function isSerializedAcceptsNullAndRealSerializedValues(): void
    {
        $helper = $this->helper();

        Assert::true($helper->isSerialized('N;'));
        Assert::true($helper->isSerialized(serialize('text')));
        Assert::true($helper->isSerialized(serialize(['41', '43'])));
        Assert::true($helper->isSerialized(serialize(true)));
        Assert::true($helper->isSerialized(serialize(42)));
        Assert::true($helper->isSerialized(serialize(1.5)));
        Assert::true($helper->isSerialized(serialize(new \stdClass())));
    }

    public function isSerializedRejectsPlainTextShortStringsAndBadEndings(): void
    {
        $helper = $this->helper();

        Assert::false($helper->isSerialized('plain text'));
        Assert::false($helper->isSerialized('a:1'));
        Assert::false($helper->isSerialized('a:1:{i:0;s:1:"x"'));
        Assert::false($helper->isSerialized('x:1:{i:0;}'));
        Assert::false($helper->isSerialized('s:3:"abc'));
    }

    public function isSerializedNonStrictAcceptsATrailingNewlineStyleEnding(): void
    {
        $helper = $this->helper();

        Assert::true($helper->isSerialized(serialize(['a']), false));
        Assert::true($helper->isSerialized(serialize('text'), false));
        Assert::false($helper->isSerialized('a:1', false));
        Assert::false($helper->isSerialized('a:1:x', false));
    }

    // ── formValue and selectedValue ──────────────────────────────────────────

    public function formValueFindsTheValueForTheFieldOrReturnsNull(): void
    {
        $values = [$this->value(1, 'other', 9), $this->value(2, 'mine')];

        Assert::same($this->helper()->formValue($values, self::FIELD_ID), 'mine');
        Assert::same($this->helper()->formValue($values, 77), null);
        Assert::same($this->helper()->formValue([], self::FIELD_ID), null);
    }

    public function selectedValueIsEmptyWhenThereIsNoUsableChoice(): void
    {
        $cvR = TypedMock::of(CustomValueRepository::class);
        $cvR->shouldNotReceive('repoCustomValuequery');

        Assert::same($this->helper()->selectedValue([], self::FIELD_ID, $cvR), '');
        Assert::same($this->helper()->selectedValue([$this->value(1, '')], self::FIELD_ID, $cvR), '');
        Assert::same($this->helper()->selectedValue([$this->value(1, '0')], self::FIELD_ID, $cvR), '');
    }

    public function selectedValueLooksUpTheChosenCustomValue(): void
    {
        $cvR = TypedMock::of(CustomValueRepository::class);
        $cvR->shouldReceive('repoCustomValuequery')->once()->with(42)->andReturn($this->value(42, 'Green'));

        Assert::same($this->helper()->selectedValue([$this->value(1, '42')], self::FIELD_ID, $cvR), 'Green');
    }

    // ── printFieldForForm ────────────────────────────────────────────────────

    public function theFormShowsADateFieldWithAnEditLink(): void
    {
        $html = $this->form($this->field('DATE', required: true), [$this->value(1, '2026-11-21')]);

        Assert::true($this->has($html, 'type="date"'));
        Assert::true($this->has($html, 'name="custom[5]"'));
        Assert::true($this->has($html, 'value="2026-11-21"'));
        Assert::true($this->has($html, 'My label'));
        Assert::true($this->has($html, 'hint.this.field.is.required'));
        Assert::true($this->has($html, '/customfield/edit/5'));
        Assert::true($this->has($html, 'custom.field.edit'));
    }

    public function theFormSaysWhenAFieldIsNotRequired(): void
    {
        $html = $this->form($this->field('DATE'));

        Assert::true($this->has($html, 'hint.this.field.is.not.required'));
    }

    public function theFormShowsASingleChoiceDropDownWithTheStoredChoiceSelected(): void
    {
        $choices = [self::FIELD_ID => [$this->value(41, 'Red'), $this->value(42, 'Green')]];
        $html = $this->form($this->field('SINGLE-CHOICE'), [$this->value(1, '42')], $choices);

        Assert::true($this->has($html, '<select'));
        Assert::true($this->has($html, 'name="custom[5]"'));
        Assert::true($this->has($html, 'Red'));
        Assert::true($this->has($html, 'Green'));
        Assert::true($this->has($html, 'selected'));
        Assert::false($this->has($html, 'multiple'));
    }

    public function theFormShowsAMultipleChoiceDropDownWithEachStoredChoiceSelected(): void
    {
        $choices = [self::FIELD_ID => [$this->value(41, 'Red'), $this->value(42, 'Green'), $this->value(43, 'Blue')]];
        $stored = serialize(['41', '43']);
        $html = $this->form($this->field('MULTIPLE-CHOICE'), [$this->value(1, $stored)], $choices);

        Assert::true($this->has($html, 'multiple'));
        Assert::true($this->has($html, 'Red'));
        Assert::true($this->has($html, 'Blue'));
        Assert::same(substr_count($html, 'selected'), 2);
    }

    public function aMultipleChoiceDropDownWithNothingStoredSelectsNothing(): void
    {
        $choices = [self::FIELD_ID => [$this->value(41, 'Red')]];
        $html = $this->form($this->field('MULTIPLE-CHOICE'), [], $choices);

        Assert::true($this->has($html, 'Red'));
        Assert::false($this->has($html, 'selected'));
    }

    public function theFormShowsARadioListBuiltFromTheStoredChoices(): void
    {
        $cvR = TypedMock::of(CustomValueRepository::class);
        $cvR->shouldReceive('repoCustomFieldquery')->once()->with(self::FIELD_ID)
            ->andReturn($this->reader([$this->value(41, 'Red'), $this->value(42, 'Green')]));

        $html = $this->form($this->field('RADIOLIST-CHOICE'), [$this->value(1, '42')], [], $cvR);

        Assert::true($this->has($html, 'type="radio"'));
        Assert::true($this->has($html, 'Red'));
        Assert::true($this->has($html, 'Green'));
        Assert::true($this->has($html, 'name="custom[5]"'));
    }

    public function theFormShowsABooleanAsACheckbox(): void
    {
        $html = $this->form($this->field('BOOLEAN'), [$this->value(1, '1')]);

        Assert::true($this->has($html, 'type="checkbox"'));
        Assert::true($this->has($html, 'form-check-input'));
        Assert::true($this->has($html, 'name="custom[5]"'));
    }

    public function theFormShowsEmailUrlAndTextFields(): void
    {
        $email = $this->form($this->field('EMAIL'), [$this->value(1, 'a@example.test')]);
        $url = $this->form($this->field('URL'), [$this->value(1, 'https://example.test')]);
        $text = $this->form($this->field('TEXT'), [$this->value(1, 'plain')]);

        Assert::true($this->has($email, 'type="email"'));
        Assert::true($this->has($email, 'a@example.test'));
        Assert::true($this->has($url, 'type="url"'));
        Assert::true($this->has($url, 'https://example.test'));
        Assert::true($this->has($text, 'type="text"'));
        Assert::true($this->has($text, 'plain'));
    }

    public function theFormShowsNumberAndTextAreaFields(): void
    {
        $number = $this->form($this->field('NUMBER'), [$this->value(1, '12')]);
        $area = $this->form($this->field('TEXTAREA'), [$this->value(1, 'long text')]);

        Assert::true($this->has($number, 'type="number"'));
        Assert::true($this->has($number, 'value="12"'));
        Assert::true($this->has($area, '<textarea'));
        Assert::true($this->has($area, 'long text'));
    }

    public function everyTextStyleFieldShowsTheRequiredOrNotRequiredHint(): void
    {
        foreach (['EMAIL', 'URL', 'TEXT', 'NUMBER', 'TEXTAREA'] as $type) {
            $required = $this->form($this->field($type, required: true));
            $optional = $this->form($this->field($type));

            Assert::true($this->has($required, 'hint.this.field.is.required'));
            Assert::true($this->has($optional, 'hint.this.field.is.not.required'));
        }
    }

    public function theFormEscapesStoredTextSoItCannotInjectMarkup(): void
    {
        $html = $this->form($this->field('TEXT'), [$this->value(1, '"><script>alert(1)</script>')]);

        Assert::false($this->has($html, '<script>'));
    }

    // ── printFieldForView (read-only) ────────────────────────────────────────

    public function theViewShowsDateNumberEmailUrlTextAndTextAreaFieldsDisabled(): void
    {
        foreach (['DATE', 'NUMBER', 'EMAIL', 'URL', 'TEXT', 'TEXTAREA'] as $type) {
            $html = $this->view($this->field($type), [$this->value(1, '7')]);

            Assert::true($this->has($html, 'disabled'));
            Assert::true($this->has($html, 'name="custom[5]"'));
        }
    }

    public function theViewShowsABooleanCheckboxDisabled(): void
    {
        $html = $this->view($this->field('BOOLEAN'), [$this->value(1, '1')]);

        Assert::true($this->has($html, 'type="checkbox"'));
        Assert::true($this->has($html, 'disabled'));
    }

    public function theViewShowsTheTextOfASingleChoice(): void
    {
        $cvR = TypedMock::of(CustomValueRepository::class);
        $cvR->shouldReceive('repoCustomValueDropDown')->with(42, self::FIELD_ID)->andReturn($this->value(42, 'Green'));

        $html = $this->view($this->field('SINGLE-CHOICE'), [$this->value(1, '42')], $cvR);

        Assert::true($this->has($html, 'Green'));
        Assert::true($this->has($html, 'disabled'));
    }

    public function theViewShowsNothingForASingleChoiceThatNoLongerExists(): void
    {
        $cvR = TypedMock::of(CustomValueRepository::class);
        $cvR->shouldReceive('repoCustomValueDropDown')->andReturn(null);

        Assert::same($this->view($this->field('SINGLE-CHOICE'), [$this->value(1, '42')], $cvR), '');
    }

    public function theViewListsEveryStoredMultipleChoiceThatStillExists(): void
    {
        $cvR = TypedMock::of(CustomValueRepository::class);
        $cvR->shouldReceive('repoCustomValueDropDown')->with(41, self::FIELD_ID)->andReturn($this->value(41, 'Red'));
        $cvR->shouldReceive('repoCustomValueDropDown')->with(43, self::FIELD_ID)->andReturn(null);
        $cvR->shouldReceive('repoCustomValueDropDown')->with(44, self::FIELD_ID)->andReturn($this->value(44, 'Blue'));

        $stored = serialize(['41', '43', '44']);
        $html = $this->view($this->field('MULTIPLE-CHOICE'), [$this->value(1, $stored)], $cvR);

        Assert::true($this->has($html, 'Red, Blue, '));
        Assert::true($this->has($html, 'disabled'));
    }

    public function theViewShowsARadioListDisabled(): void
    {
        $cvR = TypedMock::of(CustomValueRepository::class);
        $cvR->shouldReceive('repoCustomFieldquery')->with(self::FIELD_ID)
            ->andReturn($this->reader([$this->value(41, 'Red')]));

        $html = $this->view($this->field('RADIOLIST-CHOICE'), [$this->value(1, '41')], $cvR);

        Assert::true($this->has($html, 'type="radio"'));
        Assert::true($this->has($html, 'Red'));
        Assert::true($this->has($html, 'disabled'));
    }

    // ── text is HTML-encoded exactly once ────────────────────────────────────

    private const string TRICKY = 'A & B <x>';
    private const string TRICKY_ENCODED = 'A &amp; B &lt;x&gt;';

    /** True when the text appears once-encoded and nothing has been encoded a second time. */
    private function encodedOnce(string $html, string $expected = self::TRICKY_ENCODED): bool
    {
        return $this->has($html, $expected)
            && !$this->has($html, '&amp;amp;')
            && !$this->has($html, '&amp;lt;')
            && !$this->has($html, '&amp;gt;');
    }

    public function theFormEncodesTextStyleValuesOnceNotTwice(): void
    {
        foreach (['TEXT', 'TEXTAREA', 'EMAIL', 'URL'] as $type) {
            Assert::true($this->encodedOnce($this->form($this->field($type), [$this->value(1, self::TRICKY)])));
        }
    }

    public function aFormValueRoundTripsToTheOriginalTextWhenTheBrowserPostsItBack(): void
    {
        $html = $this->form($this->field('TEXT'), [$this->value(1, self::TRICKY)]);

        Assert::same(preg_match('/ value="([^"]*)"/', $html, $matches), 1);
        Assert::same(html_entity_decode($matches[1]), self::TRICKY);
    }

    public function theViewEncodesTextStyleValuesOnceNotTwice(): void
    {
        foreach (['TEXT', 'TEXTAREA', 'EMAIL', 'URL'] as $type) {
            Assert::true($this->encodedOnce($this->view($this->field($type), [$this->value(1, self::TRICKY)])));
        }
    }

    public function theFormEncodesTheLabelOfAChoiceFieldOnce(): void
    {
        $choices = [self::FIELD_ID => [$this->value(41, 'Red')]];

        foreach (['SINGLE-CHOICE', 'MULTIPLE-CHOICE'] as $type) {
            $html = $this->form($this->field($type, label: 'Tom & Jerry'), [], $choices);

            Assert::true($this->encodedOnce($html, 'Tom &amp; Jerry'));
        }
    }

    public function theFormEncodesChoiceTextOnce(): void
    {
        $choices = [self::FIELD_ID => [$this->value(41, self::TRICKY)]];
        $html = $this->form($this->field('SINGLE-CHOICE'), [], $choices);

        Assert::true($this->encodedOnce($html));
    }

    public function theViewEncodesAStoredChoiceOnce(): void
    {
        $cvR = TypedMock::of(CustomValueRepository::class);
        $cvR->shouldReceive('repoCustomValueDropDown')->andReturn($this->value(7, self::TRICKY));

        Assert::true($this->encodedOnce($this->view($this->field('SINGLE-CHOICE'), [$this->value(1, '7')], $cvR)));
        Assert::true($this->encodedOnce($this->view(
            $this->field('MULTIPLE-CHOICE'),
            [$this->value(1, serialize(['7']))],
            $cvR,
        )));
    }

    public function thePdfEncodesTheLabelAndEveryKindOfValueOnce(): void
    {
        $cvR = TypedMock::of(CustomValueRepository::class);
        $cvR->shouldReceive('repoCustomValuequery')->andReturn($this->value(7, self::TRICKY));

        $label = $this->pdf($this->field('TEXT', label: 'Tom & Jerry'), [$this->value(1, 'x')]);
        $text = $this->pdf($this->field('TEXT'), [$this->value(1, self::TRICKY)]);
        $number = $this->pdf($this->field('NUMBER'), [$this->value(1, self::TRICKY)]);
        $single = $this->pdf($this->field('SINGLE-CHOICE'), [$this->value(1, '7')], $cvR);
        $multiple = $this->pdf($this->field('MULTIPLE-CHOICE'), [$this->value(1, serialize(['7']))], $cvR);

        Assert::true($this->encodedOnce($label, 'Tom &amp; Jerry'));
        Assert::true($this->encodedOnce($text));
        Assert::true($this->encodedOnce($number));
        Assert::true($this->encodedOnce($single));
        Assert::true($this->encodedOnce($multiple));
    }

    // ── printFieldForPdf ─────────────────────────────────────────────────────

    public function thePdfShowsTheFieldLabelAndAPlainValue(): void
    {
        $html = $this->pdf($this->field('TEXT'), [$this->value(1, 'plain value')]);

        Assert::true($this->has($html, 'My label'));
        Assert::true($this->has($html, 'plain value'));
    }

    public function thePdfShowsADateValue(): void
    {
        $html = $this->pdf($this->field('DATE'), [$this->value(1, '2026-11-21')]);

        Assert::true($this->has($html, '2026-11-21'));
    }

    public function thePdfShowsTheTextOfASingleChoiceOrRadioChoice(): void
    {
        $cvR = TypedMock::of(CustomValueRepository::class);
        $cvR->shouldReceive('repoCustomValuequery')->with(42)->andReturn($this->value(42, 'Green'));

        Assert::true($this->has($this->pdf($this->field('SINGLE-CHOICE'), [$this->value(1, '42')], $cvR), 'Green'));
        Assert::true($this->has($this->pdf($this->field('RADIOLIST-CHOICE'), [$this->value(1, '42')], $cvR), 'Green'));
    }

    public function thePdfListsEachStoredMultipleChoiceThatStillExists(): void
    {
        $cvR = TypedMock::of(CustomValueRepository::class);
        $cvR->shouldReceive('repoCustomValuequery')->with(41)->andReturn($this->value(41, 'Red'));
        $cvR->shouldReceive('repoCustomValuequery')->with(43)->andReturn(null);

        $stored = serialize(['41', '43']);
        $html = $this->pdf($this->field('MULTIPLE-CHOICE'), [$this->value(1, $stored)], $cvR);

        Assert::true($this->has($html, 'Red'));
    }

    public function thePdfShowsNothingForAMultipleChoiceThatIsNotSerialized(): void
    {
        $cvR = TypedMock::of(CustomValueRepository::class);
        $cvR->shouldNotReceive('repoCustomValuequery');

        $html = $this->pdf($this->field('MULTIPLE-CHOICE'), [$this->value(1, 'plain')], $cvR);

        Assert::true($this->has($html, 'My label'));
        Assert::false($this->has($html, 'plain'));
    }

    public function thePdfShowsABooleanAsTrueWhenAValueIsStoredAndFalseOtherwise(): void
    {
        Assert::true($this->has($this->pdf($this->field('BOOLEAN'), [$this->value(1, '1')]), 'true'));
        Assert::true($this->has($this->pdf($this->field('BOOLEAN'), []), 'false'));
    }

    public function thePdfNeverEmitsTheLabelOrValueAsRawMarkup(): void
    {
        $html = $this->pdf($this->field('NUMBER', label: '<b>x</b>'), [$this->value(1, '<i>1</i>')]);

        Assert::false($this->has($html, '<i>1</i>'));
        Assert::false($this->has($html, '<b>x</b>'));
    }
}
