<?php

declare(strict_types=1);

namespace App\Contact;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Form\FormModelInterface;
use Yiisoft\Mailer\File;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Mailer\MessageBodyTemplate;
use Yiisoft\Session\Flash\FlashInterface;

/**
 * ContactMailer sends an email from the contact form.
 */
final class ContactMailer
{
    public function __construct(
        private FlashInterface $flash,
        private LoggerInterface $logger,
        private MailerInterface $mailer,
        private string $sender,
        private string $to
    ) {
        $this->mailer = $this->mailer->withTemplate(new MessageBodyTemplate(__DIR__ . '/mail/'));
    }

    public function send(FormModelInterface $form, ServerRequestInterface $request): void
    {
        $message = $this->mailer
            ->compose(
                'contact-email',
                [
                    'content' => $form->getAttributeValue('body'),
                ]
            )
            ->withSubject((string)$form->getAttributeValue('subject'))
            ->withFrom((string)$form->getAttributeValue('email'))
            ->withSender($this->sender)
            ->withTo($this->to);
                
        $attachFiles = $request->getUploadedFiles();
        /** @var array $attachFile */
        foreach ($attachFiles as $attachFile) {
            /** 
             * @var array $file 
             * @psalm-suppress MixedMethodCall 
             */
            foreach ($attachFile as $file) {
                if ($file[0]?->getError() === UPLOAD_ERR_OK && (null!==$file[0]?->getStream())) {
                    /** @psalm-suppress MixedAssignment $message */
                    $message = $message->withAttached(
                        File::fromContent(
                            (string)$file[0]?->getStream(),
                            (string)$file[0]?->getClientFilename(),
                            (string)$file[0]?->getClientMediaType()
                        ),
                    );
                }
            }
        }

        try {
            $this->mailer->send($message);
            $flashMsg = 'Thank you for contacting us, we\'ll get in touch with you as soon as possible.';
        } catch (Exception $e) {
            $flashMsg = $e->getMessage();
            $this->logger->error($flashMsg);
        } 
    }
}
