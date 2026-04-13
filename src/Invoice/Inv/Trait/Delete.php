<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\{
    Inv\InvRepository as IR
};
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Psr\Http\Message\ResponseInterface as Response;

trait Delete
{
    public function delete(
        #[RouteArgument('id')]
        int $id,
        IR $invRepo
    ): Response {
        try {
            $inv = $this->inv($id, $invRepo);
            if ($inv) {
                $this->inv_service->deleteInv($inv);
                $this->flashMessage('info',
                    $this->translator->translate('record.successfully.deleted'));
                return $this->webService->getRedirectResponse('inv/index');
            }
            return $this->webService->getRedirectResponse('inv/index');
        } catch (\Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            unset($e);
            return $this->webService->getRedirectResponse('inv/index');
        }
    }
}
