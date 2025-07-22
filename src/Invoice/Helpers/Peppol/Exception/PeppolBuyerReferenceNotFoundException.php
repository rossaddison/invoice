<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

final class PeppolBuyerReferenceNotFoundException extends \RuntimeException implements FriendlyExceptionInterface
{
    /**
     * @psalm-return 'Client/Customer Purchase Order Number ie. Buyer Reference, not found. An invoice is linked to a sales order. The sales order must have a client/customer purchase order number associated with it.'
     */
    #[\Override]
    public function getName(): string
    {
        return 'Client/Customer Purchase Order Number ie. Buyer Reference, not found. An invoice is linked to a sales order. The sales order must have a client/customer purchase order number associated with it.';
    }

    #[\Override]
    public function getSolution(): string
    {
        return <<<'SOLUTION'
                Please try again
            SOLUTION;
    }
}
