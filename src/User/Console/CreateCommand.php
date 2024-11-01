<?php

declare(strict_types=1);

namespace App\User\Console;

use App\Auth\Form\SignupForm;
use App\User\User;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Rbac\Manager;
use Yiisoft\Yii\Console\ExitCode;

final class CreateCommand extends Command
{
    protected static $defaultName = 'user/create';

    public function __construct(
        private readonly SignupForm   $signupForm,
        private readonly Manager      $manager,
        private readonly FormHydrator $formHydrator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates a user')
            ->setHelp('This command allows you to create a user')
            ->addArgument('login', InputArgument::REQUIRED, 'Login')
            ->addArgument('password', InputArgument::REQUIRED, 'Password')
            ->addArgument('isAdmin', InputArgument::OPTIONAL, 'Create user as admin');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $error
     * @param string $attribute
     * @return int
     * @throws LogicException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /**
         * @psalm-suppress MixedAssignment
         */
        $login = $input->getArgument('login');

        /**
         * @psalm-suppress MixedAssignment
         */
        $password = $input->getArgument('password');
        $isAdmin = (bool) $input->getArgument('isAdmin');

        try {
            $this->formHydrator->populate(model: $this->signupForm, data: [
                'login' => $login,
                'password' => $password,
                'passwordVerify' => $password,
            ], scope: '');
            $user = $this->signupForm->signup();
        } catch (Throwable $t) {
            /**
             * Avoid Information Exposure
             * @see https://cwe.mitre.org/data/definitions/200.html
             * Previously: $io->error($t->getMessage() . ' ' . $t->getFile() . ' ' . $t->getLine());             *
             */
            throw $t;
        }

        if (!$user instanceof User) {
            $errors = $this->signupForm->getValidationResult()->getErrorMessagesIndexedByProperty();
            array_walk($errors, fn (string $error, string $attribute): mixed => $io->error("$attribute: $error"));
            return ExitCode::DATAERR;
        }

        if ($isAdmin) {
            $userId = $user->getId();
            if ($userId === null) {
                throw new LogicException('User Id is NULL');
            }
            $this->manager->assign('admin', $userId);
        }
        $io->success('User created');

        return ExitCode::OK;
    }
}
