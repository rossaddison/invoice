<?php

declare(strict_types=1);

namespace App\Invoice\Family;

use App\Invoice\Entity\Family;
use App\Invoice\Family\FamilyForm;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\ViewRenderer;

final class FamilyController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private FamilyService $familyService;    
    private UserService $userService;    
    private Session $session;
    private Flash $flash;
    private TranslatorInterface $translator;

    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        FamilyService $familyService,
        UserService $userService,        
        Session $session,
        TranslatorInterface $translator
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/family')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->familyService = $familyService;
        $this->userService = $userService;
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->translator = $translator;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param FamilyRepository $familyRepository
     * @param SettingRepository $settingRepository
     */
    public function index(CurrentRoute $currentRoute, 
                          FamilyRepository $familyRepository, 
                          SettingRepository $settingRepository): \Yiisoft\DataResponse\DataResponse
    {
        $familys = $this->familys($familyRepository);
        $pageNum = (int)$currentRoute->getArgument('page', '1');
        $paginator = (new OffsetPaginator($familys))
            ->withPageSize((int)$settingRepository->get_setting('default_list_limit'))
            ->withCurrentPage($pageNum);
        $parameters = [
            'alert'=>$this->alert(),      
            'paginator'=> $paginator,
            'familys' => $familys, 
        ]; 
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(Request $request, FormHydrator $formHydrator): Response
    {
        $family = new Family();
        $form = new FamilyForm($family);
        $parameters = [
            'title' => $this->translator->translate('i.add_family'),
            'action' => ['family/add'],
            'errors' => [],
            'form' => $form
        ];        
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
           if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body 
                 */
                $this->familyService->saveFamily($family, $body);
                return $this->webService->getRedirectResponse('family/index');  
            } 
            $parameters['errors'] = $form->getValidationResult()?->getErrorMessagesIndexedByAttribute() ?? [];
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FamilyRepository $familyRepository
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function edit(CurrentRoute $currentRoute, Request $request, FamilyRepository $familyRepository, FormHydrator $formHydrator): Response 
    {
        $family = $this->family($currentRoute, $familyRepository);
        if ($family) {
            $form = new FamilyForm($family);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'action' => ['family/edit', ['id' => $family->getFamily_id()]],
                'errors' => [],
                'form' => $form
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body 
                 */
                if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body 
                     */
                    $this->familyService->saveFamily($family, $body);
                    return $this->webService->getRedirectResponse('family/index');
                }
                $parameters['errors'] = $form->getValidationResult()?->getErrorMessagesIndexedByAttribute() ?? [];
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        } else {
            return $this->webService->getRedirectResponse('family/index');
        }
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param FamilyRepository $familyRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, FamilyRepository $familyRepository): Response 
    {
        try {
            $family = $this->family($currentRoute, $familyRepository);
            if ($family) {
                $this->familyService->deleteFamily($family);               
                return $this->webService->getRedirectResponse('family/index');  
            }
            return $this->webService->getRedirectResponse('family/index');  
	} catch (\Exception $e) {
            unset($e);
            $this->flash_message('danger', $this->translator->translate('invoice.family.history'));
            return $this->webService->getRedirectResponse('family/index');  
        }
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param FamilyRepository $familyRepository
     */
    public function view(CurrentRoute $currentRoute, FamilyRepository $familyRepository): Response {
        $family = $this->family($currentRoute, $familyRepository);
        if ($family) {
            $form = new FamilyForm($family);
            $parameters = [
                'title' => $this->translator->translate('i.view'), 
                'action' => ['family/view', ['id' => $family->getFamily_id()]],
                'errors' => [],
                'family' => $family,
                'form' => $form,
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('family/index');  
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param FamilyRepository $familyRepository
     * @return Family|null
     */
    private function family(CurrentRoute $currentRoute, FamilyRepository $familyRepository): Family|null
    {
        $family_id = $currentRoute->getArgument('id');
        if (null!==$family_id) {
            $family = $familyRepository->repoFamilyquery($family_id);
            return $family; 
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function familys(FamilyRepository $familyRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader{
        $familys = $familyRepository->findAllPreloaded();
        return $familys;
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
     * @return Flash
     */
    private function flash_message(string $level, string $message): Flash {
      $this->flash->add($level, $message, true);
      return $this->flash;
    }
}
