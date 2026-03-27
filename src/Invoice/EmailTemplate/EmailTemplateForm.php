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
        $this->email_template_title = $emailtemplate->getEmailTemplateTitle();
        $this->email_template_type = $emailtemplate->getEmailTemplateType();
        $this->email_template_body = $emailtemplate->getEmailTemplateBody();
        $this->email_template_subject = $emailtemplate->getEmailTemplateSubject();
        $this->email_template_from_name = $emailtemplate->getEmailTemplateFromName();
        $this->email_template_from_email = $emailtemplate->getEmailTemplateFromEmail();
        $this->email_template_cc = $emailtemplate->getEmailTemplateCc();
        $this->email_template_bcc = $emailtemplate->getEmailTemplateBcc();
        $this->email_template_pdf_template = $emailtemplate->getEmailTemplatePdfTemplate();
    }

    public function getEmailTemplateTitle(): ?string
    {
        return $this->email_template_title;
    }

    public function getEmailTemplateType(): ?string
    {
        return $this->email_template_type;
    }

    public function getEmailTemplateBody(): ?string
    {
        return $this->email_template_body;
    }

    public function getEmailTemplateSubject(): ?string
    {
        return $this->email_template_subject;
    }

    public function getEmailTemplateFromName(): ?string
    {
        return $this->email_template_from_name;
    }

    public function getEmailTemplateFromEmail(): ?string
    {
        return $this->email_template_from_email;
    }

    public function getEmailTemplateCc(): ?string
    {
        return $this->email_template_cc;
    }

    public function getEmailTemplateBcc(): ?string
    {
        return $this->email_template_bcc;
    }

    public function getEmailTemplatePdfTemplate(): ?string
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
