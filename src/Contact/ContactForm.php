<?php

declare(strict_types=1);

namespace App\Contact;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Required;

final class ContactForm extends FormModel
{
    private string $name = '';
    private string $email = '';
    private string $subject = '';
    private string $body = '';
    private ?array $attachFiles = null;

    /**
     * @return string[]
     *
     * @psalm-return array{name: 'Name', email: 'Email', subject: 'Subject', body: 'Body'}
     */
    public function getAttributeLabels(): array
    {
        return [
            'name' => 'Name',
            'email' => 'Email',
            'subject' => 'Subject',
            'body' => 'Body',
        ];
    }

    /**
     * @return string
     *
     * @psalm-return 'ContactForm'
     */
    public function getFormName(): string
    {
        return 'ContactForm';
    }

    /**
     * @return (Email|Required)[][]
     *
     * @psalm-return array{name: list{Required}, email: list{Required, Email}, subject: list{Required}, body: list{Required}}
     */
    public function getRules(): array
    {
        return [
            'name' => [new Required()],
            'email' => [new Required(), new Email()],
            'subject' => [new Required()],
            'body' => [new Required()],
        ];
    }
}
