<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\GatewayStatus\Console;

use Adyen\Client as AdyenClient;
use Adyen\Environment as AdyenEnvironment;
use Adyen\Model\Checkout\PaymentMethodsRequest;
use Adyen\Service\Checkout\PaymentsApi as AdyenPaymentsApi;
use App\Invoice\Helpers\Telegram\TelegramHelper;
use Braintree\Gateway as BraintreeGateway;
use App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusException;
use App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusRow;
use App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusService;
use App\Invoice\PaymentInformation\Service\SquarePaymentService;
use DateTimeImmutable;
use GoCardlessPro\Client as GoCardlessClient;
use GoCardlessPro\Environment as GoCardlessEnvironment;
use GuzzleHttp\Client as HttpClient;
use Mollie\Api\MollieApiClient;
use Psr\Log\LoggerInterface as Logger;
use Stripe\StripeClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Pings each gateway's SANDBOX API — never live — using secret(s) from the
 * environment (populated by the weekly gateway-status GitHub Actions
 * workflow from repo secrets, entirely separate from this app's own
 * encrypted production credentials in the Setting table). A gateway is
 * skipped, not failed, whenever its `sandbox_env_var` is empty in
 * gateways.json or any one of its named environment variables is unset —
 * this is what lets sandbox coverage roll out incrementally per
 * gateway/region.
 *
 * Most gateways need exactly one secret (an API key/access token);
 * `GatewayStatusRow::$sandboxEnvVars` is a list precisely because Adyen
 * needs two (API key + merchant account — its paymentMethods() call
 * requires both). Values are resolved in the same order gateways.json
 * lists the env var names, and handed to each checkGateway() case in that
 * order.
 *
 * Only Stripe, Mollie, GoCardless, Square, Adyen, Mercado Pago, and
 * Braintree have a confirmed, genuinely side-effect-free sandbox call wired
 * up so far (a pure read each — account balance, payment methods list,
 * creditors list, business locations list, available payment methods list,
 * merchant account lookup). Every other gateway ships with
 * `sandbox_env_var: null` in gateways.json until a safe call is confirmed
 * for it too — see docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md.
 *
 * Also checks every row's human-entered `sandbox_expiry_date`
 * (gateways.json — never written by this command, see GatewayStatusRow's
 * own docblock) against today, independent of whether a sandbox check ran
 * for that gateway at all, and sends one Telegram message listing every
 * gateway that's expired (none sent when nothing has). Fires every run
 * for as long as a gateway stays expired — stateless by design, no
 * "already notified" tracking, since re-nagging weekly until someone
 * bumps the date is the point, not a bug. Deliberately scoped to
 * *sandbox account* expiry only (not the API key/credential itself —
 * most gateways' keys don't expire on a fixed schedule at all), via its
 * own TELEGRAM_BOT_TOKEN/TELEGRAM_CHAT_ID
 * GitHub repo secrets — separate from this app's own in-app Telegram bot
 * config (Setting table's telegram_token/telegram_chat_id, see
 * TelegramController), which is a different bot for a different audience
 * (clients, not maintainers) and isn't available to a CI job anyway (no
 * production database). Skipped silently, not failed, when either secret
 * is unset — same "roll out incrementally" philosophy as the sandbox
 * secrets above. A Telegram send failure is logged and swallowed, never
 * fails the command — an alerting outage shouldn't block the sandbox
 * check/rebuild/commit this same run still needs to do.
 *
 * Usage:
 *   php yii gateway-status/check-sandboxes
 */
final class CheckGatewaySandboxesCommand extends Command
{
    protected static string $defaultName = 'gateway-status/check-sandboxes';

    public function __construct(
        private readonly GatewayStatusService $service,
        private readonly Logger $logger,
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
            if ($row->sandboxEnvVars === []) {
                return $row;
            }
            $secrets = [];
            foreach ($row->sandboxEnvVars as $envVar) {
                $value = getenv($envVar);
                if ($value === false || $value === '') {
                    $io->writeln("Skipping {$row->name}: {$envVar} not set.");
                    return $row;
                }
                $secrets[] = $value;
            }

            $checked++;
            $error = $this->checkGateway($row->key, $secrets);
            $io->writeln("{$row->name}: " . ($error === null ? 'PASS' : 'FAIL'));
            return $row->withSandboxResult($today, $error === null ? 'pass' : 'fail', $error);
        }, $rows);

        $this->service->saveToJson($updatedRows);
        $this->service->syncToDatabase($updatedRows);

        $io->writeln("Checked {$checked} gateway(s) with a configured secret.");
        $this->notifyExpiredGateways($updatedRows, $today, $io);
        $io->success('Sandbox checks complete.');
        return ExitCode::OK;
    }

    /**
     * @param list<GatewayStatusRow> $rows
     */
    private function notifyExpiredGateways(array $rows, string $today, SymfonyStyle $io): void
    {
        $expired = array_values(array_filter($rows, static fn (GatewayStatusRow $row): bool => $row->isExpired($today)));
        if ($expired === []) {
            return;
        }

        $botToken = getenv('TELEGRAM_BOT_TOKEN');
        $chatId = getenv('TELEGRAM_CHAT_ID');
        if ($botToken === false || $botToken === '' || $chatId === false || $chatId === '') {
            $io->writeln('Skipping Telegram notification: TELEGRAM_BOT_TOKEN/TELEGRAM_CHAT_ID not set.');
            return;
        }

        $lines = array_map(
            static fn (GatewayStatusRow $row): string => "• {$row->name}: expired {$row->sandboxExpiryDate}",
            $expired,
        );
        $text = "⚠️ Gateway sandbox account(s) expired:\n" . implode("\n", $lines);

        try {
            $telegramHelper = new TelegramHelper($botToken, $this->logger);
            $telegramHelper->sendMessage($chatId, $text);
            $io->writeln('Telegram notification sent for ' . count($expired) . ' expired gateway(s).');
        } catch (\Throwable $e) {
            $io->writeln('Telegram notification failed: ' . $e->getMessage());
        }
    }

    /**
     * Runs one safe, read-only sandbox call for the given gateway.
     *
     * @param list<string> $secrets Resolved env var values, in the same
     *   order gateways.json lists that gateway's sandbox_env_var name(s).
     * @return string|null The error message on failure, or null on success.
     */
    private function checkGateway(string $key, array $secrets): ?string
    {
        try {
            match ($key) {
                'stripe' => $this->checkStripe($secrets[0]),
                'mollie' => $this->checkMollie($secrets[0]),
                'gocardless' => $this->checkGoCardless($secrets[0]),
                'square' => $this->checkSquare($secrets[0]),
                'adyen' => $this->checkAdyen($secrets[0], $secrets[1]),
                'mercado_pago' => $this->checkMercadoPago($secrets[0]),
                'braintree' => $this->checkBraintree($secrets[0], $secrets[1], $secrets[2]),
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

    /**
     * POST /paymentMethods is a genuine read despite the HTTP verb —
     * "Get a list of available payment methods", confirmed directly
     * against the vendored SDK source
     * (Adyen\Service\Checkout\PaymentsApi::paymentMethods()'s own
     * docblock) — no charge, no state mutation. Needs both the API key
     * and the merchant account name, unlike every other gateway checked
     * here, since merchantAccount is a required field on the request
     * (Adyen\Model\Checkout\PaymentMethodsRequest validates it can't be
     * null) — see GatewayStatusRow::$sandboxEnvVars' docblock.
     */
    private function checkAdyen(string $apiKey, string $merchantAccount): void
    {
        $client = new AdyenClient();
        $client->setXApiKey($apiKey);
        $client->setEnvironment(AdyenEnvironment::TEST);

        $request = new PaymentMethodsRequest();
        $request->setMerchantAccount($merchantAccount);

        $paymentsApi = new AdyenPaymentsApi($client);
        $paymentsApi->paymentMethods($request);
    }

    /**
     * GET /v1/payment_methods is a pure read — "Lists the payment methods
     * ... available for the country associated with the access token",
     * confirmed directly against the vendored SDK source
     * (MercadoPago\Client\PaymentMethod\PaymentMethodClient::list()'s own
     * docblock) — no charge, no state mutation, same shape as
     * Mollie/Adyen's own payment-methods-list checks above.
     */
    private function checkMercadoPago(string $accessToken): void
    {
        $client = new HttpClient();
        $client->get('https://api.mercadopago.com/v1/payment_methods', [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken],
        ]);
    }

    /**
     * `merchantAccount()->find($merchantId)` is a plain HTTP GET — confirmed
     * directly against the vendored SDK source
     * (Braintree\MerchantAccountGateway::find(), which calls
     * `$this->_http->get($path)`) — no charge, no state mutation, same
     * shape as every other gateway's sandbox check above. Needs all three
     * of merchantId/publicKey/privateKey (unlike most gateways here, which
     * need only one secret) since Braintree\Gateway's constructor requires
     * all three to authenticate at all.
     */
    private function checkBraintree(string $merchantId, string $publicKey, string $privateKey): void
    {
        $gateway = new BraintreeGateway([
            'environment' => 'sandbox',
            'merchantId' => $merchantId,
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
        ]);
        $gateway->merchantAccount()->find($merchantId);
    }
}
