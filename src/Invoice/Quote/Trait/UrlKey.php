<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Auth\Permissions;
use App\Invoice\{
    CustomField\CustomFieldRepository as CFR,
    Quote\QuoteRepository as QR,
    QuoteAmount\QuoteAmountRepository as QAR,
    QuoteItem\QuoteItemRepository as QIR,
    QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR,
    QuoteItemAmount\QuoteItemAmountRepository as QIAR,
    QuoteTaxRate\QuoteTaxRateRepository as QTRR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR,
};
use Yiisoft\{
    Json\Json,
    Router\HydratorAttribute\RouteArgument,
    User\CurrentUser,
};
use Psr\Http\Message\ResponseInterface as Response;

trait UrlKey
{
    // When you click on Send Mail whilst in the view, you will get
    // mailer_quote view showing with the url_key at the bottom
    // Use this url_key to test what the customer will
    // experience eg. invoice/quote/url_key/{url_key}
    // config/routes accesschecker ensures client has viewInv permission

    public function urlKey(#[RouteArgument('url_key')] string $urlKey,
        CurrentUser $currentUser, CFR $cfR, QAR $qaR, QIR $qiR, QIAR $qiaR,
            ACQIR $acqiR, QR $qR, QTRR $qtrR, UIR $uiR, UCR $ucR): Response
    {
        // If there is no quote with such a url_key, issue a not found response
        if ($urlKey === '') {
            return $this->webService->getNotFoundResponse();
        }

        // If there is a quote with the url key ... continue or else issue
        // not found response
        if ($qR->repoUrlKeyGuestCount($urlKey) < 1) {
            return $this->webService->getNotFoundResponse();
        }

        // If this quote has a status id that falls into the category of
        // (just)sent, viewed(in the past), approved(in the past) then continue
        $quote = $qR->repoUrlKeyGuestLoaded($urlKey);
        $quote_tax_rates = null;
        if ($quote) {
            $quote_id = $quote->getId();
            if (null !== $quote_id) {
                if ($qtrR->repoCount($quote_id) > 0) {
                    $quote_tax_rates = $qtrR->repoQuotequery($quote_id);
                }
            }
            // If the quote status is sent 2, viewed 3, or approved_with 4,
            // or approved_without 5 or rejected 6
            if (in_array($quote->getStatusId(), [2,3,4,5,6])) {
                $user_id = $quote->getUserId();
                if ($uiR->repoUserInvUserIdcount($user_id) === 1) {
                    // After signup the user was included in the userinv using
                    // Settings...User Account...+
                    $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                    // The client has been assigned to the user id using
                    // Setting...User Account...Assigned Clients
                    $user_client = $ucR->repoUserClientqueryCount($user_id,
                        $quote->getClientId()) === 1 ? true : false;
                    if ($user_inv && $user_client && $user_inv->getActive()) {
                        // If the userinv is a Guest => type = 1 ie. NOT an
                        // administrator =>type = 0
                        // So if the user has a type of 1 they are a guest.
                        if ($user_inv->getType() == 1) {
                            if ($quote->getStatusId() === 2) {
                                // The quote has just been sent so change its
                                // status otherwise leave its status alone
                                $quote->setStatusId(3);
                            }
                            $qR->save($quote);
                            $custom_fields = [
                                'invoice' => $cfR->repoTablequery('inv_custom'),
                                'client' => $cfR->repoTablequery('client_custom'),
                            ];

                            if (null !== $quote_id) {
                                $quote_amount = (
                                    ($qaR->repoQuoteAmountCount($quote_id) > 0)
                                        ? $qaR->repoQuotequery($quote_id) : null);
                                if ($quote_amount) {
                                    $parameters = [
                                        'renderTemplate' =>
                                            $this->webViewRenderer
                                                 ->renderPartialAsString(
                                            '//invoice/template/quote/public/'
                                            . ($this->sR->getSetting(
                                                'public_quote_template')
                                            ?: 'Quote_Web'), [
                                            'isGuest' => $currentUser->isGuest(),
                                            'alert' => $this->alert(),
                                            'quote' => $quote,
                                            'qiaR' => $qiaR,
                                            'acqiR' => $acqiR,
                                            'quote_amount' => $quote_amount,
                                            'items' => $qiR->repoQuotequery(
                                                $quote_id),
                                            // Get all the quote tax rates that
                                            // have been setup for this quote
                                            'quote_tax_rates' =>
                                                $quote_tax_rates,
                                            'quote_url_key' =>
                                                $urlKey,
                                            'flash_message' =>
                                                $this->flashMessage('info', ''),
                                            //'attachments' => $attachments,
                                            'custom_fields' =>
                                                $custom_fields,
                                            'has_expired' =>
                                                new \DateTimeImmutable('now')
                                                > $quote->getDateExpires() ?
                                                    true : false,
                                            'client' => $quote->getClient(),
                                            // Get the details of the user
                                            // of this quote
                                            'userInv' =>
                                                $uiR->repoUserInvUserIdcount(
                                                $user_id) > 0 ?
                                                $uiR->repoUserInvUserIdquery(
                                                    $user_id) : null,
                                            'modal_purchase_order_number' =>
                                                $this->webViewRenderer
                                                     ->renderPartialAsString(
                                '//invoice/quote/modal_purchase_order_number',
                                                    ['urlKey' => $urlKey]),
                                        ]),
                                    ];
                                    return $this->webViewRenderer->render(
                                        'url_key', $parameters);
                                } // if quote_amount
                            } // if there is a quote id
                        } // user_inv->getType
                    } // user_inv
                } // $uiR
            } // if in_array
        } // if quote
        return $this->webService->getNotFoundResponse();
    }
}