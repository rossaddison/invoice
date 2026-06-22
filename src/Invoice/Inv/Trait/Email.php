<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Helpers\{MailerHelper, MailerHelperCustomDeps, TemplateHelper};
use App\Infrastructure\Persistence\{
    Inv\Inv, InvSentLog\InvSentLog,
    EmailTemplate\EmailTemplate
};

use App\Invoice\{
    EmailTemplate\EmailTemplateRepository as ETR,
    Inv\InvEmailService,
    Inv\InvEmailStage0Deps,
    Inv\InvEmailStage1Data,
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
        $mailerDeps = new MailerHelperCustomDeps($d->ccR, $d->qcR, $d->icR, $d->pcR, $d->socR, $d->cfR, $d->cvR);
        $mailer_helper = new MailerHelper(
            $this->sR, $this->session, $this->translator, $this->logger, $this->mailer, $mailerDeps);
        $template_helper = new TemplateHelper($this->sR, $d->ccR, $d->qcR, $d->icR, $d->pcR,
            $d->socR, $d->cfR, $d->cvR);
        if (!$mailer_helper->mailerConfigured()) {
            $this->flashMessage('warning',
                $this->translator->translate('email.not.configured'));
            return $this->webService->getRedirectResponse('inv/index');
        }
        $inv = $this->inv($id, $d->iR, true);
        $inv_id = null !== $inv ? $inv->reqId() : null;
        $invoice = null !== $inv_id ? $d->iR->repoInvUnLoadedquery($inv_id) : null;
        if ($inv_id === null || !($invoice instanceof Inv)) {
            return $this->webService->getRedirectResponse('inv/index');
        }
        return $this->renderEmailStage0($head, $invoice, $inv_id, $d, $template_helper);
    }

    private function renderEmailStage0(
        WebViewRenderer $head,
        Inv $invoice,
        int $inv_id,
        InvEmailStage0Deps $d,
        TemplateHelper $template_helper,
    ): Response {
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
        $emailTemplateName = $template_helper->selectEmailInvoiceTemplate($invoice);
        if ($emailTemplateName == '') {
            $this->flashMessage('warning',
                $this->translator->translate('email.template.not.configured'));
            return $this->webService->getRedirectResponse(
                'setting/tabIndex', ['_language' => 'en'],
                    ['active' => 'invoices'],
                    'settings[email_invoice_template]');
        }
        $setting_status_email_template =
            $d->etR->repoEmailTemplatequery((int) $emailTemplateName) ?: null;
        null === $setting_status_email_template ? $this->flashMessage(
            'info',
            $this->translator->translate('default.email.template') . '=>'
            . $this->translator->translate('not.set'),
        ) : '';
        $pdfTemplateName = $template_helper->selectPdfInvoiceTemplate($invoice);
        empty($pdfTemplateName) ? $this->flashMessage(
            'info',
            $this->translator->translate('default.pdf.template') . '=>'
            . $this->translator->translate('not.set'),
        ) : '';
        $userId = $invoice->reqUserId();
        $parameters = [
            'head' => $head,
            'actionName' => 'inv/emailStage2',
            'actionArguments' => ['id' => $inv_id],
            'alert' => $this->alert(),
            'autoTemplate' => null !== $setting_status_email_template ?
                $this->getInjectEmailTemplateArray(
                    $setting_status_email_template) : [],
            'settingStatusPdfTemplate' => $pdfTemplateName,
            'emailTemplates' => $d->etR->repoEmailTemplateType('invoice'),
            'dropdownTitlesOfEmailTemplates' => $this->emailTemplates($d->etR),
            'userInv' => $d->uiR->repoUserInvUserIdcount($userId) > 0
                ? $d->uiR->repoUserInvUserIdquery($userId)
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
        InvEmailService $invEmailService,
    ): bool {
        return $invEmailService->send($inv_id, $data);
    }

    public function emailStage2(
        Request $request,
        #[RouteArgument('id')]
        int $inv_id,
        InvEmailService $invEmailService,
    ): Response {
        if (!$inv_id) {
            return $this->invMessageResponse($inv_id, 'email.not.sent');
        }
        $d = $invEmailService->d;
        if (!$invEmailService->mailerConfigured()) {
            $this->flashMessage('warning',
                $this->translator->translate('email.not.configured'));
            return $this->webService->getRedirectResponse('inv/index');
        }
        $body = $request->getParsedBody() ?? [];
        $messageKey = 'email.not.sent.successfully';
        if (is_array($body)) {
            $body['btn_cancel'] = 0;
            /** @var array $body['MailerInvForm'] */
            $to         = (string) ($body['MailerInvForm']['to_email'] ?? '');
            $from_email = (string) ($body['MailerInvForm']['from_email'] ?? '');
            if (empty($to) || empty($from_email)) {
                $messageKey = 'email.to.address.missing';
            } else {
                $from_name   = (string) ($body['MailerInvForm']['from_name'] ?? '');
                $subject     = (string) ($body['MailerInvForm']['subject'] ?? '');
                $email_body  = (string) ($body['MailerInvForm']['body'] ?? '');
                $cc          = (string) ($body['MailerInvForm']['cc'] ?? '');
                $bcc         = (string) ($body['MailerInvForm']['bcc'] ?? '');
                $attachFiles = $request->getUploadedFiles();
                $this->generateInvNumberIfApplicable($inv_id, $d->core->iR, $this->sR, $d->core->gR);
                if ($this->emailStage1(
                    $inv_id,
                    new InvEmailStage1Data($from_email, $from_name, $to, $subject, $email_body, $cc, $bcc, $attachFiles),
                    $invEmailService,
                )) {
                    $this->updateInvoiceAfterEmail($invEmailService, $inv_id);
                    $messageKey = 'email.successfully.sent';
                }
            }
        }
        return $this->invMessageResponse($inv_id, $messageKey);
    }

    private function invMessageResponse(int $inv_id, string $messageKey): Response
    {
        return $this->factory->createResponse(
            $this->webViewRenderer->renderPartialAsString(
                '//invoice/setting/inv_message',
                ['heading' => '', 'message' => $this->translator->translate($messageKey),
                 'url' => 'inv/view', 'id' => $inv_id],
            )
        );
    }

    private function updateInvoiceAfterEmail(InvEmailService $invEmailService, int $inv_id): void
    {
        $d = $invEmailService->d;
        $invoice = $d->core->iR->repoInvUnloadedquery($inv_id);
        if (!$invoice) {
            return;
        }
        $invoice->setStatusId(2);
        if (($this->sR->getSetting('read_only_toggle') == '2')
            && ($this->sR->getSetting('disable_read_only') == '0')) {
            $invoice->setIsReadOnly(true);
        }
        $this->emailedThereforeAddLog($invoice, $d->core->islR);
        $d->core->iR->save($invoice);
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
