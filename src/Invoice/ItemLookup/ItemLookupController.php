<?php
declare(strict_types=1); 

namespace App\Invoice\ItemLookup;

use App\Invoice\Entity\ItemLookup;
use App\Invoice\ItemLookup\ItemLookupService;
use App\Invoice\ItemLookup\ItemLookupRepository;
use App\Invoice\Setting\SettingRepository;
use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use Yiisoft\Yii\View\ViewRenderer;

final class ItemLookupController
{
    private Session $session;
    private Flash $flash;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private ItemLookupService $itemlookupService;
    private TranslatorInterface $translator;
    
    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        ItemLookupService $itemlookupService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/itemlookup')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->itemlookupService = $itemlookupService;
        $this->translator = $translator;
    }
    
    /**
     * @param ItemLookupRepository $itemlookupRepository
     * @param SettingRepository $settingRepository
     * @param Request $request
     * @param ItemLookupService $service
     */
    public function index(ItemLookupRepository $itemlookupRepository, SettingRepository $settingRepository, Request $request, ItemLookupService $service): \Yiisoft\DataResponse\DataResponse
    {
      $canEdit = $this->rbac();
      $parameters = [
       's'=>$settingRepository,
       'canEdit' => $canEdit,
       'itemlookups' => $this->itemlookups($itemlookupRepository),
       'alert'=> $this->alert()
      ];
      return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request, 
                        FormHydrator $formHydrator,
                        SettingRepository $settingRepository,                        

    ): Response
    {
        $parameters = [
          'title' => $this->translator->translate('invoice.add'),
          'action' => ['itemlookup/add'],
          'errors' => [],
          'body' => $request->getParsedBody(),
          's'=>$settingRepository,
          'head'=>$head,
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new ItemLookupForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                $this->itemlookupService->saveItemLookup(new ItemLookup(),$form);
                return $this->webService->getRedirectResponse('itemlookup/index');
            }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param ItemLookupRepository $itemlookupRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute,
      FormHydrator $formHydrator,
      ItemLookupRepository $itemlookupRepository, 
      SettingRepository $settingRepository, 
    ): Response {
        $lookup = $this->itemlookup($currentRoute, $itemlookupRepository);
        if ($lookup) {
            $parameters = [
              'title' => $settingRepository->trans('edit'),
              'action' => ['itemlookup/edit', ['id' => $lookup->getId()]],
              'errors' => [],
              'body' => $this->body($lookup),
              'head'=>$head,
              's'=>$settingRepository,            
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new ItemLookupForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->itemlookupService->saveItemLookup($lookup, $form);
                    return $this->webService->getRedirectResponse('itemlookup/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getNotFoundResponse();
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param ItemLookupRepository $itemlookupRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, ItemLookupRepository $itemlookupRepository 
    ): Response {    
        $lookup = $this->itemlookup($currentRoute, $itemlookupRepository);
        if ($lookup) {
            $this->itemlookupService->deleteItemLookup($lookup);               
            return $this->webService->getRedirectResponse('itemlookup/index');                
        }
        return $this->webService->getNotFoundResponse();
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param ItemLookupRepository $itemlookupRepository
     * @param SettingRepository $settingRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute, ItemLookupRepository $itemlookupRepository,
        SettingRepository $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $lookup = $this->itemlookup($currentRoute, $itemlookupRepository);
        if ($lookup) {
            $parameters = [
              'title' => $settingRepository->trans('view'),
              'action' => ['itemlookup/edit', ['id' => $lookup->getId()]],
              'errors' => [],
              'body' => $this->body($lookup),
              's'=>$settingRepository,             
              'itemlookup'=>$itemlookupRepository->repoItemLookupquery($lookup->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getNotFoundResponse();
        }
    
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response 
    {
      $canEdit = $this->userService->hasPermission('editInv');
      if (!$canEdit){
          $this->flash_message('warning', $this->translator->translate('invoice.permission'));
          return $this->webService->getRedirectResponse('itemlookup/index');
      }
      return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param ItemLookupRepository $itemlookupRepository
     * @return ItemLookup|null
     */
    private function itemlookup(CurrentRoute $currentRoute, ItemLookupRepository $itemlookupRepository): ItemLookup|null 
    {
      $itemlookup = new ItemLookup();
      $id = $currentRoute->getArgument('id');       
      if (null!==$id) {
          $itemlookup = $itemlookupRepository->repoItemLookupquery($id);
          return $itemlookup;
      }
      return $itemlookup;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function itemlookups(ItemLookupRepository $itemlookupRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader 
    {
        $itemlookups = $itemlookupRepository->findAllPreloaded();        
        return $itemlookups;
    }
    
    /**
     * 
     * @param ItemLookup $itemlookup
     * @return array
     */
    private function body(ItemLookup $itemlookup): array {
      $body = [
        'id'=>$itemlookup->getId(),
        'name'=>$itemlookup->getName(),
        'description'=>$itemlookup->getDescription(),
        'price'=>$itemlookup->getPrice()
      ];
      return $body;
    }
    
  /**
   * @return string
   */
   private function alert(): string {
     return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
     [ 
       'flash' => $this->flash,
       'errors' => [],
     ]);
   }

  /**
   * @param string $level
   * @param string $message
   * @return Flash
   */
  private function flash_message(string $level, string $message): Flash {
    $this->flash->add($level, $message, true);
    return $this->flash;
  }
}