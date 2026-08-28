<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Console;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Setting\SettingRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * One-off: the Storecove API key moved off the generic Online Payment
 * gateway-credentials mechanism (`gateway_storecove_apiKey` — Storecove
 * isn't a payment gateway) onto its own field on the Storecove settings tab
 * (`storecove_api_key`) — see project_storecove_client_openapi_pivot memory.
 * Both settings store the same `SettingRepository::encode()`-produced
 * ciphertext either way (`Cryptor::Encrypt` with the app's own decrypt key,
 * not tied to which setting row holds it), so this copies the raw stored
 * value across rather than decoding and re-encoding it.
 *
 * A fresh install never needs this — `storecove_api_key` is seeded empty by
 * `InvoiceInstallTrait::installDefaultSettingsOnFirstRun()`. This is only for
 * an install from before that change, where `storecove_api_key` doesn't
 * exist as a row yet and `gateway_storecove_apiKey` may still hold a real
 * value. Safe to run repeatedly — a no-op once migrated.
 *
 * Usage:
 *   php yii setting/migrate-storecove-api-key
 */
final class MigrateStorecoveApiKeySettingCommand extends Command
{
    protected static string $defaultName = 'setting/migrate-storecove-api-key';

    private const string OLD_KEY = 'gateway_storecove_apiKey';
    private const string NEW_KEY = 'storecove_api_key';

    public function __construct(
        private readonly SettingRepository $sR,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription(
            'One-off: moves the Storecove API key from the old gateway_storecove_apiKey '
            . 'setting to its own storecove_api_key setting. Safe to run repeatedly.',
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Migrate Storecove API Key Setting');

        $oldValue = $this->sR->getSetting(self::OLD_KEY);

        if ($this->sR->repoCount(self::NEW_KEY) === 0) {
            $setting = new Setting();
            $setting->setSettingKey(self::NEW_KEY);
            $setting->setSettingValue($oldValue);
            $this->sR->save($setting);
            $io->success($oldValue !== ''
                ? sprintf('Created "%s" and copied the existing value from "%s".', self::NEW_KEY, self::OLD_KEY)
                : sprintf('Created "%s" (empty — no existing value to migrate).', self::NEW_KEY));
            return ExitCode::OK;
        }

        $newValue = $this->sR->getSetting(self::NEW_KEY);
        if ($newValue === '' && $oldValue !== '') {
            $setting = $this->sR->withKey(self::NEW_KEY);
            if ($setting !== null) {
                $setting->setSettingValue($oldValue);
                $this->sR->save($setting);
                $io->success(sprintf(
                    'Copied the existing value from "%s" into the already-existing "%s".',
                    self::OLD_KEY,
                    self::NEW_KEY,
                ));
                return ExitCode::OK;
            }
        }

        $io->writeln(sprintf('"%s" already has a value, or there was nothing to migrate. Nothing to do.', self::NEW_KEY));
        return ExitCode::OK;
    }
}
