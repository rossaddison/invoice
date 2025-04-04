<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

final class PeppolNotFoundException extends \RuntimeException implements FriendlyExceptionInterface
{
    /**
     * @return string
     * @psalm-return 'Client/Customer not found.'
     */
    #[\Override]
    public function getName(): string
    {
        return 'Client/Customer not found.';
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
