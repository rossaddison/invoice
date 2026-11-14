<?php

declare(strict_types=1);

namespace App\Invoice\Enum;

// A service a home-services business can offer. The ones ticked are kept in the settings table under
// SETTING_KEY as a comma-separated string of values (same convention as homecare_hidden_inv_columns).
// Each ticked service gets a service product of its productName() in the Product catalogue, is listed
// on the front page, and can be chosen on the HomeCare sign up. See WindowCleaningPreset.

enum ServiceType: string
{
    public const string SETTING_KEY = 'services_offered';

    case WindowCleaning = 'window_cleaning';
    case BinCleaning = 'bin_cleaning';
    case FurnitureUplift = 'furniture_uplift';
    case ClothingUplift = 'clothing_uplift';
    case RubbishUplift = 'rubbish_uplift';
    case PavementCleaning = 'pavement_cleaning';
    case FencePainting = 'fence_painting';
    case HedgeCutting = 'hedge_cutting';
    case GrassMowing = 'grass_mowing';
    case TreeTrimming = 'tree_trimming';
    case GutterCleaning = 'gutter_cleaning';
    case HousePainting = 'house_painting';

    /**
     * The name of the service's product in the catalogue (stored data, so not translated).
     */
    public function productName(): string
    {
        return match ($this) {
            self::WindowCleaning => 'Window Cleaning',
            self::BinCleaning => 'Bin Cleaning',
            self::FurnitureUplift => 'Furniture Uplift',
            self::ClothingUplift => 'Clothing Uplift',
            self::RubbishUplift => 'Rubbish Uplift',
            self::PavementCleaning => 'Pavement Cleaning',
            self::FencePainting => 'Fence Painting',
            self::HedgeCutting => 'Hedge Cutting',
            self::GrassMowing => 'Grass Mowing',
            self::TreeTrimming => 'Tree Trimming',
            self::GutterCleaning => 'Gutter Cleaning',
            self::HousePainting => 'House Painting',
        };
    }

    /**
     * The key of the service's label in resources/messages/en/app.php, for what people read.
     */
    public function translationKey(): string
    {
        return 'service.' . str_replace('_', '.', $this->value);
    }

    /**
     * The services named by the given values, each once and in the order of this enum; anything that
     * is not a service is ignored.
     *
     * @param list<string> $values
     * @return list<self>
     */
    public static function fromValues(array $values): array
    {
        $services = [];
        foreach (self::cases() as $case) {
            if (in_array($case->value, $values, true)) {
                $services[] = $case;
            }
        }
        return $services;
    }

    /**
     * @return list<self> the services in a comma-separated setting value
     */
    public static function fromCsv(string $csv): array
    {
        return self::fromValues(array_values(array_filter(array_map('trim', explode(',', $csv)))));
    }

    /**
     * @param list<self> $services
     */
    public static function toCsv(array $services): string
    {
        return implode(',', array_map(static fn (self $service): string => $service->value, $services));
    }
}
