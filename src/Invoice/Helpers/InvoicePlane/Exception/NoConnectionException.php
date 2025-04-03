<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\InvoicePlane\Exception;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Translator\TranslatorInterface;

class NoConnectionException extends \RuntimeException implements FriendlyExceptionInterface
{
    public function __construct(private readonly TranslatorInterface $translator, private readonly Exception $e)
    {
    }

    #[\Override]
    public function getName(): string
    {
        return $this->translator->translate('invoice.invoice.invoiceplane.no.connection');
    }

    /**
     * @return string
     */
    #[\Override]
    public function getSolution(): string
    {
        return <<<'SOLUTION'
                Please try again
            SOLUTION;
    }
}
