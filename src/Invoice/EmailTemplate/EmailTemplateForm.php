<?php

declare(strict_types=1);

namespace App\Invoice\EmailTemplate;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class EmailTemplateForm extends FormModel
{
    private ?string $email_template_title = null;
    private ?string $email_template_type = null;
    private ?string $email_template_body = null;
    private ?string $email_template_subject = null;
    private ?string $email_template_from_name = null;
    private ?string $email_template_from_email = null;
    private ?string $email_template_cc = null;
    private ?string $email_template_bcc = null;
    private ?string $email_template_pdf_template = null;
                   
    public function getEmail_template_title(): string|null
    {
        return $this->email_template_title;
    }

    public function getEmail_template_type(): string|null
    {
        return $this->email_template_type;
    }
    
    public function getEmail_template_body(): string|null
    {
        return $this->email_template_body;
    }
    
    public function getEmail_template_subject(): string|null
    {
        return $this->email_template_subject;
    }
    
    public function getEmail_template_from_name(): string|null
    {
        return $this->email_template_from_name;
    }
    
    public function getEmail_template_from_email(): string|null
    {
        return $this->email_template_from_email;
    }
    
    public function getEmail_template_cc(): string|null
    {
        return $this->email_template_cc;
    }
    
    public function getEmail_template_bcc(): string|null
    {
        return $this->email_template_bcc;
    }
    
    public function getEmail_template_pdf_template(): string|null
    {
        return $this->email_template_pdf_template;
    }
    
    /**
     * @return string
     *
     * @psalm-return ''
     */
    public function getFormName(): string
    {
        return '';
    }
    
    /**
     * @return Required[][]
     *
     * @psalm-return array{email_template_title: list{Required}, email_template_type: list{Required}, email_template_body: list{Required}, email_template_subject: list{Required}, email_template_from_name: list{Required}, email_template_from_email: list{Required}}
     */
    public function getRules(): array
    {
        return [
            'email_template_title' => [new Required()],
            'email_template_type' => [new Required()],
            'email_template_body' => [new Required()],
            'email_template_subject' => [new Required()],
            'email_template_from_name' => [new Required()],
            'email_template_from_email' =>[new Required()],
        ];
    }
}
