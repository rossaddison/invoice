<?php

declare(strict_types=1);

namespace App\Invoice\EmailTemplate;

use App\Invoice\Entity\EmailTemplate;

final readonly class EmailTemplateService
{
    public function __construct(private EmailTemplateRepository $repository)
    {
    }

    /**
     * @param EmailTemplate $model
     * @param array $array
     */
    public function saveEmailTemplate(EmailTemplate $model, array $array): void
    {
        isset($array['email_template_title']) ? $model->setEmailTemplateTitle((string) $array['email_template_title']) : '';
        isset($array['email_template_type']) ? $model->setEmailTemplateType((string) $array['email_template_type']) : '';
        isset($array['email_template_body']) ? $model->setEmailTemplateBody((string) $array['email_template_body']) : '';
        isset($array['email_template_subject']) ? $model->setEmailTemplateSubject((string) $array['email_template_subject']) : '';
        isset($array['email_template_from_name']) ? $model->setEmailTemplateFromName((string) $array['email_template_from_name']) : '';
        isset($array['email_template_from_email']) ? $model->setEmailTemplateFromEmail((string) $array['email_template_from_email']) : '';
        isset($array['email_template_cc']) ? $model->setEmailTemplateCc((string) $array['email_template_cc']) : '';
        isset($array['email_template_bcc']) ? $model->setEmailTemplateBcc((string) $array['email_template_bcc']) : '';
        isset($array['email_template_pdf_template']) ? $model->setEmailTemplatePdfTemplate((string) $array['email_template_pdf_template']) : '';
        $this->repository->save($model);
    }

    /**
     * @param EmailTemplate $model
     */
    public function deleteEmailTemplate(EmailTemplate $model): void
    {
        $this->repository->delete($model);
    }
}
