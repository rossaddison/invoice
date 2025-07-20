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
        $this->identifier_format = $group->getIdentifier_format();
        $this->next_id = (int) $group->getNext_id();
        $this->left_pad = (int) $group->getLeft_pad();
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function getIdentifier_format(): string|null
    {
        return $this->identifier_format;
    }

    public function getNext_id(): int|null
    {
        return $this->next_id;
    }

    public function getLeft_pad(): int|null
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
