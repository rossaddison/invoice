<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Translator\TranslatorInterface;

class PeppolSalesOrderItemNotExistException extends \RuntimeException implements FriendlyExceptionInterface
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getName(): string
    {
        return $this->translator->translate('invoice.client.peppol.not.exist.sales.order.item');
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
