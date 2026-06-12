<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\{Inv\Inv, InvItem\InvItem};
use App\Invoice\{
    Inv\InvUrlKeyRepoDeps,
    Inv\InvUrlKeyUserDeps,
};
use Yiisoft\{
    Router\HydratorAttribute\RouteArgument, User\CurrentUser,
};
use Psr\Http\Message\ResponseInterface as Response;

trait UrlKey
{
    public function urlKey(
        #[RouteArgument('url_key')]
        string $urlKey,
        #[RouteArgument('gateway')]
        string $clientChosenGateway,
        #[RouteArgument('_language')]
        string $_language,
        CurrentUser $currentUser,
        InvUrlKeyRepoDeps $repos,
        InvUrlKeyUserDeps $ud,
    ): Response {
        // if the current user is a guest it will return a null value
        if ($urlKey === '' || $currentUser->isGuest()) {
            return $this->webService->getNotFoundResponse();
        }
        if ($clientChosenGateway === '') {
            return $this->webService->getNotFoundResponse();
        }
        // If the status is sent 2, viewed 3, or paid 4 and the url key exists
        // accept otherwise not found response
        if (($repos->iR->repoUrlKeyGuestCount($urlKey) < 1)
                && (!$currentUser->isGuest())) {
            return $this->webService->getNotFoundResponse();
        }
        $inv = $repos->iR->repoUrlKeyGuestLoaded($urlKey);
        if ($inv instanceof Inv) {
            $inv_id = $inv->reqId();
            $this->session->set('inv_id', $inv_id);
            if ($repos->itrR->repoCount($inv_id) == 0) {
                $this->flashMessage('warning',
                    $this->translator->translate('tax.rate.active.not'));
            }
            $client_id = $inv->reqClientId();
            $user = $this->activeUser($client_id, $ud->uR, $ud->ucR, $ud->uiR);
            if ($user) {
                $user_id = $user->reqId();
                if ($user_id > 0) {
                    $user_inv = $ud->uiR->repoUserInvUserIdquery($user_id);
                    // If the user is not an administrator and the status is
                    // sent 2, now mark it as viewed
                    if (null !== $user_inv && $user_inv->getActive()) {
                        if ($ud->uiR->repoUserInvUserIdcount($user_id) === 1
                                && $user_inv->getType() !== 1 && $inv->reqStatusId() === 2) {
                            // Mark the invoice as viewed and check whether
                            // it should be marked as read only according to the
                            // read only toggle setting.
                            $this->sR->invoiceMarkViewed($inv_id, $repos->iR);
                        }
                        $repos->iR->save($inv);
                        $payment_method = $inv->getPaymentMethod() !== 0 ?
                            $ud->pmR->repoPaymentMethodquery(
                                (int) $inv->getPaymentMethod()) : null;
                        $custom_fields = [
                            'invoice' => $repos->cfR->repoTablequery('inv_custom'),
                            'client' => $repos->cfR->repoTablequery('client_custom'),
                        ];
                        $attachments = $this->viewPartialInvAttachments(
                            $_language, $urlKey, $client_id, $ud->upR);
                        $inv_amount = ((
                            $repos->iaR->repoInvAmountCount($inv_id) > 0) ?
                                $repos->iaR->repoInvquery($inv_id) : null);
                        if ($inv_amount) {
                            $is_overdue = (
                                $inv_amount->getBalance() > 0
                                    && $inv->getDateDue()
                                        < (new \DateTimeImmutable('now')) ?
                                            true : false);
                            $parameters = [
                                'renderTemplate' =>
                                    $this->webViewRenderer->renderPartialAsString(
                                        '//invoice/template/invoice/public/'
                                            . ($this->sR->getSetting(
                                                'public_invoice_template')
                                                    ?: 'Invoice_Web'), [
                                    'alert' => $this->alert(),
                                    'aliases' => $this->sR->getImg(),
                                    'attachments' => $attachments,
                                    'balance' =>
                                        ($inv_amount->getTotal() ?? 0.00) -
                                            ($inv_amount->getPaid() ?? 0.00),
                                    // Gateway that the paying user has selected
                                    'client_chosen_gateway' =>
                                        $clientChosenGateway,
                                    'client' => $inv->getClient(),
                                    'custom_fields' => $custom_fields,
                                    'inv' => $inv,
                                    'inv_amount' => $inv_amount,
                                    'inv_tax_rates' => ($inv_id > 0)
                                        && $repos->itrR->repoCount($inv_id) > 0 ?
                                            $repos->itrR->repoInvquery($inv_id) : [],
                                    'inv_url_key' => $urlKey,
                                    'iiaR' => $repos->iiaR,
                                    'aciiR' => $ud->aciiR,
                                    'is_overdue' => $is_overdue,
                                    'items' => ($inv_id > 0) ?
                                        $repos->iiR->repoInvquery($inv_id) :
                                            new InvItem(),
                                    '_language' => $_language,
                                    'payment_method' => $payment_method,
                                    'paymentTermsArray' =>
                                        $this->sR->getPaymentTermArray(
                                            $this->translator),
                                    'userInv' =>
                                        $ud->uiR->repoUserInvUserIdcount($user_id)
                                            > 0 ? $ud->uiR->repoUserInvUserIdquery(
                                            $user_id) : null,
                                ]),
                            ];
                            return $this->webViewRenderer->render('url_key',
                                    $parameters);
                        } // if inv_amount
                        $this->flashMessage('warning',
                            $this->translator->translate('amount.no'));
                        return $this->webService->getNotFoundResponse();
                    } // null!== $user_inv
                } // null!== $user_id
            } // if user_inv
            $this->flashMessage('danger',
                $this->translator->translate('client.not.allocated.to.user'));
            return $this->webService->getNotFoundResponse();
        } // if inv
        $this->flashMessage('danger',
            $this->translator->translate('not.found'));
        return $this->webService->getNotFoundResponse();
    }
}
