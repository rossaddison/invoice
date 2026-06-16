<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Receipt configuration section of a PMode (non-repudiation and reply pattern).
 */
class PModeReceiptConfig
{
    private bool $sendReceipt = true;
    private bool $receiptNonRepudiation = true;
    private string $receiptReplyPattern = 'Response';

    public function shouldSendReceipt(): bool { return $this->sendReceipt; }
    public function shouldSignReceipt(): bool { return $this->receiptNonRepudiation; }
    public function getReceiptReplyPattern(): string { return $this->receiptReplyPattern; }

    public function setReceiptReplyPattern(string $pattern): self
    {
        $this->receiptReplyPattern = $pattern;
        return $this;
    }
}
