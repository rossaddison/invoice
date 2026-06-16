<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Security configuration section of a PMode (WS-Security signing and encryption).
 */
class PModeSecurity
{
    private bool $signEnabled = true;
    private string $signCertificate = '';
    private string $signAlgorithm = As4Constants::SIGNATURE_ALGORITHM;
    private string $signHashAlgorithm = As4Constants::HASH_ALGORITHM;
    private bool $encryptEnabled = true;
    private string $encryptCertificate = '';
    private string $encryptAlgorithm = As4Constants::ENCRYPTION_ALGORITHM;
    private string $keyAgreement = As4Constants::KEY_AGREEMENT;
    private string $keyWrapping = As4Constants::KEY_WRAPPING;
    private string $keyDerivation = As4Constants::KEY_DERIVATION;
    private string $keyDerivationPrf = As4Constants::KEY_DERIVATION_PRF;
    private string $wssVersion = '1.1.1';

    public function isSigningEnabled(): bool { return $this->signEnabled; }
    public function getSignCertificate(): string { return $this->signCertificate; }
    public function getSignAlgorithm(): string { return $this->signAlgorithm; }
    public function getSignHashAlgorithm(): string { return $this->signHashAlgorithm; }

    public function setSignCertificate(string $cert): self
    {
        $this->signCertificate = $cert;
        return $this;
    }

    public function isEncryptionEnabled(): bool { return $this->encryptEnabled; }
    public function getEncryptCertificate(): string { return $this->encryptCertificate; }
    public function getEncryptAlgorithm(): string { return $this->encryptAlgorithm; }
    public function getKeyAgreement(): string { return $this->keyAgreement; }
    public function getKeyWrapping(): string { return $this->keyWrapping; }
    public function getKeyDerivation(): string { return $this->keyDerivation; }
    public function getKeyDerivationPrf(): string { return $this->keyDerivationPrf; }
    public function getWssVersion(): string { return $this->wssVersion; }

    public function setEncryptCertificate(string $cert): self
    {
        $this->encryptCertificate = $cert;
        return $this;
    }
}
