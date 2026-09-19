<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * Cancels issued or viewed invoices that have no payments applied. A void
 * invoice keeps its number and stays visible as void (14); anything that
 * has been paid is corrected with a credit note instead.
 */
final class InvVoidController
{
    use FlashMessage;

    public function __construct(
        private readonly DataResponseFactoryInterface $factory,
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
        if ($this->iR->repoInvUnLoadedquery($id) === null) {
            return $this->webService->getNotFoundResponse();
        }

        if ($this->voidIfAllowed($id)) {
            $this->flashMessage('info', $this->translator->translate('invoice.void.success'));
        } else {
            $this->flashMessage('warning', $this->translator->translate('invoice.void.not.allowed'));
        }

        return $this->webService->getRedirectResponse('inv/view', ['id' => $id]);
    }

    /**
     * Batch version for the inv/index toolbar: voids every eligible selected
     * invoice and reports the numbers of the ones that were skipped.
     */
    public function voidSelected(Request $request): Response
    {
        /** @var mixed $keyList */
        $keyList = $request->getQueryParams()['keylist'] ?? [];
        $voided = 0;
        $skipped = [];
        if (is_array($keyList)) {
            /** @var mixed $value */
            foreach ($keyList as $value) {
                $id = (int) (is_scalar($value) ? $value : 0);
                if ($this->voidIfAllowed($id)) {
                    $voided++;
                } elseif ($id > 0) {
                    $number = $this->iR->repoInvUnLoadedquery($id)?->getNumber();
                    $skipped[] = ($number !== null && $number !== '') ? $number : '#' . $id;
                }
            }
        }

        if ($voided > 0) {
            $this->flashMessage('info', $this->translator->translate('invoice.void.success') . ' (' . $voided . ')');
        }
        if ($skipped !== []) {
            $this->flashMessage(
                'warning',
                $this->translator->translate('invoice.void.not.allowed') . ' ' . implode(', ', $skipped)
            );
        }

        return $this->factory->createResponse(Json::encode(['success' => $voided > 0 ? 1 : 0]));
    }

    private function voidIfAllowed(int $id): bool
    {
        $inv = $id > 0 ? $this->iR->repoInvUnLoadedquery($id) : null;
        if ($inv === null) {
            return false;
        }
        $status = $inv->reqStatusId();
        $paid = $this->iaR->repoInvquery($id)?->getPaid() ?? 0.00;
        if (($status !== 2 && $status !== 3) || $paid > 0.00) {
            return false;
        }

        $inv->setStatusId(14);
        $inv->setIsReadOnly(true);
        $this->iR->save($inv);

        return true;
    }
}
