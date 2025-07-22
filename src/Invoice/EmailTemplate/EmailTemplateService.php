<?php

declare(strict_types=1);

namespace App\Invoice\EmailTemplate;

use App\Invoice\Entity\EmailTemplate;

final readonly class EmailTemplateService
{
    public function __construct(private EmailTemplateRepository $repository)
    {
    }

    public function saveEmailTemplate(EmailTemplate $model, array $array): void
    {
        isset($array['email_template_title']) ? $model->setEmail_template_title((string) $array['email_template_title']) : '';
        isset($array['email_template_type']) ? $model->setEmail_template_type((string) $array['email_template_type']) : '';
        isset($array['email_template_body']) ? $model->setEmail_template_body((string) $array['email_template_body']) : '';
        isset($array['email_template_subject']) ? $model->setEmail_template_subject((string) $array['email_template_subject']) : '';
        isset($array['email_template_from_name']) ? $model->setEmail_template_from_name((string) $array['email_template_from_name']) : '';
        isset($array['email_template_from_email']) ? $model->setEmail_template_from_email((string) $array['email_template_from_email']) : '';
        isset($array['email_template_cc']) ? $model->setEmail_template_cc((string) $array['email_template_cc']) : '';
        isset($array['email_template_bcc']) ? $model->setEmail_template_bcc((string) $array['email_template_bcc']) : '';
        isset($array['email_template_pdf_template']) ? $model->setEmail_template_pdf_template((string) $array['email_template_pdf_template']) : '';
        $this->repository->save($model);
    }

    public function deleteEmailTemplate(EmailTemplate $model): void
    {
        $this->repository->delete($model);
    }
}
