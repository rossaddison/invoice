<?php

declare(strict_types=1); 

namespace App\Invoice\Qa;

use App\Invoice\BaseController;
use App\Invoice\Entity\Qa;
use App\Invoice\Qa\QaForm;
use App\Invoice\Qa\QaService;
use App\Invoice\Qa\QaRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\{ResponseInterface as Response, ServerRequestInterface as Request};

use Yiisoft\{Data\Cycle\Reader\EntityReader,
    FormModel\FormHydrator, Http\Method, Input\Http\Attribute\Parameter\Query,
    Router\HydratorAttribute\RouteArgument, Session\SessionInterface,
    Session\Flash\Flash, Translator\TranslatorInterface,
    Yii\View\Renderer\WebViewRenderer};
use Exception;

final class QaController extends BaseController
{
    protected string $controllerName = 'invoice/qa';

    public function __construct(
        private QaService $qaService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,    
    ) {
        parent::__construct(
            $webService, $userService, $translator,
            $webViewRenderer, $session, $sR, $flash);
        $this->qaService = $qaService;
    }  
           
    public function index(
        QaRepository $qaRepository,
        sR $sR,
        #[RouteArgument('page')] int $page = 1,
        #[Query('page')]
        ?string $queryPage = null,
        #[Query('sort')]
        ?string $querySort = null): Response
    {
      $page = $queryPage ?? $page;
      $parameters = [
        'qas' => $qaRepository->findAllPreloaded(),
        'alert' => $this->alert(),
        'defaultPageSizeOffsetPaginator' => $sR->getSetting('default_list_limit')
            ? (int)$sR->getSetting('default_list_limit') : 1,
        'page' => (int) $page > 0 ? (int) $page : 1,
        'sortString' => $querySort ?? '-id',
      ];
      return $this->webViewRenderer->render('index', $parameters);
    } 
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(Request $request, FormHydrator $formHydrator): Response
    {
        $qa = new Qa();
        $form = new QaForm($qa);
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'qa/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
        ];        
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $this->qaService->saveQa($qa, $body);
                    $this->flashMessage('info',
                            $this->translator->translate(
                                'record.successfully.created'));
                    return $this->webService->getRedirectResponse('qa/index');
                }
                $parameters['errors'] = $form->getValidationResult()
                                             ->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            } // is_array($body)   
        }
        return $this->webViewRenderer->render('_form', $parameters);
    }
    
    /**
     * @param QaRepository $qaRepository
     * @param int $id
     * @return Response
     */
    public function delete(QaRepository $qaRepository,
        #[RouteArgument('id')] int $id 
    ): Response {
        try {
            $qa = $this->qa($qaRepository, $id);
            if ($qa) {
                $this->qaService->deleteQa($qa);               
                $this->flashMessage('info',
                    $this->translator->translate('record.successfully.deleted'));
                return $this->webService->getRedirectResponse('qa/index'); 
            }
            return $this->webService->getRedirectResponse('qa/index'); 
	} catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('qa/index'); 
        }
    }
        
    public function edit(Request $request, FormHydrator $formHydrator,
        QaRepository $qaRepository, #[RouteArgument('id')] int $id): Response {
        $qa = $this->qa($qaRepository, $id);
        if ($qa){
            $form = new QaForm($qa);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'qa/edit', 
                'actionArguments' => ['id' => $id],
                'errors' => [],
                'form' => $form,
                
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                        $this->qaService->saveQa($qa, $body);
                        return $this->webService->getRedirectResponse('qa/index');
                    }
                    $parameters['errors'] =
                        $form->getValidationResult()
                             ->getErrorMessagesIndexedByProperty();
                    $parameters['form'] = $form;
                }    
            }
            return $this->webViewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('qa/index');
    }
    
    /**     
     * @param QaRepository $qaRepository
     * @param int $id
     * @return Qa|null
     */
    private function qa(QaRepository $qaRepository, int $id): Qa|null
    {
        if ($id) {
            $qa = $qaRepository->repoQaQuery((string)$id);
            return $qa;
        }
        return null;
    }
        
    /**
     * @param QaRepository $qaRepository
     * @param int $id
     * @return Response
     */
    public function view(QaRepository $qaRepository,
        #[RouteArgument('id')] int $id): Response 
    {
        $qa = $this->qa($qaRepository, $id); 
        if ($qa) {
            $form = new QaForm($qa);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'qa/view', 
                'actionArguments' => ['id' => $id],
                'form' => $form,
                'qa' => $qa,
            ];        
        return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('
                qa/index');
    }
}
