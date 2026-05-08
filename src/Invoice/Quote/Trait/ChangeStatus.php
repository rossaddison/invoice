<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\Setting\SettingRepository as SR;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Json\Json;

trait ChangeStatus
{
    public function changeStatus(Request $request, QR $qR, SR $sR): Response
    {
        $data = $request->getQueryParams();
        $parameters = ['success' => 0];
         /**
         * Purpose: Provide a list of ids from quote/index checkbox column
         * as an array
         * @var array $data['keylist']
         */
        $raw = $data['keylist'] ?? null;
        $keyList = is_array($raw)
            ? array_map(static fn(mixed $v): string => (string) $v, $raw)
            : [];
        $statusId = (int) ($data['status_id'] ?? 0);
        if ($keyList !== [] && $statusId >= 1 && $statusId <= 6) {
            foreach ($keyList as $value) {
                $quoteId = (int) $value;
                if ($statusId === 2) {
                    $sR->quoteMarkSent($quoteId, $qR);
                } elseif ($statusId === 3) {
                    $sR->quoteMarkViewed($quoteId, $qR);
                } else {
                    $quote = $qR->repoQuoteUnLoadedquery($quoteId);
                    if (null !== $quote) {
                        $quote->setStatusId($statusId);
                        $qR->save($quote);
                    }
                }
                $parameters['success'] = 1;
            }
            $this->flashMessage('info',
                $this->translator->translate('record.successfully.updated'));
        }
        return $this->factory->createResponse(Json::encode($parameters));
    }
}
