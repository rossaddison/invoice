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
            foreach ($fields as $key => $setting) {
                $result = $this->checkGatewaySecret($driver, $key, $setting, $io);
                if ($result === null) {
                    continue;
                }
                $checked++;
                if ($result !== '') {
                    $corrupted[] = $result;
                }
            }
        }

        return $this->reportResults($io, $checked, $corrupted);
    }

    /**
     * Checks a single driver/field's stored secret, writing its result line to $io whenever it was
     * actually a configured password secret. Split out of execute() purely to keep its cognitive
     * complexity within SonarQube's limit (php:S3776); the decode/report step is further split into
     * decodeAndReportSecret() purely to keep this method's own return count within SonarQube's limit
     * (php:S1142).
     *
     * @param array{type: string, label: string} $setting
     *
     * @return string|null null when this field isn't a configured password secret at all (skipped, not
     *   counted); '' when checked and fine; the corrupted label when checked and corrupted.
     */
    private function checkGatewaySecret(string $driver, string $key, array $setting, SymfonyStyle $io): ?string
    {
        if ($setting['type'] !== 'password') {
            return null;
        }
        $stored = $this->sR->getSetting('gateway_' . strtolower($driver) . '_' . $key);
        if ($stored === '') {
            return null; // never configured — not this regression's concern
        }
        return $this->decodeAndReportSecret("{$driver}.{$key}", $stored, $io);
    }

    /**
     * @return string '' when the secret decodes cleanly; the corrupted $label otherwise.
     */
    private function decodeAndReportSecret(string $label, string $stored, SymfonyStyle $io): string
    {
        try {
            $decoded = (string) $this->sR->decode($stored);
            if ($decoded === '' || !mb_check_encoding($decoded, 'UTF-8')) {
                $io->writeln("{$label}: CORRUPTED (decodes to empty/non-UTF8)");
                return $label;
            }
            $io->writeln("{$label}: ok");
            return '';
        } catch (CryptorException $e) {
            $io->writeln("{$label}: CORRUPTED ({$e->getMessage()})");
            return $label;
        }
    }

    /**
     * @param list<string> $corrupted
     */
    private function reportResults(SymfonyStyle $io, int $checked, array $corrupted): int
    {
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
