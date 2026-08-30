<?php

declare(strict_types=1);

namespace App\User\Console;

use App\Auth\Form\SignupForm;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\AppConstants;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\UserInv\UserInvRepository;
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

/**
 * Console-only shortcut to bootstrap a real, usable account -- most
 * often the very first admin on a fresh install, before any signup email
 * can be sent or clicked. Deliberately mirrors what a real /signup +
 * clicked-verification-link produces (see SignupController::signup()/
 * htmlBodyWithMaskedRandomAndTimeTokenLink()), rather than only the
 * User+Identity rows SignupForm::signup() itself creates:
 *
 *   - A UserInv extension row -- without one,
 *     AuthController::resolveLoginResponse() treats the account as
 *     nonexistent and silently logs it straight back out, with no
 *     validation error shown anywhere (found installing fresh
 *     2026-08-30). Created active here (unlike the public flow's
 *     initially-inactive row awaiting an emailed confirmation link --
 *     nothing will ever deliver or click one for a console-created
 *     account).
 *   - A non-empty email -- required by
 *     TwoFactorAuth::showSetup()'s `strlen($email) > 0` gate, mandatory
 *     for every admin-role account regardless of the global enable_tfa
 *     setting. Missing it produces the same silent redirect-to-error
 *     pattern as the missing UserInv row above.
 */
final class CreateCommand extends Command
{
    protected static string $defaultName = 'user/create';

    public function __construct(
        private readonly SignupForm $signupForm,
        private readonly Manager $manager,
        private readonly FormHydrator $formHydrator,
        private readonly UserInvRepository $uiR,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription('Creates a user')
            ->setHelp('This command allows you to create a user')
            ->addArgument('login', InputArgument::REQUIRED, 'Login')
            ->addArgument('password', InputArgument::REQUIRED, 'Password')
            ->addArgument('email', InputArgument::REQUIRED, 'Email -- required: 2FA setup (mandatory for admins) needs one')
            ->addArgument('isAdmin', InputArgument::OPTIONAL, 'Create user as admin');
    }

    #[\Override]
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

        /**
         * @psalm-suppress MixedAssignment
         */
        $email = $input->getArgument('email');
        $isAdmin = (bool) $input->getArgument('isAdmin');

        try {
            $this->formHydrator->populate(model: $this->signupForm, data: [
                'login' => $login,
                'email' => $email,
                'password' => $password,
                'passwordVerify' => $password,
            ], scope: '');
            $user = $this->signupForm->signup();
        } catch (Throwable) {
            $io->error('User creation failed.');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$user instanceof User) {
            $errors = $this->signupForm->getValidationResult()->getErrorMessagesIndexedByProperty();
            array_walk($errors, fn (string $error, string $attribute): mixed => $io->error("$attribute: $error"));
            return ExitCode::DATAERR;
        }

        $userId = $user->reqId();
        if ($isAdmin) {
            $this->manager->assign(AppConstants::ROLE_ADMIN, $userId);
        }

        $userInv = new UserInv();
        $userInv->setUserId($userId);
        $userInv->setType($isAdmin ? 0 : 1);
        // Active immediately -- a console-created account has no signup
        // email to click a confirmation link from (see class docblock).
        $userInv->setActive(true);
        $this->uiR->save($userInv);

        $io->success('User created');

        return ExitCode::OK;
    }
}
