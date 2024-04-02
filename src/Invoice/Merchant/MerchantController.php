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
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Data\Paginator\OffsetPaginator;
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
     * @param SettingRepository $sR
     * @param MerchantService $service
     */
    public function index(MerchantRepository $merchantRepository, SettingRepository $sR, MerchantService $service): \Yiisoft\DataResponse\DataResponse
    {
         $canEdit = $this->rbac();
         $merchants = $this->merchants($merchantRepository);
         $paginator = (new OffsetPaginator($merchants));
         $parameters = [ 
          'canEdit' => $canEdit,
          'paginator' => $paginator,   
          'merchants' => $this->merchants($merchantRepository),
          'grid_summary'=> $sR->grid_summary(
               $paginator, 
               $this->translator, 
               (int)$sR->get_setting('default_list_limit'), 
               $this->translator->translate('invoice.merchant'), ''),      
          'alert'=> $this->alert()
         ];      
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SettingRepository $settingRepository
     * @param InvRepository $invRepository
     * @return Response
     */
    public function add(Request $request, 
                        FormHydrator $formHydrator,
                        SettingRepository $settingRepository,                        
                        InvRepository $invRepository
    ): Response
    {
        $merchant = new Merchant();
        $form = new MerchantForm($merchant);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['merchant/add'],
            'errors' => [],
            'form' => $form,
            'invs'=>$invRepository->findAllPreloaded(),
        ];
        
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                $this->merchantService->saveMerchant($merchant, $body);
                return $this->webService->getRedirectResponse('merchant/index');
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
     * @param MerchantRepository $merchantRepository
     * @param SettingRepository $sR
     * @param InvRepository $invRepository
     * @return Response
     */
    public function edit(Request $request, CurrentRoute $currentRoute,
                        FormHydrator $formHydrator,
                        MerchantRepository $merchantRepository,
                        SettingRepository $sR,
                        InvRepository $invRepository
    ): Response {
        $merchant = $this->merchant($currentRoute, $merchantRepository);
        if ($merchant) {
            $form = new MerchantForm($merchant);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'action' => ['merchant/edit', ['id' => $merchant->getId()]],
                'errors' => [],
                'form' => $form,
                'invs' => $invRepository->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body
                     */
                    $this->merchantService->saveMerchant($merchant, $body);
                    return $this->webService->getRedirectResponse('merchant/index');
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('merchant/index');
    }
    
    /**
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
     * @param InvRepository $invRepository
     * @param MerchantRepository $merchantRepository
     */
    public function view(CurrentRoute $currentRoute, 
                         InvRepository $invRepository,   
                         MerchantRepository $merchantRepository
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $merchant = $this->merchant($currentRoute, $merchantRepository);
        if ($merchant) {
            $form = new MerchantForm($merchant);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'action' => ['merchant/view', ['id' =>$merchant->getId()]],
                'form' => $form,
                'invs' => $invRepository->findAllPreloaded(),
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
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function merchants(MerchantRepository $merchantRepository): \Yiisoft\Data\Cycle\Reader\EntityReader 
    {
        $merchants = $merchantRepository->findAllPreloaded();        
        return $merchants;
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