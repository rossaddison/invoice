<?php

declare(strict_types=1);

namespace App\Invoice\Group\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Translator\TranslatorInterface;

class GroupException extends \RuntimeException implements FriendlyExceptionInterface
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getName(): string
    {
        return $this->translator->translate('invoice.group.document.number');
    }

    /**
     * @return string
     * @psalm-return '    Please contact your administrator'
     */
    public function getSolution(): ?string
    {
        return <<<'SOLUTION'
                Please contact your administrator
            SOLUTION;
    }
}
