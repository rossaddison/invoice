<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Inv\InvPdfService;
use Yiisoft\{Html\Html, Json\Json, Router\HydratorAttribute\RouteArgument};
use Psr\Http\Message\ResponseInterface as Response;

trait HtmlTrait
{
    public function html(
        #[RouteArgument('include')] int $include,
        InvPdfService $invPdfService,
    ): Response {
        $invId = (int) $this->session->get('inv_id');
        $html = $invPdfService->generateHtml($invId, $include === 1);
        if ($html !== '') {
            return $this->factory->createResponse('<pre>' . Html::encode($html) . '</pre>');
        }
        return $this->factory->createResponse(Json::encode(['success' => 0]));
    }
}
