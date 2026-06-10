<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Helpers\{MailerHelper, MailerHelperCustomDeps, MailerSendParams, TemplateHelper};
use App\Invoice\Setting\SettingRepository as SR;
use Psr\Log\LoggerInterface;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;

final readonly class InvEmailService
{
    public function __construct(
        private SR $sR,
        private SessionInterface $session,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
        private MailerInterface $mailer,
        public readonly InvEmailStage2Deps $d,
        private InvPdfService $invPdfService,
    ) {
    }

    public function mailerConfigured(): bool
    {
        return $this->mailerHelper()->mailerConfigured();
    }

    public function send(int $invId, InvEmailStage1Data $data): bool
    {
        $stream  = $this->sR->getSetting('pdf_stream_inv') === '1';
        $pdfPath = $this->invPdfService->generate($invId, $stream, true);
        if (!$pdfPath) {
            return false;
        }
        $templateHelper = $this->templateHelper();
        $d = $this->d;
        $parse = fn(string $tpl): string => $templateHelper->parseTemplate(
            $invId, true, $tpl,
            $d->custom->cvR, $d->core->iR, $d->core->iaR,
            $d->relation->qR, $d->relation->qaR, $d->relation->soR, $d->core->uiR,
        );
        $params = new MailerSendParams(
            $parse($data->fromEmail),
            $parse($data->fromName),
            $data->to,
            $parse($data->subject),
            $parse($data->emailBody),
            $parse($data->cc),
            $parse($data->bcc),
        );
        return $this->mailerHelper()->yiiMailerSend($params, $data->attachFiles, $pdfPath, $this->d->core->uiR);
    }

    private function mailerDeps(): MailerHelperCustomDeps
    {
        return new MailerHelperCustomDeps(
            $this->d->custom->ccR, $this->d->custom->qcR, $this->d->core->icR, $this->d->custom->pcR,
            $this->d->custom->socR, $this->d->custom->cfR, $this->d->custom->cvR,
        );
    }

    private function mailerHelper(): MailerHelper
    {
        return new MailerHelper(
            $this->sR, $this->session, $this->translator,
            $this->logger, $this->mailer, $this->mailerDeps(),
        );
    }

    private function templateHelper(): TemplateHelper
    {
        return new TemplateHelper(
            $this->sR, $this->d->custom->ccR, $this->d->custom->qcR, $this->d->core->icR,
            $this->d->custom->pcR, $this->d->custom->socR, $this->d->custom->cfR, $this->d->custom->cvR,
        );
    }
}
