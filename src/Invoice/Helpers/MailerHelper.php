<?php
declare(strict_types=1); 

Namespace App\Invoice\Helpers;

// Entities
use App\Invoice\Entity\UserInv;
// Repositories
use App\Invoice\ClientCustom\ClientCustomRepository as CCR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\PaymentCustom\PaymentCustomRepository as PCR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SOCR;
use App\Invoice\QuoteCustom\QuoteCustomRepository as QCR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\Setting\SettingRepository as SRepo;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\Invoice\Helpers\PdfHelper;
use App\Invoice\Helpers\TemplateHelper;
use App\Invoice\Helpers\InvoiceHelper;

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
use Yiisoft\Mailer\MessageBodyTemplate;

Class MailerHelper
{
        private SRepo $s;
        private Session $session;
        private TranslatorInterface $translator;
        private PdfHelper $pdfhelper;
        private TemplateHelper $templatehelper;
        private InvoiceHelper $invoicehelper;
        private Flash $flash;
    
    public function __construct(SRepo $s, 
        Session $session,
        TranslatorInterface $translator,
        private LoggerInterface $logger,    
        private MailerInterface $mailer,    
        CCR $ccR, QCR $qcR, ICR $icR, PCR $pcR, SOCR $socR, CFR $cfR, CVR $cvR)
    {
        $this->s = $s;
        $this->session = $session;
        $this->translator = $translator;
        $this->pdfhelper = new PdfHelper($s, $session);
        $this->templatehelper = new TemplateHelper($s, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
        $this->invoicehelper = new InvoiceHelper($s, $session);
        $this->logger = $logger;
        // yii-mailer: Not using yii's contact-email template but ...mail/invoice/invoice.php 
        $this->mailer = $this->mailer->withTemplate(new MessageBodyTemplate(dirname(dirname(dirname(__DIR__))). '/src/Contact/mail/invoice'));    
        $this->flash = new Flash($session);
    }
    
    public function mailer_configured(): bool
    {
        return (
            ($this->s->get_setting('email_send_method') == 'symfony') 
        );
    }
    
    // This function will be used with cron at a later stage

    /**
     * 
     * @param string $quote_id
     * @param QR $qR
     * @param UIR $uiR
     * @param UrlGenerator $urlGenerator
     * @param ServerRequestInterface $request
     * @return bool
     */
    private function email_quote_status(string $quote_id, QR $qR, UIR $uiR, UrlGenerator $urlGenerator, ServerRequestInterface $request) : bool
    {
        if (!$this->mailer_configured()) {
            return false;
        }
        $quote = $qR->repoCount($quote_id) > 0 ? $qR->repoQuoteLoadedquery($quote_id) : null;
        if ($quote) {
            $url = $urlGenerator->generate('quote/view',['id'=>$quote_id]);
            $user_id = $quote->getUser()?->getId() ?? null;
            $user_inv = null!==$user_id ? $uiR->repoUserInvUserIdquery($user_id) : null;
            if (null!==$user_inv) {
                if (null!==$quote->getClient()?->getClient_name()) {  
                    $from_email = $user_inv->getEmail() ?? '';        
                    $from_name = $user_inv->getName() ?? '';        
                    $subject = sprintf($this->s->trans('quote_status_email_subject'),
                        $quote->getClient()?->getClient_name() ?? '',
                        $quote->getNumber() ?? ''
                    );                
                    $body = sprintf(nl2br($this->s->trans('quote_status_email_body')),
                        $quote->getClient()?->getClient_name() ?? '',
                        // TODO: Hyperlink for base url in Html
                        $quote->getNumber() ?? '', $url
                    );

                    if ($this->s->get_setting('email_send_method') == 'yiimail') {
                        return $this->yii_mailer_send($from_email, $from_name, $from_email, $subject, $body, null, null, [], '', $uiR);
                    }
                }    
            }
        }    
        return false;
    }
    
    /**
     * 
     * @param string $from_email
     * @param string $from_name
     * @param string $to
     * @param string $subject
     * @param string $html_body
     * @param array|string|null $cc
     * @param array|string|null $bcc
     * @param array $attachFiles
     * @param string|null $pdf_template_target_path
     * @param UIR|null $uiR
     * @return bool
     */
    public function yii_mailer_send(
        string $from_email,
        string $from_name,
        string $to,    
        string $subject,
        string $html_body,
        array|string|null $cc,
        array|string|null $bcc,
        array $attachFiles,
        // $target_path of pdfs generated    
        string|null $pdf_template_target_path,    
        UIR|null $uiR): bool
    {
        if (!empty($cc) && is_string($cc) &&(strlen($cc)>4) && !is_array($cc)) {
            // Allow multiple CC's delimited by comma or semicolon
            $cc = (strpos($cc, ',')) ? explode(',', $cc) : explode(';', $cc);
        }
        
        if (!empty($bcc) && is_string($bcc) && (strlen($bcc)>4) && !is_array($bcc)) {
            // Allow multiple BCC's delimited by comma or semicolon
            $bcc = (strpos($bcc, ',')) ? explode(',', $bcc) : explode(';', $bcc);
        }
        
        // Bcc mails to admin && the admin email account has been setup under userinv which is an extension table of user
        if (null!==$uiR) { 
            if (($this->s->get_setting('bcc_mails_to_admin') == 1) && ($uiR->repoUserInvUserIdcount((string)1) > 0)) {
                $user_inv = $uiR->repoUserInvUserIdquery((string)1) ?: null;
                $email = null!==$user_inv ? $user_inv->getEmail() : '';
                // $bcc should be an array after the explode 
                is_array($bcc) && $email!=='' ? array_unshift($bcc, $email) : '';
            }
        }
        $email = $this->mailer
            ->compose(
                'contact-email',
                [
                    'content' => $html_body,
                ]
            )
            ->withCharSet('UTF-8')
            ->withSubject($subject)
            ->withDate(new \DateTimeImmutable('now'))
            ->withFrom([$from_email=>$from_name])
            ->withTo($to);
        /** @var array<array-key, string>|string $cc */
        is_array($cc) && !empty($cc) ? $email->withCC($cc) : '';
        /** @var array<array-key, string>|string $bcc */
        is_array($bcc) && !empty($bcc) ? $email->withBcc($bcc) : '';
        !empty($html_body) ? $email->withHtmlBody($html_body) : '';
        !empty($html_body) ? $email->withTextBody(strip_tags($html_body)) : '';
        /** @var array $attachFile */
        foreach ($attachFiles as $attachFile) {
            /** 
             * @var array $file 
             * @psalm-suppress MixedMethodCall 
             */
            foreach ($attachFile as $file) {
                if ($file[0]?->getError() === UPLOAD_ERR_OK && (null!==$file[0]?->getStream())) {
                    /** @psalm-suppress MixedAssignment $email */
                    $email = $email->withAttached(
                        File::fromContent(
                            (string)$file[0]?->getStream(),
                            (string)$file[0]?->getClientFilename(),
                            (string)$file[0]?->getClientMediaType()
                        ),
                    );
                }
            }
        }
        
        // If Setting...View...Email...Attach Invoice/Quote on email is 'yes' => attach archived pdf
        // generated by PdfHelper->generate_inv_pdf
        if (!empty($pdf_template_target_path)) { 
            $path_info = pathinfo($pdf_template_target_path);
            $path_info_file_name = $path_info['filename'];
            $email_attachments_with_pdf_template = $email->withAttached(File::fromPath(FileHelper::normalizePath($pdf_template_target_path), 
                                                    $path_info_file_name, 
                                                    'application/pdf')
            );
        } else {
            $email_attachments_with_pdf_template = $email;
        }
        // Ensure that the administrator exists in the userinv extension table. If the email is blank generate a flash
        if (null!==$uiR) {
            if ($uiR->repoUserInvUserIdcount((string)1) == 0) {
                $admin = new UserInv();
                $admin->setUser_id(1);
                // Administrator's are given a type of 0, Guests eg. Accountant 1
                $admin->setType(0);
                $admin->setName('Administrator');
                $admin->setEmail('setup@your.email');
                $uiR->save($admin);
            }
        }
        try {
            $this->mailer->send($email_attachments_with_pdf_template);
            $this->flash_message('info', $this->s->trans('email_successfully_sent'));
            return true;
        } catch (\Exception $e) {
            $this->flash_message('warning', $this->translator->translate('invoice.invoice.email.not.sent.successfully')); 
            $this->logger->error($e->getMessage());            
        } 
        return false;
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
    
    

