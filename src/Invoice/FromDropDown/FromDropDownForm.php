<?php

declare(strict_types=1);

namespace App\Invoice\FromDropDown;

use App\Invoice\Entity\FromDropDown;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class FromDropDownForm extends FormModel
{
    #[Required]
    private ?string $email = '';
    #[Required]
    private ?bool $include = false;
    #[Required]
    private ?bool $default_email = false;

    public function __construct(FromDropDown $from)
    {
        $this->email = $from->getEmail();
        $this->include = $from->getInclude();
        $this->default_email = $from->getDefault_email();
    }

    public function getEmail(): string|null
    {
        return $this->email;
    }

    public function getInclude(): bool|null
    {
        return $this->include;
    }

    public function getDefault_email(): bool|null
    {
        return $this->default_email;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
