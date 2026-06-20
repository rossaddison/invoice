<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EmailTemplate\Trait;

/**
 * @method int requireId(?int $id, string $context)
 */
trait EmailTemplateTrait2
{

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
