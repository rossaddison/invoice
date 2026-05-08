<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Invoice\{
    Quote\QuoteRepository as QR,
    QuoteAllowanceCharge\QuoteAllowanceChargeRepository as ACQR,
    QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR,
    QuoteItemAmount\QuoteItemAmountRepository as QIAR,
    QuoteCustom\QuoteCustomRepository as QCR,
    QuoteCustom\QuoteCustomService,
    QuoteItem\QuoteItemRepository as QIR,
    QuoteItem\QuoteItemService,
    QuoteTaxRate\QuoteTaxRateRepository as QTRR,
    QuoteTaxRate\QuoteTaxRateService,
    QuoteAmount\QuoteAmountRepository as QAR,
    QuoteAmount\QuoteAmountService,
};
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Psr\Http\Message\ResponseInterface as Response;

trait Delete
{
    public function delete(
        #[RouteArgument('id')]
        int $id,
        QR $quoteRepo
    ): Response {
        $deleteNot = $this->translator->translate('quote.delete.not');
        try {
            $quote = $this->quote($id, $quoteRepo);
            // Quotes cannot be deleted if either a corresponding Sales Order
            // or Invoice is derived from it
            if ($quote && (($quote->getSoId() == 0)
                    && ($quote->getInvId() == 0))) {
                $this->quote_service
                     ->deleteQuote($quote);
                $this->flashMessage('success', $this->translator->translate(
                        'record.successfully.deleted'));
                return $this->webService->getRedirectResponse('quote/index');
            }
            $this->flashMessage('danger', $deleteNot);
            return $this->webService->getNotFoundResponse();
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger', $deleteNot);
            return $this->webService->getRedirectResponse('quote/index');
        }
    }
}