<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

// Entities
use App\Infrastructure\Persistence\UserInv\UserInv;
// Repositories
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\Setting\SettingRepository as SRepo;
use App\Invoice\UserInv\UserInvRepository as UIR;
//psr
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
//yiisoft
use Yiisoft\Files\FileHelper;
use Yiisoft\Router\UrlGeneratorInterface as UrlGenerator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Translator\TranslatorInterface ;
// Mailer
use Yiisoft\Mailer\File;
use Yiisoft\Mailer\MailerInterface;

class MailerHelper
{
    private readonly TemplateHelper $templatehelper;
    private readonly InvoiceHelper $invoicehelper;
    private readonly Flash $flash;

    public function __construct(
        private readonly SRepo $s,
        private readonly Session $session,
        private readonly TranslatorInterface $translator,
        private LoggerInterface $logger,
        private MailerInterface $mailer,
        MailerHelperCustomDeps $d,
    ) {
        $this->templatehelper = new TemplateHelper($this->s, $d->ccR, $d->qcR, $d->icR, $d->pcR, $d->socR, $d->cfR, $d->cvR);
        $this->invoicehelper = new InvoiceHelper($this->s, $this->session, $this->translator);
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->flash = new Flash($this->session);
    }

    public function mailerConfigured(): bool
    {
        return
            $this->s->getSetting('email_send_method') == 'symfony'
        ;
    }

    /**
     * @param string $quote_id
     * @param QR $qR
     * @param UIR $uiR
     * @param UrlGenerator $urlGenerator
     * @return bool
     */
    private function emailQuoteStatus(string $quote_id,
            QR $qR,
            UIR $uiR,
            UrlGenerator $urlGenerator): bool
    {
        if (!$this->mailerConfigured()) {
            return false;
        }
        $quote = $qR->repoCount((int) $quote_id) > 0 ? $qR->repoQuoteLoadedquery((int) $quote_id) : null;
        if ($quote) {
            $url = $urlGenerator->generate('quote/view', ['id' => $quote_id]);
            $user_id = $quote->getUser()?->reqId() ?? null;
            $user_inv = null !== $user_id ?
                $uiR->repoUserInvUserIdquery($user_id) : null;
            if (null !== $user_inv && null !== $quote->getClient()?->getClientName()) {
                    $from_email = $user_inv->getUser()?->getEmail() ?? '';
                    $from_name = $user_inv->getName() ?? '';
                    $subject = sprintf(
                        $this->translator->translate('quote.status.email.subject'),
                        $quote->getClient()?->getClientName() ?? '',
                        $quote->getNumber() ?? '',
                    );
                    $body = sprintf(
                        nl2br($this->translator->translate('quote.status.email.body')),
                        $quote->getClient()?->getClientName() ?? '',
                        $quote->getNumber() ?? '',
                        $url,
                    );

                    if ($this->s->getSetting('email_send_method') == 'yiimail') {
                        $mailerParams = new MailerSendParams($from_email, $from_name, $from_email, $subject, $body, null, null);
                        return $this->yiiMailerSend($mailerParams, [], '', $uiR);
                    }
            }
        }
        return false;
    }

    /**
     * @param MailerSendParams $params
     * @param array $attachFiles
     * @param string|null $pdf_template_target_path
     * @param UIR|null $uiR
     * @return bool
     */
    public function yiiMailerSend(
        MailerSendParams $params,
        array $attachFiles,
        // $target_path of pdfs generated
        ?string $pdf_template_target_path,
        ?UIR $uiR,
    ): bool {
        $cc = $params->cc;
        $bcc = $params->bcc;
        if (null !== $cc && is_string($cc) && (strlen($cc) > 4) && !is_array($cc)) {
            // Allow multiple CC's delimited by comma or semicolon
            $cc = (strpos($cc, ',') > 0) ? explode(',', $cc) : explode(';', $cc);
        }

        if (null !== $bcc && is_string($bcc) && (strlen($bcc) > 4) && !is_array($bcc)) {
            // Allow multiple BCC's delimited by comma or semicolon
            $bcc = (strpos($bcc, ',') > 0) ? explode(',', $bcc) : explode(';', $bcc);
        }

        // Bcc mails to admin && the admin email account has been setup under userinv which is an extension table of user
        if (null !== $uiR && ($this->s->getSetting('bcc_mails_to_admin') == 1)
                    && ($uiR->repoUserInvUserIdcount(1) > 0)) {
            $user_inv = $uiR->repoUserInvUserIdquery(1) ?: null;
            $email = null !== $user_inv ? $user_inv->getUser()?->getEmail() : '';
            // $bcc should be an array after the explode
            is_array($bcc) && $email !== '' ? array_unshift($bcc, $email) : '';
        }

        $email = new \Yiisoft\Mailer\Message(
            charset: 'utf-8',
            subject: $params->subject,
            date: new \DateTimeImmutable('now'),
            from: [$params->from_email => $params->from_name],
            to: $params->to,
            htmlBody: $params->html_body,
        );

        /** @var array<array-key, string>|string $cc */
        is_array($cc) && !empty($cc) ? $email->withCC($cc) : '';
        /** @var array<array-key, string>|string $bcc */
        is_array($bcc) && !empty($bcc) ? $email->withBcc($bcc) : '';

        /** @var array $attachFile */
        foreach ($attachFiles as $attachFile) {
            /**
             * @var array $file
             * @psalm-suppress MixedMethodCall
             */
            foreach ($attachFile as $file) {
                if ($file[0]?->getError() === UPLOAD_ERR_OK && (null !== $file[0]?->getStream())) {
                    /** @psalm-suppress MixedAssignment $email */
                    $email = $email->withAttachments(
                        File::fromContent(
                            (string) $file[0]?->getStream(),
                            (string) $file[0]?->getClientFilename(),
                            (string) $file[0]?->getClientMediaType(),
                        ),
                    );
                }
            }
        }

        if (null !== $pdf_template_target_path) {
            $path_info = pathinfo($pdf_template_target_path);
            $path_info_file_name = $path_info['filename'];
            $email_attachments_with_pdf_template = $email->withAttachments(
                File::fromPath(
                    FileHelper::normalizePath($pdf_template_target_path),
                    $path_info_file_name,
                    'application/pdf',
                ),
            );
        } else {
            $email_attachments_with_pdf_template = $email;
        }
        // Ensure that the administrator exists in the userinv extension table. If the email is blank generate a flash
        if (null !== $uiR && $uiR->repoUserInvUserIdcount(1) == 0) {
            $admin = new UserInv();
            $admin->setUserId(1);
            // Administrator's are given a type of 0, Guests eg. Accountant 1
            $admin->setType(0);
            $admin->setName('Administrator');
            $uiR->save($admin);
        }
        try {
            $this->mailer->send($email_attachments_with_pdf_template);
            $this->flashMessage('info', $this->translator->translate('email.successfully.sent'));
            return true;
        } catch (\Exception $e) {
            $this->flashMessage('warning', $this->translator->translate('email.not.sent.successfully')
                                            . "\n"
                                            . $this->translator->translate('email.exception')
                                            . "\n");
            $this->logger->error($e->getMessage());
        }
        return false;
    }

    /**
    * @param string $level
    * @param string $message
    * @return Flash|null
    * @psalm-suppress UnusedReturnValue
    */
    private function flashMessage(string $level, string $message): ?Flash
    {
        if (strlen($message) > 0) {
            $this->flash->add($level, $message, true);
            return $this->flash;
        }
        return null;
    }
}
