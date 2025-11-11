<?php

declare(strict_types=1);

namespace App\Invoice\EmailTemplate;

use App\Invoice\Entity\EmailTemplate;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

final class EmailTemplateForm extends FormModel
{
    #[Length(min: 0, max: 200, skipOnEmpty: true)]
    private ?string $email_template_title = null;
    #[Length(min: 0, max: 151, skipOnEmpty: true)]
    private ?string $email_template_type = null;
    #[Required]
    private ?string $email_template_body = null;
    #[Length(min: 0, max: 998, skipOnEmpty: true)]
    private ?string $email_template_subject = null;
    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $email_template_from_name = null;
    #[Email()]
    #[Length(min: 0, max: 254, skipOnEmpty: true)]
    private ?string $email_template_from_email = null;

    #[Length(min: 0, max: 500, skipOnEmpty: true)]
    private ?string $email_template_cc = null;

    #[Length(min: 0, max: 500, skipOnEmpty: true)]
    private ?string $email_template_bcc = null;
    #[Length(min: 0, max: 151, skipOnEmpty: true)]
    private ?string $email_template_pdf_template = null;

    public function __construct(EmailTemplate $emailtemplate)
    {
        $this->email_template_title = $emailtemplate->getEmail_template_title();
        $this->email_template_type = $emailtemplate->getEmail_template_type();
        $this->email_template_body = $emailtemplate->getEmail_template_body();
        $this->email_template_subject = $emailtemplate->getEmail_template_subject();
        $this->email_template_from_name = $emailtemplate->getEmail_template_from_name();
        $this->email_template_from_email = $emailtemplate->getEmail_template_from_email();
        $this->email_template_cc = $emailtemplate->getEmail_template_cc();
        $this->email_template_bcc = $emailtemplate->getEmail_template_bcc();
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
