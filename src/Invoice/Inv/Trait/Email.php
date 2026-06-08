<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Helpers\MailerHelper;
use App\Invoice\Helpers\TemplateHelper;
use App\Infrastructure\Persistence\{
    Inv\Inv, InvSentLog\InvSentLog,
    EmailTemplate\EmailTemplate
};

use App\Invoice\{
    EmailTemplate\EmailTemplateRepository as ETR,
    Inv\InvEmailStage0Deps,
    Inv\InvEmailStage1Data,
    Inv\InvEmailStage2Deps,
    Inv\MailerInvForm,
    InvSentLog\InvSentLogRepository as ISLR
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
        InvEmailStage0Deps $d,
    ): Response {
        $mailer_helper = new MailerHelper(
            $this->sR, $this->session, $this->translator, $this->logger,
                $this->mailer, $d->ccR, $d->qcR, $d->icR, $d->pcR, $d->socR, $d->cfR, $d->cvR);
        $template_helper = new TemplateHelper($this->sR, $d->ccR, $d->qcR, $d->icR, $d->pcR,
            $d->socR, $d->cfR, $d->cvR);
        if (!$mailer_helper->mailerConfigured()) {
            $this->flashMessage('warning',
                $this->translator->translate('email.not.configured'));
            return $this->webService->getRedirectResponse('inv/index');
        }
        $inv = $this->inv($id, $d->iR, true);
        if (null !== $inv) {
            $inv_id = $inv->reqId();
            $invoice = $d->iR->repoInvUnLoadedquery($inv_id);
            if ($invoice instanceof Inv) {
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
                if ($template_helper->selectEmailInvoiceTemplate($invoice) == '') {
                    $this->flashMessage('warning',
                        $this->translator->translate('email.template.not.configured'));
                    return $this->webService->getRedirectResponse(
                        'setting/tabIndex', ['_language' => 'en'],
                            ['active' => 'invoices'],
                            'settings[email_invoice_template]');
                }
                $setting_status_email_template =
                    $d->etR->repoEmailTemplatequery((int)
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
                    'autoTemplate' => null !== $setting_status_email_template ?
                        $this->getInjectEmailTemplateArray(
                            $setting_status_email_template) : [],
                    'settingStatusPdfTemplate' =>
                        $template_helper->selectPdfInvoiceTemplate($invoice),
                    'emailTemplates' => $d->etR->repoEmailTemplateType('invoice'),
                    'dropdownTitlesOfEmailTemplates' => $this->emailTemplates($d->etR),
                    'userInv' => $d->uiR->repoUserInvUserIdcount(
                        $invoice->reqUserId()) > 0 ?
                        $d->uiR->repoUserInvUserIdquery($invoice->reqUserId())
                        : null,
                    'invoice' => $invoice,
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
            }
            return $this->webService->getRedirectResponse('inv/index');
        }
        return $this->webService->getRedirectResponse('inv/index');
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
     *
     * @psalm-return array<''|int, null|string>
     */
    public function emailTemplates(ETR $etR): array
    {
        $email_templates = $etR->repoEmailTemplateType('invoice');
        $data = [];
        /** @var EmailTemplate $email_template */
        foreach ($email_templates as $email_template) {
            $data[] = $email_template->getEmailTemplateTitle();
        }
        return $data;
    }

    public function emailStage1(
        int $inv_id,
        InvEmailStage1Data $data,
        InvEmailStage2Deps $d,
    ): bool {
        $template_helper = new TemplateHelper($this->sR, $d->ccR, $d->qcR, $d->icR, $d->pcR,
            $d->socR, $d->cfR, $d->cvR);
        $mailer_helper = new MailerHelper(
            $this->sR,
            $this->session,
            $this->translator,
            $this->logger,
            $this->mailer,
            $d->ccR,
            $d->qcR,
            $d->icR,
            $d->pcR,
            $d->socR,
            $d->cfR,
            $d->cvR,
        );
        $inv_amount = (($d->iaR->repoInvAmountCount($inv_id) > 0) ?
            $d->iaR->repoInvquery($inv_id) : null);
        $inv_custom_values = $this->invCustomValues($inv_id, $d->icR);
        $inv = $d->iR->repoCount($inv_id) > 0 ?
            $d->iR->repoInvUnLoadedquery($inv_id) : null;
        if ($inv) {
            $stream = ($this->sR->getSetting('pdf_stream_inv') == '1' ?
                true : false);
            $so = (($soId = $inv->getSoId()) > 0 ? $d->soR->repoSalesOrderLoadedquery(
                $soId) : null);
            $pdf_template_target_path = $this->pdfHelper->generateInvPdf(
                $inv_id, $inv->reqUserId(), $stream, true, $so, $inv_amount,
                    $inv_custom_values, $d->cR, $d->cvR, $d->cfR, $d->dlR, $d->aciR, $d->iiR,
                        $d->aciiR, $d->iiaR, $d->iR, $d->itrR, $d->uiR, $this->webViewRenderer);
            if ($pdf_template_target_path) {
                $mail_message = $template_helper->parseTemplate(
                    $inv_id, true, $data->emailBody, $d->cvR, $d->iR, $d->iaR, $d->qR,
                        $d->qaR, $d->soR, $d->uiR);
                $mail_subject = $template_helper->parseTemplate(
                    $inv_id, true, $data->subject, $d->cvR, $d->iR, $d->iaR, $d->qR,
                        $d->qaR, $d->soR, $d->uiR);
                $mail_cc = $template_helper->parseTemplate($inv_id, true,
                    $data->cc, $d->cvR, $d->iR, $d->iaR, $d->qR, $d->qaR, $d->soR, $d->uiR);
                $mail_bcc = $template_helper->parseTemplate($inv_id, true,
                    $data->bcc, $d->cvR, $d->iR, $d->iaR, $d->qR, $d->qaR, $d->soR, $d->uiR);
                /**
                 * @var string $data->from[0]
                 * @var string $data->from[1]
                 */
                $mail_from = [
                    $template_helper->parseTemplate($inv_id, true,
                        $data->from[0], $d->cvR, $d->iR, $d->iaR, $d->qR, $d->qaR, $d->soR, $d->uiR),
                    $template_helper->parseTemplate($inv_id, true,
                        $data->from[1], $d->cvR, $d->iR, $d->iaR, $d->qR, $d->qaR, $d->soR, $d->uiR),
                ];
                $message = $mail_message;
                return $mailer_helper->yiiMailerSend(
                    $mail_from[0],
                    $mail_from[1],
                    $data->to,
                    $mail_subject,
                    $message,
                    $mail_cc,
                    $mail_bcc,
                    $data->attachFiles,
                    $pdf_template_target_path,
                    $d->uiR,
                );
            }
        }
        return false;
    }

    public function emailStage2(
        Request $request,
        #[RouteArgument('id')]
        int $inv_id,
        InvEmailStage2Deps $d,
    ): Response {
        if ($inv_id) {
            $mailer_helper = new MailerHelper($this->sR, $this->session,
                $this->translator, $this->logger, $this->mailer, $d->ccR, $d->qcR,
                    $d->icR, $d->pcR, $d->socR, $d->cfR, $d->cvR);
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
                /** @var string $email_body */
                $email_body = $body['MailerInvForm']['body'] ?? '';
                /** @var string $cc */
                $cc = $body['MailerInvForm']['cc'] ?? '';
                /** @var string $bcc */
                $bcc = $body['MailerInvForm']['bcc'] ?? '';

                $attachFiles = $request->getUploadedFiles();

                $this->generateInvNumberIfApplicable($inv_id, $d->iR, $this->sR, $d->gR);

                if ($this->emailStage1(
                    $inv_id,
                    new InvEmailStage1Data($from, $to, $subject, $email_body, $cc, $bcc, $attachFiles),
                    $d,
                )) {
                    $invoice = $d->iR->repoInvUnloadedquery($inv_id);
                    if ($invoice) {
                        $invoice->setStatusId(2);
                        if (($this->sR->getSetting('read_only_toggle') == '2')
                            && ($this->sR->getSetting('disable_read_only') == '0')) {
                            $invoice->setIsReadOnly(true);
                        }
                        $this->emailedThereforeAddLog($invoice, $d->islR);
                        $d->iR->save($invoice);
                    }
                    return $this->factory->createResponse(
                        $this->webViewRenderer->renderPartialAsString(
                        '//invoice/setting/inv_message',
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
                    ['heading' => '',
                        'message' => $this->translator->translate(
                                'email.not.sent.successfully'),
                        'url' => 'inv/view',
                        'id' => $inv_id],
                ));
            }
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
        $invSentLog->setClientId($invoice->reqClientId());
        $invSentLog->setInvId($invoice->reqId());
        $invSentLog->setDateSent(new \DateTimeImmutable('now'));
        $islR->save($invSentLog);
    }
}
