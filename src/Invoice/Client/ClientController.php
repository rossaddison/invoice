<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Auth\Permissions;
use App\Invoice\BaseController;
// Entity's
use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\Entity\ClientCustom;
use App\Invoice\Entity\Inv;
use App\Invoice\Inv\InvForm;
use App\Invoice\Entity\Quote;
// Services
use App\Service\WebControllerService;
use App\Invoice\ClientCustom\ClientCustomService;
// Forms
use App\Invoice\ClientCustom\ClientCustomForm;
use App\Invoice\Quote\QuoteForm;
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
// Traits
use App\Invoice\Traits\ClientCustomFieldTrait;
use App\Invoice\Traits\ClientNoteTrait;
use App\Invoice\Traits\ClientOptionsDataTrait;
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
use Yiisoft\Data\Reader\CountableDataInterface as CDI;
use Yiisoft\Data\Reader\LimitableDataInterface as LDI;
use Yiisoft\Data\Reader\OffsetableDataInterface as ODI;
use Yiisoft\Data\Reader\ReadableDataInterface as RDI;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

// Miscellaneous

final class ClientController extends BaseController
{
    use ClientCustomFieldTrait;
    use ClientNoteTrait;
    use ClientOptionsDataTrait;

    private const ROUTE_INDEX = 'client/index';

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
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator,
                $webViewRenderer, $session, $sR, $flash);
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
        $cId = $currentRoute->getArgument('id');
        if (null !== $cId) {
            return $cR->repoClientquery((int) $cId);
        }
        return null;
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
        $origin     = $currentRoute->getArgument('origin');
        $new_client = new Client();
        $form       = new ClientForm();
        $parameters         = $this->buildAddParameters($currentRoute,
            $cfR, $cvR, $new_client, $origin);
        $parameters['form'] = $form;

        if ($request->getMethod() === Method::POST) {
            if (!$formHydrator->populateFromPostAndValidate($form, $request)) {
                $parameters['errors'] = $form->getValidationResult()
                                             ->getErrorMessagesIndexedByProperty();
                return $this->webViewRenderer->render('_form', $parameters);
            }
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                /** @var array<string, mixed> $body */
                $formName = $form->getFormName();
                /** @var array<string, mixed> $saveBody */
                $saveBody = isset($body[$formName]) && is_array($body[$formName])
                    ? $body[$formName]
                    : $body;
                $cId = $this->clientService->saveClient($new_client, $saveBody);
                if (null !== $cId) {
                    $this->saveNewClientCustomFields($saveBody, $cId, $formHydrator);
                    $this->flashMessage('info',
                        $this->translator->translate('record.successfully.created'));
                    $redirect = $this->redirectAfterAdd($origin);
                    if (null !== $redirect) {
                        return $redirect;
                    }
                }
            }
        }

        return $this->webViewRenderer->render('_form', $parameters);
    }

    /**
     * Build the view-parameters array for the add form.
     *
     * @param CurrentRoute $currentRoute
     * @param cfR $cfR
     * @param cvR $cvR
     * @param Client $new_client
     * @param string|null $origin
     * @return array<string, mixed>
     */
    private function buildAddParameters(
        CurrentRoute $currentRoute,
        cfR $cfR,
        cvR $cvR,
        Client $new_client,
        ?string $origin,
    ): array {
        $countries = new CountryHelper();
        $custom    = $this->fetchCustomFieldsAndValues($cfR, $cvR, 'client_custom');
        return [
            'title'           => $this->translator->translate('add'),
            'alert'           => $this->alert(),
            'actionName'      => 'client/add',
            'actionArguments' => ['origin' => $origin],
            'origin'          => $origin,
            'errors'          => [],
            'errorsCustom'    => [],
            'client'          => $new_client,
            'aliases'         => new Aliases([
                '@invoice'  => dirname(__DIR__),
                '@language' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Language',
            ]),
            'selectedCountry'  => $this->sR->getSetting('default_country'),
            'selectedLanguage' => $this->sR->getSetting('default_language'),
            'datepicker_dropdown_locale_cldr'         => $this->session->get('_language')
                ?? 'en',
            'optionsDataGender'                       => $this->optionsDataGender(),
            'optionsDataClientFrequencyDropdownFilter' =>
                $this->optionsDataClientFrequencyDropDownFilter(),
            'postal_address_count' => 0,
            'postaladdresses'      => null,
            'countries'       =>
                $countries->getCountryList($currentRoute->getArgument('_language')
                    ?? 'en'),
            'customFields'    => $custom['customFields'],
            'customValues'    => $custom['customValues'],
            'clientCustomValues' => [],
            'clientCustomForm'   => new ClientCustomForm(new ClientCustom()),
            'formFields'         => $this->formFields,
        ];
    }

    /**
     * Save each custom field submitted with a newly created client.
     *
     * @param array<string, mixed> $body
     * @param int|string $cId
     * @param FormHydrator $formHydrator
     * @return void
     */
    private function saveNewClientCustomFields(
        array $body,
        int|string $cId,
        FormHydrator $formHydrator,
    ): void {
        if (!isset($body['custom'])) {
            return;
        }
        /**
         * @var array<int|string, array<mixed>|string> $custom
         */
        $custom = $body['custom'];
        foreach ($custom as $custom_field_id => $value) {
            $clientCustom     = new ClientCustom();
            $clientCustomForm = new ClientCustomForm($clientCustom);
            $client_custom    = [
                'client_id'       => $cId,
                'custom_field_id' => $custom_field_id,
                'value'           => is_array($value) ? serialize($value) : $value,
            ];
            if ($formHydrator->populateAndValidate($clientCustomForm, $client_custom)) {
                $this->clientCustomService->saveClientCustom($clientCustom, $client_custom);
            }
        }
    }

    /**
     * Return the appropriate redirect after a successful client add, or null
     * if the origin does not map to a known route (falls back to re-rendering).
     *
     * @param string|null $origin
     * @return Response|null
     */
    private function redirectAfterAdd(?string $origin): ?Response
    {
        if ($origin === 'dashboard') {
            return $this->webService->getRedirectResponse('invoice/dashboard');
        }
        if ($origin === 'main' || $origin === 'add') {
            return $this->webService->getRedirectResponse(self::ROUTE_INDEX);
        }
        return null;
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
        $client = $this->resolveClientWithId($currentRoute, $cR);
        if (null === $client) {
            return $this->webService->getRedirectResponse(self::ROUTE_INDEX);
        }

        $origin     = $currentRoute->getArgument('origin');
        $parameters = $this->buildEditParameters($currentRoute, $cfR, $cvR,
                $ccR, $paR, $client, $origin);
        /** @var ClientForm $form */
        $form = $parameters['form'];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $returned_form = $this->saveFormFields($body, $form, $client, $formHydrator);
                $parameters['body'] = $body;
                if ($returned_form->isValid()) {
                    $this->processCustomFields($body, $formHydrator,
                            $this->clientCustomFieldProcessor,
                            (string) $client->reqId());
                    $this->flashMessage('info',
                            $this->translator->translate('record.successfully.updated'));
                    $redirect = $this->redirectAfterEdit($origin);
                    if (null !== $redirect) {
                        return $redirect;
                    }
                } else {
                    $parameters['form']   = $returned_form;
                    $parameters['errors'] =
                        $returned_form->getValidationResult()
                                      ->getErrorMessagesIndexedByProperty();
                }
            }
        }

        return $this->webViewRenderer->render('_form', $parameters);
    }

    /**
     * Resolve a Client from the route that also has a non-null ID.
     * Returns null when either condition fails (guards edit/view entry points).
     *
     * @param CurrentRoute $currentRoute
     * @param cR $cR
     * @return Client|null
     */
    private function resolveClientWithId(CurrentRoute $currentRoute, cR $cR): ?Client
    {
        $client = $this->client($currentRoute, $cR);
        if (!($client instanceof Client)) {
            return null;
        }
        return $client;
    }

    /**
     * Resolve the current user's id and their UserInv record in one step.
     * Returns null when the user is not logged in, has no id, or has no UserInv entry.
     *
     * @param uiR $uiR
     * @return array{user_id: int|string, userInv: \App\Invoice\Entity\UserInv}|null
     */
    private function resolveGuestUserContext(uiR $uiR): ?array
    {
        $user_id = $this->userService->getUser()?->getId();
        if (null === $user_id) {
            return null;
        }
        $userInv = $uiR->repoUserInvUserIdcount($user_id) > 0
            ? $uiR->repoUserInvUserIdquery($user_id)
            : null;
        if (null === $userInv) {
            return null;
        }
        return ['user_id' => $user_id, 'userInv' => $userInv];
    }

    /**
     * Build the view-parameters array for the edit form.
     *
     * @param CurrentRoute $currentRoute
     * @param cfR $cfR
     * @param cvR $cvR
     * @param ccR $ccR
     * @param paR $paR
     * @param Client $client
     * @param ?string $origin
     * @return array<string, mixed>
     */
    private function buildEditParameters(
        CurrentRoute $currentRoute,
        cfR $cfR,
        cvR $cvR,
        ccR $ccR,
        paR $paR,
        Client $client,
        ?string $origin,
    ): array {
        $cId             = (string) $client->reqId();
        $form            = ClientForm::show($client);
        $countries       = new CountryHelper();
        $custom          = $this->fetchCustomFieldsAndValues($cfR, $cvR, 'client_custom');
        $postaladdresses = $paR->repoClientAll($cId);
        return [
            'title'           => $this->translator->translate('edit'),
            'actionName'      => 'client/edit',
            'actionArguments' => ['id' => $cId, 'origin' => $origin],
            'alert'           => $this->alert(),
            'errors'          => [],
            'errorsCustom'    => [],
            'origin'          => $origin,
            'client'          => $client,
            'form'            => $form,
            'optionsDataGender'                        => $this->optionsDataGender(),
            'optionsDataClientFrequencyDropdownFilter' => $this->optionsDataClientFrequencyDropDownFilter(),
            'optionsDataPostalAddresses'               => $this->optionsDataPostalAddress($postaladdresses),
            'aliases' => new Aliases([
                '@invoice'  => dirname(__DIR__),
                '@language' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Language',
            ]),
            'selectedCountry'  => $client->getClientCountry() ??
                $this->sR->getSetting('default_country'),
            'selectedLanguage' => $client->getClientLanguage() ??
                $this->sR->getSetting('default_language'),
            'datepicker_dropdown_locale_cldr' =>
                $currentRoute->getArgument('_language', 'en'),
            'postal_address_count' => $paR->repoClientCount($cId),
            'countries'    =>
                $countries->getCountryList($currentRoute->getArgument('_language') ?? 'en'),
            'customFields' => $custom['customFields'],
            'customValues' => $custom['customValues'],
            'clientCustomValues' => $this->clientCustomValues($cId, $ccR),
            'clientCustomForm'   => new ClientCustomForm(new ClientCustom()),
            'formFields'         => $this->formFields,
        ];
    }

    /**
     * Return the redirect Response for known edit origins, or null to re-render.
     *
     * @param string|null $origin
     * @return Response|null
     */
    private function redirectAfterEdit(?string $origin): ?Response
    {
        return match($origin) {
            'edit' => $this->webService->getRedirectResponse(self::ROUTE_INDEX),
            'inv'  => $this->webService->getRedirectResponse('inv/index'),
            default => null,
        };
    }

    public function delete(CurrentRoute $currentRoute, cR $cR): Response
    {
        try {
            $this->clientService->deleteClient($this->client($currentRoute, $cR));
            $this->flashMessage('info',
                $this->translator->translate('record.successfully.deleted'));
//UserClient Entity automatically deletes the UserClient record relevant to this client
            return $this->webService->getRedirectResponse(self::ROUTE_INDEX);
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger',
                    $this->translator->translate('client.delete.history.exits.no'));
            return $this->webService->getRedirectResponse(self::ROUTE_INDEX);
        }
    }

    /**
     * @param array $body
     * @param ClientForm $form
     * @param Client $client
     * @param FormHydrator $formHydrator
     * @return ClientForm
     */
    public function saveFormFields(array $body, ClientForm $form,
            Client $client, FormHydrator $formHydrator): ClientForm
    {
        if ($formHydrator->populateAndValidate($form, $body)) {
            $formName = $form->getFormName();
            /** @var array<string, mixed> $saveBody */
            $saveBody = isset($body[$formName]) && is_array($body[$formName])
                ? $body[$formName]
                : $body;
            $this->clientService->saveClient($client, $saveBody);
        }
        return $form;
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
     * @return Response
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
    ): Response {
        /**
         * Related logic: see $canEdit used in client/index.php to display via label
         *      whether a client has been assigned to an active user account
         */
        $canEdit = $this->userService->hasPermission(Permissions::EDIT_INV);
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse(self::ROUTE_INDEX);
        }
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
        /** @psalm-var RDI<array-key, array<array-key, mixed>|object>&LDI&ODI&CDI $clients */
        $clients = $cR->findAllWithActive($active)->withSort($sort);
        if (isset($query_params['filter_client_name'])
                && !empty($query_params['filter_client_name'])) {
            $clients = $cR->filterClientName(
                    (string) $query_params['filter_client_name']);
        }
        if (isset($query_params['filter_client_surname'])
                && !empty($query_params['filter_client_surname'])) {
            $clients = $cR->filterClientSurname(
                    (string) $query_params['filter_client_surname']);
        }
        if ((isset($query_params['filter_client_name'])
                && !empty($query_params['filter_client_name']))
           && (isset($query_params['filter_client_surname'])
                   && !empty($query_params['filter_client_surname']))) {
            $clients = $cR->filterClientNameSurname(
                    (string) $query_params['filter_client_name'],
                        (string) $query_params['filter_client_surname']);
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
            'defaultPageSizeOffsetPaginator' =>
                $this->sR->getSetting('default_list_limit') ?
                    (int) $this->sR->getSetting('default_list_limit') : 1,
            'modal_create_client' => $this->webViewRenderer->renderPartialAsString('//invoice/client/modal_create_client'),
            'optionsDataClientNameDropdownFilter' =>
                $this->optionsDataClientNameDropdownFilter($cR),
            'optionsDataClientSurnameDropdownFilter' =>
                $this->optionsDataClientSurnameDropdownFilter($cR),
            'urlCreator' => $urlCreator,
            'visible' =>
                $this->sR->getSetting('columns_all_visible') == '0' ? false : true,
        ];
        return $this->webViewRenderer->render('index', $parameters);
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
        $context = $this->resolveGuestUserContext($uiR);
        if (null === $context) {
            return $this->webService->getNotFoundResponse();
        }
        $user_id = (string) $context['user_id'];
        $userInv = $context['userInv'];
        $client_array = $ucR->getAssignedToUser($user_id);
        if (empty($client_array)) {
            $this->flashMessage('warning',
                $this->translator->translate('user.clients.assigned.not'));
            return $this->webService->getNotFoundResponse();
        }
        $clients = $cR->repoUserClient($client_array);
        /** @psalm-var positive-int $listLimit */
        $listLimit = $userInv->getListLimit() > 0 ? ($userInv->getListLimit() ?? 1) : 1;
        $paginator = (new DataOffsetPaginator($clients))
            ->withPageSize($listLimit)
            ->withCurrentPage($currentPageNeverZero)
            ->withToken(PageToken::next((string) $page));
        $parameters = [
            'paginator' => $paginator,
            'alert'     => $this->alert(),
            'iR'        => $iR,
            'iaR'       => $iaR,
            'editInv'   => $this->userService->hasPermission(Permissions::EDIT_INV),
            'active'    => $active,
            'cpR'       => $cpR,
            'defaultPageSizeOffsetPaginator' =>
                $this->sR->getSetting('default_list_limit')
                    ? (int) $this->sR->getSetting('default_list_limit') : 1,
            'modal_create_client' =>
                $this->webViewRenderer->renderPartialAsString(
                    '//invoice/client/modal_create_client'),
            'userInv'    => $userInv,
            'urlCreator' => $urlCreator,
        ];
        return $this->webViewRenderer->render('guest', $parameters);
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
        qR $qR,
        pymtR $pymtR,
        ucR $ucR,
    ): Response {
        $client = $this->client($currentRoute, $cR);
        if (!($client instanceof Client)) {
            return $this->webService->getRedirectResponse(self::ROUTE_INDEX);
        }
        $cId = $client->reqId();

        $clientCustomForm = new ClientCustomForm(new ClientCustom());

        // Note: client_id is used as the 'origin'
        //  (could be 'quote','main','dashboard')
        $bootstrap5ModalQuote = new Bootstrap5ModalQuote(
            $this->translator, $this->webViewRenderer, $cR, $gR, $this->sR, $ucR,
            new QuoteForm(new Quote()),
        );
        $bootstrap5ModalInv = new Bootstrap5ModalInv(
            $this->translator, $this->webViewRenderer, $cR, $gR, $this->sR, $ucR,
            new InvForm(new Inv()),
        );

        $parameters = [
            'title'             => $this->translator->translate('client'),
            'alert'             => $this->alert(),
            'clientCustomForm'  => $clientCustomForm,
            'custom_fields'     => $cfR->repoTablequery('client_custom'),
            'customValues'      => $cvR->fixCfValueToCf($cfR->repoTablequery('client_custom')),
            'cpR'               => $cpR,
            'clientCustomValues'=> $this->clientCustomValues((string) $cId, $ccR),
            'client'            => $client,
            'client_notes'      => $cnR->repoClientNoteCount($cId) > 0
                                       ? $cnR->repoClientquery((string) $cId) : [],
            'partial_client_address' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/client/partial_client_address', ['client' => $client]
            ),
            'client_modal_layout_quote' =>
                $bootstrap5ModalQuote->renderPartialLayoutWithFormAsString((string) $cId, []),
            'client_modal_layout_inv' =>
                $bootstrap5ModalInv->renderPartialLayoutWithFormAsString((string) $cId, []),
            'partial_notes' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/clientnote/partial_notes',
                ['client_notes' => $cnR->repoClientquery((string) $cId)]
            ),
            // All payments are loaded here and filtered inside the view partial
            // via: if ($payment->getInv()->reqClientId() === $client->reqId())
            'payment_table' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/payment/partial_payment_table', [
                    'client'   => $client,
                    'payments' => $pymtR->repoPaymentInvLoadedAll(
                        (int) $this->sR->getSetting('payment_list_limit') ?: 10
                    ),
                ]
            ),
            'delivery_locations' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/client/client_delivery_location_list',
                ['locations' => $delR->repoClientquery((string) $client->reqId())]
            ),
        ];

        // Quote tables — "all" plus one per status (1 draft … 6 cancelled)
        $parameters['quote_table'] = $this->renderQuoteTablePartial($qR, $cId);
        $quoteStatusKeys = [
            1 => 'quote_draft_table',
            2 => 'quote_sent_table',
            3 => 'quote_viewed_table',
            4 => 'quote_approved_table',
            5 => 'quote_rejected_table',
            6 => 'quote_cancelled_table',
        ];
        foreach ($quoteStatusKeys as $status => $key) {
            $parameters[$key] = $this->renderQuoteTablePartial($qR, $cId, $status);
        }

        // Invoice tables — "all" (with session) plus one per status
        //  (1 draft … 13 written-off)
        $parameters['invoice_table'] = $this->renderInvTablePartial($iR, $cId,
                null, $session);
        $invStatusKeys = [
            1  => 'invoice_draft_table',
            2  => 'invoice_sent_table',
            3  => 'invoice_viewed_table',
            4  => 'invoice_paid_table',
            5  => 'invoice_overdue_table',
            6  => 'invoice_unpaid_table',
            7  => 'invoice_reminder_sent_table',
            8  => 'invoice_seven_day_table',
            9  => 'invoice_legal_claim_table',
            10 => 'invoice_judgement_table',
            11 => 'invoice_officer_table',
            12 => 'invoice_credit_table',
            13 => 'invoice_written_off_table',
        ];
        foreach ($invStatusKeys as $status => $key) {
            $parameters[$key] = $this->renderInvTablePartial($iR, $cId, $status);
        }

        return $this->webViewRenderer->render('view', $parameters);
    }

    /**
     * Render the quote partial table for a client, optionally filtered by status.
     *
     * @param qR $qR
     * @param int|string $cId
     * @param int|null $status  null = all quotes; 1–6 = specific status
     * @return string
     */
    private function renderQuoteTablePartial(qR $qR, int|string $cId,
        ?int $status = null): string
    {
        $id = (int) $cId;
        $data = $status === null
            ? ['quote_count' => $qR->repoCountByClient($id),
                'quotes' => $qR->repoClient($id)]
            : ['quote_count' => $qR->byClientQuoteStatusCount($id, $status),
                'quotes' => $qR->byClientQuoteStatus($id, $status)];

        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/quote/partial_quote_table', $data
        );
    }

    /**
     * Render the invoice partial table for a client,
     *  optionally filtered by status.
     *
     * @param iR $iR
     * @param int|string $cId
     * @param int|null $status  null = all invoices; 1–13 = specific status
     * @param SessionInterface|null $session  only needed for
     *  the "all invoices" table
     * @return string
     */
    private function renderInvTablePartial(
        iR $iR,
        int|string $cId,
        ?int $status = null,
        ?SessionInterface $session = null,
    ): string {
        $id = (int) $cId;
        $data = $status === null
            ? ['invoice_count' => $iR->repoCountByClient($id),
                'invoices' => $iR->repoClient($id)]
            : ['invoice_count' => $iR->byClientInvStatusCount($id, $status),
                'invoices' => $iR->byClientInvStatus($id, $status)];

        $data['inv_statuses'] = $iR->getStatuses($this->translator);
        if ($session !== null) {
            $data['session'] = $session;
        }

        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/partial_inv_table', $data
        );
    }
}
