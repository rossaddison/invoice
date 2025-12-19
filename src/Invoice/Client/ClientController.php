<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Auth\Permissions;
use App\Invoice\BaseController;
// Entity's
use App\Invoice\Entity\Client;
use App\Invoice\Entity\ClientNote;
use App\Invoice\Entity\ClientCustom;
use App\Invoice\Entity\Inv;
use App\Invoice\Inv\InvForm;
use App\Invoice\Entity\PostalAddress;
use App\Invoice\Entity\Quote;
// Services
use App\Service\WebControllerService;
use App\Invoice\ClientCustom\ClientCustomService;
// Forms
use App\Invoice\ClientCustom\ClientCustomForm;
use App\Invoice\ClientNote\ClientNoteService as cnS;
use App\Invoice\ClientNote\ClientNoteForm;
use App\Invoice\Quote\QuoteForm;
use App\Invoice\UserClient\Exception\NoClientsAssignedToUserException;
use App\User\UserService;
// Repositories
use App\Invoice\Client\ClientRepository as cR;
use App\Invoice\ClientCustom\ClientCustomRepository as ccR;
use App\Invoice\ClientNote\ClientNoteRepository as cnR;
use App\Invoice\ClientPeppol\ClientPeppolRepository as cpR;
use App\Invoice\CustomValue\CustomValueRepository as cvR;
use App\Invoice\CustomField\CustomFieldRepository as cfR;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as delR;
use App\Invoice\Group\GroupRepository as gR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvRecurring\InvRecurringRepository as irR;
use App\Invoice\Payment\PaymentRepository as pymtR;
use App\Invoice\PostalAddress\PostalAddressRepository as paR;
use App\Invoice\Quote\QuoteRepository as qR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\UserClient\UserClientRepository as ucR;
use App\Invoice\UserInv\UserInvRepository as uiR;
// Helpers
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Client\ClientCustomFieldProcessor;
// Psr\\Http
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// Widgets
use App\Widget\Bootstrap5ModalQuote;
use App\Widget\Bootstrap5ModalInv;
// Yii
use Yiisoft\Aliases\Aliases;
use Yiisoft\Data\Paginator\OffsetPaginator as DataOffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

// Miscellaneous

final class ClientController extends BaseController
{
    protected string $controllerName = 'invoice/client';

    public function __construct(
        private ClientService $clientService,
        private ClientCustomService $clientCustomService,
        private ClientCustomFieldProcessor $clientCustomFieldProcessor,
        private \App\Widget\FormFields $formFields,
        private DataResponseFactoryInterface $factory,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->clientService = $clientService;
        $this->clientCustomService = $clientCustomService;
        $this->factory = $factory;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param cR $cR
     * @return Client|null
     */
    private function client(CurrentRoute $currentRoute, cR $cR): ?Client
    {
        $client_id = $currentRoute->getArgument('id');
        if (null !== $client_id) {
            return $cR->repoClientquery($client_id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function clients(cR $cR, int $active): EntityReader
    {
        return $cR->findAllWithActive($active);
    }

    /**
     * @param string $client_id
     * @param ccR $ccR
     * @return array
     */
    public function client_custom_values(string $client_id, ccR $ccR): array
    {
        // Get all the custom fields that have been registered with this client on creation, retrieve existing values via repo, and populate
        // custom_field_form_values array
        $custom_field_form_values = [];
        if ($ccR->repoClientCount($client_id) > 0) {
            $client_custom_fields = $ccR->repoFields($client_id);
            /**
             * @var int $key
             * @var string $val
             */
            foreach ($client_custom_fields as $key => $val) {
                $custom_field_form_values['custom[' . (string) $key . ']'] = $val;
            }
        }
        return $custom_field_form_values;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param cfR $cfR
     * @param cvR $cvR
     * @return Response
     */
    public function add(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        cfR $cfR,
        cvR $cvR,
    ): Response {
        $origin = $currentRoute->getArgument('origin');
        $countries = new CountryHelper();
        $new_client = new Client();
        $form = new ClientForm($new_client);
        $clientCustom = new ClientCustom();
        $clientCustomForm = new ClientCustomForm($clientCustom);
        $custom = $this->fetchCustomFieldsAndValues($cfR, $cvR, 'client_custom');

        $parameters = [
            'title' => $this->translator->translate('add'),
            'alert' => $this->alert(),
            'actionName' => 'client/add',
            'actionArguments' => ['origin' => $origin],
            'origin' => $origin,
            'errors' => [],
            'errorsCustom' => [],
            'client' => $new_client,
            'aliases' => new Aliases(['@invoice' => dirname(__DIR__), '@language' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Language']),
            'selectedCountry' => $this->sR->getSetting('default_country'),
            'selectedLanguage' => $this->sR->getSetting('default_language'),
            'datepicker_dropdown_locale_cldr' => $this->session->get('_language') ?? 'en',
            'optionsDataGender' => $this->optionsDataGender(),
            'optionsDataClientFrequencyDropdownFilter' => $this->optionsDataClientFrequencyDropDownFilter(),
            'postal_address_count' => 0,
            'postaladdresses' => null,
            /**
                 * Default to en so that all country names are in English if route language not found
                 * TODO: rebuild country list to match currently available languages
                 * Related logic: see src\Invoice\Helpers\Country-list\en
                 */
            'countries' => $countries->get_country_list($currentRoute->getArgument('_language') ?? 'en'),
            'customFields' => $custom['customFields'],
            'customValues' => $custom['customValues'],
            'clientCustomValues' => [],
            'clientCustomForm' => $clientCustomForm,
            'formFields' => $this->formFields,
        ];
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $client_id = $this->clientService->saveClient($new_client, $body, $this->sR);
                    if (null !== $client_id) {
                        if (isset($body['custom'])) {
                            // Retrieve the custom array
                            /** @var array $custom */
                            $custom = $body['custom'];
                            /**
                             * @var int $custom_field_id
                             * @var array|string $value
                             */
                            foreach ($custom as $custom_field_id => $value) {
                                $clientCustom = new ClientCustom();
                                $clientCustomForm = new ClientCustomForm($clientCustom);
                                $client_custom = [];
                                $client_custom['client_id'] = $client_id;
                                $client_custom['custom_field_id'] = $custom_field_id;
                                // Note: There are no Required rules for value under ClientCustomForm
                                $client_custom['value'] = is_array($value) ? serialize($value) : $value;
                                if ($formHydrator->populateAndValidate($clientCustomForm, $client_custom)) {
                                    $this->clientCustomService->saveClientCustom($clientCustom, $client_custom);
                                }
                                // These two can be used to create customised labels for custom field error validation on the form
                                // Currently not used.
                                $parameters['clientCustomForm'] = $clientCustomForm;
                                $parameters['errorsCustom'] = $clientCustomForm->getValidationResult()->getErrorMessagesIndexedByProperty();
                            }
                        }
                        $this->flashMessage('info', $this->translator->translate('record.successfully.created'));
                        if ($origin == 'main' || $origin == 'add') {
                            return $this->webService->getRedirectResponse('client/index');
                        }
                        if ($origin == 'dashboard') {
                            return $this->webService->getRedirectResponse('invoice/dashboard');
                        }
                    }
                }
            } else {
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            }
        }
        $parameters['form'] = $form;
        return $this->viewRenderer->render('_form', $parameters);
    }

    public function edit(
        Request $request,
        cR $cR,
        ccR $ccR,
        cfR $cfR,
        cvR $cvR,
        FormHydrator $formHydrator,
        paR $paR,
        CurrentRoute $currentRoute,
    ): Response {
        $origin = $currentRoute->getArgument('origin');
        $client = $this->client($currentRoute, $cR) ?? null;
        if (null !== $client) {
            $form = new ClientForm($client);
            $clientCustom = new ClientCustom();
            $clientCustomForm = new ClientCustomForm($clientCustom);
            $selected_country = $client->getClient_country();
            $selected_language = $client->getClient_language();
            $countries = new CountryHelper();
            $client_id = $client->getClient_id();
            if (null !== $client_id) {
                $postaladdresses = $paR->repoClientAll((string) $client_id);
                $parameters = [
                    'title' => $this->translator->translate('edit'),
                    'actionName' => 'client/edit',
                    'actionArguments' => ['id' => $client_id, 'origin' => $origin],
                    'alert' => $this->alert(),
                    'errors' => [],
                    'origin' => $origin,
                    'errorsCustom' => [],
                    'client' => $client,
                    'form' => $form,
                    'optionsDataGender' => $this->optionsDataGender(),
                    'optionsDataClientFrequencyDropdownFilter' => $this->optionsDataClientFrequencyDropDownFilter(),
                    'optionsDataPostalAddresses' => $this->optionsDataPostalAddress($postaladdresses),
                    'aliases' => new Aliases(['@invoice' => dirname(__DIR__), '@language' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Language']),
                    'selectedCountry' => $selected_country ?? $this->sR->getSetting('default_country'),
                    'selectedLanguage' => $selected_language ?? $this->sR->getSetting('default_language'),
                    'datepicker_dropdown_locale_cldr' => $currentRoute->getArgument('_language', 'en'),
                    'postal_address_count' => $paR->repoClientCount((string) $client_id),
                    /**
                    * Default to en so that all country names are in English if route language not found
                    * TODO: rebuild country list to match currently available languages
                    * Related logic: see src\Invoice\Helpers\Country-list\en
                    */
                    'countries' => $countries->get_country_list($currentRoute->getArgument('_language') ?? 'en'),
                    // Prepare custom fields and values
                    'customFields' => $this->fetchCustomFieldsAndValues($cfR, $cvR, 'client_custom')['customFields'],
                    'customValues' => $this->fetchCustomFieldsAndValues($cfR, $cvR, 'client_custom')['customValues'],
                    'clientCustomValues' => $this->client_custom_values((string) $client_id, $ccR),
                    'clientCustomForm' => $clientCustomForm,
                    'formFields' => $this->formFields,
                ];
                if ($request->getMethod() === Method::POST) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        $returned_form = $this->save_form_fields($body, $form, $client, $formHydrator);
                        $parameters['body'] = $body;
                        if (!$returned_form->isValid()) {
                            $parameters['form'] = $returned_form;
                            $parameters['errors'] = $returned_form->getValidationResult()->getErrorMessagesIndexedByProperty();
                            return $this->viewRenderer->render('_form', $parameters);
                        }
                        $this->processCustomFields($body, $formHydrator, $this->clientCustomFieldProcessor, (string) $client_id);
                    } // is_array
                    $this->flashMessage('info', $this->translator->translate('record.successfully.updated'));
                    if ($origin  == 'edit') {
                        return $this->webService->getRedirectResponse('client/index');
                    }
                    if ($origin  == 'inv') {
                        return $this->webService->getRedirectResponse('inv/index');
                    }
                }
                return $this->viewRenderer->render('_form', $parameters);
            } // null!==client_id
        } //client
        return $this->webService->getRedirectResponse('client/index');
    }



    /**
     * @param FormHydrator $formHydrator
     * @param array $body
     * @param mixed $matches
     * @param string $client_id
     * @param ccR $ccR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function custom_fields(FormHydrator $formHydrator, array $body, mixed $matches, string $client_id, ccR $ccR): \Yiisoft\DataResponse\DataResponse
    {
        if (!empty($body['custom'])) {
            $db_array = [];
            $values = [];
            /**
             * @var array $custom
             */
            foreach ($body['custom'] as $custom) {
                if (preg_match("/^(.*)\[\]$/i", (string) $custom['name'], $matches)) {
                    $values[$matches[1]][] = (string) $custom['value'];
                } else {
                    $values[(string) $custom['name']] = (string) $custom['value'];
                }
            }

            foreach ($values as $key => $value) {
                preg_match("/^custom\[(.*?)\](?:\[\]|)$/", $key, $matches);
                if ($matches) {
                    /**
                    * @var string $value
                    */
                    $db_array[$matches[1]] = $value;
                }
            }

            foreach ($db_array as $key => $value) {
                $client_custom = [];
                $client_custom['client_id'] = $client_id;
                $client_custom['custom_field_id'] = $key;
                $client_custom['value'] = $value;
                $model = ($ccR->repoClientCustomCount($client_id, $key) == 1 ? $ccR->repoFormValuequery($client_id, $key) : new ClientCustom());
                if ($model instanceof ClientCustom) {
                    $form = new ClientCustomForm($model);
                    if ($formHydrator->populateAndValidate($form, $client_custom)) {
                        $this->clientCustomService->saveClientCustom($model, $client_custom);
                    }
                }
            }
            $parameters = [
                'success' => 1,
            ];
            return $this->factory->createResponse(Json::encode($parameters));
        }
        $parameters = [
            'success' => 0,
        ];
        return $this->factory->createResponse(Json::encode($parameters));
    }

    public function delete(CurrentRoute $currentRoute, cR $cR): Response
    {
        try {
            $this->clientService->deleteClient($this->client($currentRoute, $cR));
            $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));
            //UserClient Entity automatically deletes the UserClient record relevant to this client
            return $this->webService->getRedirectResponse('client/index');
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger', $this->translator->translate('client.delete.history.exits.no'));
            return $this->webService->getRedirectResponse('client/index');
        }
    }

    /**
     * @param array $body
     * @param ClientForm $form
     * @param Client $client
     * @param FormHydrator $formHydrator
     * @return ClientForm
     */
    public function save_form_fields(array $body, ClientForm $form, Client $client, FormHydrator $formHydrator): ClientForm
    {
        if ($formHydrator->populateAndValidate($form, $body)) {
            $this->clientService->saveClient($client, $body, $this->sR);
        }
        return $form;
    }

    /**
     * @param string $client_id
     * @param int $custom_field_id
     * @param ccR $ccR
     * @return bool
     */
    public function add_custom_field(string $client_id, int $custom_field_id, ccR $ccR): bool
    {
        return $ccR->repoClientCustomCount($client_id, (string) $custom_field_id) > 0 ? false : true;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param UrlGenerator $urlGenerator
     * @param cR $cR
     * @param iaR $iaR
     * @param iR $iR
     * @param cpR $cpR
     * @param ucR $ucR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function index(
        CurrentRoute $currentRoute,
        Request $request,
        UrlGenerator $urlGenerator,
        cR $cR,
        iaR $iaR,
        iR $iR,
        cpR $cpR,
        ucR $ucR,
    ): \Yiisoft\DataResponse\DataResponse {
        /**
         * Related logic: see $canEdit used in client/index.php to display via label
         *      whether a client has been assigned to an active user account
         */
        $canEdit = $this->rbac();
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page']
         */
        $page = $query_params['page'] ?? $currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = (int) $page > 0 ? (int) $page : 1;
        $active = (int) $currentRoute->getArgument('active', '2');
        /** @var string $query_params['sort'] */
        $sortString = $query_params['sort'] ?? '-id';
        $urlCreator = new UrlCreator($urlGenerator);
        $order = OrderHelper::stringToArray($sortString);
        $urlCreator->__invoke([], $order);
        $sort = Sort::only(['id', 'client_birthdate', 'client_mobile', 'client_phone'])
                    // (Related logic: see vendor\yiisoft\data\src\Reader\Sort
                    // - => 'desc'  so -id => default descending on id
                    // Show the latest products first => -id
                    ->withOrder($order);
        /**
         * @psalm-var \Yiisoft\Data\Reader\ReadableDataInterface<array-key, array<array-key, mixed>|object>&\Yiisoft\Data\Reader\LimitableDataInterface&\Yiisoft\Data\Reader\OffsetableDataInterface&\Yiisoft\Data\Reader\CountableDataInterface $clients
         */
        $clients = $this->clients_with_sort($cR, $sort, $active);
        if (isset($query_params['filter_client_name']) && !empty($query_params['filter_client_name'])) {
            $clients = $cR->filter_client_name((string) $query_params['filter_client_name']);
        }
        if (isset($query_params['filter_client_surname']) && !empty($query_params['filter_client_surname'])) {
            $clients = $cR->filter_client_surname((string) $query_params['filter_client_surname']);
        }
        if ((isset($query_params['filter_client_name']) && !empty($query_params['filter_client_name']))
           && (isset($query_params['filter_client_surname']) && !empty($query_params['filter_client_surname']))) {
            $clients = $cR->filter_client_name_surname((string) $query_params['filter_client_name'], (string) $query_params['filter_client_surname']);
        }
        $paginator = (new DataOffsetPaginator($clients))
            ->withPageSize($this->sR->positiveListLimit())
            ->withCurrentPage($currentPageNeverZero)
            ->withToken(PageToken::next((string) $page));
        $parameters = [
            'paginator' => $paginator,
            'alert' => $this->alert(),
            'iR' => $iR,
            'iaR' => $iaR,
            'canEdit' => $canEdit,
            'active' => $active,
            'cpR' => $cpR,
            'ucR' => $ucR,
            'defaultPageSizeOffsetPaginator' => $this->sR->getSetting('default_list_limit')
                                                    ? (int) $this->sR->getSetting('default_list_limit') : 1,
            'modal_create_client' => $this->viewRenderer->renderPartialAsString('//invoice/client/modal_create_client'),
            'optionsDataClientNameDropdownFilter' => $this->optionsDataClientNameDropdownFilter($cR),
            'optionsDataClientSurnameDropdownFilter' => $this->optionsDataClientSurnameDropdownFilter($cR),
            'urlCreator' => $urlCreator,
            'visible' => $this->sR->getSetting('columns_all_visible') == '0' ? false : true,
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param cR $cR
     * @param Sort $sort
     * @param int $active
     *
     * @return \Yiisoft\Data\Reader\DataReaderInterface&\Yiisoft\Data\Reader\SortableDataInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface
     */
    private function clients_with_sort(cR $cR, Sort $sort, int $active): \Yiisoft\Data\Reader\SortableDataInterface
    {
        $query = $this->clients($cR, $active);
        return $query->withSort($sort);
    }

    public function guest(
        CurrentRoute $currentRoute,
        Request $request,
        UrlGenerator $urlGenerator,
        cR $cR,
        iaR $iaR,
        iR $iR,
        cpR $cpR,
        ucR $ucR,
        uiR $uiR,
    ): Response {
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page']
         */
        $page = $query_params['page'] ?? $currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = (int) $page > 0 ? (int) $page : 1;
        $active = (int) $currentRoute->getArgument('active', '2');
        /** @var string $query_params['sort'] */
        $sortString = $query_params['sort'] ?? '-id';
        $urlCreator = new UrlCreator($urlGenerator);
        $order = OrderHelper::stringToArray($sortString);
        $urlCreator->__invoke([], $order);
        $user = $this->userService->getUser();
        if (null !== $user) {
            $user_id = $user->getId();
            if (null !== $user_id) {
                // Use this user's id to see whether a user has been setup under UserInv i.e. yii-invoice's list of users
                // and use userInv to build the guest's pagesizelimiter
                $userInv = ($uiR->repoUserInvUserIdcount($user_id) > 0 ? $uiR->repoUserInvUserIdquery($user_id) : null);
                if (null !== $userInv) {
                    $client_array = $ucR->get_assigned_to_user($user_id);
                    if (!empty($client_array)) {
                        $clients = $cR->repoUserClient($client_array);
                        /**
                         * @psalm-var positive-int $listLimit
                         */
                        $listLimit = $userInv->getListLimit() > 0 ? ($userInv->getListLimit() ?? 1) : 1;
                        $paginator = (new DataOffsetPaginator($clients))
                            ->withPageSize($listLimit)
                            ->withCurrentPage($currentPageNeverZero)
                            ->withToken(PageToken::next((string) $page));
                        $parameters = [
                            'paginator' => $paginator,
                            'alert' => $this->alert(),
                            'iR' => $iR,
                            'iaR' => $iaR,
                            'editInv' => $this->userService->hasPermission(Permissions::EDIT_INV),
                            'active' => $active,
                            'cpR' => $cpR,
                            'defaultPageSizeOffsetPaginator' => $this->sR->getSetting('default_list_limit')
                                                                ? (int) $this->sR->getSetting('default_list_limit') : 1,
                            'modal_create_client' => $this->viewRenderer->renderPartialAsString('//invoice/client/modal_create_client'),
                            'userInv' => $userInv,
                            'urlCreator' => $urlCreator,
                        ];
                        return $this->viewRenderer->render('guest', $parameters);
                    }
                    $this->flashMessage('warning', $this->translator->translate('user.clients.assigned.not'));
                    throw new NoClientsAssignedToUserException($this->translator);
                } // null!==$userInv
            } // null!== $user_id
        } // null!== $this->userService
        return $this->webService->getNotFoundResponse();
    }

    public function load_client_notes(Request $request, cnR $cnR): \Yiisoft\DataResponse\DataResponse
    {
        $body = $request->getQueryParams();
        /** @var int $body['client_id'] */
        $client_id = $body['client_id'];
        $data = $cnR->repoClientNoteCount($client_id) > 0 ? $cnR->repoClientquery((string) $client_id) : null;
        $parameters = [
            'success' => 1,
            'data' => $data,
        ];
        return $this->factory->createResponse(Json::encode($parameters));
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission(Permissions::EDIT_INV);
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse('client/index');
        }
        return $canEdit;
    }

    /**
     * @param FormHydrator $formHydrator
     * @param Request $request
     * @param ccR $ccR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function save_custom(FormHydrator $formHydrator, Request $request, ccR $ccR): \Yiisoft\DataResponse\DataResponse
    {
        $body = $request->getQueryParams();
        $custom = $body['custom'] ? (array) $body['custom'] : '';
        $custom_field_body = [
            'custom' => $custom,
        ];
        $client_id = (string) $this->session->get('client_id');
        if (isset($custom_field_body['custom'])) {
            $db_array = [];
            $values = [];
            /**
             * @var array $custom_field_body['custom']
             * @var array $custom
             */
            foreach ($custom_field_body['custom'] as $custom) {
                $customName = (string) $custom['name'];
                $customValue = (string) $custom['value'];
                if (preg_match("/^(.*)\[\]$/i", $customName, $matches)) {
                    $values[$matches[1]][] = $customValue;
                } else {
                    $values[$customName] = $customValue;
                }
            }
            foreach ($values as $key => $value) {
                preg_match("/^custom\[(.*?)\](?:\[\]|)$/", $key, $matches);
                if ($matches) {
                    $db_array[$matches[1]] = $value;
                }
            }
            foreach ($db_array as $key => $value) {
                $form = new ClientCustomForm(new ClientCustom());
                $client_custom = [];
                $client_custom['client_id'] = $client_id;
                $client_custom['custom_field_id'] = $key;
                /**
                 * @var string $value
                 */
                $client_custom['value'] = $value;
                $model = ($ccR->repoClientCustomCount($client_id, $key) == 1 ? $ccR->repoFormValuequery($client_id, $key) : new ClientCustom());
                if (null !== $model) {
                    if ($formHydrator->populateAndValidate($form, $client_custom)) {
                        $this->clientCustomService->saveClientCustom($model, $client_custom);
                    }
                }
            }
            $parameters = [
                'success' => 1,
            ];
            return $this->factory->createResponse(Json::encode($parameters));
        }
        $parameters = [
            'success' => 0,
        ];
        return $this->factory->createResponse(Json::encode($parameters));
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param cnS $cnS
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function save_client_note_new(Request $request, FormHydrator $formHydrator, cnS $cnS): \Yiisoft\DataResponse\DataResponse
    {
        $datehelper = new DateHelper($this->sR);
        //receive data ie. note
        $body = $request->getQueryParams();
        /**
         * @var string $body['client_id']
         */
        $client_id = $body['client_id'];
        $date = new \DateTimeImmutable('now');
        /**
         * @var string $body['client_note']
         */
        $note = $body['client_note'];
        $data = [
            'client_id' => $client_id,
            'date' => $date->format('Y-m-d'),
            'note' => $note,
        ];
        $form = new ClientNoteForm(new ClientNote());
        if ($formHydrator->populateAndValidate($form, $data)) {
            $cnS->addClientNote(new ClientNote(), $data);
            $parameters = [
                'success' => 1,
            ];
        } else {
            $parameters = [
                'success' => 0,
                'validation_errors' => $form->getValidationResult()->getErrorMessagesIndexedByProperty(),
            ];
        }
        return $this->factory->createResponse(Json::encode($parameters));
    }

    public function delete_client_note(Request $request, cnR $cnR, cnS $cnS): \Yiisoft\DataResponse\DataResponse
    {
        $body = $request->getQueryParams();
        /**
         * @var string $body['note_id']
         */
        $note_id = $body['note_id'] ?? '';

        if (empty($note_id)) {
            return $this->factory->createResponse(Json::encode([
                'success' => 0,
                'message' => 'Note ID is required',
            ]));
        }

        $clientNote = $cnR->repoClientNotequery($note_id);
        if ($clientNote) {
            $cnS->deleteClientNote($clientNote);
            return $this->factory->createResponse(Json::encode([
                'success' => 1,
            ]));
        } else {
            return $this->factory->createResponse(Json::encode([
                'success' => 0,
                'message' => 'Note not found',
            ]));
        }
    }

    /**
     * @return array
     */
    private function optionsDataGender(): array
    {
        $optionsDataGender = [];
        $genders_array = [
            $this->translator->translate('gender.male'),
            $this->translator->translate('gender.female'),
            $this->translator->translate('gender.other'),
        ];
        foreach ($genders_array as $key => $val) {
            $optionsDataGender[(string) $key] = $val;
        }
        return $optionsDataGender;
    }

    /**
     * @param EntityReader $postalAddresses
     * @return array
     */
    private function optionsDataPostalAddress(EntityReader $postalAddresses): array
    {
        $optionsDataPostalAddress = [];
        /**
         * @var PostalAddress $postalAddress
         */
        foreach ($postalAddresses as $postalAddress) {
            $paId = (int) $postalAddress->getId();
            $address = [];
            if ($paId > 0) {
                if ($postalAddress->getStreet_name()) {
                    $address[] = $postalAddress->getStreet_name();
                }
                if ($postalAddress->getAdditional_street_name()) {
                    $address[] = $postalAddress->getAdditional_street_name();
                }
                if ($postalAddress->getBuilding_number()) {
                    $address[] = $postalAddress->getBuilding_number();
                }
                if ($postalAddress->getCity_name()) {
                    $address[] = $postalAddress->getCity_name();
                }
                if ($postalAddress->getPostalzone()) {
                    $address[] = $postalAddress->getPostalzone();
                }
                if ($postalAddress->getCountrysubentity()) {
                    $address[] = $postalAddress->getCountrysubentity();
                }
                if ($postalAddress->getCountry()) {
                    $address[] = $postalAddress->getCountry();
                }
                $optionsDataPostalAddress[$paId] = implode(',', $address);
            }
        }
        return $optionsDataPostalAddress;
    }

    public function optionsDataClientNameDropdownFilter(cR $cR): array
    {
        $optionsDataClientName = [];
        $clients = $cR->findAllPreloaded();
        /**
         * @var Client $client
         */
        foreach ($clients as $client) {
            $firstname = $client->getClient_name();
            $optionsDataClientName[$firstname] = $firstname;
        }
        return $optionsDataClientName;
    }

    public function optionsDataClientSurnameDropdownFilter(cR $cR): array
    {
        $optionsDataClientSurname = [];
        $clients = $cR->findAllPreloaded();
        /**
         * @var Client $client
         */
        foreach ($clients as $client) {
            $surname = $client->getClient_surname();
            if (null != $surname) {
                $optionsDataClientSurname[$surname] = $surname;
            }
        }
        return $optionsDataClientSurname;
    }

    public function optionsDataClientFrequencyDropdownFilter(): array
    {
        $optionsDataClientFrequency = [];
        $optionsDataClientFrequency['None'] = 'None';
        $optionsDataClientFrequency['Only 1'] = 'Only 1';
        $optionsDataClientFrequency['Daily'] = 'Daily';
        $optionsDataClientFrequency['Weekly'] = 'Weekly';
        $optionsDataClientFrequency['Monthly'] = 'Monthly';
        $optionsDataClientFrequency['2 Monthly'] = '2 Monthly';
        $optionsDataClientFrequency['3 Monthly'] = '3 Monthly';
        $optionsDataClientFrequency['6 Monthly'] = '6 Monthly';
        return $optionsDataClientFrequency;
    }

    /**
     * @param SessionInterface $session
     * @param CurrentRoute $currentRoute
     * @param cR $cR
     * @param cfR $cfR
     * @param cnR $cnR
     * @param cpR $cpR
     * @param cvR $cvR
     * @param ccR $ccR
     * @param delR $delR
     * @param gR $gR
     * @param iR $iR
     * @param irR $irR
     * @param qR $qR
     * @param pymtR $pymtR
     * @param ucR $ucR
     * @return Response
     */
    public function view(
        SessionInterface $session,
        CurrentRoute $currentRoute,
        cR $cR,
        cfR $cfR,
        cnR $cnR,
        cpR $cpR,
        cvR $cvR,
        ccR $ccR,
        delR $delR,
        gR $gR,
        iR $iR,
        irR $irR,
        qR $qR,
        pymtR $pymtR,
        ucR $ucR,
    ): Response {
        $quote = new Quote();
        $quoteForm = new QuoteForm($quote);
        $bootstrap5ModalQuote = new Bootstrap5ModalQuote($this->translator, $this->viewRenderer, $cR, $gR, $this->sR, $ucR, $quoteForm);

        $inv = new Inv();
        $invForm = new InvForm($inv);
        $bootstrap5ModalInv = new Bootstrap5ModalInv($this->translator, $this->viewRenderer, $cR, $gR, $this->sR, $ucR, $invForm);

        $clientCustom = new ClientCustom();
        $clientCustomForm = new ClientCustomForm($clientCustom);

        $optionsGroupData = [];

        $groups = $gR->findAllPreloaded();
        /**
         * @var \App\Invoice\Entity\Group
         */
        foreach ($groups as $group) {
            $optionsGroupData[$group->getId()] = $group->getName();
        }
        $client = $this->client($currentRoute, $cR);
        if ($client instanceof Client) {
            $client_id = $client->getClient_id();
            if (null !== $client_id) {
                $parameters = [
                    'title' => $this->translator->translate('client'),
                    'alert' => $this->alert(),
                    'clientCustomForm' => $clientCustomForm,
                    'custom_fields' => $cfR->repoTablequery('client_custom'),
                    'customValues' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('client_custom')),
                    'cpR' => $cpR,
                    'clientCustomValues' => $this->client_custom_values((string) $client_id, $ccR),
                    'client' => $client,
                    'client_notes' => $cnR->repoClientNoteCount($client_id) > 0 ? $cnR->repoClientquery((string) $client_id) : [],
                    'partial_client_address' => $this->viewRenderer->renderPartialAsString('//invoice/client/partial_client_address', [
                        'client' => $client,
                    ]),
                    // Note here the client_id is presented as the 'origin'. Origin could be 'quote', 'main', 'dashboard'
                    'client_modal_layout_quote' => $bootstrap5ModalQuote->renderPartialLayoutWithFormAsString((string) $client_id, []),
                    'client_modal_layout_inv' => $bootstrap5ModalInv->renderPartialLayoutWithFormAsString((string) $client_id, []),
                    'quote_table' => $this->viewRenderer->renderPartialAsString('//invoice/quote/partial_quote_table', [
                        'quote_count' => $qR->repoCountByClient($client_id),
                        'quotes' => $qR->repoClient($client_id),
                    ]),
                    'quote_draft_table' => $this->viewRenderer->renderPartialAsString('//invoice/quote/partial_quote_table', [
                        'quote_count' => $qR->by_client_quote_status_count($client_id, 1),
                        'quotes' => $qR->by_client_quote_status($client_id, 1),
                    ]),
                    'quote_sent_table' => $this->viewRenderer->renderPartialAsString('//invoice/quote/partial_quote_table', [
                        'quote_count' => $qR->by_client_quote_status_count($client_id, 2),
                        'quotes' => $qR->by_client_quote_status($client_id, 2),
                    ]),
                    'quote_viewed_table' => $this->viewRenderer->renderPartialAsString('//invoice/quote/partial_quote_table', [
                        'quote_count' => $qR->by_client_quote_status_count($client_id, 3),
                        'quotes' => $qR->by_client_quote_status($client_id, 3),
                    ]),
                    'quote_approved_table' => $this->viewRenderer->renderPartialAsString('//invoice/quote/partial_quote_table', [
                        'quote_count' => $qR->by_client_quote_status_count($client_id, 4),
                        'quotes' => $qR->by_client_quote_status($client_id, 4),
                    ]),
                    'quote_rejected_table' => $this->viewRenderer->renderPartialAsString('//invoice/quote/partial_quote_table', [
                        'quote_count' => $qR->by_client_quote_status_count($client_id, 5),
                        'quotes' => $qR->by_client_quote_status($client_id, 5),
                    ]),
                    'quote_cancelled_table' => $this->viewRenderer->renderPartialAsString('//invoice/quote/partial_quote_table', [
                        'quote_count' => $qR->by_client_quote_status_count($client_id, 6),
                        'quotes' => $qR->by_client_quote_status($client_id, 6),
                    ]),
                    'invoice_table' => $this->viewRenderer->renderPartialAsString('//invoice/inv/partial_inv_table', [
                        'invoice_count' => $iR->repoCountByClient($client_id),
                        'invoices' => $iR->repoClient($client_id),
                        'inv_statuses' => $iR->getStatuses($this->translator),
                        'session' => $session,
                    ]),
                    'invoice_draft_table' => $this->viewRenderer->renderPartialAsString('//invoice/inv/partial_inv_table', [
                        'invoice_count' => $iR->by_client_inv_status_count($client_id, 1),
                        'invoices' => $iR->by_client_inv_status($client_id, 1),
                        'inv_statuses' => $iR->getStatuses($this->translator),
                    ]),
                    'invoice_sent_table' => $this->viewRenderer->renderPartialAsString('//invoice/inv/partial_inv_table', [
                        'invoice_count' => $iR->by_client_inv_status_count($client_id, 2),
                        'invoices' => $iR->by_client_inv_status($client_id, 2),
                        'inv_statuses' => $iR->getStatuses($this->translator),
                    ]),
                    'invoice_viewed_table' => $this->viewRenderer->renderPartialAsString('//invoice/inv/partial_inv_table', [
                        'invoice_count' => $iR->by_client_inv_status_count($client_id, 3),
                        'invoices' => $iR->by_client_inv_status($client_id, 3),
                        'inv_statuses' => $iR->getStatuses($this->translator),
                    ]),
                    'invoice_paid_table' => $this->viewRenderer->renderPartialAsString('//invoice/inv/partial_inv_table', [
                        'invoice_count' => $iR->by_client_inv_status_count($client_id, 4),
                        'invoices' => $iR->by_client_inv_status($client_id, 4),
                        'inv_statuses' => $iR->getStatuses($this->translator),
                    ]),
                    'invoice_overdue_table' => $this->viewRenderer->renderPartialAsString('//invoice/inv/partial_inv_table', [
                        'invoice_count' => $iR->by_client_inv_status_count($client_id, 5),
                        'invoices' => $iR->by_client_inv_status($client_id, 5),
                        'inv_statuses' => $iR->getStatuses($this->translator),
                    ]),
                    'invoice_unpaid_table' => $this->viewRenderer->renderPartialAsString('//invoice/inv/partial_inv_table', [
                        'invoice_count' => $iR->by_client_inv_status_count($client_id, 6),
                        'invoices' => $iR->by_client_inv_status($client_id, 6),
                        'inv_statuses' => $iR->getStatuses($this->translator),
                    ]),
                    'invoice_reminder_sent_table' => $this->viewRenderer->renderPartialAsString('//invoice/inv/partial_inv_table', [
                        'invoice_count' => $iR->by_client_inv_status_count($client_id, 7),
                        'invoices' => $iR->by_client_inv_status($client_id, 7),
                        'inv_statuses' => $iR->getStatuses($this->translator),
                    ]),
                    'invoice_seven_day_table' => $this->viewRenderer->renderPartialAsString('//invoice/inv/partial_inv_table', [
                        'invoice_count' => $iR->by_client_inv_status_count($client_id, 8),
                        'invoices' => $iR->by_client_inv_status($client_id, 8),
                        'inv_statuses' => $iR->getStatuses($this->translator),
                    ]),
                    'invoice_legal_claim_table' => $this->viewRenderer->renderPartialAsString('//invoice/inv/partial_inv_table', [
                        'invoice_count' => $iR->by_client_inv_status_count($client_id, 9),
                        'invoices' => $iR->by_client_inv_status($client_id, 9),
                        'inv_statuses' => $iR->getStatuses($this->translator),
                    ]),
                    'invoice_judgement_table' => $this->viewRenderer->renderPartialAsString('//invoice/inv/partial_inv_table', [
                        'invoice_count' => $iR->by_client_inv_status_count($client_id, 10),
                        'invoices' => $iR->by_client_inv_status($client_id, 10),
                        'inv_statuses' => $iR->getStatuses($this->translator),
                    ]),
                    'invoice_officer_table' => $this->viewRenderer->renderPartialAsString('//invoice/inv/partial_inv_table', [
                        'invoice_count' => $iR->by_client_inv_status_count($client_id, 11),
                        'invoices' => $iR->by_client_inv_status($client_id, 11),
                        'inv_statuses' => $iR->getStatuses($this->translator),
                    ]),
                    'invoice_credit_table' => $this->viewRenderer->renderPartialAsString('//invoice/inv/partial_inv_table', [
                        'invoice_count' => $iR->by_client_inv_status_count($client_id, 12),
                        'invoices' => $iR->by_client_inv_status($client_id, 12),
                        'inv_statuses' => $iR->getStatuses($this->translator),
                    ]),
                    'invoice_written_off_table' => $this->viewRenderer->renderPartialAsString('//invoice/inv/partial_inv_table', [
                        'invoice_count' => $iR->by_client_inv_status_count($client_id, 13),
                        'invoices' => $iR->by_client_inv_status($client_id, 13),
                        'inv_statuses' => $iR->getStatuses($this->translator),
                    ]),
                    'partial_notes' => $this->viewRenderer->renderPartialAsString('//invoice/clientnote/partial_notes', [
                        'client_notes' => $cnR->repoClientquery((string) $client_id),
                    ]),
                    'payment_table' => $this->viewRenderer->renderPartialAsString('//invoice/payment/partial_payment_table', [
                        'client' => $client,
                        // All payments from the client are loaded and filtered in the view with
                        // if ($payment->getInv()->getClient_id() === $client->getClient_id())
                        'payments' => $pymtR->repoPaymentInvLoadedAll((int) $this->sR->getSetting('payment_list_limit') ?: 10),
                    ]),
                    'delivery_locations' => $this->viewRenderer->renderPartialAsString('//invoice/client/client_delivery_location_list', [
                        'locations' => $delR->repoClientquery((string) $client->getClient_id()),
                    ]),
                ];
                return $this->viewRenderer->render('view', $parameters);
            }
            return $this->webService->getRedirectResponse('client/index');
        } // if $client
        return $this->webService->getRedirectResponse('client/index');
    }
}
