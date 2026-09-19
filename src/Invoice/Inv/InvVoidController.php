<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * Cancels an issued or viewed invoice that has no payments applied. The
 * invoice keeps its number and stays visible as void (14); anything that
 * has been paid is corrected with a credit note instead.
 */
final class InvVoidController
{
    use FlashMessage;

    public function __construct(
        private readonly Flash $flash,
        private readonly InvRepository $iR,
        private readonly iaR $iaR,
        private readonly Translator $translator,
        private readonly WebControllerService $webService,
    ) {
    }

    public function void(CurrentRoute $currentRoute): Response
    {
        $id = (int) $currentRoute->getArgument('id', '0');
        $inv = $id > 0 ? $this->iR->repoInvUnLoadedquery($id) : null;
        if ($inv === null) {
            return $this->webService->getNotFoundResponse();
        }

        $paid = $this->iaR->repoInvquery($id)?->getPaid() ?? 0.00;
        $status = $inv->reqStatusId();
        if (($status !== 2 && $status !== 3) || $paid > 0.00) {
            $this->flashMessage('warning', $this->translator->translate('invoice.void.not.allowed'));
            return $this->webService->getRedirectResponse('inv/view', ['id' => $id]);
        }

        $inv->setStatusId(14);
        $inv->setIsReadOnly(true);
        $this->iR->save($inv);
        $this->flashMessage('info', $this->translator->translate('invoice.void.success'));

        return $this->webService->getRedirectResponse('inv/view', ['id' => $id]);
    }
}
