<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class PeppolNotFoundException extends \RuntimeException implements FriendlyExceptionInterface
{
    /**
     * @return string
     * @psalm-return 'Client/Customer not found.'
     */
    public function getName(): string
    {
        return 'Client/Customer not found.';
    }

    /**
     * @return string
     */
    public function getSolution(): string
    {
        return <<<'SOLUTION'
                Please try again
            SOLUTION;
    }
}
