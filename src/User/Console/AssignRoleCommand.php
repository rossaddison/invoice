<?php

declare(strict_types=1);

namespace App\User\Console;

use App\User\User;
use InvalidArgumentException;
use Stringable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Manager;
use Yiisoft\Rbac\Role;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Cycle\Command\CycleDependencyProxy;

/**
 * e.g > yii user/assignRole admin 1
 */

final class AssignRoleCommand extends Command
{
    protected static string $defaultName = 'user/assignRole';

    public function __construct(
        private readonly CycleDependencyProxy $promise,
        private readonly Manager $manager,
        private readonly ItemsStorageInterface $itemsStorage,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function configure(): void
    {
        $this
            ->setDescription('Assign RBAC role to given user')
            ->setHelp('This command allows you to assign RBAC role to user')
            ->addArgument('role', InputArgument::REQUIRED, 'RBAC role')
            ->addArgument('userId', InputArgument::REQUIRED, 'User id');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /**
         * @var mixed $input->getArgument('role')
         * @var string $roleName
         */
        $roleName = $input->getArgument('role');
        /**
         * @var mixed $input->getArgument('userId')
         * @var int $userId
         */
        $userId = $input->getArgument('userId');

        try {
            $orm = $this->promise->getORM();
            $userRepo = $orm->getRepository(User::class);
            $user = $userRepo->findByPK($userId);
            if (null === $user) {
                throw new InvalidArgumentException('Can\'t find user');
            }
            if (null === $user->getId()) {
                throw new InvalidArgumentException('User Id is NULL');
            }

            /**
             * @var string $roleName
             */
            $role = $this->itemsStorage->getRole($roleName);

            if (null === $role) {
                $helper = $this->getHelper('question');
                if (!$helper instanceof QuestionHelper) {
                    throw new \LogicException('The "question" helper is not an instance of QuestionHelper');
                }
                $question = new ConfirmationQuestion('Role doesn\'t exist. Create new one? ', false);

                if (!$helper->ask($input, $output, $question)) {
                    return ExitCode::OK;
                }

                $role = new Role($roleName);
                $this->manager->addRole($role);
            }

            /**
             * @var int|string|Stringable $userId
             */
            $this->manager->assign($roleName, $userId);

            $io->success('Role was assigned to given user');
        } catch (Throwable $t) {
            $io->error($t->getMessage());

            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }
}
