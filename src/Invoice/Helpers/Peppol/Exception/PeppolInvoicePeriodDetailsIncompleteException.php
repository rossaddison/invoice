<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

final class PeppolInvoicePeriodDetailsIncompleteException extends \RuntimeException implements FriendlyExceptionInterface
{
    /**
     * @return string
     *
     * @psalm-return 'Invoice Period Details Incomplete or Non-existant. See delivery/edit/{inv_id}'
     */
    #[\Override]
    public function getName(): string
    {
        return 'Invoice Period Details Incomplete or Non-existant. See delivery/edit/{inv_id}';
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
