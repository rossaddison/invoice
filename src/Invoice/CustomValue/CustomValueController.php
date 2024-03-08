<?php

declare(strict_types=1); 

namespace App\Invoice\CustomValue;

use App\Invoice\Entity\CustomValue;
use App\Invoice\CustomValue\CustomValueService;
use App\Invoice\CustomValue\CustomValueRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\CustomField\CustomFieldForm;
use App\Invoice\CustomField\CustomFieldRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\ViewRenderer;

final class CustomValueController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private CustomValueService $customvalueService;
    private TranslatorInterface $translator;
        
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        CustomValueService $customvalueService,
        TranslatorInterface $translator,
    )    
    {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/customvalue')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->customvalueService = $customvalueService;
        $this->translator = $translator;
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @param CustomValueRepository $customvalueRepository
     * @param CustomFieldRepository $customfieldRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function index(SessionInterface $session, CustomValueRepository $customvalueRepository, CustomFieldRepository $customfieldRepository, SettingRepository $settingRepository): Response
    {
         $canEdit = $this->rbac($session);
         $custom_field_id = (string)$session->get('custom_field_id');
         $custom_values = $customvalueRepository->repoCustomFieldquery((int)$custom_field_id);
         $parameters = [
          'custom_field' => $customfieldRepository->repoCustomFieldquery($custom_field_id),
          'custom_field_id' => $custom_field_id,
          'canEdit' => $canEdit,
          'custom_values' => $custom_values,
          'custom_values_types'=> array_merge($this->user_input_types(), $this->custom_value_fields()),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }
     
    /**
     * @param SessionInterface $session
     * @param CustomFieldRepository $customfieldRepository
     * @param CustomValueRepository $customvalueRepository
     * @param SettingRepository $settingRepository
     * @param CurrentRoute $currentRoute
     * @param CustomValueService $service
     * @return Response
     */
    public function field(SessionInterface $session, CustomFieldRepository $customfieldRepository, CustomValueRepository $customvalueRepository, SettingRepository $settingRepository, CurrentRoute $currentRoute, CustomValueService $service): Response
    {      
        $canEdit = $this->rbac($session);
        $id = $currentRoute->getArgument('id');
        if (null!==$id) {
            null!==($session->get('custom_field_id')) ?: $session->set('custom_field_id', $id);
            $custom_field = $customfieldRepository->repoCustomFieldquery($id);
            $customvalues = $customvalueRepository->repoCustomFieldquery((int)$id);    
            if ($custom_field) {
                $field_form = new CustomFieldForm($custom_field);
                $parameters = [
                    'canEdit' => $canEdit,
                    'field_form' => $field_form,
                    'custom_field' => $custom_field,
                    'custom_values_types' => array_merge($this->user_input_types(), $this->custom_value_fields()), 
                    'custom_values'=> $customvalues,
                ];
                return $this->viewRenderer->render('field', $parameters);
            }
        }
        return $this->webService->getRedirectResponse('customvalue/index');   
    }
    
    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param CustomFieldRepository $custom_fieldRepository
     * @return Response
     */
    public function new(Request $request, SessionInterface $session, CurrentRoute $currentRoute, 
                        FormHydrator $formHydrator,                        
                        CustomFieldRepository $custom_fieldRepository
    ): Response
    {
        $field_id = $currentRoute->getArgument('id');        
        if (null!==$field_id) {
            $session->set('custom_field_id', $field_id);
            $custom_field = $custom_fieldRepository->repoCustomFieldquery($field_id);
            $custom_value = new CustomValue();
            if ($custom_field){
                $form = new CustomValueForm($custom_value);
                $parameters = [
                    'title' => $this->translator->translate('invoice.add'),
                    'action' => ['customvalue/add'],
                    'errors' => [],
                    'form' => $form,
                    'custom_field'=>$custom_field, 
                    'custom_fields'=>$custom_fieldRepository->findAllPreloaded()
                ];

                if ($request->getMethod() === Method::POST) {
                    $body = $request->getParsedBody() ?? '';
                    if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                        /**
                         * @psalm-suppress PossiblyInvalidArgument $body
                         */
                        $this->customvalueService->saveCustomValue($custom_value, $body);
                        return $this->webService->getRedirectResponse('customvalue/field', ['id' => $field_id]);                 
                    }
                    $parameters['form'] = $form;
                    $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
                }
                return $this->viewRenderer->render('new', $parameters);
            }            
        } //if custom_fiedl
        return $this->webService->getRedirectResponse('customvalue/index');
    }
    
    /**
     * @param SessionInterface $session
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param CustomValueRepository $customvalueRepository
     * @param CustomFieldRepository $custom_fieldRepository
     * @return Response
     */
    public function edit(SessionInterface $session, Request $request, CurrentRoute $currentRoute, 
                        FormHydrator $formHydrator,
                        CustomValueRepository $customvalueRepository,                
                        CustomFieldRepository $custom_fieldRepository
    ): Response {
        $custom_field_id = (string)$session->get('custom_field_id');
        $custom_field = $custom_fieldRepository->repoCustomFieldquery($custom_field_id);
        $custom_value = $this->customvalue($currentRoute, $customvalueRepository);
        if ($custom_field && $custom_value) {
            $form = new CustomValueForm($custom_value);
            $parameters = [
                'title' =>  $this->translator->translate('invoice.edit'),
                'action' => ['customvalue/edit', ['id' => $custom_value->getId()]],
                'errors' => [],
                'form' => $form,
                'custom_field' => $custom_field,
                'custom_fields'=> $custom_fieldRepository->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body
                     */
                    $this->customvalueService->saveCustomValue($custom_value, $body);
                    return $this->webService->getRedirectResponse('customvalue/field', ['id' => $custom_field_id]);                 
                }
                $parameters['form'] = $form;
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
            }
            return $this->viewRenderer->render('edit', $parameters);
        }
        return $this->webService->getRedirectResponse('customvalue/index');   
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @param CurrentRoute $currentRoute
     * @param CustomValueRepository $customvalueRepository
     * @return Response
     */
    public function delete(SessionInterface $session,CurrentRoute $currentRoute,
                           CustomValueRepository $customvalueRepository
    ): Response {
        $custom_field_id = (string)$session->get('custom_field_id');
            $custom_value = $this->customvalue($currentRoute,$customvalueRepository);
            if ($custom_value) {
                $this->customvalueService->deleteCustomValue($custom_value);               
                $this->flash($session, 'info', $this->translator->translate('i.record_successfully_deleted'));
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
                'title' => $this->translator->translate('i.view'),
                'action' => ['customvalue/view', ['id' => $custom_value->getId()]],
                'form' => $form,
                'customvalue' => $custom_value->getId(),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('customvalue/index'); 
    }
        
    /**
     * @return Response|true
     */
    private function rbac(SessionInterface $session): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash($session,'warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('customvalue/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param CustomValueRepository $customvalueRepository
     * @return CustomValue|null
     */
    private function customvalue(CurrentRoute $currentRoute,CustomValueRepository $customvalueRepository): CustomValue|null
    {
        $id = $currentRoute->getArgument('id');
        if (null!==$id) {
            $customvalue = $customvalueRepository->repoCustomValuequery($id);
            return $customvalue;
        }
        return null;
    }  
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function customvalues(CustomValueRepository $customvalueRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader 
    {
        $customvalues = $customvalueRepository->findAllPreloaded();        
        return $customvalues;
    }
    
    private function flash(SessionInterface $session, string $level, string $message): Flash{
        $flash = new Flash($session);
        $flash->set($level, $message); 
        return $flash;
    }
    
    /**
     * @return string[]
     *
     * @psalm-return list{'TEXT', 'DATE', 'BOOLEAN'}
     */
    public function user_input_types() : array
    {
        return array(
            'TEXT',
            'DATE',
            'BOOLEAN'
        );
    }

    /**
     * @return string[]
     *
     * @psalm-return list{'SINGLE-CHOICE', 'MULTIPLE-CHOICE'}
     */
    public function custom_value_fields() : array
    {
        return array(
            'SINGLE-CHOICE',
            'MULTIPLE-CHOICE'
        );
    }
}

