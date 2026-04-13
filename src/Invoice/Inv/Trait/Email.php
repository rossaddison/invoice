<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Helpers\MailerHelper;
use App\Invoice\Helpers\TemplateHelper;

use App\Invoice\Entity\
{
    Inv, InvSentLog, EmailTemplate
};

use App\Invoice\{
    Client\ClientRepository as CR,
    ClientCustom\ClientCustomRepository as CCR,
    CustomValue\CustomValueRepository as CVR,
    CustomField\CustomFieldRepository as CFR,
    DeliveryLocation\DeliveryLocationRepository as DLR,
    EmailTemplate\EmailTemplateRepository as ETR,
    Group\GroupRepository as GR,
    Inv\InvRepository as IR,
    Inv\MailerInvForm,
    InvAllowanceCharge\InvAllowanceChargeRepository as ACIR,
    InvCustom\InvCustomRepository as ICR,
    InvItem\InvItemRepository as IIR,
    InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR,
    InvAmount\InvAmountRepository as IAR,
    InvItemAmount\InvItemAmountRepository as IIAR,
    InvSentLog\InvSentLogRepository as ISLR,
    InvTaxRate\InvTaxRateRepository as ITRR,
    PaymentCustom\PaymentCustomRepository as PCR,
    Quote\QuoteRepository as QR,
    QuoteAmount\QuoteAmountRepository as QAR,
    QuoteCustom\QuoteCustomRepository as QCR,
    SalesOrder\SalesOrderRepository as SOR,
    SalesOrderCustom\SalesOrderCustomRepository as SOCR,
    UserInv\UserInvRepository as UIR
};

use Yiisoft\{
    Json\Json, Router\HydratorAttribute\RouteArgument,
    Yii\View\Renderer\WebViewRenderer
};

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait Email
{
    public function emailGetInvoiceTemplates(string $type = 'pdf'): array
    {
        return $this->sR->getInvoiceTemplates($type);
    }

    public function emailStage0(
        WebViewRenderer $head,
        #[RouteArgument('id')]
        int $id,
        CCR $ccR,
        CFR $cfR,
        CVR $cvR,
        ETR $etR,
        ICR $icR,
        IR $iR,
        PCR $pcR,
        SOCR $socR,
        QCR $qcR,
        UIR $uiR,
    ): Response {
        $mailer_helper = new MailerHelper(
            $this->sR, $this->session, $this->translator, $this->logger,
                $this->mailer, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
        $template_helper = new TemplateHelper($this->sR, $ccR, $qcR, $icR, $pcR,
            $socR, $cfR, $cvR);
        if (!$mailer_helper->mailerConfigured()) {
            $this->flashMessage('warning',
                $this->translator->translate('email.not.configured'));
            return $this->webService->getRedirectResponse('inv/index');
        }
        $inv = $this->inv($id, $iR, true);
        if ($inv instanceof Inv) {
            $inv_id = $inv->getId();
            $invoice = $iR->repoInvUnLoadedquery((string) $inv_id);
            if ($invoice instanceof Inv) {
                // Get all custom fields
                $custom_fields = [];
                $custom_tables = [
                    'client_custom' => 'client',
                    'inv_custom' => 'invoice',
                    'payment_custom' => 'payment',
                    'quote_custom' => 'quote',
                    'salesorder_custom' => 'salesorder',
                ];
                foreach (array_keys($custom_tables) as $table) {
                    $custom_fields[$table] = $cfR->repoTablequery($table);
                }
                if ($template_helper->selectEmailInvoiceTemplate(
                    $invoice) == '') {
                    $this->flashMessage('warning',
                    $this->translator->translate('email.template.not.configured'));
                    return $this->webService->getRedirectResponse(
                        'setting/tabIndex', ['_language' => 'en'],
                            ['active' => 'invoices'],
                            'settings[email_invoice_template]');
                }
                $setting_status_email_template = $etR->repoEmailTemplatequery(
                    $template_helper->selectEmailInvoiceTemplate($invoice)) ?:
                        null;
                null === $setting_status_email_template ? $this->flashMessage(
                    'info',
                    $this->translator->translate('default.email.template') . '=>'
                    . $this->translator->translate('not.set'),
                ) : '';
                empty($template_helper->selectPdfInvoiceTemplate($invoice)) ?
                    $this->flashMessage(
                    'info',
                    $this->translator->translate('default.pdf.template') . '=>'
                    . $this->translator->translate('not.set'),
                ) : '';
                $parameters = [
                    'head' => $head,
                    'actionName' => 'inv/emailStage2',
                    'actionArguments' => ['id' => $inv_id],
                    'alert' => $this->alert(),
                    // If email templates have been built under Setting...Email
                    // Template for Normal, Overdue, and Paid and Setting...View
                    // ...Invoice...Invoice Templates have been linked to these
                    // built email templates then an email template should
                    // automatically appear on the mailer_invoice form by
                    // passing the status related email template to the
                    // get_inject_email_template_array function
                    'autoTemplate' => null !== $setting_status_email_template ?
                        $this->getInjectEmailTemplateArray(
                            $setting_status_email_template) : [],
                    //eg. If the invoice is overdue ie. status is 5,
                    //automatically select the 'overdue' pdf template
                    //which has 'overdue' text on it as a watermark
                    'settingStatusPdfTemplate' =>
                        $template_helper->selectPdfInvoiceTemplate($invoice),
                    'emailTemplates' => $etR->repoEmailTemplateType('invoice'),
                    'dropdownTitlesOfEmailTemplates' => $this->emailTemplates(
                        $etR),
                    'userInv' => $uiR->repoUserInvUserIdcount(
                        $invoice->getUserId()) > 0 ?
                        $uiR->repoUserInvUserIdquery($invoice->getUserId())
                        : null,
                    'invoice' => $invoice,
                    // All templates ie. overdue, paid, invoice
                    'pdfTemplates' => $this->emailGetInvoiceTemplates('pdf'),
                    'templateTags' => $this->webViewRenderer->renderPartialAsString(
                        '//invoice/emailtemplate/template-tags-with-inv', [
                            'custom_fields' => $custom_fields,
                            'template_tags_quote' => '',
                            'template_tags_inv' =>
                            $this->webViewRenderer->renderPartialAsString(
                                '//invoice/emailtemplate/template-tags-inv', [
                            'custom_fields_inv_custom' =>
                                $custom_fields['inv_custom'],
                        ]),
                    ]),
                    'form' => new MailerInvForm(),
                ];
                return $this->webViewRenderer->render('mailer_invoice', $parameters);
            }// if invoice
            return $this->webService->getRedirectResponse('inv/index');
        } // if $inv
        return $this->webService->getRedirectResponse('inv/index');
    }

    public function getInjectEmailTemplateArray(EmailTemplate $email_template):
        array
    {
        return [
            'body' => Json::htmlEncode($email_template->getEmailTemplateBody()),
            'subject' => $email_template->getEmailTemplateSubject() ?? '',
            'from_name' => $email_template->getEmailTemplateFromName() ?? '',
            'from_email' => $email_template->getEmailTemplateFromEmail() ?? '',
            'cc' => $email_template->getEmailTemplateCc() ?? '',
            'bcc' => $email_template->getEmailTemplateBcc() ?? '',
            'pdf_template' => $email_template->getEmailTemplatePdfTemplate()
                ?? '',
        ];
    }

    /**
     * @param ETR $etR
     *
     * @return (string|null)[]
     *
     * @psalm-return array<''|int, null|string>
     */
    public function emailTemplates(ETR $etR): array
    {
        $email_templates = $etR->repoEmailTemplateType('invoice');
        $data = [];
        /** @var EmailTemplate $email_template */
        foreach ($email_templates as $email_template) {
            if (null !== $email_template->getEmailTemplateId()) {
                $data[] = $email_template->getEmailTemplateTitle();
            }
        }
        return $data;
    }

    public function emailStage1(
        ?string $inv_id,
        array $from,
        // $to can only have one email address
        string $to,
        string $subject,
        string $email_body,
        string $cc,
        string $bcc,
        array $attachFiles,
        CR $cR,
        CCR $ccR,
        CFR $cfR,
        DLR $dlR,
        ACIR $aciR,
        CVR $cvR,
        IAR $iaR,
        ICR $icR,
        IIAR $iiaR,
        ACIIR $aciiR,
        IIR $iiR,
        IR $iR,
        ITRR $itrR,
        PCR $pcR,
        SOCR $socR,
        QR $qR,
        QAR $qaR,
        QCR $qcR,
        SOR $soR,
        UIR $uiR,
        WebViewRenderer $webViewRenderer,
    ): bool {
        $template_helper = new TemplateHelper($this->sR, $ccR, $qcR, $icR, $pcR,
            $socR, $cfR, $cvR);
        $mailer_helper = new MailerHelper(
            $this->sR,
            $this->session,
            $this->translator,
            $this->logger,
            $this->mailer,
            $ccR,
            $qcR,
            $icR,
            $pcR,
            $socR,
            $cfR,
            $cvR,
        );
        if (null !== $inv_id) {
            $inv_amount = (($iaR->repoInvAmountCount((int) $inv_id) > 0) ?
                $iaR->repoInvquery((int) $inv_id) : null);
            $inv_custom_values = $this->invCustomValues($inv_id, $icR);
            $inv = $iR->repoCount($inv_id) > 0 ?
                $iR->repoInvUnLoadedquery($inv_id) : null;
            if ($inv) {
                // The Google sign under Invoices ... Pdf Settings
                // The initial recommendation for testing email sending is that
                // this be set to off ie. 1 so that a plain successful message
                // can be output without interferance from a pdf
                $stream = ($this->sR->getSetting('pdf_stream_inv') == '1' ?
                    true : false);
                $so = ($inv->getSoId() ? $soR->repoSalesOrderLoadedquery(
                    $inv->getSoId()) : null);
                // true => invoice ie. not quote
                // If $stream is false => pdfhelper->generate_inv_pdf =>
                // mpdfhelper->pdf_Create => filename returned
                $pdf_template_target_path = $this->pdfHelper->generateInvPdf(
                    $inv_id, $inv->getUserId(), $stream, true, $so, $inv_amount,
                        $inv_custom_values, $cR, $cvR, $cfR, $dlR, $aciR, $iiR,
                            $aciiR, $iiaR, $iR, $itrR, $uiR, $webViewRenderer);
                if ($pdf_template_target_path) {
                    $mail_message = $template_helper->parseTemplate(
                        $inv_id, true, $email_body, $cvR, $iR, $iaR, $qR,
                            $qaR, $soR, $uiR);
                    $mail_subject = $template_helper->parseTemplate(
                        $inv_id, true, $subject, $cvR, $iR, $iaR, $qR,
                            $qaR, $soR, $uiR);
                    $mail_cc = $template_helper->parseTemplate($inv_id, true,
                        $cc, $cvR, $iR, $iaR, $qR, $qaR, $soR, $uiR);
                    $mail_bcc = $template_helper->parseTemplate($inv_id, true,
                        $bcc, $cvR, $iR, $iaR, $qR, $qaR, $soR, $uiR);
                    // from[0] is the from_email and from[1] is the from_name
                    /**
                     * @var string $from[0]
                     * @var string $from[1]
                     */
                    $mail_from = [$template_helper->parseTemplate($inv_id, true,
                        $from[0], $cvR, $iR, $iaR, $qR, $qaR, $soR, $uiR),
                            $template_helper->parseTemplate($inv_id, true,
                                $from[1], $cvR, $iR, $iaR, $qR, $qaR, $soR,
                                    $uiR)];
                    //$message = (empty($mail_message) ? 'this is a message ' :
                    //  $mail_message);
                    $message = $mail_message;
                    // mail_from[0] is the from_email and mail_from[1] is the
                    // from_name
                    return $mailer_helper->yiiMailerSend(
                        $mail_from[0],
                        $mail_from[1],
                        $to,
                        $mail_subject,
                        $message,
                        $mail_cc,
                        $mail_bcc,
                        $attachFiles,
                        $pdf_template_target_path,
                        $uiR,
                    );
                } //is_string
            } //inv
            return false;
        } // inv_id
        return false;
    }

    public function emailStage2(
        Request $request,
        #[RouteArgument('id')]
        int $inv_id,
        CR $cR,
        CCR $ccR,
        CFR $cfR,
        DLR $dlR,
        ACIR $aciR,
        CVR $cvR,
        GR $gR,
        IAR $iaR,
        ACIIR $aciiR,
        IIAR $iiaR,
        ICR $icR,
        IIR $iiR,
        IR $iR,
        ITRR $itrR,
        PCR $pcR,
        SOCR $socR,
        QR $qR,
        QAR $qaR,
        QCR $qcR,
        SOR $soR,
        UIR $uiR,
        ISLR $islR,
    ): Response {
        if ($inv_id) {
            $mailer_helper = new MailerHelper($this->sR, $this->session,
                $this->translator, $this->logger, $this->mailer, $ccR, $qcR,
                    $icR, $pcR, $socR, $cfR, $cvR);
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $body['btn_cancel'] = 0;
                if (!$mailer_helper->mailerConfigured()) {
                    $this->flashMessage('warning',
                        $this->translator->translate('email.not.configured'));
                    return $this->webService->getRedirectResponse('inv/index');
                }
                /**
                 * @var string $to
                 */
                $to = $body['MailerInvForm']['to_email'] ?? '';
                if (empty($to)) {
                    return $this->factory->createResponse(
                        $this->webViewRenderer->renderPartialAsString(
                        '//invoice/setting/inv_message',
                        ['heading' => '', 'message' =>
                         $this->translator->translate('email.to.address.missing'),
                            'url' => 'inv/view', 'id' => $inv_id],
                    ));
                }
                /**
                 * @var array $from
                 */
                $from = [
                    $body['MailerInvForm']['from_email'] ?? '',
                    $body['MailerInvForm']['from_name'] ?? '',
                ];

                if (empty($from[0])) {
                    return $this->factory->createResponse(
                        $this->webViewRenderer->renderPartialAsString(
                        '//invoice/setting/inv_message',
                        ['heading' => '',
                         'message' =>
                         $this->translator->translate('email.to.address.missing'),
                         'url' => 'inv/view', 'id' => $inv_id],
                    ));
                }
                /** @var array $body['MailerInvForm'] */
                $subject = (string) $body['MailerInvForm']['subject'] ?: '';
                /**  @var string $email_body */
                $email_body = $body['MailerInvForm']['body'] ?? '';

                /**  @var string $cc */
                $cc = $body['MailerInvForm']['cc'] ?? '';
                /**  @var string $bcc */
                $bcc = $body['MailerInvForm']['bcc'] ?? '';

                $attachFiles = $request->getUploadedFiles();

                $this->generateInvNumberIfApplicable((string) $inv_id,
                    $iR, $this->sR, $gR);

                // Custom fields are automatically included on the invoice
                if ($this->emailStage1(
                    (string) $inv_id,
                    $from,
                    $to,
                    $subject,
                    $email_body,
                    $cc,
                    $bcc,
                    $attachFiles,
                    $cR,
                    $ccR,
                    $cfR,
                    $dlR,
                    $aciR,
                    $cvR,
                    $iaR,
                    $icR,
                    $iiaR,
                    $aciiR,
                    $iiR,
                    $iR,
                    $itrR,
                    $pcR,
                    $socR,
                    $qR,
                    $qaR,
                    $qcR,
                    $soR,
                    $uiR,
                    $this->webViewRenderer,
                )) {
                    $invoice = $iR->repoInvUnloadedquery((string) $inv_id);
                    if ($invoice) {
                        //draft->sent->view->paid
                        //set the invoice to sent ie. 2
                        $invoice->setStatusId(2);
                        // Make read_only if status is sent i.e. 2 and read-only
                        // ability exists
                        if (($this->sR->getSetting('read_only_toggle') == '2')
                            &&  ($this->sR->getSetting(
                                    'disable_read_only') == '0')) {
                            $invoice->setIsReadOnly(true);
                        }
                        //keep a record of all the times this invoice is sent
                        $this->emailedThereforeAddLog($invoice, $islR);
                        $iR->save($invoice);
                    }
                    return $this->factory->createResponse(
                        $this->webViewRenderer->renderPartialAsString(
                        '//invoice/setting/inv_message',
                        // EMAIL SENT
                        ['heading' => '',
                            'message' => $this->translator->translate(
                                'email.successfully.sent'),
                            'url' => 'inv/view',
                            'id' => $inv_id],
                    ));
                }
                return $this->factory->createResponse(
                    $this->webViewRenderer->renderPartialAsString(
                    '//invoice/setting/inv_message',
                    // EMAIL ... NOT ... SENT
                    ['heading' => '',
                        'message' => $this->translator->translate(
                                'email.not.sent.successfully'),
                        'url' => 'inv/view',
                        'id' => $inv_id],
                ));
                //$this->email_stage_1
            } //is_array(body)
            return $this->factory->createResponse(
                    $this->webViewRenderer->renderPartialAsString(
                '//invoice/setting/inv_message',
                ['heading' => '', 'message' => $this->translator->translate(
                        'email.not.sent.successfully'),
                    'url' => 'inv/view', 'id' => $inv_id],
            ));
        }
        return $this->factory->createResponse(
                $this->webViewRenderer->renderPartialAsString(
            '//invoice/setting/inv_message',
            ['heading' => '', 'message' => $this->translator->translate(
                    'email.not.sent'),
                'url' => 'inv/view', 'id' => $inv_id],
        ));
    }

    private function emailedThereforeAddLog(Inv $invoice, ISLR $islR): void
    {
        $invSentLog = new InvSentLog();
        $invSentLog->setClientId((int) $invoice->getClientId());
        $invSentLog->setInvId((int) $invoice->getId());
        $invSentLog->setDateSent(new \DateTimeImmutable('now'));
        $islR->save($invSentLog);
    }
}
