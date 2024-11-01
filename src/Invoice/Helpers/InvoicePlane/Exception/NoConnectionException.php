<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\InvoicePlane\Exception;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Translator\TranslatorInterface;

class NoConnectionException extends \RuntimeException implements FriendlyExceptionInterface
{
    private TranslatorInterface $translator;
    private Exception $e;

    public function __construct(TranslatorInterface $translator, Exception $e)
    {
        $this->translator = $translator;
        $this->e = $e;
    }

    public function getName(): string
    {
        return $this->translator->translate('invoice.invoice.invoiceplane.no.connection');
    }

    /**
     * @return string
     * @psalm-return '    Please try again'
     */
    public function getSolution(): ?string
    {
        return <<<'SOLUTION'
                Please try again
            SOLUTION;
    }
}
