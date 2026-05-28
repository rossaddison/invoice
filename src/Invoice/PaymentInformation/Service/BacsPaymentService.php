<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Invoice\CompanyPrivate\CompanyPrivateRepository as CpR;
use chillerlan\QRCode\QRCode;
use DateTimeImmutable;

final readonly class BacsPaymentService
{
    public function __construct(
        private CpR $cpR
    ) {}

    public function isCompanyPrivateActive(): bool
    {
        return $this->cpR->repoCompanyPrivateActive() !== null;
    }

    public function isBacsConfigured(): bool
    {
        $cp = $this->cpR->repoCompanyPrivateActive();
        return $cp !== null
            && ($cp->getBacsSortCode() ?? '') !== ''
            && ($cp->getBacsAccountNumber() ?? '') !== '';
    }

    public function getSortCode(): string
    {
        return $this->cpR->repoCompanyPrivateActive()?->getBacsSortCode() ?? '';
    }

    public function getAccountNumber(): string
    {
        return $this->cpR->repoCompanyPrivateActive()?->getBacsAccountNumber() ?? '';
    }

    public function getBeneficiaryName(): string
    {
        return $this->cpR->repoCompanyPrivateActive()?->getCompany()?->getName() ?? '';
    }

    /**
     * Generates a short, unique BACS payment reference for a client.
     * Format: WIN-{clientId}-{yyyymm} — max 18 chars, safe for all UK banks.
     */
    public function generateReference(int $clientId): string
    {
        return 'WIN-' . $clientId . '-' . (new DateTimeImmutable())->format('Ym');
    }

    /**
     * Builds the string encoded in the QR code.
     * Plain-text format readable by any QR scanner; no IBAN calculation required.
     */
    public function buildQrContent(
        string $reference,
        float $amount,
        string $currency = 'GBP',
    ): string {
        $lines = [
            'Pay: ' . $this->getBeneficiaryName(),
            'Sort code: ' . $this->formatSortCode($this->getSortCode()),
            'Account: ' . $this->getAccountNumber(),
            'Ref: ' . $reference,
        ];
        if ($amount > 0.00) {
            $lines[] = 'Amount: ' . $currency . number_format($amount, 2);
        }
        return implode("\n", $lines);
    }

    public function renderQrDataUri(string $qrContent): string
    {
        return (string) new QRCode()->render($qrContent);
    }

    private function formatSortCode(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';
        if (strlen($digits) === 6) {
            return substr($digits, 0, 2) . '-' . substr($digits, 2, 2) . '-' . substr($digits, 4, 2);
        }
        return $raw;
    }
}
