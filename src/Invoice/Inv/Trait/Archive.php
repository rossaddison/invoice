<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use Yiisoft\Http\Method;
use Psr\{Http\Message\ResponseInterface as Response,
    Http\Message\ServerRequestInterface as Request,
};

trait Archive
{
    public function archive(Request $request): Response
    {
        $invoice_archive = [];
        $flash_message = '';
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if (is_array($body)) {
                /**
                 * @var string $value
                 */
                foreach ($body as $key => $value) {
                    if ((string) $key === 'invoice_number') {
                        $invoice_archive =
                        $this->sR->getInvoiceArchivedFilesWithFilter($value);
                        $flash_message = $value;
                    }
                }
            }
        } else {
            $invoice_archive = $this->sR->getInvoiceArchivedFilesWithFilter('');
            $flash_message = '';
        }
        $this->flashMessage('info', $flash_message);
        $parameters = [
            'partial_inv_archive' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/inv/partial_inv_archive',
                [
                    'invoices_archive' => $invoice_archive,
                ],
            ),
            'alert' => $this->alert(),
            'body' => $request->getParsedBody(),
        ];
        return $this->webViewRenderer->render('archive', $parameters);
    }    
}
