<?php

declare(strict_types=1);

namespace App\Invoice\EmailTemplate;

use App\Invoice\Entity\EmailTemplate;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class EmailTemplateForm extends FormModel
{
    #[Required]
    private ?string $email_template_title = null;
    #[Required]
    private ?string $email_template_type = null;
    #[Required]
    private ?string $email_template_body = null;
    #[Required]
    private ?string $email_template_subject = null;
    #[Required]
    private ?string $email_template_from_name = null;
    #[Required]
    private ?string $email_template_from_email = null;

    private ?string $email_template_cc = null;

    private ?string $email_template_bcc = null;
    #[Required]
    private ?string $email_template_pdf_template = null;

    public function __construct(EmailTemplate $emailtemplate)
    {
        $this->email_template_title        = $emailtemplate->getEmail_template_title();
        $this->email_template_type         = $emailtemplate->getEmail_template_type();
        $this->email_template_body         = $emailtemplate->getEmail_template_body();
        $this->email_template_subject      = $emailtemplate->getEmail_template_subject();
        $this->email_template_from_name    = $emailtemplate->getEmail_template_from_name();
        $this->email_template_from_email   = $emailtemplate->getEmail_template_from_email();
        $this->email_template_cc           = $emailtemplate->getEmail_template_cc();
        $this->email_template_bcc          = $emailtemplate->getEmail_template_bcc();
        $this->email_template_pdf_template = $emailtemplate->getEmail_template_pdf_template();
    }

    public function getEmail_template_title(): ?string
    {
        return $this->email_template_title;
    }

    public function getEmail_template_type(): ?string
    {
        return $this->email_template_type;
    }

    public function getEmail_template_body(): ?string
    {
        return $this->email_template_body;
    }

    public function getEmail_template_subject(): ?string
    {
        return $this->email_template_subject;
    }

    public function getEmail_template_from_name(): ?string
    {
        return $this->email_template_from_name;
    }

    public function getEmail_template_from_email(): ?string
    {
        return $this->email_template_from_email;
    }

    public function getEmail_template_cc(): ?string
    {
        return $this->email_template_cc;
    }

    public function getEmail_template_bcc(): ?string
    {
        return $this->email_template_bcc;
    }

    public function getEmail_template_pdf_template(): ?string
    {
        return $this->email_template_pdf_template;
    }

    /**
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
