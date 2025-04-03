<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\StoreCove\Exceptions;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Translator\TranslatorInterface;

final class ContactLastNameNotFoundException extends \RuntimeException implements FriendlyExceptionInterface
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    #[\Override]
    public function getName(): string
    {
        return $this->translator->translate('invoice.storecove.supplier.contact.lastname.not.found');
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
