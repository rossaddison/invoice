<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class GoogleTranslateTypeNotFoundException extends \RuntimeException implements FriendlyExceptionInterface
{
    /**
     * @psalm-return 'There appears to be no language related file selected.'
     */
    #[\Override]
    public function getName(): string
    {
        return 'There appears to be no language related file selected.';
    }

    #[\Override]
    public function getSolution(): string
    {
        return <<<'SOLUTION'
                Please try again later.
            SOLUTION;
    }
}
