<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use Yiisoft\FormModel\FormModel;

final class MailerInvForm extends FormModel
{
    private string $to_email = '';
    private string $email_template = '';
    private string $from_name = '';
    private string $from_email = '';
    private string $cc = '';
    private string $bcc = '';
    private string $subject = '';
    private string $pdf_template = '';
    private string $body = '';
    private ?array $attachFiles = null;
    private string $guest_url = '';

    /**
     * @return string
     *
     * @psalm-return 'MailerInvForm'
     */
    #[\Override]
    public function getFormName(): string
    {
        return 'MailerInvForm';
    }
}
