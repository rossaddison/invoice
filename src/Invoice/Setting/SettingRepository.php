<?php

declare(strict_types=1);

namespace App\Invoice\Setting;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\Context\DefaultContext;
use Brick\Money\CurrencyConverter;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Money;
use App\Infrastructure\Persistence\Setting\Setting;
use App\Infrastructure\Persistence\Company\Company;
use App\Invoice\Company\CompanyRepository as compR;
use App\Invoice\CompanyPrivate\CompanyPrivateRepository as compPR;
use App\Invoice\Setting\Trait\SettingConfigTrait;
use App\Invoice\Setting\Trait\SettingFileFolderTrait;
use App\Invoice\Setting\Trait\SettingGovMtdTrait;
use App\Invoice\Setting\Trait\SettingInvoiceMarkTrait;
use App\Invoice\Setting\Trait\SettingLocaleTrait;
use App\Invoice\Setting\Trait\SettingMiscTrait;
use App\Invoice\Setting\Trait\SettingPaymentTrait;
use App\Invoice\Setting\Trait\SettingStaticPathsTrait;
use App\Invoice\Setting\Trait\SettingTooltipTrait;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of Setting
 * @extends Select\Repository<TEntity>
 */
final class SettingRepository extends Select\Repository
{
    use SettingConfigTrait;
    use SettingFileFolderTrait;
    use SettingGovMtdTrait;
    use SettingInvoiceMarkTrait;
    use SettingLocaleTrait;
    use SettingMiscTrait;
    use SettingPaymentTrait;
    use SettingStaticPathsTrait;
    use SettingTooltipTrait;

    public array $settingsArray = [];

    private const string DECRYPT_KEY = 'base64:3iqxXZEG5aR0NPvmE4qubcE/'
            . 'sn6nuzXKLrZVRMP3/Ak=';

    private string $decryptKey = self::DECRYPT_KEY;

    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(
        Select $select,
        private readonly EntityWriter $entityWriter,
        private readonly TranslatorInterface $translator,
        private readonly compR $compR,
        private readonly compPR $compPR,
    ) {
        parent::__construct($select);
    }

    /**
     * Get settings without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }

    /**
     * @param string $setting_key
     * @return int
     */
    public function repoCount(string $setting_key): int
    {
        return $this->select()
                    ->where(['setting_key' => $setting_key])
                    ->count();
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param Setting|null $setting
     * @throws Throwable
     */
    public function save(?Setting $setting): void
    {
        if (null !== $setting) {
            $this->entityWriter->write([$setting]);
        }
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param Setting|null $setting
     * @throws Throwable
     */
    public function delete(?Setting $setting): void
    {
        $this->entityWriter->delete([$setting]);
    }

    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'setting_key', 'setting_value'])
                ->withOrder(['setting_key' => 'asc']),
        );
    }

    public function getActiveCompany(): ?Company
    {
        return $this->compR->repoCompanyActivequery();
    }

    public function getEnv(): string
    {
        $config = $this->getConfigParams();
        $params = $config->get('params');
        return (string) $params['env'];
    }

    public function filterSettingKey(string $setting_key): EntityReader
    {
        $select = $this->select();
        $query = $select->where(['setting_key' => ltrim(rtrim($setting_key))]);
        return $this->prepareDataReader($query);
    }

    public function filterSettingValue(string $setting_value): EntityReader
    {
        $select = $this->select();
        $query = $select->where(['setting_value' => ltrim(rtrim($setting_value))]);
        return $this->prepareDataReader($query);
    }

    /**
     * Note: setExchangeRate(
     *  string $sourceCurrencyCode,
     *  string $targetCurrencyCode,
     *  $exchangeRate)
     * @param BigNumber|float|int|string $from
     * @return string
     */
    public function currencyConverter(BigNumber|int|float|string $from): string
    {
        $a = $this->getSetting('currency_code_from');
        $b = $this->getSetting('peppol_document_currency');
        $one_of_a_converts_to_this_of_b = $this->getSetting('currency_from_to');
        $one_of_b_converts_to_this_of_a = $this->getSetting('currency_to_from');
        if ($a !== $b) {
            $provider = ConfigurableProvider::builder()
                ->addExchangeRate($a, $b, $one_of_a_converts_to_this_of_b)
                ->addExchangeRate($b, $a, $one_of_b_converts_to_this_of_a)
                ->build();
            $converter = new CurrencyConverter($provider);
            $money = Money::of((string) $from, $a);
            // see https://github.com/brick/money#Using an ORM
            $int = $converter->convert($money, $b, [], new DefaultContext(), RoundingMode::Down)
                // convert to cents in order to use the int
                ->getMinorAmount()
                ->toInt();
            $formatted = number_format(((float)$int) / 100.00 ?: 0.00, 2, '.', '');
            if ($this->getSetting('peppol_debug_with_emojis') == '1') {
                return (string) $from
                    . ' ' . $a . ' '
                    . ' x ' . $one_of_a_converts_to_this_of_b . '↔️'
                    . $formatted
                    . ' ' . $b . ' ';
            }
            return $formatted;
        } else {
            /**
             * @psalm-suppress InvalidCast $from
             */
            $amt = number_format(((float)$from) ?: 0.00, 2, '.', '');
            return $this->getSetting('peppol_debug_with_emojis') == '1'
             ? '➡️' . $amt : $amt;
        }
    }

    /**
     * @param int $setting_id
     * @return Setting|null
     */
    public function repoSettingquery(int $setting_id): ?Setting
    {
        $query = $this
            ->select()
            ->where(['id' => $setting_id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $setting_key
     * @return Setting|null
     */
    public function withKey(string $setting_key): ?Setting
    {
        $query = $this
            ->select()
            ->where(['setting_key' => $setting_key]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $setting_value
     * @return Setting|null
     */
    public function withValue(string $setting_value): ?Setting
    {
        $query = $this
            ->select()
            ->where(['setting_value' => $setting_value]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * Build settings array
     */
    public function loadSettings(): void
    {
        if ($this->settingsArray !== []) {
            return;
        }
        $all_settings = $this->findAllPreloaded();
        /** @var Setting $setting */
        foreach ($all_settings as $setting) {
            /** @var string $this->settingsArray[$setting->getSettingKey()] */
            $this->settingsArray[$setting->getSettingKey()] =
                    $setting->getSettingValue();
        }
    }

    /**
     * @param string $key
     * @return string
     */
    public function getSetting(string $key): string
    {
        $this->loadSettings();
        return (string) ($this->settingsArray[$key] ?? '');
    }

    /**
     * @psalm-return positive-int
     */
    public function positiveListLimit(): int
    {
        $defaultListLimit = (int) $this->getSetting('default_list_limit');
        if ($defaultListLimit > 0) {
            /**
             * @psalm-var positive-int $positiveInt
             */
            return $defaultListLimit;
        }
        return  1;
    }

    /**
     * @param string $key
     * @return string
     */
    public function setting(string $key): string
    {
        $this->loadSettings();
        /** @var string $this->settingsArray[$key] */
        return $this->settingsArray[$key];
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function setSetting(string $key, string $value): void
    {
        $this->settingsArray[$key] = $value;
    }

    public function mailerEnabled(): bool
    {
        return $this->configParams()['esmtp_enabled'];
    }
}
