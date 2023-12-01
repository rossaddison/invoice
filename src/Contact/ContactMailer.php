<?php

declare(strict_types=1);

namespace App\Contact;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Form\YiisoftFormModel\FormModelInterface;
use Yiisoft\Mailer\File;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Mailer\MessageBodyTemplate;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;

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
        private string $sender,
        private string $to
    ) {
        $this->flash = New Flash($session);
        $this->session = $session;
        $this->mailer = $this->mailer->withTemplate(new MessageBodyTemplate(__DIR__ . '/mail/'));
    }

    public function send(FormModelInterface $form, ServerRequestInterface $request): void
    {
        $message = $this->mailer
            ->compose(
                'contact-email',
                [
                    'content' => $form->getPropertyValue('body'),
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
            $this->flash_message('info', $flashMsg);
        } catch (Exception $e) {
            $flashMsg = $e->getMessage();
            $this->logger->error($flashMsg);
        } 
    }
    
    /**
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash_message(string $level, string $message): Flash {
      $this->flash->add($level, $message, true);
      return $this->flash;
    }
}
