<?php

declare(strict_types=1);

namespace App\Invoice\CustomField;

use App\Invoice\BaseController;
use App\Invoice\Entity\CustomField;
use App\Invoice\CustomValue\CustomValueRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\User\UserService;
use App\Service\WebControllerService;
// Psr
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// Yii
use Yiisoft\Data\Paginator\OffsetPaginator as DataOffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class CustomFieldController extends BaseController
{
    protected string $controllerName = 'invoice/customfield';

    public function __construct(
        private CustomFieldService $customFieldService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->customFieldService = $customFieldService;
    }

    /**
     * @param CustomFieldRepository $customfieldRepository
     * @param Request $request
     */
    public function index(CustomFieldRepository $customfieldRepository, Request $request): \Yiisoft\DataResponse\DataResponse
    {
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page']
         */
        $page = $query_params['page'] ?? 1;
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        /** @var string $query_params['sort'] */
        $sort = Sort::only(['id'])
                ->withOrderString($query_params['sort'] ?? '-id');
        $customFields = $this->customFieldsWithSort($customfieldRepository, $sort);
        $paginator = (new DataOffsetPaginator($customFields))
        ->withPageSize($this->sR->positiveListLimit())
        ->withCurrentPage($currentPageNeverZero)
        ->withToken(PageToken::next((string) $page));
        $this->rbac();
        $this->flashMessage('info', $this->viewRenderer->renderPartialAsString('//invoice/info/custom_field'));
        $parameters = [
            'page' => $page,
            'paginator' => $paginator,
            'defaultPageSizeOffsetPaginator' => $this->sR->getSetting('default_list_limit')
                                                  ? (int) $this->sR->getSetting('default_list_limit') : 1,
            'custom_tables' => $this->custom_tables(),
            'custom_value_fields' => self::custom_value_fields(),
            'alert' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param CustomFieldRepository $cfR
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\DataReaderInterface&\Yiisoft\Data\Reader\SortableDataInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, CustomField>
     */
    private function customFieldsWithSort(CustomFieldRepository $cfR, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface
    {
        $query = $this->customFields($cfR);
        return $query->withSort($sort);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(
        Request $request,
        FormHydrator $formHydrator,
    ): Response {
        $body = $request->getParsedBody() ?? [];
        $custom_field = new CustomField();
        $form = new CustomFieldForm($custom_field);
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'customfield/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'tables' => $this->custom_tables(),
            'user_input_types' => ['NUMBER','TEXT','DATE','BOOLEAN'],
            'custom_value_fields' => ['SINGLE-CHOICE','MULTIPLE-CHOICE'],
            // Create an array for "moduled" ES6 jquery script. The script is "moduled" and therefore deferred by default to avoid
            // the $ undefined reference error in the DOM.
            'positions' => $this->positions(),
        ];

        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->customFieldService->saveCustomField($custom_field, $body);
                    return $this->webService->getRedirectResponse('customfield/index');
                }
            }
            $parameters['form'] = $form;
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param CustomFieldRepository $customfieldRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        CustomFieldRepository $customfieldRepository,
    ): Response {
        $custom_field = $this->customfield($currentRoute, $customfieldRepository);
        if ($custom_field) {
            $form = new CustomFieldForm($custom_field);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'customfield/edit',
                'actionArguments' => ['id' => $custom_field->getId()],
                'errors' => [],
                'form' => $form,
                'tables' => $this->custom_tables(),
                'user_input_types' => ['NUMBER','TEXT','DATE','BOOLEAN'],
                'custom_value_fields' => ['SINGLE-CHOICE','MULTIPLE-CHOICE'],
                'positions' => $this->positions(),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->customFieldService->saveCustomField($custom_field, $body);
                        return $this->webService->getRedirectResponse('customfield/index');
                    }
                }
                $parameters['form'] = $form;
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('customfield/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param CustomFieldRepository $customfieldRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, CustomFieldRepository $customfieldRepository, CustomValueRepository $customvalueRepository): Response
    {
        $custom_field = $this->customfield($currentRoute, $customfieldRepository);
        if ($custom_field instanceof CustomField) {
            $custom_values = $customvalueRepository->repoCustomFieldquery_count((int) $custom_field->getId());
            // Make sure all custom values associated with the custom field have been deleted first before commencing
            if (!($custom_values > 0)) {
                $this->customFieldService->deleteCustomField($custom_field);
                return $this->webService->getRedirectResponse('customfield/index');
            }
        }
        // Return to the index and warn of existing custom values associated with the custom field
        $this->flashMessage('warning', $this->translator->translate('custom.value.delete'));
        return $this->webService->getRedirectResponse('customfield/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param CustomFieldRepository $customfieldRepository
     */
    public function view(CurrentRoute $currentRoute, CustomFieldRepository $customfieldRepository): Response
    {
        $custom_field = $this->customfield($currentRoute, $customfieldRepository);
        if ($custom_field) {
            $form = new CustomFieldForm($custom_field);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'customfield/edit',
                'actionArguments' => ['id' => $custom_field->getId()],
                'errors' => [],
                'form' => $form,
                'custom_tables' => $this->custom_tables(),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('customfield/index');
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse('customfield/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param CustomFieldRepository $customfieldRepository
     * @return CustomField|null
     */
    private function customfield(CurrentRoute $currentRoute, CustomFieldRepository $customfieldRepository): CustomField|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $customfieldRepository->repoCustomFieldquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function customfields(CustomFieldRepository $customfieldRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $customfieldRepository->findAllPreloaded();
    }

    /**
     * @return string
     */
    private function positions(): string
    {
        // The default position on the form is custom fields so if none of the other options are chosen then the new field
        // will appear under the default custom field section. The client form has five areas where the new field can appear.
        $positions = [
            'client' => ['i.custom_fields', 'i.address', 'i.contact_information', 'i.personal_information', 'i.tax_information'],
            'product' => ['i.custom_fields'],
            // A custom field created with "properties" will appear in the address section
            'invoice' => ['i.custom_fields','i.properties'],
            'payment' => ['i.custom_fields'],
            'quote' => ['i.custom_fields','i.properties'],
            'user' => ['i.custom_fields', 'i.account_information', 'i.address', 'i.tax_information', 'i.contact_information'],
        ];
        foreach ($positions as $key => $val) {
            foreach ($val as $key2 => $val2) {
                $val[$key2] = $this->translator->translate($val2);
            }
            $positions[$key] = $val;
        }
        return Json::encode($positions);
    }

    /**
     * @return array
     */
    private function custom_tables(): array
    {
        return [
            'client_custom' => 'client',
            'product_custom' => 'product',
            'inv_custom' => 'invoice',
            'payment_custom' => 'payment',
            'quote_custom' => 'quote',
            'sales_order_custom' => 'sales_order',
            'user_custom' => 'user',
        ];
    }

    /**
     * @return string[]
     *
     * @psalm-return list{'SINGLE-CHOICE', 'MULTIPLE-CHOICE'}
     */
    public static function custom_value_fields(): array
    {
        return [
            'SINGLE-CHOICE',
            'MULTIPLE-CHOICE',
        ];
    }
}
