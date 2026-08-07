<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Console;

use App\Invoice\Libraries\CryptorException;
use App\Invoice\Setting\SettingRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * One-off diagnostic for the CryptorException regression shipped in commit
 * 1ebba1a2 and reverted in 1d6f8bbd (August 2026) — see
 * docs/CRYPTOR_DECODE_REGRESSION_AUGUST_2026.md. Any deployment that
 * updated to 1ebba1a2 and then saved *any* Settings tab (all gateways share
 * one form) had every already-configured gateway secret silently
 * double-encrypted, not just the gateway being edited.
 *
 * Decodes every stored `gateway_{driver}_{field}` password-type setting and
 * reports which ones fail — so a corrupted secret can be found and
 * re-entered in one pass, instead of discovering each one individually as
 * its gateway fails live (an `AuthenticationException`/`401` on first use,
 * not a decode-time crash — decode() always "succeeds" against corrupted
 * ciphertext, it just doesn't produce the original plaintext).
 *
 * This can only find secrets that are unreadable garbage (wrong padding,
 * non-UTF8 bytes, etc.) or empty when they shouldn't be. A corrupted value
 * that happens to decode into something string-shaped will not be flagged
 * — this is a scan for definite breakage, not a proof of correctness. Given
 * that, treat a clean report as "no *obviously* corrupted secrets", and
 * still verify sensitive gateways (the ones you actually use) by testing a
 * real payment.
 *
 * Usage:
 *   php yii setting/check-gateway-secrets
 */
final class CheckGatewaySecretsCommand extends Command
{
    protected static string $defaultName = 'setting/check-gateway-secrets';

    public function __construct(
        private readonly SettingRepository $sR,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription(
            'Decode every stored payment gateway secret and report which ones are corrupted (see the August 2026 CryptorException regression).',
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Check Gateway Secrets');

        $checked = 0;
        $corrupted = [];

        /** @var array<string, array<string, array{type: string, label: string}>> $gateways */
        $gateways = $this->sR->activePaymentGateways();
        foreach ($gateways as $driver => $fields) {
            $d = strtolower($driver);
            foreach ($fields as $key => $setting) {
                if ($setting['type'] !== 'password') {
                    continue;
                }
                $stored = $this->sR->getSetting('gateway_' . $d . '_' . $key);
                if ($stored === '') {
                    continue; // never configured — not this regression's concern
                }

                $checked++;
                $label = "{$driver}.{$key}";
                try {
                    $decoded = (string) $this->sR->decode($stored);
                    if ($decoded === '' || !mb_check_encoding($decoded, 'UTF-8')) {
                        $corrupted[] = $label;
                        $io->writeln("{$label}: CORRUPTED (decodes to empty/non-UTF8)");
                        continue;
                    }
                    $io->writeln("{$label}: ok");
                } catch (CryptorException $e) {
                    $corrupted[] = $label;
                    $io->writeln("{$label}: CORRUPTED ({$e->getMessage()})");
                }
            }
        }

        $io->writeln("Checked {$checked} configured secret(s).");
        if ($corrupted !== []) {
            $io->error(
                'Corrupted: ' . implode(', ', $corrupted)
                . '. Re-enter these in Settings > Online Payment with the real value.',
            );
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $io->success('No obviously corrupted secrets found. Still verify any gateway you actually use with a real test payment.');
        return ExitCode::OK;
    }
}
