<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: \App\Invoice\EmailTemplate\EmailTemplateRepository::class)]
class EmailTemplate
{
    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(#[Column(type: 'text', nullable: true)]
        private ?string $email_template_title = '', #[Column(type: 'string(151)', nullable: true)]
        private ?string $email_template_type = '', #[Column(type: 'longText')]
        private string $email_template_body = '', #[Column(type: 'text', nullable: true)]
        private ?string $email_template_subject = '', #[Column(type: 'text', nullable: true)]
        private ?string $email_template_from_name = '', #[Column(type: 'text', nullable: true)]
        private ?string $email_template_from_email = '', #[Column(type: 'text', nullable: true)]
        private ?string $email_template_cc = '', #[Column(type: 'text', nullable: true)]
        private ?string $email_template_bcc = '', #[Column(type: 'string(151)', nullable: true)]
        private ?string $email_template_pdf_template = '')
    {
    }

    public function getEmailTemplateId(): ?int
    {
        return $this->id;
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

    public function getEmailTemplateBcc(): ?string
    {
        return $this->email_template_bcc;
    }

    public function setEmailTemplateBcc(string $email_template_bcc): void
    {
        $this->email_template_bcc = $email_template_bcc;
    }

    public function getEmailTemplatePdfTemplate(): ?string
    {
        return $this->email_template_pdf_template;
    }

    public function setEmailTemplatePdfTemplate(string $email_template_pdf_template): void
    {
        $this->email_template_pdf_template = $email_template_pdf_template;
    }
}
