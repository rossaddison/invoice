<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Infrastructure\Persistence\{Contract\Contract, Group\Group};
use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\QuoteTaxRate\QuoteTaxRate;
use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Invoice\{
    Quote\QuoteEditCoreDeps,
    Quote\QuoteEditFormDeps,
    Quote\QuoteEditLocationDeps,
    QuoteTaxRate\QuoteTaxRateForm,
};
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
        QuoteEditCoreDeps $core,
        QuoteEditLocationDeps $loc,
        QuoteEditFormDeps $form,
    ): Response {
        $quote = $this->quote($id, $core->quoteRepo, true);
        if (null !== $quote) {
            $quoteForm = QuoteForm::show($quote);
            $quoteCustomForm = new QuoteCustomForm();
            $quote_id = $quote->reqId();
            $client_id = $quote->reqClientId();
            $dels = $loc->delRepo->repoClientquery($quote->reqClientId());
            $parameters = [
                'title' => '',
                'alert' => $this->alert(),
                'actionName' => 'quote/edit',
                'actionArguments' => ['id' => $quote_id],
                'errors' => [],
                'form' => $quoteForm,
                'optionsData' => $this->editOptionsData(
                    $quote,
                    $client_id,
                    $core,
                    $loc,
                ),
                'invs' => $core->invRepo->findAllPreloaded(),
                'clients' => $core->clientRepo->findAllPreloaded(),
                'dels' => $dels,
                'groups' => $core->groupRepo->findAllPreloaded(),
                'numberhelper' => new NumberHelper($this->sR),
                'quote_statuses' => $core->quoteRepo->getStatuses($this->translator),
                'cvH' => new CVH($this->sR, $form->cvR),
                'customFields' => $this->fetchCustomFieldsAndValues(
                    $form->cfR, $form->cvR, 'quote_custom')['customFields'],
                // Applicable to normally building up permanent selection lists
                // eg. dropdowns
                'customValues' => $this->fetchCustomFieldsAndValues(
                    $form->cfR, $form->cvR, 'quote_custom')['customValues'],
                // There will initially be no custom_values attached to this
                // quote until they are filled in the field on the form
                'quoteCustomValues' => $this->quoteCustomValues($quote_id, $form->qcR),
                'quote' => $quote,
                'quoteCustomForm' => $quoteCustomForm,
                'delCount' => $loc->delRepo->repoClientCount($quote->reqClientId()),
                'returnUrlAction' => 'edit',
                'formFields' => $this->formFields,
            ];
            $loc->delRepo->repoClientCount($quote->reqClientId()) > 0 ? '' :
                $this->flashMessage('warning', $this->translator->translate(
                    'quote.delivery.location.none'));
            if ($request->getMethod() === Method::POST) {
                $body = (array) $request->getParsedBody();
                $quote = $this->quote($id, $core->quoteRepo, false);
                if ($quote) {
                    $quoteForm = QuoteForm::show($quote);
                    $client_id = $quote->reqClientId();
                    $user = $this->activeUser($client_id, $form->uR, $core->ucR, $core->uiR);
                    if (null !== $user) {
                        if ($formHydrator->populateAndValidate($quoteForm, $body)) {
                            $this->quote_service->saveQuote($user, $quote,
                                $body, $this->sR, $core->groupRepo);
                            $this->processCustomFields($body, $formHydrator,
                                $this->quoteCustomFieldProcessor,
                                    $quote_id);
                            $this->flashMessage('success',
                                $this->translator->translate(
                                    'record.successfully.updated'));
                            return $this->webService->getRedirectResponse(
                                'quote/view', ['id' => $quote_id]);
                        }
                        $parameters['form'] = $quoteForm;
                        $parameters['errors'] =
                            $quoteForm->getValidationResult()
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
        QuoteEditCoreDeps $core,
        QuoteEditLocationDeps $loc,
    ): array {
        $contracts = $loc->contractRepo->repoClient($quote->reqClientId());
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
        $dLocs = $loc->delRepo->repoClientquery($client_id);
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

        $groups = $core->groupRepo->findAllPreloaded();
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
        foreach ($core->quoteRepo->getStatuses($this->translator) as $key => $status) {
            $optionsDataQuoteStatus[$key] = (string) $status['label'];
        }
        return [
            'client' => $core->clientRepo->optionsData($core->ucR),
            'contract' => $optionsDataContract,
            'deliveryLocation' => $optionsDataDeliveryLocations,
            'group' => $optionsDataGroup,
            'quoteStatus' => $optionsDataQuoteStatus,
        ];
    }
}
