<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class GoogleTranslateTypeNotFoundException extends \RuntimeException implements FriendlyExceptionInterface
{
    /**
     * @return string
     *
     * @psalm-return 'There appears to be no language related file selected.'
     */
    public function getName(): string
    {
        return 'There appears to be no language related file selected.';
    }
    
    /**
     * @return string
     *
     * @psalm-return '    Please try again later.'
     */
    public function getSolution(): ?string
    {
        return <<<'SOLUTION'
                Please try again later.
            SOLUTION;
    }
}