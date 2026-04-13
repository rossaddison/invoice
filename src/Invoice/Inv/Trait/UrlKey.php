<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Entity\{Inv, InvItem};
use App\Invoice\{
    CustomField\CustomFieldRepository as CFR,
    Inv\InvRepository as IR,
    InvItem\InvItemRepository as IIR,
    InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR,
    InvAmount\InvAmountRepository as IAR,
    InvItemAmount\InvItemAmountRepository as IIAR,
    InvTaxRate\InvTaxRateRepository as ITRR,
    PaymentMethod\PaymentMethodRepository as PMR,
    Upload\UploadRepository as UPR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR
};
use App\User\UserRepository as UR;
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
        CFR $cfR,
        IAR $iaR,
        IIAR $iiaR,
        ACIIR $aciiR,
        IIR $iiR,
        IR $iR,
        ITRR $itrR,
        UR $uR,
        UCR $ucR,
        UIR $uiR,
        PMR $pmR,
        UPR $upR,
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
        if (($iR->repoUrlKeyGuestCount($urlKey) < 1)
                && (!$currentUser->isGuest())) {
            return $this->webService->getNotFoundResponse();
        }
        $inv = $iR->repoUrlKeyGuestLoaded($urlKey);
        if ($inv instanceof Inv) {
            $inv_id = $inv->getId();
            $this->session->set('inv_id', $inv_id);
            if ($itrR->repoCount($inv_id) == 0) {
                $this->flashMessage('warning',
                    $this->translator->translate('tax.rate.active.not'));
            }
            $client_id = $inv->getClientId();
            $user = $this->activeUser($client_id, $uR, $ucR, $uiR);
            if ($user) {
                $user_id = $user->getId();
                if (null !== $user_id) {
                    $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                    // If the user is not an administrator and the status is
                    // sent 2, now mark it as viewed
                    if (null !== $user_inv && $user_inv->getActive()) {
                        if ($uiR->repoUserInvUserIdcount($user_id) === 1
                                && $user_inv->getType() !== 1 && $inv->getStatusId() === 2) {
                            // Mark the invoice as viewed and check whether
                            // it should be marked as read only according to the
                            // read only toggle setting.
                            $this->sR->invoiceMarkViewed((string) $inv_id, $iR);
                        }
                        $iR->save($inv);
                        $payment_method = $inv->getPaymentMethod() !== 0 ?
                            $pmR->repoPaymentMethodquery(
                                (string) $inv->getPaymentMethod()) : null;
                        $custom_fields = [
                            'invoice' => $cfR->repoTablequery('inv_custom'),
                            'client' => $cfR->repoTablequery('client_custom'),
                        ];
                        $attachments = $this->viewPartialInvAttachments(
                            $_language, $urlKey, (int) $client_id, $upR);
                        $inv_amount = ((
                            $iaR->repoInvAmountCount((int) $inv_id) > 0) ?
                                $iaR->repoInvquery((int) $inv_id) : null);
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
                                        && $itrR->repoCount($inv_id) > 0 ?
                                            $itrR->repoInvquery($inv_id) : [],
                                    'inv_url_key' => $urlKey,
                                    'iiaR' => $iiaR,
                                    'aciiR' => $aciiR,
                                    'is_overdue' => $is_overdue,
                                    'items' => ($inv_id > 0) ?
                                        $iiR->repoInvquery($inv_id) :
                                            new InvItem(),
                                    '_language' => $_language,
                                    'payment_method' => $payment_method,
                                    'paymentTermsArray' =>
                                        $this->sR->getPaymentTermArray(
                                            $this->translator),
                                    'userInv' =>
                                        $uiR->repoUserInvUserIdcount($user_id)
                                            > 0 ? $uiR->repoUserInvUserIdquery(
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
