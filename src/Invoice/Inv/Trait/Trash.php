<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Inv\InvRepository as IR;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Psr\Http\Message\ResponseInterface as Response;

trait Trash
{
    public function trash(IR $invRepo): Response
    {
        $parameters = [
            'alert'   => $this->alert(),
            'trashed' => $invRepo->findTrashed(),
        ];
        return $this->webViewRenderer->render('trash', $parameters);
    }

    public function restore(
        #[RouteArgument('id')]
        int $id,
        IR $invRepo,
    ): Response {
        try {
            $inv = $invRepo->findTrashedById($id);
            if ($inv) {
                $this->inv_service->restoreInv($inv);
                $this->flashMessage('success',
                    $this->translator->translate('delete.invoice.restored'));
            }
        } catch (\Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            unset($e);
        }
        return $this->webService->getRedirectResponse('inv/trash');
    }
}
