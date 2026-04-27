<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Infrastructure\Persistence\{Contract\Contract, Group\Group};
use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\QuoteCustom\QuoteCustom;
use App\Infrastructure\Persistence\QuoteTaxRate\QuoteTaxRate;
use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Invoice\{
    Client\ClientRepository as CR,
    Contract\ContractRepository as ContractRepo,
    CustomField\CustomFieldRepository as CFR,
    CustomValue\CustomValueRepository as CVR,
    DeliveryLocation\DeliveryLocationRepository as DLR,
    Group\GroupRepository as GR,
    Inv\InvRepository as IR,
    Quote\QuoteRepository as QR,
    QuoteCustom\QuoteCustomRepository as QCR,
    QuoteTaxRate\QuoteTaxRateForm,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR,
};
use App\User\UserRepository as UR;
use App\Invoice\Quote\QuoteForm;
use App\Invoice\QuoteCustom\QuoteCustomForm;
use App\Invoice\Helpers\{CustomValuesHelper as CVH, NumberHelper};
use Yiisoft\{
    FormModel\FormHydrator,
    Http\Method,
    Json\Json,
    Router\HydratorAttribute\RouteArgument,
};
use Psr\{
    Http\Message\ResponseInterface as Response,
    Http\Message\ServerRequestInterface as Request,
};

trait Edit
{
    public function edit(
        Request $request,
        #[RouteArgument('id')]
        int $id,
        FormHydrator $formHydrator,
        QR $quoteRepo,
        IR $invRepo,
        CR $clientRepo,
        ContractRepo $contractRepo,
        DLR $delRepo,
        GR $groupRepo,
        CFR $cfR,
        CVR $cvR,
        QCR $qcR,
        UR $uR,
        UCR $ucR,
        UIR $uiR,
    ): Response {
        $quote = $this->quote($id, $quoteRepo, true);
        if (null !== $quote) {
            $form = QuoteForm::show($quote);
            $quoteCustomForm = new QuoteCustomForm();
            $quote_id = $quote->reqId();
            $client_id = $quote->reqClientId();
            $dels = $delRepo->repoClientquery((string) $quote->reqClientId());
            $parameters = [
                'title' => '',
                'alert' => $this->alert(),
                'actionName' => 'quote/edit',
                'actionArguments' => ['id' => $quote_id],
                'errors' => [],
                'form' => $form,
                'optionsData' => $this->editOptionsData(
                    $quote,
                    $client_id,
                    $clientRepo,
                    $contractRepo,
                    $delRepo,
                    $groupRepo,
                    $quoteRepo,
                    $ucR,
                ),
                'invs' => $invRepo->findAllPreloaded(),
                'clients' => $clientRepo->findAllPreloaded(),
                'dels' => $dels,
                'groups' => $groupRepo->findAllPreloaded(),
                'numberhelper' => new NumberHelper($this->sR),
                'quote_statuses' => $quoteRepo->getStatuses($this->translator),
                'cvH' => new CVH($this->sR, $cvR),
                'customFields' => $this->fetchCustomFieldsAndValues(
                    $cfR, $cvR, 'quote_custom')['customFields'],
                // Applicable to normally building up permanent selection lists
                // eg. dropdowns
                'customValues' => $this->fetchCustomFieldsAndValues(
                    $cfR, $cvR, 'quote_custom')['customValues'],
                // There will initially be no custom_values attached to this
                // quote until they are filled in the field on the form
                'quoteCustomValues' => $this->quoteCustomValues($quote_id, $qcR),
                'quote' => $quote,
                'quoteCustomForm' => $quoteCustomForm,
                'delCount' => $delRepo->repoClientCount((string) $quote->reqClientId()),
                'returnUrlAction' => 'edit',
                'formFields' => $this->formFields,
            ];
            $delRepo->repoClientCount((string) $quote->reqClientId()) > 0 ? '' :
                $this->flashMessage('warning', $this->translator->translate(
                    'quote.delivery.location.none'));
            if ($request->getMethod() === Method::POST) {
                $body = (array) $request->getParsedBody();
                $quote = $this->quote($id, $quoteRepo, false);
                if ($quote) {
                    $form = QuoteForm::show($quote);
                    $client_id = $quote->reqClientId();
                    $user = $this->activeUser((string) $client_id, $uR, $ucR, $uiR);
                    if (null !== $user) {
                        if ($formHydrator->populateAndValidate($form, $body)) {
                            $this->quote_service->saveQuote($user, $quote,
                                $body, $this->sR, $groupRepo);
                            $this->processCustomFields($body, $formHydrator,
                                $this->quoteCustomFieldProcessor,
                                    (string) $quote_id);
                            $this->flashMessage('success',
                                $this->translator->translate(
                                    'record.successfully.updated'));
                            return $this->webService->getRedirectResponse(
                                'quote/view', ['id' => $quote_id]);
                        }
                        $parameters['form'] = $form;
                        $parameters['errors'] =
                            $form->getValidationResult()
                                 ->getErrorMessagesIndexedByProperty();
                        return $this->webViewRenderer->render('_form', $parameters);
                    }
                }
            }
            return $this->webViewRenderer->render('_form', $parameters);
        } // $quote
        return $this->webService->getNotFoundResponse();
    }

    // '#quote_tax_submit' => quote.js
    public function saveQuoteTaxRate(Request $request,
        FormHydrator $formHydrator): Response
    {
        $body = $request->getQueryParams();
        $ajax_body = [
            'quote_id' => $body['quote_id'],
            'tax_rate_id' => $body['tax_rate_id'],
            'include_item_tax' => $body['include_item_tax'],
            'quote_tax_rate_amount' => 0.00,
        ];
        $quoteTaxRate = new QuoteTaxRate();
        $ajax_content = QuoteTaxRateForm::show($quoteTaxRate);
        if ($formHydrator->populateAndValidate($ajax_content, $ajax_body)) {
            $this->quote_tax_rate_service->saveQuoteTaxRate($quoteTaxRate,
                $ajax_body);
            $parameters = [
                'success' => 1,
                'flash_message' => $this->translator->translate(
                    'quote.tax.rate.saved'),
            ];
            //return response to quote.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));
        }
        $parameters = [
            'success' => 0,
            'flash_message' => $this->translator->translate(
                'quote.tax.rate.incomplete.fields'),
        ];
        //return response to quote.js to reload page at location
        return $this->factory->createResponse(Json::encode($parameters));
    }

    private function editOptionsData(
        Quote $quote,
        int $client_id,
        CR $clientRepo,
        ContractRepo $contractRepo,
        DLR $delRepo,
        GR $groupRepo,
        QR $quoteRepo,
        UCR $ucR,
    ): array {
        $contracts = $contractRepo->repoClient((string) $quote->reqClientId());
        $optionsDataContract = [];
        /**
         * @var Contract $contract
         */
        foreach ($contracts as $contract) {
            $id = $contract->reqId();
            $contractLine = [];
            if (null !== $contract->getName()) {
                $contractLine[] = $contract->getName();
            }
            if (null !== $contract->getReference()) {
                $contractLine[] = $contract->getReference();
            }
            $optionsDataContract[$id] = implode(',', $contractLine);
            
        }

        $dLocs = $delRepo->repoClientquery((string) $client_id);
        $optionsDataDeliveryLocations = [];
        /**
         * @var DeliveryLocation $dLoc
         */
        foreach ($dLocs as $dLoc) {
            $dLocId = $dLoc->reqId();
            $address = [];
            if (null !== $dLoc->getAddress1()) {
                $address[] = $dLoc->getAddress1();
            }
            if (null !== $dLoc->getAddress2()) {
                $address[] = $dLoc->getAddress2();
            }
            if (null !== $dLoc->getCity()) {
                $address[] = $dLoc->getCity();
            }
            $optionsDataDeliveryLocations[$dLocId] = implode(', ', $address);
            
        }

        $groups = $groupRepo->findAllPreloaded();
        $optionsDataGroup = [];
        /**
         * @var Group $group
         */
        foreach ($groups as $group) {
            $optionsDataGroup[$group->reqId()] = $group->getName();
        }

        $optionsDataQuoteStatus = [];
        /**
         * @var string $key
         * @var array $status
         */
        foreach ($quoteRepo->getStatuses($this->translator) as $key => $status) {
            $optionsDataQuoteStatus[$key] = (string) $status['label'];
        }
        return [
            'client' => $clientRepo->optionsData($ucR),
            'contract' => $optionsDataContract,
            'deliveryLocation' => $optionsDataDeliveryLocations,
            'group' => $optionsDataGroup,
            'quoteStatus' => $optionsDataQuoteStatus,
        ];
    }
}