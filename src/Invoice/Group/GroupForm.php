<?php

declare(strict_types=1);

namespace App\Invoice\Group;

use App\Invoice\Entity\Group;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class GroupForm extends FormModel
{
    #[Required]
    private ?string $name = '';
    #[Required]
    private ?string $identifier_format = '';
    #[Required]
    private ?int $next_id = null;
    #[Required]
    private ?int $left_pad = null;

    public function __construct(Group $group)
    {
        $this->name = $group->getName();
        $this->identifier_format = $group->getIdentifierFormat();
        $this->next_id = (int) $group->getNextId();
        $this->left_pad = (int) $group->getLeftPad();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getIdentifierFormat(): ?string
    {
        return $this->identifier_format;
    }

    public function getNextId(): ?int
    {
        return $this->next_id;
    }

    public function getLeftPad(): ?int
    {
        return $this->left_pad;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
