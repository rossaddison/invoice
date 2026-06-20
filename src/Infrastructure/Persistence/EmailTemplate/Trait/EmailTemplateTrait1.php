<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EmailTemplate\Trait;

/**
 * @method int requireId(?int $id, string $context)
 */
trait EmailTemplateTrait1
{

    /**
     * @throws \LogicException if the entity has not been persisted yet.
     */
    public function reqEmailTemplateId(): int
    {
        if ($this->id === null) {
            throw new \LogicException(
                'EmailTemplate has no ID (not persisted yet)'
            );
        }

        return $this->id;
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function getEmailTemplateTitle(): ?string
    {
        return $this->email_template_title;
    }

    public function setEmailTemplateTitle(string $email_template_title): void
    {
        $this->email_template_title = $email_template_title;
    }

    public function getEmailTemplateType(): ?string
    {
        return $this->email_template_type;
    }

    public function setEmailTemplateType(string $email_template_type): void
    {
        $this->email_template_type = $email_template_type;
    }

    public function getEmailTemplateBody(): string
    {
        return $this->email_template_body;
    }

    public function setEmailTemplateBody(string $email_template_body): void
    {
        $this->email_template_body = $email_template_body;
    }

    public function getEmailTemplateSubject(): ?string
    {
        return $this->email_template_subject;
    }

    public function setEmailTemplateSubject(string $email_template_subject): void
    {
        $this->email_template_subject = $email_template_subject;
    }

    public function getEmailTemplateFromName(): ?string
    {
        return $this->email_template_from_name;
    }

    public function setEmailTemplateFromName(string $email_template_from_name): void
    {
        $this->email_template_from_name = $email_template_from_name;
    }

    public function getEmailTemplateFromEmail(): ?string
    {
        return $this->email_template_from_email;
    }

    public function setEmailTemplateFromEmail(string $email_template_from_email): void
    {
        $this->email_template_from_email = $email_template_from_email;
    }

    public function getEmailTemplateCc(): ?string
    {
        return $this->email_template_cc;
    }

    public function setEmailTemplateCc(string $email_template_cc): void
    {
        $this->email_template_cc = $email_template_cc;
    }
}
