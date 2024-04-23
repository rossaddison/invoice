<?php
declare(strict_types=1); 

namespace App\Invoice\Sumex;

use App\Invoice\Entity\Sumex;
use App\Invoice\Sumex\SumexService;
use App\Invoice\Sumex\SumexForm;
use App\Invoice\Sumex\SumexRepository;
use App\Invoice\Setting\SettingRepository;
use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\ViewRenderer;
use Yiisoft\FormModel\FormHydrator;

final class SumexController
{
    private Flash $flash;
    private Session $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private SumexService $sumexService;
    private TranslatorInterface $translator;
    private DataResponseFactoryInterface $factory;

    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        SumexService $sumexService,
        TranslatorInterface $translator,
        DataResponseFactoryInterface $factory,
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer;      
        $this->webService = $webService;
        $this->userService = $userService;
        $this->sumexService = $sumexService;
        $this->translator = $translator;
        $this->factory = $factory;
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/sumex')
                                               ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/sumex')
                                               ->withLayout('@views/layout/invoice.php');
        }
    }
    
  /**
   * @param SumexRepository $sumexRepository
   * @param SettingRepository $s
   */
    public function index(SumexRepository $sumexRepository, SettingRepository $s): \Yiisoft\DataResponse\DataResponse
    {
        $canEdit = $this->rbac();
        $sumexs = $this->sumexs($sumexRepository); 
        $paginator = (new OffsetPaginator($sumexs));
        $parameters = [
            'canEdit' => $canEdit,
            'grid_summary'=> $s->grid_summary(
                $paginator,
                $this->translator, 
                (int)$s->get_setting('default_list_limit'), 
                $this->translator->translate('i.invoice_sumex'), ''), 
            'sumexs' => $sumexs, 
            'paginator' => $paginator,   
            'alert'=> $this->alert()
        ];
        return $this->viewRenderer->render('index', $parameters);
    }
    
  /**
   * @return string
   */
   private function alert(): string {
     return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
     [ 
       'flash' => $this->flash
     ]);
   }

    /**
     * @param string $level
     * @param string $message
     * @return Flash|null
     */
    private function flash_message(string $level, string $message): Flash|null {
        if (strlen($message) > 0) {
            $this->flash->add($level, $message, true);
            return $this->flash;
        }
        return null;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SettingRepository $settingRepository
     * @return Response
     */    
    public function add(CurrentRoute $currentRoute, Request $request, 
                        FormHydrator $formHydrator,
                        SettingRepository $settingRepository
    ): Response
    {
        $inv_id = $currentRoute->getArgument('inv_id');
        $model = new Sumex();
        $form = new SumexForm($model);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['sumex/add', ['inv_id' => $inv_id]],
            'inv_id' => $inv_id,
            'form' => $form,
            'optionsDataReasons' => $this->optionsDataReasons(),
            'errors' => [],
        ];
        
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody();
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                $this->sumexService->saveSumex($model, $body);
                return $this->webService->getRedirectResponse('sumex/index');
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param SumexRepository $sumexRepository
     * @return Response
     */
    public function edit(Request $request, CurrentRoute $currentRoute,
                        FormHydrator $formHydrator,
                        SumexRepository $sumexRepository
    ): Response {
        $sumex = $this->sumex($currentRoute, $sumexRepository);
        if ($sumex) {
            $form = new SumexForm($sumex);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'action' => ['sumex/edit', ['id' => $sumex->getId()]],
                'form' => $form,
                'optionsDataReasons' => $this->optionsDataReasons(),
                'errors' => [],
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody();
                    if (is_array($body) && isset($body['invoice'])) {
                        /**
                         * @psalm-suppress PossiblyInvalidArgument $body
                         */
                        $this->sumexService->saveSumex($sumex, $body);
                        $this->flash_message('success', $this->translator->translate('i.record_successfully_updated'));
                        $id = (string)$body['invoice'];
                        return $this->webService->getRedirectResponse('inv/view', ['id' => $id]);
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
                $parameters['form'] = $form;                
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('sumex/index');
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param SumexRepository $sumexRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, SumexRepository $sumexRepository 
    ): Response {
        $sumex = $this->sumex($currentRoute, $sumexRepository);
        if ($sumex) {
            $this->sumexService->deleteSumex($sumex); 
            $this->flash_message('success', $this->translator->translate('i.record_successfully_deleted'));
        }
        return $this->webService->getRedirectResponse('sumex/index');        
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param SumexRepository $sumexRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute, SumexRepository $sumexRepository) : \Yiisoft\DataResponse\DataResponse|Response {
        $sumex = $this->sumex($currentRoute, $sumexRepository);
        if ($sumex) {
            $form = new SumexForm($sumex);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'action' => ['sumex/edit', ['id' => $sumex->getId()]],
                'optionsDataReasons' => $this->optionsDataReasons(),
                'errors' => [],
                'form' => $form
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('sumex/index');         
    }
    
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('sumex/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param SumexRepository $sumexRepository
     * @return Sumex|null
     */
    private function sumex(CurrentRoute $currentRoute, SumexRepository $sumexRepository): Sumex|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $sumex = $sumexRepository->repoSumexquery($id);
            return $sumex;            
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function sumexs(SumexRepository $sumexRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        $sumexs = $sumexRepository->findAllPreloaded();        
        return $sumexs;
    }
    
    private function optionsDataReasons() : array
    {
        $reasons = [
            'disease',
            'accident',
            'maternity',
            'prevention',
            'birthdefect',
            'unknown'
        ];
        $optionsDataReasons = [];        
        foreach ($reasons as $key => $value) {
            $optionsDataReasons[$key] = $this->translator->translate('i.reason_' . $value);
        }
        return $optionsDataReasons;
    }    
}