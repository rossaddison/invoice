<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

final class PeppolDeliveryLocationNotFoundException extends \RuntimeException implements FriendlyExceptionInterface
{
    /**
     * @psalm-return 'Delivery Location not found.'
     */
    #[\Override]
    public function getName(): string
    {
        return 'Delivery Location not found.';
    }

    #[\Override]
    public function getSolution(): string
    {
        return <<<'SOLUTION'
                Please try again
            SOLUTION;
    }
}
