<?php

declare(strict_types=1); 

namespace App\Invoice\Merchant;

use App\Invoice\Entity\Merchant;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Merchant\MerchantService;
use App\Invoice\Merchant\MerchantRepository;
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

final class MerchantController
{
    private Session $session;
    private Flash $flash;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private MerchantService $merchantService;
    private TranslatorInterface $translator;
    
    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        MerchantService $merchantService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/merchant')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->merchantService = $merchantService;
        $this->translator = $translator;
    }
    
    /**
     * @param MerchantRepository $merchantRepository
     * @param SettingRepository $settingRepository
     * @param Request $request
     * @param MerchantService $service
     */
    public function index(MerchantRepository $merchantRepository, SettingRepository $settingRepository, Request $request, MerchantService $service): \Yiisoft\DataResponse\DataResponse
    {
         $canEdit = $this->rbac();
         $parameters = [      
          's'=>$settingRepository,
          'canEdit' => $canEdit,
          'merchants' => $this->merchants($merchantRepository),
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
     * @param InvRepository $invRepository
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request, 
                        FormHydrator $formHydrator,
                        SettingRepository $settingRepository,                        
                        InvRepository $invRepository
    ): Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['merchant/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'head'=>$head,
            
            'invs'=>$invRepository->findAllPreloaded(),
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new MerchantForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                $this->merchantService->saveMerchant(new Merchant(), $form, $settingRepository);
                return $this->webService->getRedirectResponse('merchant/index');
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
     * @param MerchantRepository $merchantRepository
     * @param SettingRepository $settingRepository
     * @param InvRepository $invRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute,
                        FormHydrator $formHydrator,
                        MerchantRepository $merchantRepository, 
                        SettingRepository $settingRepository,                        
                        InvRepository $invRepository
    ): Response {
        $merchant = $this->merchant($currentRoute, $merchantRepository);
        if ($merchant) {
            $parameters = [
                'title' => $settingRepository->trans('edit'),
                'action' => ['merchant/edit', ['id' => $merchant->getId()]],
                'errors' => [],
                'body' => $this->body($merchant),
                'head'=>$head,
                's'=>$settingRepository,
                'invs'=>$invRepository->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new MerchantForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->merchantService->saveMerchant($merchant, $form, $settingRepository);
                    return $this->webService->getRedirectResponse('merchant/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('merchant/index');
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param MerchantRepository $merchantRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, MerchantRepository $merchantRepository 
    ): Response {
        $merchant = $this->merchant($currentRoute, $merchantRepository);
        if ($merchant) {
            $this->merchantService->deleteMerchant($merchant);               
            return $this->webService->getRedirectResponse('merchant/index');        
        }
        return $this->webService->getRedirectResponse('merchant/index');        
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param MerchantRepository $merchantRepository
     * @param SettingRepository $settingRepository
     */
    public function view(CurrentRoute $currentRoute, MerchantRepository $merchantRepository,
        SettingRepository $settingRepository
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $merchant = $this->merchant($currentRoute, $merchantRepository);
        if ($merchant) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['merchant/view', ['id' =>$merchant->getId()]],
                'errors' => [],
                'body' => $this->body($merchant),
                's'=>$settingRepository,             
                'merchant'=>$merchantRepository->repoMerchantquery($merchant->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('merchant/index');        
    }
    
    /**
     * @param MerchantRepository $mR
     * @param SettingRepository $sR
     */
    public function online_log(MerchantRepository $mR, SettingRepository $sR): \Yiisoft\DataResponse\DataResponse {
        $parameters = [
            's'=>$sR,
            'payment_logs'=>$mR->findAllPreloaded(),
        ];
        return $this->viewRenderer->render('_view', $parameters);
    }
    
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('merchant/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param MerchantRepository $merchantRepository
     * @return Merchant|null
     */
    private function merchant(CurrentRoute $currentRoute, MerchantRepository $merchantRepository): Merchant|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $merchant = $merchantRepository->repoMerchantquery($id);
            return $merchant;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function merchants(MerchantRepository $merchantRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader 
    {
        $merchants = $merchantRepository->findAllPreloaded();        
        return $merchants;
    }
    
    /**
     * 
     * @param Merchant $merchant
     * @return array
     */
    private function body(Merchant $merchant): array {
        $body = [
          'inv_id'=>$merchant->getInv_id(),
          'successful'=>$merchant->getSuccessful(),
          'date'=>$merchant->getDate(),
          'driver'=>$merchant->getDriver(),
          'response'=>$merchant->getResponse(),
          'reference'=>$merchant->getReference()
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