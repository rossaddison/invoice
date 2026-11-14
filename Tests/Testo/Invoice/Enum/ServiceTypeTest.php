<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Enum;

use App\Invoice\Enum\ServiceType;
use Testo\Assert;
use Testo\Test;

/**
 * The services a home-services business can offer, and how the ticked ones are kept in the
 * services_offered setting.
 */
#[Test]
final class ServiceTypeTest
{
    public function thereAreTwelveServicesWindowCleaningFirst(): void
    {
        Assert::count(ServiceType::cases(), 12);
        Assert::same(ServiceType::cases()[0], ServiceType::WindowCleaning);
    }

    public function everyServiceHasAnEnglishLabelAndAProductName(): void
    {
        /** @var array<string, string> $messages */
        $messages = require dirname(__DIR__, 4) . '/resources/messages/en/app.php';
        $productNames = [];
        foreach (ServiceType::cases() as $service) {
            Assert::true(isset($messages[$service->translationKey()]));
            // the label people read and the product name are the same English words
            Assert::same($messages[$service->translationKey()], $service->productName());
            $productNames[] = $service->productName();
        }
        Assert::count(array_unique($productNames), 12);
    }

    public function theTranslationKeyIsTheValueWithDotsForUnderscores(): void
    {
        Assert::same(ServiceType::BinCleaning->translationKey(), 'service.bin.cleaning');
        Assert::same(ServiceType::HousePainting->translationKey(), 'service.house.painting');
    }

    public function valuesBecomeServicesOnceEachInTheOrderOfTheEnum(): void
    {
        $services = ServiceType::fromValues(['hedge_cutting', 'window_cleaning', 'hedge_cutting', 'not_a_service']);

        Assert::same($services, [ServiceType::WindowCleaning, ServiceType::HedgeCutting]);
    }

    public function aCommaSeparatedSettingValueIsReadAndWrittenBack(): void
    {
        $services = ServiceType::fromCsv(' grass_mowing, bin_cleaning ,,nonsense');

        Assert::same($services, [ServiceType::BinCleaning, ServiceType::GrassMowing]);
        Assert::same(ServiceType::toCsv($services), 'bin_cleaning,grass_mowing');
    }

    public function anEmptySettingValueIsNoServices(): void
    {
        Assert::same(ServiceType::fromCsv(''), []);
        Assert::same(ServiceType::toCsv([]), '');
    }

    public function theSettingKeyIsServicesOffered(): void
    {
        Assert::same(ServiceType::SETTING_KEY, 'services_offered');
    }
}
