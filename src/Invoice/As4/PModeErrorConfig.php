<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Error-handling configuration section of a PMode.
 */
class PModeErrorConfig
{
    private bool $reportAsResponse = true;
    private bool $processErrorNotifyConsumer = true;
    private bool $processErrorNotifyProducer = true;
    private bool $missingReceiptNotifyProducer = true;

    public function shouldReportAsResponse(): bool { return $this->reportAsResponse; }
    public function shouldNotifyConsumerOnError(): bool { return $this->processErrorNotifyConsumer; }
    public function shouldNotifyProducerOnError(): bool { return $this->processErrorNotifyProducer; }
    public function shouldNotifyProducerOnMissingReceipt(): bool { return $this->missingReceiptNotifyProducer; }
}
