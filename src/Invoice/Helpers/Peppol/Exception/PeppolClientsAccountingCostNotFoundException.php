<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Translator\TranslatorInterface;

final class PeppolClientsAccountingCostNotFoundException extends \RuntimeException implements FriendlyExceptionInterface
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @return string
     */
    #[\Override]
    public function getName(): string
    {
        return $this->translator->translate('invoice.client.peppol.not.found.accounting.cost');
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
