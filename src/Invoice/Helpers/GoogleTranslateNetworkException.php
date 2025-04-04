<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class GoogleTranslateNetworkException extends \RuntimeException implements FriendlyExceptionInterface
{
    /**
     * @return string
     *
     * @psalm-return 'There appears to be a Network error.'
     */
    #[\Override]
    public function getName(): string
    {
        return 'There appears to be a Network error.';
    }

    /**
     * @return string
     */
    #[\Override]
    public function getSolution(): string
    {
        return <<<'SOLUTION'
                Please try again later.
            SOLUTION;
    }
}
