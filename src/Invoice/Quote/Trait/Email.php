<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Infrastructure\Persistence\EmailTemplate\EmailTemplate;
use App\Invoice\Quote\{
    MailerQuoteForm,
    QuoteEmailStage0Deps,
    QuoteEmailStage1Data,
    QuoteEmailStage2Deps,
    QuotePdfService,
};
use App\Invoice\Helpers\{MailerHelper, MailerHelperCustomDeps, MailerSendParams, TemplateHelper};
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
        #[RouteArgument('id')] int $id,
        QuoteEmailStage0Deps $d,
    ): Response {
        $mailerDeps = new MailerHelperCustomDeps($d->ccR, $d->qcR, $d->icR, $d->pcR, $d->socR, $d->cfR, $d->cvR);
        $mailer_helper = new MailerHelper(
            $this->sR, $this->session, $this->translator, $this->logger, $this->mailer, $mailerDeps);
        $template_helper = new TemplateHelper(
            $this->sR, $d->ccR, $d->qcR, $d->icR, $d->pcR, $d->socR, $d->cfR, $d->cvR);
        if (!$mailer_helper->mailerConfigured()) {
            $this->flashMessage('warning', $this->translator->translate('email.not.configured'));
            return $this->webService->getRedirectResponse('quote/index');
        }
        $quote_entity = $this->quote($id, $d->qR, true);
        if ($quote_entity) {
            $quote_id = $quote_entity->reqId();
            $quote = $d->qR->repoQuoteUnLoadedquery($quote_id);
            if ($quote) {
                $custom_fields = [];
                $custom_tables = [
                    'client_custom' => 'client',
                    'inv_custom' => 'invoice',
                    'payment_custom' => 'payment',
                    'quote_custom' => 'quote',
                    'salesorder_custom' => 'salesorder',
                ];
                foreach (array_keys($custom_tables) as $table) {
                    $custom_fields[$table] = $d->cfR->repoTablequery($table);
                }
                if ($template_helper->selectEmailQuoteTemplate() == '') {
                    $this->flashMessage('warning',
                        $this->translator->translate('quote.email.templates.not.configured'));
                    return $this->webService->getRedirectResponse(
                        'setting/tabIndex', ['_language' => 'en'],
                            ['active' => 'quotes'], 'settings[email_quote_template]');
                }
                $setting_status_email_template =
                    $d->etR->repoEmailTemplatequery((int) $template_helper->selectEmailQuoteTemplate()) ?: null;
                null === $setting_status_email_template ? $this->flashMessage(
                    'info',
                    $this->translator->translate('default.email.template') . '=>'
                    . $this->translator->translate('not.set'),
                ) : '';
                empty($template_helper->selectPdfQuoteTemplate()) ? $this->flashMessage(
                    'info',
                    $this->translator->translate('default.pdf.template') . '=>'
                    . $this->translator->translate('not.set'),
                ) : '';
                $parameters = [
                    'head' => $head,
                    'actionName' => 'quote/emailStage2',
                    'actionArguments' => ['id' => $quote_id],
                    'alert' => $this->alert(),
                    'autoTemplate' => null !== $setting_status_email_template
                        ? $this->getInjectEmailTemplateArray($setting_status_email_template)
                        : [],
                    'settingStatusPdfTemplate' => $template_helper->selectPdfQuoteTemplate(),
                    'email_templates' => $d->etR->repoEmailTemplateType('quote'),
                    'dropdownTitlesOfEmailTemplates' => $this->emailTemplates($d->etR),
                    'userInv' => $d->uiR->repoUserInvUserIdcount($quote->reqUserId()) > 0
                        ? $d->uiR->repoUserInvUserIdquery($quote->reqUserId())
                        : null,
                    'quote' => $quote,
                    'pdfTemplates' => $this->emailGetQuoteTemplates('pdf'),
                    'templateTags' => $this->webViewRenderer->renderPartialAsString(
                        '//invoice/emailtemplate/template-tags', [
                            'custom_fields' => $custom_fields,
                            'template_tags_inv' => '',
                            'template_tags_quote' => $this->webViewRenderer->renderPartialAsString(
                                '//invoice/emailtemplate/template-tags-quote', [
                                    'custom_fields_quote_custom' => $custom_fields['quote_custom'],
                                ]),
                        ]),
                    'form' => new MailerQuoteForm(),
                    'custom_fields' => $custom_fields,
                ];
                return $this->webViewRenderer->render('mailer_quote', $parameters);
            }
            return $this->webService->getRedirectResponse('quote/index');
        }
        return $this->webService->getRedirectResponse('quote/index');
    }

    public function getInjectEmailTemplateArray(EmailTemplate $email_template): array
    {
        return [
            'body' => Json::htmlEncode($email_template->getEmailTemplateBody()),
            'subject' => $email_template->getEmailTemplateSubject() ?? '',
            'from_name' => $email_template->getEmailTemplateFromName() ?? '',
            'from_email' => $email_template->getEmailTemplateFromEmail() ?? '',
            'cc' => $email_template->getEmailTemplateCc() ?? '',
            'bcc' => $email_template->getEmailTemplateBcc() ?? '',
            'pdf_template' => $email_template->getEmailTemplatePdfTemplate() ?? '',
        ];
    }

    /**
     * @return (string|null)[]
     * @psalm-return array<''|int, null|string>
     */
    public function emailTemplates(\App\Invoice\EmailTemplate\EmailTemplateRepository $etR): array
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
        int $quote_id,
        QuoteEmailStage1Data $data,
        QuoteEmailStage2Deps $d,
        QuotePdfService $quotePdfService,
    ): bool {
        $template_helper = new TemplateHelper(
            $this->sR, $d->ccR, $d->qcR, $d->icR, $d->pcR, $d->socR, $d->cfR, $d->cvR);
        $mailerDeps = new MailerHelperCustomDeps($d->ccR, $d->qcR, $d->icR, $d->pcR, $d->socR, $d->cfR, $d->cvR);
        $mailer_helper = new MailerHelper(
            $this->sR, $this->session, $this->translator, $this->logger, $this->mailer, $mailerDeps);
        $quote_entity = $d->qR->repoCount($quote_id) > 0
            ? $d->qR->repoQuoteUnLoadedquery($quote_id)
            : null;
        if ($quote_entity) {
            $pdf_template_target_path = $quotePdfService->generate($quote_id, false, true);
            if ($pdf_template_target_path !== '') {
                $mail_message = $template_helper->parseTemplate(
                    $quote_id, false, $data->emailBody, $d->cvR, $d->iR, $d->iaR, $d->qR, $d->qaR, $d->soR, $d->uiR);
                $mail_subject = $template_helper->parseTemplate(
                    $quote_id, false, $data->subject, $d->cvR, $d->iR, $d->iaR, $d->qR, $d->qaR, $d->soR, $d->uiR);
                $mail_cc = $template_helper->parseTemplate(
                    $quote_id, false, $data->cc, $d->cvR, $d->iR, $d->iaR, $d->qR, $d->qaR, $d->soR, $d->uiR);
                $mail_bcc = $template_helper->parseTemplate(
                    $quote_id, false, $data->bcc, $d->cvR, $d->iR, $d->iaR, $d->qR, $d->qaR, $d->soR, $d->uiR);
                $mail_from_email = $template_helper->parseTemplate($quote_id, false, $data->fromEmail, $d->cvR, $d->iR, $d->iaR, $d->qR, $d->qaR, $d->soR, $d->uiR);
                $mail_from_name = $template_helper->parseTemplate($quote_id, false, $data->fromName, $d->cvR, $d->iR, $d->iaR, $d->qR, $d->qaR, $d->soR, $d->uiR);
                $mailerParams = new MailerSendParams($mail_from_email, $mail_from_name, $data->to, $mail_subject, $mail_message, $mail_cc, $mail_bcc);
                return $mailer_helper->yiiMailerSend($mailerParams, $data->attachFiles, $pdf_template_target_path, $d->uiR);
            }
        }
        return false;
    }

    public function emailStage2(
        Request $request,
        #[RouteArgument('id')] int $quote_id,
        QuoteEmailStage2Deps $d,
        QuotePdfService $quotePdfService,
    ): Response {
        if ($quote_id) {
            $mailerDeps = new MailerHelperCustomDeps($d->ccR, $d->qcR, $d->icR, $d->pcR, $d->socR, $d->cfR, $d->cvR);
            $mailer_helper = new MailerHelper(
                $this->sR, $this->session, $this->translator, $this->logger, $this->mailer, $mailerDeps);
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $body['btn_cancel'] = 0;
                if (!$mailer_helper->mailerConfigured()) {
                    $this->flashMessage('warning', $this->translator->translate('email.not.configured'));
                    return $this->webService->getRedirectResponse('quote/index');
                }
                /** @var array $body['MailerQuoteForm'] */
                $to = (string) ($body['MailerQuoteForm']['to_email'] ?? '');
                if (empty($to)) {
                    return $this->factory->createResponse(
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/setting/quote_message',
                            ['heading' => '', 'message' =>
                                $this->translator->translate('email.to.address.missing'),
                             'url' => 'quote/view', 'id' => $quote_id],
                        ));
                }
                $from_email = (string) ($body['MailerQuoteForm']['from_email'] ?? '');
                $from_name = (string) ($body['MailerQuoteForm']['from_name'] ?? '');
                if (empty($from_email)) {
                    return $this->factory->createResponse(
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/setting/quote_message',
                            ['heading' => '', 'message' =>
                                $this->translator->translate('email.to.address.missing'),
                             'url' => 'quote/view', 'id' => $quote_id],
                        ));
                }
                /** @var string $subject */
                $subject = $body['MailerQuoteForm']['subject'] ?? '';
                $email_body = (string) ($body['MailerQuoteForm']['body'] ?? '');
                /** @var string $cc */
                $cc = $body['MailerQuoteForm']['cc'] ?? '';
                /** @var string $bcc */
                $bcc = $body['MailerQuoteForm']['bcc'] ?? '';
                $attachFiles = $request->getUploadedFiles();
                $this->generateQuoteNumberIfApplicable($quote_id, $d->qR, $this->sR, $d->gR);
                if ($this->emailStage1(
                    $quote_id,
                    new QuoteEmailStage1Data($from_email, $from_name, $to, $subject, $email_body, $cc, $bcc, $attachFiles),
                    $d,
                    $quotePdfService,
                )) {
                    $this->sR->quoteMarkSent($quote_id, $d->qR);
                    $this->flashMessage('success', $this->translator->translate('email.successfully.sent'));
                    return $this->webService->getRedirectResponse('quote/view', ['id' => $quote_id]);
                }
            }
        }
        $this->flashMessage('danger', $this->translator->translate('email.not.sent.successfully'));
        return $this->webService->getRedirectResponse('quote/view', ['id' => $quote_id]);
    }
}
