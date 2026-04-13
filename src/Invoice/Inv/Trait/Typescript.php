<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\{
    Entity\InvTaxRate, InvTaxRate\InvTaxRateForm, Group\GroupRepository as GR,
    Inv\InvRepository as IR};
use Psr\{
    Http\Message\ResponseInterface as Response,
    Http\Message\ServerRequestInterface as Request,
};
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Json\Json;

trait Typescript
{
    // Invoice\Asset\InvoiceCdnAsset.php OR InvoiceNodeModulesAsset.php
    // Invoice\Asset\rebuild\js\invoice-typescript-iife.min.js
    // src\typescript\invoice.ts handleMarkAsSent
    public function markAsSent(Request $request, IR $iR, GR $gR):
        Response
    {
        $data = $request->getQueryParams();
        $parameters = ['success' => 0];
        /**
         * @var array $data['keylist']
         */
        $keyList = $data['keylist'] ?? [];
        if (!empty($keyList)) {
            /**
             * @var string $value
             */
            foreach ($keyList as $value) {
                /**
                 * @var \App\Invoice\Entity\Inv $inv
                 */
                $inv = $iR->repoInvUnLoadedquery($value);
                if (null !== $inv->getInvAmount()->getTotal()
                        && $inv->getInvAmount()->getTotal() > 0) {
                    $inv->setStatusId(2);
                    if (strlen($inv->getNumber() ?? '') == 0) {
                        $inv->setNumber((string) $gR->generateNumber(
                            (int) $inv->getGroupId(), true));
                    }
/**
 * If the invoice has been sent either by 1. checkbox and the 'sent' button in
 * the index or by 2. 'email' then it must be made readonly so that it cannot be
 * edited depending on what the 'read_only_toggle' status is and whether read
 * only effects i.e. disable_read_only, are being used. 'disable_read_only' is
 * false by default in InvoiceController on setting up.
 *
 * Related logic: see 'read_only_toggle' Settings ....
 * Invoices ... Other Settings ... Disable the read only button on ... {status}
 */
                    if (($this->sR->getSetting('read_only_toggle') == '2')
                      &&  ($this->sR->getSetting('disable_read_only') == '0')) {
                        $inv->setIsReadOnly(true);
                    }
                    $iR->save($inv);
                    $parameters['success'] = 1;
                } else {
                    $parameters['success'] = 0;
                }
            }
            $this->flashMessage('info',
                $this->translator->translate('record.successfully.updated'));
        }
        return $this->factory->createResponse(Json::encode($parameters));
    }

    public function markSentAsDraft(Request $request, IR $iR):
        Response
    {
        $data = $request->getQueryParams();
        $parameters = ['success' => 0];
        /**
         * @var array $data['keylist']
         */
        $keyList = $data['keylist'] ?? [];
        if (!empty($keyList)) {
            /**
             * @var string $value
             */
            foreach ($keyList as $value) {
                /**
                 * @var \App\Invoice\Entity\Inv $inv
                 */
                $inv = $iR->repoInvUnLoadedquery($value);
                if ($inv->getInvAmount()->getTotal() >= 0) {
                    /**
                     * Only invoices with a 'sent' status are targeted to be
                     * set to draft
                     */
                    if ($inv->getStatusId() == 2) {
                        $inv->setStatusId(1);
                    }
                    /**
                     * Invoices are set to 'read only' if the status is 'sent'
                     * and the ability to mark invoices as 'read only' has now
                     * been disabled
                     */
                    if (($this->sR->getSetting('read_only_toggle') == '2')
                     &&  ($this->sR->getSetting('disable_read_only') == '1')) {
                        /**
                         * The invoice is now a draft and so now must be
                         * editable i.e. not 'read-only'
                         */
                        $inv->setIsReadOnly(false);
                    }
                    $iR->save($inv);
                    $parameters['success'] = 1;
                } else {
                    $parameters['success'] = 0;
                }
            }
            $this->flashMessage('info',
             $this->translator->translate('record.successfully.updated'));
            $this->flashMessage('success',
             $this->translator->translate('security.disable.read.only.success'));
        }
        return $this->factory->createResponse(Json::encode($parameters));
    }

    // invoice\src\typescript\invoice.ts handleAddInvoiceTax
    public function saveInvTaxRate(Request $request,
        FormHydrator $formHydrator): Response
    {
        $body = $request->getQueryParams();
        $ajax_body = [
            'inv_id' => $body['inv_id'],
            'tax_rate_id' => $body['inv_tax_rate_id'],
            'include_item_tax' => $body['include_inv_item_tax'],
            'inv_tax_rate_amount' => 0.00,
        ];
        $invTaxRate = new InvTaxRate();
        $form = new InvTaxRateForm($invTaxRate);
        if ($formHydrator->populateAndValidate($form, $ajax_body)) {
            $this->inv_tax_rate_service->saveInvTaxRate($invTaxRate, $ajax_body);
            $parameters = [
                'success' => 1,
            ];
            //return response to inv.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));
        }
        $parameters = [
            'success' => 0,
        ];
        //return response to inv.js to reload page at location
        return $this->factory->createResponse(Json::encode($parameters));
    }
}
