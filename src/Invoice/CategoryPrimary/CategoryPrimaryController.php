<?php

declare(strict_types=1); 

namespace App\Invoice\CategoryPrimary;

use App\Invoice\Entity\CategoryPrimary;
use App\Invoice\CategoryPrimary\CategoryPrimaryForm;
use App\Invoice\CategoryPrimary\CategoryPrimaryService;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Traits\FlashMessage;
use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

use \Exception;

final class CategoryPrimaryController
{
    use FlashMessage;
    
    private Flash $flash;
    
    public function __construct(
        private ViewRenderer $viewRenderer,
        private WebControllerService $webService,
        private CategoryPrimaryService $categoryPrimaryService,
        private UserService $userService,        
        private DataResponseFactoryInterface $factory,
        private SessionInterface $session,
        private TranslatorInterface $translator,
    )    
    {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/categoryprimary')
                                           // The Controller layout dir is now redundant: replaced with an alias 
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->flash = new Flash($this->session);
        $this->userService = $userService;
        $this->factory = $factory;
        $this->categoryPrimaryService = $categoryPrimaryService;
        $this->translator = $translator;
    }
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(Request $request, 
                        FormHydrator $formHydrator
    ) : Response
    {
        $categoryPrimary = new CategoryPrimary();
        $form = new CategoryPrimaryForm($categoryPrimary);
        $parameters = [
            'title' => $this->translator->translate('i.add'),
            'actionName' => 'categoryprimary/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form
        ];
        
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $this->categoryPrimaryService->saveCategoryPrimary($categoryPrimary, $body);
                    return $this->webService->getRedirectResponse('categoryprimary/index');
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            } // is_array($body)   
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * @return string
     */
    private function alert() : string {
        return $this->viewRenderer->renderPartialAsString('//invoice/layout/alert',
        [
            'flash' => $this->flash,
            'errors' => [],
        ]);
    }
        
    public function index(CategoryPrimaryRepository $categoryPrimaryRepository, 
                          SettingRepository $settingRepository, #[RouteArgument('page')] int $page = 1): Response
    {      
      $categoryPrimary = $categoryPrimaryRepository->findAllPreloaded();
      /** @psalm-var positive-int $currentPageNeverZero */
      $currentPageNeverZero = $page > 0 ? $page : 1;
      $paginator = (new OffsetPaginator($categoryPrimary))
      ->withPageSize($settingRepository->positiveListLimit())
      ->withCurrentPage($currentPageNeverZero)
      ->withToken(PageToken::next((string)$page));
      $parameters = [
        'categoryprimarys' => $this->categoryprimaries($categoryPrimaryRepository),
        'paginator' => $paginator,
        'alert' => $this->alert(),
        'defaultPageSizeOffsetPaginator' => $settingRepository->getSetting('default_list_limit')
                                                      ? (int)$settingRepository->getSetting('default_list_limit') : 1
      ];
    return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param CategoryPrimaryRepository $categoryPrimaryRepository
     * @param int $id
     * @return Response
     */
    public function delete(CategoryPrimaryRepository $categoryPrimaryRepository, #[RouteArgument('id')] int $id 
    ): Response {
        try {
                if ($id) {
                    $categoryPrimary = $this->categoryprimary($categoryPrimaryRepository, $id);
                    if ($categoryPrimary) {
                        $this->categoryPrimaryService->deleteCategoryPrimary($categoryPrimary);               
                        $this->flashMessage('info', $this->translator->translate('i.record_successfully_deleted'));
                        return $this->webService->getRedirectResponse('categoryprimary/index'); 
                    }
                }    
            return $this->webService->getRedirectResponse('categoryprimary/index'); 
	} catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('categoryprimary/index'); 
        }
    }
        
    public function edit(Request $request, 
                         FormHydrator $formHydrator,
                         CategoryPrimaryRepository $categoryPrimaryRepository, #[RouteArgument('id')] int $id): Response {
        if ($id) {
            $categoryprimary = $this->categoryprimary($categoryPrimaryRepository, $id);
            if ($categoryprimary){
                $form = new CategoryPrimaryForm($categoryprimary);
                $parameters = [
                    'title' => $this->translator->translate('i.edit'),
                    'actionName' => 'categoryprimary/edit', 
                    'actionArguments' => ['id' => $id],
                    'errors' => [],
                    'form' => $form,
                    'category_primaries' => $categoryPrimaryRepository->findAllPreloaded()
                ];
                if ($request->getMethod() === Method::POST) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                            $this->categoryPrimaryService->saveCategoryPrimary($categoryprimary, $body);
                            return $this->webService->getRedirectResponse('categoryprimary/index');
                        }
                        $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                        $parameters['form'] = $form;
                    }    
                }
                return $this->viewRenderer->render('_form', $parameters);
            }
        }    
        return $this->webService->getRedirectResponse('categoryprimary/index');
    }
    
    /**     
     * @param CategoryPrimaryRepository $categoryPrimaryRepository
     * @param int $id
     * @return CategoryPrimary|null
     */
    private function categoryprimary(CategoryPrimaryRepository $categoryPrimaryRepository, int $id) : CategoryPrimary|null
    {
        if ($id) {
            $categoryPrimary = $categoryPrimaryRepository->repoCategoryPrimaryQuery((string)$id);
            if (null!==$categoryPrimary) {
                return $categoryPrimary;
            }
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function categoryprimaries(CategoryPrimaryRepository $categoryPrimaryRepository) : \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        $categoryprimaries = $categoryPrimaryRepository->findAllPreloaded();        
        return $categoryprimaries;
    }
        
    /**
     * @param CategoryPrimaryRepository $categoryPrimaryRepository
     * @param SettingRepository $settingRepository
     * @param int id
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CategoryPrimaryRepository $categoryPrimaryRepository, #[RouteArgument('id')] int $id) 
                         : \Yiisoft\DataResponse\DataResponse|Response 
    {
        if ($id) {    
            $category_primary = $this->categoryprimary($categoryPrimaryRepository, $id); 
            if ($category_primary) {
                $form = new CategoryPrimaryForm($category_primary);
                $parameters = [
                    'title' => $this->translator->translate('i.view'),
                    'actionName' => 'categoryprimary/view', 
                    'actionArguments' => ['id' => $id],
                    'form' => $form,
                    'category_primary' => $category_primary,
                ];        
            return $this->viewRenderer->render('_view', $parameters);
            }
        }    
        return $this->webService->getRedirectResponse('categoryprimary/index');
    }
}

