<?php

declare(strict_types=1);

namespace App\Invoice\EmailTemplate;

use App\Invoice\Entity\EmailTemplate;
use App\Invoice\EmailTemplate\EmailTemplateRepository;

final class EmailTemplateService
{
    private EmailTemplateRepository $repository;

    public function __construct(EmailTemplateRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * @param EmailTemplate $model
     * @param array $array
     * @return void
     */
    public function saveEmailTemplate(EmailTemplate $model, array $array): void
    {
        isset($array['email_template_title']) ? $model->setEmail_template_title((string)$array['email_template_title']) : '';
        isset($array['email_template_type']) ? $model->setEmail_template_type((string)$array['email_template_type']) : '';
        isset($array['email_template_body']) ? $model->setEmail_template_body((string)$array['email_template_body']) : '';
        isset($array['email_template_subject']) ? $model->setEmail_template_subject((string)$array['email_template_subject']) : '';
        isset($array['email_template_from_name']) ? $model->setEmail_template_from_name((string)$array['email_template_from_name']) : '';
        isset($array['email_template_from_email']) ? $model->setEmail_template_from_email((string)$array['email_template_from_email']) : '';
        isset($array['email_template_cc']) ? $model->setEmail_template_cc((string)$array['email_template_cc']) : '';
        isset($array['email_template_bcc']) ? $model->setEmail_template_bcc((string)$array['email_template_bcc']) : '';
        isset($array['email_template_pdf_template']) ? $model->setEmail_template_pdf_template((string)$array['email_template_pdf_template']) : '';
        $this->repository->save($model);
    }
    
    /**
     * @param EmailTemplate $model
     * @return void
     */
    public function deleteEmailTemplate(EmailTemplate $model): void
    {
        $this->repository->delete($model);
    }
}
