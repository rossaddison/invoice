<?php

declare(strict_types=1);

namespace App\Contact;

use Exception;
use App\Contact\ContactForm;
use Psr\Log\LoggerInterface;
use Yiisoft\Mailer\File;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Mailer\MessageBodyTemplate;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * ContactMailer sends an email from the contact form.
 */
final class ContactMailer
{
    public function __construct(
        private Session $session,
        private Flash $flash,
        private LoggerInterface $logger,
        private MailerInterface $mailer,
        private Translator $translator,
        private string $sender,
        private string $to
    ) {
        $this->flash = New Flash($session);
        $this->session = $session;
        $this->mailer = $this->mailer->withTemplate(new MessageBodyTemplate(__DIR__ . '/mail/'));
        $this->translator = $translator;
    }

    public function send(ContactForm $form): void
    {
        $message = $this->mailer
            ->compose(
                'contact-email',
                [
                    'content' => $form->getPropertyValue('body'),
                ]
            )
            ->withSubject((string)$form->getPropertyValue('subject'))
            ->withFrom([(string)$form->getPropertyValue('email') => (string)$form->getPropertyValue('name')])
            ->withSender($this->sender)
            ->withTo($this->to);
                
        /** @var array $attachFile */
        foreach ($form->getPropertyValue('attachFiles') as $attachFile) {
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
            $this->flash_message('info', $this->translator->translate('menu.contact.soon'));
        } catch (Exception $e) {
            $flashMsg = $e->getMessage();
            $this->logger->error($flashMsg);
        } 
    }
    
     /**
     * @param string $level
     * @param string $message
     * @return Flash|null
     */
    private function flash_message(string $level, string $message): Flash|null {
        if (strlen($message) > 0) {
            $this->flash->add($level, $message, true);
            return $this->flash;
        }
        return null;
    }
}
