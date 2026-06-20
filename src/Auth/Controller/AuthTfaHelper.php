<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Infrastructure\Persistence\User\User;
use App\Invoice\Setting\SettingRepository;
use App\User\RecoveryCodeService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

final readonly class AuthTfaHelper
{
    public function __construct(
        private SettingRepository $sR,
        private RecoveryCodeService $recoveryCodeService,
    ) {}

    public function isValidTotpCode(string $code): bool
    {
        return \preg_match('/^\d{6}$/', $code) === 1;
    }

    public function isValidBackupCode(string $code): bool
    {
        return \preg_match('/^[A-Za-z0-9]{8}$/', $code) === 1;
    }

    public function sanitizeAndValidateCode(mixed $input): ?string
    {
        if (!is_string($input) && !is_numeric($input)) {
            return null;
        }

        $code = trim((string) $input);
        $code = preg_replace('/[^A-Za-z0-9]/', '', $code);

        if ($code === null || $code === '') {
            return null;
        }

        $length = strlen($code);
        if ($length !== 6 && $length !== 8) {
            return null;
        }

        if ($length === 6 && !$this->isValidTotpCode($code)) {
            return null;
        }

        if ($length === 8 && !$this->isValidBackupCode($code)) {
            return null;
        }

        return $code;
    }

    public function generateQrDataUri(string $content): string
    {
        $eccLevel = $this->sR->getSetting('qr_ecc_level');
        $options = new QROptions([
            'eccLevel' => strlen($eccLevel) > 0 ? (int) $eccLevel : 0b01,
            'imageBase64' => true,
            'scale' => 4,
        ]);
        /** @psalm-suppress InvalidArgument $options **/
        return (string) (new QRCode($options))->render($content);
    }

    public function generateBackupRecoveryCodes(User $user): array
    {
        $codes = $this->recoveryCodeService->generateBackupCodes(5, 8);
        $this->recoveryCodeService->persistBackupCodes($user, $codes);
        return $codes;
    }

    public function removeBackupRecoveryCodes(User $user): void
    {
        if ($this->recoveryCodeService->userHasBackupCodes($user)) {
            $this->recoveryCodeService->removeBackupRecoveryCodes($user);
        }
    }
}
