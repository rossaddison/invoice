<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EmailTemplate;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use App\Infrastructure\Persistence\EmailTemplate\Trait\EmailTemplateTrait1;
use App\Infrastructure\Persistence\EmailTemplate\Trait\EmailTemplateTrait2;

#[Entity(repository: \App\Invoice\EmailTemplate\EmailTemplateRepository::class)]
class EmailTemplate
{

    use EmailTemplateTrait1;
    use EmailTemplateTrait2;

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
}
