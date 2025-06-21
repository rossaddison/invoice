<?php

declare(strict_types=1);

namespace App\Invoice\UserClient\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Translator\TranslatorInterface;

class NoClientsAssignedToUserException extends \RuntimeException implements FriendlyExceptionInterface
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    #[\Override]
    public function getName(): string
    {
        return $this->translator->translate('user.clients.assigned.not');
    }

    /**
     * @return string
     */
    #[\Override]
    public function getSolution(): string
    {
        return <<<'SOLUTION'
                Please contact your administrator
            SOLUTION;
    }
}
