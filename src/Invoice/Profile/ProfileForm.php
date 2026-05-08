<?php

declare(strict_types=1);

namespace App\Invoice\Profile;

use App\Infrastructure\Persistence\Profile\Profile;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Email;
use DateTimeImmutable;

final class ProfileForm extends FormModel
{
    private ?int $company_id = null;
    private ?int $current = 0;
    #[Required]
    private ?string $mobile = '';
    #[Email]
    private ?string $email = '';
    private ?string $description = '';
    private mixed $date_created = '';
    private mixed $date_modified = '';

    public static function show(Profile $profile): self
    {
        $form = new self();
        $form->company_id = $profile->reqCompanyId();
        $form->current = $profile->getCurrent();
        $form->mobile = $profile->getMobile();
        $form->email = $profile->getEmail();
        $form->description = $profile->getDescription();
        $form->date_created = $profile->getDateCreated();
        $form->date_modified = $profile->getDateModified();
        return $form;
    }

    public function getCompanyId(): ?int
    {
        return $this->company_id;
    }

    public function getCurrent(): ?int
    {
        return $this->current;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDateCreated(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string|null $this->date_created
         */
        return $this->date_created;
    }

    public function getDateModified(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string|null $this->date_modified
         */
        return $this->date_modified;
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
