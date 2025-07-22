<?php

declare(strict_types=1);

namespace App\User;

final class RecoveryCodeService
{
    public function __construct(private RecoveryCodeRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Save or update a RecoveryCode model.
     */
    public function saveRecoveryCode(RecoveryCode $model, User $user, string $codeHash, bool $used): void
    {
        $model->setUser($user);
        $model->setCodeHash($codeHash);
        $model->setUsed($used);

        $this->repository->save($model);
    }

    public function deleteRecoveryCode(RecoveryCode $model): void
    {
        $this->repository->delete($model);
    }

    public function removeBackupRecoveryCodes(User $user): void
    {
        $codes = $this->repository->findByUser($user);
        /**
         * @var RecoveryCode $code
         */
        foreach ($codes as $code) {
            $this->deleteRecoveryCode($code);
        }
    }

    public function userHasBackupCodes(User $user): bool
    {
        $count = $this->repository->findByUserCount($user);

        return $count > 0;
    }

    public function generateBackupCodes(int $count = 5, int $length = 8): array
    {
        $codes = [];
        // Ensure length is at least 2 and even
        $length  = ($length < 2) ? 2 : ((0 === $length % 2) ? $length : $length + 1);
        $lenDiv2 = (int) ($length / 2);

        for ($i = 0; $i < $count; ++$i) {
            /** @var int<1, max> $lenDiv2 */
            $code    = bin2hex(random_bytes($lenDiv2));
            $codes[] = strtoupper($code);
        }

        return $codes;
    }

    public function persistBackupCodes(User $user, array $plainCodes): void
    {
        /** @var string[] $plainCodes */
        foreach ($plainCodes as $code) {
            $hash         = password_hash($code, PASSWORD_DEFAULT);
            $recoveryCode = new RecoveryCode($user, $hash, false);
            $this->repository->save($recoveryCode);
        }
    }

    public function validateAndMarkCodeAsUsed(User $user, string $inputCode): bool
    {
        // Fetch all recovery codes for the user
        $codes = $this->repository->findByUser($user);

        /**
         * @var RecoveryCode $recoveryCode
         */
        foreach ($codes as $recoveryCode) {
            // Only check unused codes
            if (!$recoveryCode->isUsed()) {
                // Use password_verify for secure hash comparison
                if (password_verify($inputCode, $recoveryCode->getCodeHash())) {
                    // Mark the code as used and persist i.e. save
                    $recoveryCode->setUsed(true);
                    $this->repository->save($recoveryCode);

                    return true;
                }
            }
        }

        // No valid code found
        return false;
    }
}
