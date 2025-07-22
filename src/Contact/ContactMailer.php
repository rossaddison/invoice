<?php

declare(strict_types=1);

namespace App\Contact;

use Psr\Log\LoggerInterface;
use Yiisoft\Mailer\File;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * ContactMailer sends an email from the contact form.
 */
final class ContactMailer
{
    public function __construct(
        private Session $session,
        private Flash $flash,
        private readonly LoggerInterface $logger,
        private MailerInterface $mailer,
        private Translator $translator,
        private readonly string $sender,
        private readonly string $to,
    ) {
        $this->flash      = new Flash($session);
        $this->session    = $session;
        $this->mailer     = $mailer;
        $this->translator = $translator;
    }

    public function send(ContactForm $form): void
    {
        $message = new \Yiisoft\Mailer\Message(
            charset: 'utf-8',
            subject: (string) $form->getPropertyValue('subject'),
            date: new \DateTimeImmutable('now'),
            from: [(string) $form->getPropertyValue('email') => (string) $form->getPropertyValue('name')],
            sender: $this->sender,
            to: $this->to,
            textBody: (string) $form->getPropertyValue('body'),
        );

        /** @var array $attachFile */
        foreach ($form->getPropertyValue('attachFiles') as $attachFile) {
            /**
             * @var array $file
             *
             * @psalm-suppress MixedMethodCall
             */
            foreach ($attachFile as $file) {
                if (UPLOAD_ERR_OK === $file[0]?->getError() && (null !== $file[0]?->getStream())) {
                    /** @psalm-suppress MixedAssignment $message */
                    $message = $message->withAttachments(
                        File::fromContent(
                            (string) $file[0]?->getStream(),
                            (string) $file[0]?->getClientFilename(),
                            (string) $file[0]?->getClientMediaType(),
                        ),
                    );
                }
            }
        }
        try {
            $this->mailer->send($message);
            $this->flashMessage('info', $this->translator->translate('menu.contact.soon'));
        } catch (\Exception $e) {
            $flashMsg = $e->getMessage();
            $this->logger->error($flashMsg);
        }
    }

    private function flashMessage(string $level, string $message): ?Flash
    {
        if (strlen($message) > 0) {
            $this->flash->add($level, $message, true);

            return $this->flash;
        }

        return null;
    }
}
