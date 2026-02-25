<?php

declare(strict_types=1);

namespace App\Invoice\CustomValue;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Invoice\Entity\CustomValue;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\CustomField\CustomFieldForm;
use App\Invoice\CustomField\CustomFieldRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class CustomValueController extends BaseController
{
    protected string $controllerName = 'invoice/customvalue';

    public function __construct(
        private CustomValueService $customValueService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        $this->customValueService = $customValueService;
    }

    /**
     * @param CustomValueRepository $customvalueRepository
     * @param CustomFieldRepository $customfieldRepository
     * @return Response
     */
    public function index(CustomValueRepository $customvalueRepository, CustomFieldRepository $customfieldRepository): Response
    {
        $this->rbac();
        $custom_field_id = (string) $this->session->get('custom_field_id');
        $custom_values = $customvalueRepository->repoCustomFieldquery((int) $custom_field_id);
        $parameters = [
            'custom_field' => $customfieldRepository->repoCustomFieldquery($custom_field_id),
            'custom_field_id' => $custom_field_id,
            'custom_values' => $custom_values,
            'custom_values_types' => array_merge($this->user_input_types(), $this->custom_value_fields()),
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    /**
     * @param CustomFieldRepository $customfieldRepository
     * @param CustomValueRepository $customvalueRepository
     * @param CurrentRoute $currentRoute
     * @param CustomValueService $service
     * @return Response
     */
    public function field(CustomFieldRepository $customfieldRepository, CustomValueRepository $customvalueRepository, CurrentRoute $currentRoute, CustomValueService $service): Response
    {
        $this->rbac();
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            null !== ($this->session->get('custom_field_id')) ?: $this->session->set('custom_field_id', $id);
            $custom_field = $customfieldRepository->repoCustomFieldquery($id);
            $customvalues = $customvalueRepository->repoCustomFieldquery((int) $id);
            if ($custom_field) {
                $field_form = new CustomFieldForm($custom_field);
                $parameters = [
                    'field_form' => $field_form,
                    'custom_field' => $custom_field,
                    'custom_values_types' => array_merge($this->user_input_types(), $this->custom_value_fields()),
                    'custom_values' => $customvalues,
                ];
                return $this->webViewRenderer->render('field', $parameters);
            }
        }
        return $this->webService->getRedirectResponse('customvalue/index');
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param CustomFieldRepository $custom_fieldRepository
     * @return Response
     */
    public function new(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        CustomFieldRepository $custom_fieldRepository,
    ): Response {
        $field_id = $currentRoute->getArgument('id');
        if (null !== $field_id) {
            $this->session->set('custom_field_id', $field_id);
            $custom_field = $custom_fieldRepository->repoCustomFieldquery($field_id);
            $custom_value = new CustomValue();
            if ($custom_field) {
                $form = new CustomValueForm($custom_value);
                $parameters = [
                    'actionName' => 'customvalue/new',
                    'actionArguments' => ['id' => $field_id],
                    'errors' => [],
                    'form' => $form,
                    'custom_field' => $custom_field,
                    'custom_fields' => $custom_fieldRepository->findAllPreloaded(),
                ];

                if ($request->getMethod() === Method::POST) {
                    $body = $request->getParsedBody() ?? [];
                    if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                        if (is_array($body)) {
                            $this->customValueService->saveCustomValue($custom_value, $body);
                            return $this->webService->getRedirectResponse('customvalue/field', ['id' => $field_id]);
                        }
                    }
                    $parameters['form'] = $form;
                    $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                }
                return $this->webViewRenderer->render('new', $parameters);
            }
        } //if custom_fiedl
        return $this->webService->getRedirectResponse('customvalue/index');
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param CustomValueRepository $customvalueRepository
     * @param CustomFieldRepository $custom_fieldRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        CustomValueRepository $customvalueRepository,
        CustomFieldRepository $custom_fieldRepository,
    ): Response {
        $custom_field_id = (string) $this->session->get('custom_field_id');
        $custom_field = $custom_fieldRepository->repoCustomFieldquery($custom_field_id);
        $custom_value = $this->customvalue($currentRoute, $customvalueRepository);
        if ($custom_field && $custom_value) {
            $form = new CustomValueForm($custom_value);
            $parameters = [
                'actionName' => 'customvalue/edit',
                'actionArguments' => ['id' => $custom_value->getId()],
                'errors' => [],
                'form' => $form,
                'custom_field' => $custom_field,
                'custom_fields' => $custom_fieldRepository->findAllPreloaded(),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->customValueService->saveCustomValue($custom_value, $body);
                        return $this->webService->getRedirectResponse('customvalue/field', ['id' => $custom_field_id]);
                    }
                }
                $parameters['form'] = $form;
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            }
            return $this->webViewRenderer->render('edit', $parameters);
        }
        return $this->webService->getRedirectResponse('customvalue/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param CustomValueRepository $customvalueRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        CustomValueRepository $customvalueRepository,
    ): Response {
        $custom_field_id = (string) $this->session->get('custom_field_id');
        $custom_value = $this->customvalue($currentRoute, $customvalueRepository);
        if ($custom_value) {
            $this->customValueService->deleteCustomValue($custom_value);
            $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));
            return $this->webService->getRedirectResponse('customvalue/field', ['id' => $custom_field_id]);
        }
        return $this->webService->getRedirectResponse('customvalue/field', ['id' => $custom_field_id]);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param CustomValueRepository $customvalueRepository
     */
    public function view(CurrentRoute $currentRoute, CustomValueRepository $customvalueRepository): Response
    {
        $custom_value = $this->customvalue($currentRoute, $customvalueRepository);
        if ($custom_value) {
            $form = new CustomValueForm($custom_value);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'customvalue/view',
                'actionArguments' => ['id' => $custom_value->getId()],
                'form' => $form,
            ];
            return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('customvalue/index');
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission(Permissions::EDIT_INV);
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse('customvalue/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param CustomValueRepository $customvalueRepository
     * @return CustomValue|null
     */
    private function customvalue(CurrentRoute $currentRoute, CustomValueRepository $customvalueRepository): ?CustomValue
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $customvalueRepository->repoCustomValuequery($id);
        }
        return null;
    }

    /**
     * @return string[]
     *
     * @psalm-return list{'TEXT', 'DATE', 'BOOLEAN'}
     */
    public function user_input_types(): array
    {
        return [
            'TEXT',
            'DATE',
            'BOOLEAN',
        ];
    }

    /**
     * @return string[]
     *
     * @psalm-return list{'SINGLE-CHOICE', 'MULTIPLE-CHOICE'}
     */
    public function custom_value_fields(): array
    {
        return [
            'SINGLE-CHOICE',
            'MULTIPLE-CHOICE',
        ];
    }
}
