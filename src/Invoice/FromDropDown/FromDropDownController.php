<?php

declare(strict_types=1); 

namespace App\Invoice\FromDropDown;

use App\Invoice\Entity\FromDropDown;
use App\Invoice\FromDropDown\FromDropDownService;
use App\Invoice\FromDropDown\FromDropDownRepository;

use App\Invoice\Setting\SettingRepository;
use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use Yiisoft\Yii\View\ViewRenderer;

use \Exception;

final class FromDropDownController
{
    private Flash $flash;
    private Session $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private FromDropDownService $fromService;
        private TranslatorInterface $translator;
    
    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        FromDropDownService $fromService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/from')
                                           // The Controller layout dir is now redundant: replaced with an alias 
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->fromService = $fromService;
        $this->translator = $translator;
    }
    
    
    
    public function add(ViewRenderer $head, Request $request, 
                        FormHydrator $formHydrator,
                        SettingRepository $settingRepository,                        

    ) : Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['from/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'head'=>$head,
            
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new FromDropDownForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                $this->fromService->saveFromDropDown(new FromDropDown(),$form);
                return $this->webService->getRedirectResponse('from/index');
            }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * @return string
     */
    private function alert() : string {
        return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
        [
            'flash'=>$this->flash,
            'errors' => [],
        ]);
    }
    
    /**
     * @param FromDropDown $from     * @return array
     */
    private function body(FromDropDown $from) : array {
        $body = [
                
          'id'=>$from->getId(),
          'email'=>$from->getEmail(),
          'include'=>$from->getInclude(),
          'default_email'=>$from->getDefault_email()
                ];
        return $body;
    }
        
    public function index(CurrentRoute $currentRoute, FromDropDownRepository $fromRepository, SettingRepository $settingRepository): Response
    {      
      $page = (int) $currentRoute->getArgument('page', '1');
      $from = $fromRepository->findAllPreloaded();
      $paginator = (new OffsetPaginator($from))
      ->withPageSize((int) $settingRepository->get_setting('default_list_limit'))
      ->withCurrentPage($page)
      ->withNextPageToken((string) $page);
      $parameters = [
      'froms' => $this->froms($fromRepository),
      'paginator' => $paginator,
      'alert' => $this->alert(),
      'max' => (int) $settingRepository->get_setting('default_list_limit'),
      'grid_summary' => $settingRepository->grid_summary($paginator, $this->translator, (int) $settingRepository->get_setting('default_list_limit'), $this->translator->translate('plural'), ''),
    ];
    return $this->viewRenderer->render('/invoice/from/index', $parameters);
    }
    
    /**
     * 
     * @param SettingRepository $settingRepository
     * @param CurrentRoute $currentRoute
     * @param FromDropDownRepository $fromRepository
     * @return Response
     */
    public function delete(SettingRepository $settingRepository, CurrentRoute $currentRoute,FromDropDownRepository $fromRepository 
    ): Response {
        try {
            $from = $this->from($currentRoute, $fromRepository);
            if ($from) {
                $this->fromService->deleteFromDropDown($from);               
                $this->flash_message('info', $settingRepository->trans('record_successfully_deleted'));
                return $this->webService->getRedirectResponse('from/index'); 
            }
            return $this->webService->getRedirectResponse('from/index'); 
	} catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('from/index'); 
        }
    }
        
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute, 
                       FormHydrator $formHydrator,
                        FromDropDownRepository $fromRepository, 
                        SettingRepository $settingRepository,                        

    ): Response {
        $from = $this->from($currentRoute, $fromRepository);
        if ($from){
            $parameters = [
                'title' => $this->translator->translate('invoice.edit'),
                'action' => ['from/edit', ['id' => $from->getId()]],
                'errors' => [],
                'body' => $this->body($from),
                'head'=>$head,
                's'=>$settingRepository,
                
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new FromDropDownForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->fromService->saveFromDropDown($from,$form);
                    return $this->webService->getRedirectResponse('from/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('from/index');
    }
    
    /**
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash_message(string $level, string $message): Flash{
        $this->flash->add($level, $message, true); 
        return $this->flash;
    }
    
    //For rbac refer to AccessChecker    
    
    /**
     * @param CurrentRoute $currentRoute
     * @param FromDropDownRepository $fromRepository
     * @return FromDropDown|null
     */
    private function from(CurrentRoute $currentRoute, FromDropDownRepository $fromRepository) : FromDropDown|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $from = $fromRepository->repoFromDropDownLoadedquery($id);
            return $from;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function froms(FromDropDownRepository $fromRepository) : \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $froms = $fromRepository->findAllPreloaded();        
        return $froms;
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @param FromDropDownRepository $fromRepository
     * @param SettingRepository $settingRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute,FromDropDownRepository $fromRepository,
        SettingRepository $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $from = $this->from($currentRoute, $fromRepository); 
        if ($from) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['from/view', ['id' => $from->getId()]],
                'errors' => [],
                'body' => $this->body($from),
                'from'=>$from,
            ];        
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('from/index');
    }
}

