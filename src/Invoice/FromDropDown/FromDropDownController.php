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

use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
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
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(Request $request, FormHydrator $formHydrator) : Response
    {
        $entity = new FromDropDown();
        $form = new FromDropDownForm($entity);
        $parameters = [
            'title' => $this->translator->translate('i.add'),
            'action' => ['from/add'],
            'errors' => [],
            'form' => $form
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                $this->fromService->saveFromDropDown($entity, $body);
                return $this->webService->getRedirectResponse('from/index');
            }
            $parameters['errors'] = $form->getValidationResult()?->getErrorMessagesIndexedByAttribute() ?? [];
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * @return string
     */
    private function alert() : string {
        return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
        [
            'flash' => $this->flash
        ]);
    }
        
    public function index(CurrentRoute $currentRoute, FromDropDownRepository $fromRepository, SettingRepository $settingRepository): Response
    {      
      $page = $currentRoute->getArgument('page', '1');
      $from = $fromRepository->findAllPreloaded();
      $paginator = (new OffsetPaginator($from))
      ->withPageSize((int) $settingRepository->get_setting('default_list_limit'))
      ->withCurrentPage((int)$page)
      ->withToken(PageToken::next((string)$page));
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
     * @param CurrentRoute $currentRoute
     * @param FromDropDownRepository $fromRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute,FromDropDownRepository $fromRepository 
    ): Response {
        try {
            $from = $this->from($currentRoute, $fromRepository);
            if ($from) {
                $this->fromService->deleteFromDropDown($from);               
                $this->flash_message('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->webService->getRedirectResponse('from/index'); 
            }
            return $this->webService->getRedirectResponse('from/index'); 
	} catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('from/index'); 
        }
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param FromDropDownRepository $fromRepository
     * @return Response
     */
    public function edit(Request $request, CurrentRoute $currentRoute, 
                         FormHydrator $formHydrator,
                         FromDropDownRepository $fromRepository) : Response 
    {
        $from = $this->from($currentRoute, $fromRepository);
        if ($from){
            $form = new FromDropDownForm($from);
            $parameters = [
                'title' => $this->translator->translate('invoice.edit'),
                'action' => ['from/edit', ['id' => $from->getId()]],
                'errors' => [],
                'form' => $form
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body
                     */
                    $this->fromService->saveFromDropDown($from, $body);
                    return $this->webService->getRedirectResponse('from/index');
                }
                $parameters['errors'] = $form->getValidationResult()?->getErrorMessagesIndexedByAttribute() ?? [];
                $parameters['form'] = $form;
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
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute,FromDropDownRepository $fromRepository) : \Yiisoft\DataResponse\DataResponse|Response {
        $from = $this->from($currentRoute, $fromRepository); 
        if ($from) {
            $form = new FromDropDownForm($from);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'action' => ['from/view', ['id' => $from->getId()]],
                'errors' => [],
                'form' => $form,
                'from'=>$from
            ];        
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('from/index');
    }
}

