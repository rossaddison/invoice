<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

final class PeppolBuyerPostalAddressNotFoundException extends \RuntimeException implements FriendlyExceptionInterface
{
    /**
     * @return string
     *
     * @psalm-return 'Client/Customer Postal Address, not found. Business Rule 10 (BR-10): An Invoice shall contain the Buyers postal address (BG-8).'
     */
    #[\Override]
    public function getName(): string
    {
        return 'Client/Customer Postal Address, not found. Business Rule 10 (BR-10): An Invoice shall contain the Buyers postal address (BG-8).';
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
