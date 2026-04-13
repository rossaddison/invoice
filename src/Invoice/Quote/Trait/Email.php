<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Invoice\Entity\EmailTemplate;
use App\Invoice\{
    Client\ClientRepository as CR,
    ClientCustom\ClientCustomRepository as CCR,
    CustomField\CustomFieldRepository as CFR,
    CustomValue\CustomValueRepository as CVR,
    DeliveryLocation\DeliveryLocationRepository as DLR,
    EmailTemplate\EmailTemplateRepository as ETR,
    Group\GroupRepository as GR,
    Inv\InvRepository as IR,
    InvAmount\InvAmountRepository as IAR,
    InvCustom\InvCustomRepository as ICR,
    PaymentCustom\PaymentCustomRepository as PCR,
    Quote\QuoteRepository as QR,
    QuoteAmount\QuoteAmountRepository as QAR,
    QuoteCustom\QuoteCustomRepository as QCR,
    QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR,
    QuoteItemAmount\QuoteItemAmountRepository as QIAR,
    QuoteItem\QuoteItemRepository as QIR,
    QuoteTaxRate\QuoteTaxRateRepository as QTRR,
    SalesOrder\SalesOrderRepository as SOR,
    SalesOrderCustom\SalesOrderCustomRepository as SOCR,
    UserInv\UserInvRepository as UIR,
};
use App\Invoice\Quote\MailerQuoteForm;
use App\Invoice\Helpers\{MailerHelper, TemplateHelper};
use Yiisoft\{
    Json\Json,
    Router\HydratorAttribute\RouteArgument,
    Yii\View\Renderer\WebViewRenderer,
};
use Psr\{
    Http\Message\ResponseInterface as Response,
    Http\Message\ServerRequestInterface as Request,
};

trait Email
{
    public function emailGetQuoteTemplates(string $type = 'pdf'): array
    {
        return $this->sR->getQuoteTemplates($type);
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
        QR $qR,
        PCR $pcR,
        SOCR $socR,
        QCR $qcR,
        UIR $uiR,
    ): Response {
        $mailer_helper = new MailerHelper(
            $this->sR, $this->session, $this->translator, $this->logger,
                $this->mailer, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
        $template_helper = new TemplateHelper(
            $this->sR, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
        if (!$mailer_helper->mailerConfigured()) {
            $this->flashMessage('warning', $this->translator->translate(
                'email.not.configured'));
            return $this->webService->getRedirectResponse('quote/index');
        }
        $quote_entity = $this->quote($id, $qR, true);
        if ($quote_entity) {
            $quote_id = $quote_entity->getId();
            $quote = $qR->repoQuoteUnLoadedquery((string) $quote_id);
            if ($quote) {
                // Get all custom fields
                $custom_fields = [];
                $custom_tables = [
                    'client_custom' => 'client',
                    'inv_custom' => 'invoice',
                    'payment_custom' => 'payment',
                    'quote_custom' => 'quote',
                    'salesorder_custom' => 'salesorder'
                ];
                foreach (array_keys($custom_tables) as $table) {
                    $custom_fields[$table] = $cfR->repoTablequery($table);
                }
                if ($template_helper->selectEmailQuoteTemplate() == '') {
                    $this->flashMessage('warning',
                        $this->translator->translate(
                            'quote.email.templates.not.configured'));
                    return $this->webService->getRedirectResponse(
                        'setting/tabIndex', ['_language' => 'en'],
                            ['active' => 'quotes'],
                                'settings[email_quote_template]');
                }
                $setting_status_email_template = $etR->repoEmailTemplatequery(
                    $template_helper->selectEmailQuoteTemplate())
                                               ?: null;
                null === $setting_status_email_template ? $this->flashMessage(
                    'info',
                    $this->translator->translate('default.email.template')
                        . '=>'
                        . $this->translator->translate('not.set'),
                ) : '';

                empty($template_helper->selectPdfQuoteTemplate()) ?
                    $this->flashMessage('info',
                    $this->translator->translate('default.pdf.template')
                        . '=>'
                        . $this->translator->translate('not.set'),
                ) : '';
                $parameters = [
                    'head' => $head,
                    'actionName' => 'quote/emailStage2',
                    'actionArguments' => ['id' => $quote_id],
                    'alert' => $this->alert(),
                    'autoTemplate' => null !== $setting_status_email_template
                        ? $this->getInjectEmailTemplateArray(
                            $setting_status_email_template)
                        : [],
                    'settingStatusPdfTemplate' =>
                        $template_helper->selectPdfQuoteTemplate(),
                    'email_templates' => $etR->repoEmailTemplateType('quote'),
                    'dropdownTitlesOfEmailTemplates' =>
                        $this->emailTemplates($etR),
                    'userInv' => $uiR->repoUserInvUserIdcount(
                        $quote->getUserId()) > 0 ? $uiR->repoUserInvUserIdquery(
                            $quote->getUserId()) : null,
                    'quote' => $quote,
                    'pdfTemplates' => $this->emailGetQuoteTemplates('pdf'),
                    'templateTags' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/emailtemplate/template-tags', [
                        'custom_fields' => $custom_fields,
                        'template_tags_inv' => '',
                        'template_tags_quote' =>
                            $this->webViewRenderer->renderPartialAsString(
                                '//invoice/emailtemplate/template-tags-quote', [
                            'custom_fields_quote_custom' =>
                                $custom_fields['quote_custom'],
                        ]),
                    ]),
                    'form' => new MailerQuoteForm(),
                    'custom_fields' => $custom_fields,
                ];
                return $this->webViewRenderer->render('mailer_quote', $parameters);
            } // quote
            return $this->webService->getRedirectResponse('quote/index');
        } // quote_entity
        return $this->webService->getRedirectResponse('quote/index');
    }

    public function getInjectEmailTemplateArray(
        EmailTemplate $email_template): array
    {
        return [
            'body' => Json::htmlEncode(
                $email_template->getEmailTemplateBody()),
            'subject' =>
                $email_template->getEmailTemplateSubject() ?? '',
            'from_name' =>
                $email_template->getEmailTemplateFromName() ?? '',
            'from_email' =>
                $email_template->getEmailTemplateFromEmail() ?? '',
            'cc' =>
                $email_template->getEmailTemplateCc() ?? '',
            'bcc' =>
                $email_template->getEmailTemplateBcc() ?? '',
            'pdf_template' =>
                $email_template->getEmailTemplatePdfTemplate() ?? '',
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
        $email_templates = $etR->repoEmailTemplateType('quote');
        $data = [];
        /** @var EmailTemplate $email_template */
        foreach ($email_templates as $email_template) {
            $data[] = $email_template->getEmailTemplateTitle();
        }
        return $data;
    }

    public function emailStage1(
        ?string $quote_id,
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
        CVR $cvR,
        IAR $iaR,
        ICR $icR,
        QIAR $qiaR,
        ACQIR $acqiR,
        QIR $qiR,
        IR $iR,
        QTRR $qtrR,
        PCR $pcR,
        SOCR $socR,
        QR $qR,
        QAR $qaR,
        QCR $qcR,
        SOR $soR,
        UIR $uiR,
        WebViewRenderer $webViewRenderer,
    ): bool {
        // All custom repositories, including icR have to be initialised.
        $template_helper = new TemplateHelper(
            $this->sR, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
        $mailer_helper = new MailerHelper(
            $this->sR, $this->session, $this->translator, $this->logger,
                $this->mailer, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
        if (null !== $quote_id) {
            $quote_amount = (($qaR->repoQuoteAmountCount($quote_id) > 0) ?
                $qaR->repoQuotequery($quote_id) : null);
            $quote_custom_values = $this->quoteCustomValues($quote_id, $qcR);
            $quote_entity = $qR->repoCount($quote_id) > 0 ?
                $qR->repoQuoteUnLoadedquery($quote_id) : null;
            if ($quote_entity) {
                $stream = false;
                $pdf_template_target_path =
                    $this->pdfHelper->generateQuotePdf(
                        $quote_id, $quote_entity->getUserId(), $stream, true,
                            $quote_amount, $quote_custom_values, $cR, $cvR,
                                $cfR, $dlR, $qiR, $qiaR, $acqiR, $qR, $qtrR,
                                    $uiR, $webViewRenderer);
                if ($pdf_template_target_path) {
                    $mail_message = $template_helper->parseTemplate(
                        $quote_id, false, $email_body, $cvR, $iR, $iaR,
                            $qR, $qaR, $soR, $uiR);
                    $mail_subject = $template_helper->parseTemplate(
                        $quote_id, false, $subject, $cvR, $iR, $iaR, $qR,
                            $qaR, $soR, $uiR);
                    $mail_cc = $template_helper->parseTemplate(
                        $quote_id, false, $cc, $cvR, $iR, $iaR, $qR, $qaR,
                            $soR, $uiR);
                    $mail_bcc = $template_helper->parseTemplate($quote_id,
                        false, $bcc, $cvR, $iR, $iaR, $qR, $qaR, $soR,
                            $uiR);
                    // from[0] is the from_email and from[1] is the from_name
                    /**
                     * @var string $from[0]
                     * @var string $from[1]
                     */
                    $mail_from
                        = [$template_helper->parseTemplate($quote_id, false,
                            $from[0], $cvR, $iR, $iaR, $qR, $qaR, $soR,
                                $uiR),
                            $template_helper->parseTemplate($quote_id, false,
                                $from[1], $cvR, $iR, $iaR, $qR, $qaR, $soR,
                                    $uiR)];
                    // mail_from[0] is the from_email and mail_from[1] is
                    // the from_name
                    return $mailer_helper->yiiMailerSend(
                        $mail_from[0],
                        $mail_from[1],
                        $to,
                        $mail_subject,
                        $mail_message,
                        $mail_cc,
                        $mail_bcc,
                        $attachFiles,
                        $pdf_template_target_path,
                        $uiR,
                    );
                } // pdf_template_target_path
            } // quote_entity
            return false;
        } // quote_id
        return false;
    }

    public function emailStage2(
        Request $request,
        #[RouteArgument('id')]
        int $quote_id,
        CR $cR,
        CCR $ccR,
        CFR $cfR,
        DLR $dlR,
        CVR $cvR,
        GR $gR,
        IAR $iaR,
        QIAR $qiaR,
        ACQIR $acqiR,
        ICR $icR,
        QIR $qiR,
        IR $iR,
        QTRR $qtrR,
        PCR $pcR,
        SOCR $socR,
        QR $qR,
        QAR $qaR,
        QCR $qcR,
        SOR $soR,
        UIR $uiR,
    ): Response {
        if ($quote_id) {
            $mailer_helper = new MailerHelper(
                $this->sR, $this->session, $this->translator, $this->logger,
                    $this->mailer, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $body['btn_cancel'] = 0;
                if (!$mailer_helper->mailerConfigured()) {
                    $this->flashMessage('warning', $this->translator->translate(
                        'email.not.configured'));
                    return $this->webService->getRedirectResponse(
                        'quote/index');
                }

                /**
                 * @var array $body['MailerQuoteForm']
                 */
                $to = (string) $body['MailerQuoteForm']['to_email'] ?: '';
                if (empty($to)) {
                    return $this->factory->createResponse(
                        $this->webViewRenderer->renderPartialAsString(
                        '//invoice/setting/quote_message',
                        ['heading' => '',
                            'message' => $this->translator->translate(
                                'email.to.address.missing'), 'url' =>
                                    'quote/view','id' => $quote_id],
                    ));
                }

                /**
                 * @var array $from
                 */
                $from = [
                    $body['MailerQuoteForm']['from_email'] ?? '',
                    $body['MailerQuoteForm']['from_name'] ?? '',
                ];


                if (empty($from[0])) {
                    return $this->factory->createResponse(
                        $this->webViewRenderer->renderPartialAsString(
                        '//invoice/setting/quote_message',
                        ['heading' => '', 'message' =>
                            $this->translator->translate(
                                'email.to.address.missing'),
                                    'url' => 'quote/view','id' => $quote_id],
                    ));
                }

                /**
                 * @var string $subject
                 */
                $subject = $body['MailerQuoteForm']['subject'] ?? '';
                /**  @var string $body */
                $email_body = (string) $body['MailerQuoteForm']['body'];

                /**
                 * @var string $cc
                 */
                $cc = $body['MailerQuoteForm']['cc'] ?? '';
                /**
                 * @var string $bcc
                 */
                $bcc = $body['MailerQuoteForm']['bcc'] ?? '';

                $attachFiles = $request->getUploadedFiles();

                $this->generateQuoteNumberIfApplicable((string) $quote_id,
                    $qR, $this->sR, $gR);
                // Custom fields are automatically included on the quote
                if ($this->emailStage1((string) $quote_id, $from, $to,
                        $subject, $email_body, $cc, $bcc, $attachFiles, $cR,
                            $ccR, $cfR, $dlR, $cvR, $iaR, $icR, $qiaR, $acqiR,
                                $qiR, $iR, $qtrR, $pcR, $socR, $qR, $qaR, $qcR,
                                    $soR, $uiR, $this->webViewRenderer)) {
                    $this->sR->quoteMarkSent((string) $quote_id, $qR);
                    $this->flashMessage('success', $this->translator->translate(
                        'email.successfully.sent'));
                    return $this->webService->getRedirectResponse('quote/view',
                        ['id' => $quote_id]);
                }
            }
        } // quote_id
        $this->flashMessage('danger', $this->translator->translate(
                'email.not.sent.successfully'));
        return $this->webService->getRedirectResponse(
                'quote/view', ['id' => $quote_id]);
    }
}