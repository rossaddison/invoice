<?php

declare(strict_types=1);

namespace App\Invoice\FromDropDown;

use App\Infrastructure\Persistence\FromDropDown\FromDropDown;
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

    public static function show(FromDropDown $from): self
    {
        $form = new self();
        $form->email = $from->getEmail();
        $form->include = $from->getInclude();
        $form->default_email = $from->getDefaultEmail();
        return $form;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getInclude(): ?bool
    {
        return $this->include;
    }

    public function getDefaultEmail(): ?bool
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
