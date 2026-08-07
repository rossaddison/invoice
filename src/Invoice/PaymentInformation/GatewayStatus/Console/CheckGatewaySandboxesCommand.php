<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\GatewayStatus\Console;

use App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusException;
use App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusRow;
use App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusService;
use App\Invoice\PaymentInformation\Service\SquarePaymentService;
use DateTimeImmutable;
use GoCardlessPro\Client as GoCardlessClient;
use GoCardlessPro\Environment as GoCardlessEnvironment;
use GuzzleHttp\Client as HttpClient;
use Mollie\Api\MollieApiClient;
use Stripe\StripeClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Pings each gateway's SANDBOX API — never live — using a secret from the
 * environment (populated by the weekly gateway-status GitHub Actions
 * workflow from repo secrets, entirely separate from this app's own
 * encrypted production credentials in the Setting table). A gateway is
 * skipped, not failed, whenever its `sandbox_env_var` is unset in
 * gateways.json or the named environment variable itself is unset — this is
 * what lets sandbox coverage roll out incrementally per gateway/region.
 *
 * Only Stripe, Mollie, GoCardless, and Square have a confirmed, genuinely
 * side-effect-free sandbox call wired up so far (a pure read each — account
 * balance, payment methods list, creditors list, business locations list).
 * Every other gateway ships with `sandbox_env_var: null` in gateways.json
 * until a safe call is
 * confirmed for it too — see docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md.
 *
 * Usage:
 *   php yii gateway-status/check-sandboxes
 */
final class CheckGatewaySandboxesCommand extends Command
{
    protected static string $defaultName = 'gateway-status/check-sandboxes';

    public function __construct(
        private readonly GatewayStatusService $service,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription(
            "Ping each gateway's sandbox API (where a safe check is wired up and its secret is set) and record the result.",
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Check Gateway Sandboxes');

        $rows = $this->service->loadFromJson();
        $today = (new DateTimeImmutable())->format('Y-m-d');
        $checked = 0;

        $updatedRows = array_map(function (GatewayStatusRow $row) use ($io, $today, &$checked): GatewayStatusRow {
            if ($row->sandboxEnvVar === null) {
                return $row;
            }
            $secret = getenv($row->sandboxEnvVar);
            if ($secret === false || $secret === '') {
                $io->writeln("Skipping {$row->name}: {$row->sandboxEnvVar} not set.");
                return $row;
            }

            $checked++;
            $error = $this->checkGateway($row->key, $secret);
            $io->writeln("{$row->name}: " . ($error === null ? 'PASS' : 'FAIL'));
            return $row->withSandboxResult($today, $error === null ? 'pass' : 'fail', $error);
        }, $rows);

        $this->service->saveToJson($updatedRows);
        $this->service->syncToDatabase($updatedRows);

        $io->writeln("Checked {$checked} gateway(s) with a configured secret.");
        $io->success('Sandbox checks complete.');
        return ExitCode::OK;
    }

    /**
     * Runs one safe, read-only sandbox call for the given gateway.
     *
     * @return string|null The error message on failure, or null on success.
     */
    private function checkGateway(string $key, string $secret): ?string
    {
        try {
            match ($key) {
                'stripe' => $this->checkStripe($secret),
                'mollie' => $this->checkMollie($secret),
                'gocardless' => $this->checkGoCardless($secret),
                'square' => $this->checkSquare($secret),
                default => throw new GatewayStatusException("No sandbox check wired up for gateway '{$key}'."),
            };
            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    private function checkStripe(string $secretKey): void
    {
        $stripe = new StripeClient($secretKey);
        $stripe->balance->retrieve();
    }

    private function checkMollie(string $apiKey): void
    {
        $mollie = new MollieApiClient();
        $mollie->setApiKey($apiKey);
        $mollie->methods->allEnabled();
    }

    private function checkGoCardless(string $accessToken): void
    {
        $client = new GoCardlessClient([
            'access_token' => $accessToken,
            'environment' => GoCardlessEnvironment::SANDBOX,
        ]);
        $client->creditors()->list(['params' => ['limit' => 1]]);
    }

    /**
     * No SDK installed for Square (see SquarePaymentService's own
     * docblock — its HTTP layer isn't Guzzle-compatible), so this is a
     * direct HTTP call, matching how SquarePaymentService itself talks to
     * Square. Sandbox base URL hardcoded here deliberately: this command
     * only ever checks sandboxes (see its own class docblock), so there's
     * no live/sandbox branch to get wrong the way SquarePaymentService's
     * own baseUrl() has to handle.
     */
    private function checkSquare(string $accessToken): void
    {
        $client = new HttpClient();
        $client->get('https://connect.squareupsandbox.com/v2/locations', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Square-Version' => SquarePaymentService::SQUARE_VERSION,
            ],
        ]);
    }
}
