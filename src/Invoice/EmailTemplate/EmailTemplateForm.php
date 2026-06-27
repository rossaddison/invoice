<?php

declare(strict_types=1);

namespace App\Invoice\EmailTemplate;

use App\Infrastructure\Persistence\EmailTemplate\EmailTemplate;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

final class EmailTemplateForm extends FormModel
{
    #[Length(min: 0, max: 200, skipOnEmpty: true)]
    public ?string $email_template_title = null;
    #[Length(min: 0, max: 151, skipOnEmpty: true)]
    public ?string $email_template_type = null;
    #[Required]
    public ?string $email_template_body = null;
    #[Length(min: 0, max: 998, skipOnEmpty: true)]
    public ?string $email_template_subject = null;
    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $email_template_from_name = null;
    #[Email()]
    #[Length(min: 0, max: 254, skipOnEmpty: true)]
    public ?string $email_template_from_email = null;

    #[Length(min: 0, max: 500, skipOnEmpty: true)]
    public ?string $email_template_cc = null;

    #[Length(min: 0, max: 500, skipOnEmpty: true)]
    public ?string $email_template_bcc = null;
    #[Length(min: 0, max: 151, skipOnEmpty: true)]
    public ?string $email_template_pdf_template = null;

    public static function show(EmailTemplate $emailtemplate): self
    {
        $form = new self();
        $form->email_template_title = $emailtemplate->getEmailTemplateTitle();
        $form->email_template_type = $emailtemplate->getEmailTemplateType();
        $form->email_template_body = $emailtemplate->getEmailTemplateBody();
        $form->email_template_subject = $emailtemplate->getEmailTemplateSubject();
        $form->email_template_from_name = $emailtemplate->getEmailTemplateFromName();
        $form->email_template_from_email = $emailtemplate->getEmailTemplateFromEmail();
        $form->email_template_cc = $emailtemplate->getEmailTemplateCc();
        $form->email_template_bcc = $emailtemplate->getEmailTemplateBcc();
        $form->email_template_pdf_template = $emailtemplate->getEmailTemplatePdfTemplate();
        return $form;
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
