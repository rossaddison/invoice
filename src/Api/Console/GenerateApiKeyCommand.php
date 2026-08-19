<?php

declare(strict_types=1);

namespace App\Api\Console;

use App\Api\ApiClientRepository;
use App\Infrastructure\Persistence\ApiClient\ApiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Mints a new external-caller API key for `ApiKeyAuthMiddleware` — e.g. one
 * for the future headless webshop storefront (see
 * `docs/WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md`).
 *
 * Prints the plaintext key exactly once. Only its sha256 hash is stored
 * (`ApiClient::key_hash`) — if the printed value is lost, there is no
 * recovery path, only generating a fresh key and disabling the old
 * `ApiClient` row.
 *
 * Usage:
 *   php yii api-client/generate webshop
 */
final class GenerateApiKeyCommand extends Command
{
    protected static string $defaultName = 'api-client/generate';

    public function __construct(private readonly ApiClientRepository $apiClientRepository)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription('Generate a new API key for an external caller (e.g. the webshop storefront).')
            ->addArgument('name', InputArgument::REQUIRED, 'Human-readable label for this caller, e.g. "webshop"');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $name */
        $name = $input->getArgument('name');

        try {
            $plaintextKey = bin2hex(random_bytes(32));
            $client = new ApiClient(name: $name, key_hash: hash('sha256', $plaintextKey));
            $this->apiClientRepository->save($client);

            $io->success(sprintf('API key generated for "%s".', $name));
            $io->warning('This key is shown once and cannot be recovered — store it now.');
            $io->writeln($plaintextKey);
        } catch (Throwable $t) {
            $io->error($t->getMessage());

            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }
}
