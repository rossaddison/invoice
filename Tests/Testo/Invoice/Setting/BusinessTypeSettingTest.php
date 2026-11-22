<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Setting;

use App\Invoice\Enum\BusinessType;
use App\Invoice\Enum\ServiceType;
use App\Invoice\Setting\SettingRepository;
use DateTime;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Translator\TranslatorInterface;

/**
 * business_type and services_offered are settings like any other: a first-run default under InvoiceController
 * (InvoiceInstallTrait), a tooltip, and a field on a settings tab. business_type is on the General tab and can be put
 * back to Standard there, so window-cleaning mode is reversible without the database. services_offered is on the
 * Front Page tab.
 */
#[Test]
final class BusinessTypeSettingTest
{
    private function root(): string
    {
        return dirname(__DIR__, 4);
    }

    private function repository(): SettingRepository
    {
        return (new ReflectionClass(SettingRepository::class))->newInstanceWithoutConstructor();
    }

    /**
     * The General tab as the browser would get it, with the given stored settings.
     *
     * @param array<string, string> $settings
     */
    private function generalTab(array $settings): string
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

            public function infoIcon(string $key): string
            {
                return '<i data-info="' . $key . '"></i>';
            }

            public function formatCurrency(mixed $amount): string
            {
                return (string) $amount;
            }
        };
        $translator = TypedMock::of(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => '[' . $id . ']');

        $variables = [
            's' => $s,
            'translator' => $translator,
            'body' => [],
            'languages' => [],
            'time_zones' => [],
            'first_days_of_weeks' => [],
            'date_formats' => [],
            'countries' => [],
            'gateway_currency_codes' => [],
            'number_formats' => [],
            'current_date' => new DateTime('2026-11-22'),
        ];
        return $this->renderGeneralTab($variables);
    }

    /**
     * Takes the variables as a plain array, so the view is analysed against what the renderer would give it and
     * not against the literal built in generalTab().
     *
     * @param array<string, mixed> $variables
     */
    private function renderGeneralTab(array $variables): string
    {
        ob_start();
        (static function () use ($variables): void {
            extract($variables);
            require __DIR__ . '/../../../../resources/views/invoice/setting/views/partial_settings_general.php';
        })();
        return (string) ob_get_clean();
    }

    public function bothKeysHaveAFirstRunDefaultUnderInvoiceController(): void
    {
        $source = (string) file_get_contents($this->root() . '/src/Invoice/Trait/InvoiceInstallTrait.php');

        Assert::same(preg_match("/\\\$default_settings\\['business_type'\\]\\s*=\\s*'';/", $source), 1);
        Assert::same(preg_match("/\\\$default_settings\\['services_offered'\\]\\s*=\\s*'';/", $source), 1);
        Assert::same(BusinessType::SETTING_KEY, 'business_type');
        Assert::same(ServiceType::SETTING_KEY, 'services_offered');
    }

    public function bothKeysHaveATooltipWithAWhyAndAWhere(): void
    {
        $tooltips = $this->repository()->tooltipArray();

        foreach (['business_type', 'services_offered'] as $key) {
            Assert::true(isset($tooltips[$key]));
            /** @var array<string, string> $entry */
            $entry = $tooltips[$key];
            Assert::true(($entry['why'] ?? '') !== '');
            Assert::true(($entry['where'] ?? '') !== '');
        }
        Assert::true(str_contains($this->repository()->infoIcon('business_type', true), 'bi-info-circle'));
    }

    public function theLabelsAreInTheEnglishMessages(): void
    {
        $messages = (string) file_get_contents($this->root() . '/resources/messages/en/app.php');

        foreach (['business.type', 'business.type.standard', 'business.type.window.cleaning'] as $key) {
            Assert::same(preg_match("/'" . preg_quote($key, '/') . "'\\s*=>/", $messages), 1);
        }
    }

    public function theGeneralTabOffersStandardAndWindowCleaningWithAnInfoIcon(): void
    {
        $html = $this->generalTab([]);

        Assert::true(str_contains($html, 'name="settings[business_type]"'));
        Assert::true(str_contains($html, '<i data-info="business_type"></i>'));
        Assert::true(str_contains($html, '[business.type.standard]</option>'));
        Assert::true(str_contains($html, 'value="window_cleaning"'));
    }

    public function anInstallWithNoRowShowsStandardSelected(): void
    {
        $html = $this->generalTab([]);

        Assert::true(str_contains($html, '<option value selected>[business.type.standard]</option>'));
        Assert::false(str_contains($html, '<option value="window_cleaning" selected>'));
    }

    public function windowCleaningShowsSelectedAndStandardPutsItBack(): void
    {
        $on = $this->generalTab(['business_type' => 'window_cleaning']);
        $off = $this->generalTab(['business_type' => '']);

        Assert::true(str_contains($on, '<option value="window_cleaning" selected>'));
        Assert::false(str_contains($on, '<option value selected>'));
        // choosing Standard posts an empty value, which clears the setting and so the mode
        Assert::true(str_contains($off, '<option value selected>[business.type.standard]</option>'));
        Assert::false(str_contains($off, '<option value="window_cleaning" selected>'));
    }

    public function aValueThatIsNotAKnownTypeShowsAsStandard(): void
    {
        $html = $this->generalTab(['business_type' => 'something_else']);

        Assert::true(str_contains($html, '<option value selected>[business.type.standard]</option>'));
        Assert::false(str_contains($html, '<option value="window_cleaning" selected>'));
    }

    public function theServicesAreOnTheFrontPageTabNotTheGeneralTab(): void
    {
        $front = (string) file_get_contents(
            $this->root() . '/resources/views/invoice/setting/views/partial_settings_front_page.php',
        );

        Assert::true(str_contains($front, "'settings[' . ServiceType::SETTING_KEY . '][]'"));
        Assert::false(str_contains($this->generalTab([]), 'services_offered'));
    }
}
