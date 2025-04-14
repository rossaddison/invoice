<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use App\Auth\Identity;
use App\Auth\Token;
use App\User\User;
use App\Invoice\Entity\UserInv;
use App\Invoice\Entity\UserClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Cycle\Command\CycleDependencyProxy;

final class UserRelatedTruncate5Command extends Command
{
    protected static $defaultName = 'invoice/userrelated/truncate5';

    public function __construct(
        private CycleDependencyProxy $promise,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function configure(): void
    {
        $this
            ->setDescription('Truncates, i.e removes all records, in the tables related to the user.')
            ->setHelp('user_client, user_inv, user.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** Note tables must be truncated in this sequence in order to avoid integrity constraint violations **/

        $io = new SymfonyStyle($input, $output);

        $tables = ['user_client', 'user_inv', 'token', 'identity', 'user'];

        foreach ($tables as $table) {
            $this->promise
                ->getDatabaseProvider()
                ->database()
                ->delete($table)
                ->run();
        }

        if (0 === count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(UserClient::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(UserInv::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(Token::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(Identity::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(User::class)->findAll()) ? $findAll : iterator_to_array($findAll))) {
            $io->success('Done');
            return ExitCode::OK;
        }
        $io->error('Unspecified error');
        return ExitCode::UNSPECIFIED_ERROR;
    }
}
