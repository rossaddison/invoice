<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\{Inv\Inv, InvSentLog\InvSentLog};
use App\Invoice\EmailTemplate\EmailTemplateRepository as ETR;
use App\Invoice\Helpers\{
    MailerHelper, MailerHelperCustomDeps, MailerSendParams, ParseTemplateDeps, TemplateHelper,
};
use App\Invoice\Setting\SettingRepository as SR;
use Psr\Log\LoggerInterface;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;

final readonly class InvBatchEmailService
{
    public function __construct(
        private SR $sR,
        private SessionInterface $session,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
        private MailerInterface $mailer,
        public readonly InvBatchEmailDeps $d,
        private InvPdfService $invPdfService,
    ) {}

    /**
     * @param  list<int> $invIds
     * @return list<array{client: string, email: string, source: string, invoice_count: int}>
     */
    public function preview(array $invIds): array
    {
        /** @var array<int, array{client: string, email: string, source: string, invoice_count: int}> $groups */
        $groups = [];
        foreach ($invIds as $invId) {
            $inv = $this->d->iR->repoInvLoadedquery($invId);
            if ($inv === null) {
                continue;
            }
            $clientId = $inv->reqClientId();
            if (!isset($groups[$clientId])) {
                $groups[$clientId] = $this->buildClientPreview($clientId, $inv);
            }
            $groups[$clientId]['invoice_count']++;
        }
        return array_values($groups);
    }

    /**
     * @param list<int> $invIds
     */
    public function sendBatch(array $invIds, int $emailTemplateId, ETR $etR): bool
    {
        $emailTemplate = $etR->repoEmailTemplatequery($emailTemplateId);
        if ($emailTemplate === null) {
            return false;
        }

        /** @var array<int, list<Inv>> $groups */
        $groups = [];
        foreach ($invIds as $invId) {
            $inv = $this->d->iR->repoInvLoadedquery($invId);
            if ($inv === null) {
                continue;
            }
            $groups[$inv->reqClientId()][] = $inv;
        }

        $anySuccess = false;
        foreach ($groups as $clientId => $invoices) {
            $to = $this->resolveEmail($clientId, $invoices[0]);
            if ($to === '') {
                continue;
            }

            $firstInvId = $invoices[0]->reqId();
            $userId     = $invoices[0]->reqUserId();
            $userInv    = $this->d->uiR->repoUserInvUserIdCount($userId) > 0
                ? $this->d->uiR->repoUserInvUserIdquery($userId)
                : null;

            $bodyWithTable = str_replace(
                '{{{invoice_table}}}',
                $this->buildInvoiceTable($invoices),
                $emailTemplate->getEmailTemplateBody(),
            );

            $parseDeps = new ParseTemplateDeps(
                $this->d->custom->cvR,
                $this->d->iR,
                $this->d->iaR,
                $this->d->relation->qR,
                $this->d->relation->qaR,
                $this->d->relation->soR,
                $this->d->uiR,
            );
            $th    = $this->templateHelper();
            $parse = static fn (string $tpl): string =>
                $th->parseTemplate($firstInvId, true, $tpl, $parseDeps);

            $fromEmail = $userInv?->getUser()?->getEmail()
                ?? $emailTemplate->getEmailTemplateFromEmail()
                ?? '';
            $fromName  = $userInv?->getName()
                ?? $emailTemplate->getEmailTemplateFromName()
                ?? '';

            $params = new MailerSendParams(
                $parse($fromEmail),
                $parse($fromName),
                $to,
                $parse($emailTemplate->getEmailTemplateSubject() ?? ''),
                $parse($bodyWithTable),
                $parse($emailTemplate->getEmailTemplateCc()      ?? ''),
                $parse($emailTemplate->getEmailTemplateBcc()     ?? ''),
            );

            $pdfPaths = [];
            $stream   = $this->sR->getSetting('pdf_stream_inv') === '1';
            foreach ($invoices as $inv) {
                $path = $this->invPdfService->generate($inv->reqId(), $stream, true);
                if ($path !== '') {
                    $pdfPaths[] = $path;
                }
            }

            $sent = $this->mailerHelper()->yiiMailerSend($params, [], $pdfPaths, $this->d->uiR);
            if ($sent) {
                foreach ($invoices as $inv) {
                    $inv->setStatusId(2);
                    $this->d->iR->save($inv);
                    $this->logSent($inv);
                }
                $anySuccess = true;
            }
        }

        return $anySuccess;
    }

    /**
     * @return array{client: string, email: string, source: string, invoice_count: int}
     */
    private function buildClientPreview(int $clientId, Inv $sampleInv): array
    {
        $userClient = $this->d->ucR->repoUserquery($clientId);
        if ($userClient !== null) {
            $email  = $userClient->getUser()?->getEmail() ?? '';
            $source = 'User account';
        } else {
            $email  = $sampleInv->getClient()?->getClientEmail() ?? '';
            $source = 'Client record';
        }
        return [
            'client'        => $sampleInv->getClient()?->getClientFullName() ?? 'Unknown',
            'email'         => $email,
            'source'        => $source,
            'invoice_count' => 0,
        ];
    }

    private function resolveEmail(int $clientId, Inv $sampleInv): string
    {
        $userClient = $this->d->ucR->repoUserquery($clientId);
        if ($userClient !== null) {
            $email = $userClient->getUser()?->getEmail() ?? '';
            if ($email !== '') {
                return $email;
            }
        }
        return $sampleInv->getClient()?->getClientEmail() ?? '';
    }

    /**
     * @param list<Inv> $invoices
     */
    private function buildInvoiceTable(array $invoices): string
    {
        $rows = '';
        foreach ($invoices as $inv) {
            $amount  = $this->d->iaR->repoInvquery($inv->reqId());
            $total   = number_format($amount?->getTotal()   ?? 0.0, 2);
            $balance = number_format($amount?->getBalance() ?? 0.0, 2);
            $rows .= '<tr>'
                . '<td>' . htmlspecialchars($inv->getNumber() ?? '', ENT_QUOTES) . '</td>'
                . '<td>' . $inv->getDateCreated()->format('Y-m-d') . '</td>'
                . '<td>' . $total . '</td>'
                . '<td>' . $balance . '</td>'
                . '<td>' . htmlspecialchars($inv->getNote() ?? '', ENT_QUOTES) . '</td>'
                . '<td><a href="inv/url_key/' . htmlspecialchars($inv->getUrlKey(), ENT_QUOTES) . '">View</a></td>'
                . '</tr>';
        }
        $th = '<th style="padding:4px 8px;text-align:left;border-bottom:1px solid #ddd">';
        $td = 'style="padding:4px 8px;border-bottom:1px solid #eee"';
        $rows = str_replace('<td>', "<td $td>", $rows);
        return '<table style="width:100%;border-collapse:collapse;font-family:sans-serif;font-size:13px">'
            . '<thead><tr>'
            . $th . 'Invoice #</th>'
            . $th . 'Date</th>'
            . $th . 'Total</th>'
            . $th . 'Balance</th>'
            . $th . 'Note</th>'
            . $th . 'Link</th>'
            . '</tr></thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '</table>';
    }

    private function logSent(Inv $inv): void
    {
        $log = new InvSentLog();
        $log->setInvId($inv->reqId());
        $log->setClientId($inv->reqClientId());
        $log->setDateSent(new \DateTimeImmutable('now'));
        $this->d->islR->save($log);
    }

    private function mailerHelper(): MailerHelper
    {
        return new MailerHelper(
            $this->sR, $this->session, $this->translator, $this->logger, $this->mailer,
            new MailerHelperCustomDeps(
                $this->d->custom->ccR, $this->d->custom->qcR, $this->d->icR,
                $this->d->custom->pcR, $this->d->custom->socR, $this->d->custom->cfR,
                $this->d->custom->cvR,
            ),
        );
    }

    private function templateHelper(): TemplateHelper
    {
        return new TemplateHelper(
            $this->sR, $this->d->custom->ccR, $this->d->custom->qcR,
            $this->d->icR, $this->d->custom->pcR, $this->d->custom->socR,
            $this->d->custom->cfR, $this->d->custom->cvR,
        );
    }
}
