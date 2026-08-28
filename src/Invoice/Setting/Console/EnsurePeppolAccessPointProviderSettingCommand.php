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
 * One-off: peppol_access_point_provider (PeppolSendServiceRouter's own
 * setting, defaulting to 'storecove' — see that class's docblock) has no
 * Settings-tab UI field yet, so on an already-installed instance there's
 * no form-save path that would auto-create its row the way a genuinely
 * new settings-tab field does. A fresh install gets it seeded by
 * InvoiceInstallTrait's default_settings already; this is only for an
 * install from before that change. Safe to run repeatedly — a no-op once
 * the row exists.
 *
 * Usage:
 *   php yii setting/ensure-peppol-access-point-provider
 */
final class EnsurePeppolAccessPointProviderSettingCommand extends Command
{
    protected static string $defaultName = 'setting/ensure-peppol-access-point-provider';

    private const string KEY = 'peppol_access_point_provider';
    private const string DEFAULT_VALUE = 'storecove';

    public function __construct(
        private readonly SettingRepository $sR,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription(
            'One-off: creates the peppol_access_point_provider setting row '
            . '(defaulting to storecove) if it does not already exist. Safe to run repeatedly.',
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Ensure Peppol Access Point Provider Setting');

        if ($this->sR->repoCount(self::KEY) > 0) {
            $io->writeln(sprintf(
                '"%s" already exists (value: "%s"). Nothing to do.',
                self::KEY,
                $this->sR->getSetting(self::KEY),
            ));
            return ExitCode::OK;
        }

        $setting = new Setting();
        $setting->setSettingKey(self::KEY);
        $setting->setSettingValue(self::DEFAULT_VALUE);
        $this->sR->save($setting);

        $io->success(sprintf('Created "%s" = "%s".', self::KEY, self::DEFAULT_VALUE));
        return ExitCode::OK;
    }
}
