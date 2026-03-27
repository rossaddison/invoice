<?php

declare(strict_types=1);

namespace App\Invoice\Profile;

use App\Invoice\Entity\Profile;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Translator\TranslatorInterface as Translator;
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

    public function __construct(Profile $profile, private readonly Translator $translator)
    {
        $this->company_id = (int) $profile->getCompanyId();
        $this->current = $profile->getCurrent();
        $this->mobile = $profile->getMobile();
        $this->email = $profile->getEmail();
        $this->description = $profile->getDescription();
        $this->date_created = $profile->getDateCreated();
        $this->date_modified = $profile->getDateModified();
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

    #[\Override]
    public function getPropertyLabels(): array
    {
        return [
            'current' => $this->translator->translate('profile.property.label.current'),
            'mobile' => $this->translator->translate('profile.property.label.mobile'),
            'email' => $this->translator->translate('profile.property.label.email'),
            'description' => $this->translator->translate('profile.property.label.description'),
        ];
    }

    #[\Override]
    public function getPropertyHints(): array
    {
        $required = 'hint.this.field.is.required';
        $not_required = 'hint.this.field.is.not.required';
        return [
            'company_id' => $this->translator->translate($required),
            'email' => $this->translator->translate($required),
            'mobile' => $this->translator->translate($required),
            'description' => $this->translator->translate($not_required),
        ];
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
