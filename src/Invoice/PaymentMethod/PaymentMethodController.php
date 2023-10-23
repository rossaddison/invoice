<?php

declare(strict_types=1); 

namespace App\Invoice\PaymentMethod;

use App\Invoice\Entity\PaymentMethod;
use App\Invoice\PaymentMethod\PaymentMethodService;
use App\Invoice\PaymentMethod\PaymentMethodRepository;
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

final class PaymentMethodController
{
    private Flash $flash;
    private Session $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private PaymentMethodService $paymentmethodService;
    private TranslatorInterface $translator;
    
    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        PaymentMethodService $paymentmethodService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/paymentmethod')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->paymentmethodService = $paymentmethodService;
        $this->translator = $translator;
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
    
    /**
     * @param PaymentMethodRepository $paymentmethodRepository
     * @param SettingRepository $settingRepository
     * @param Request $request
     * @param PaymentMethodService $service
     */
    public function index(PaymentMethodRepository $paymentmethodRepository, SettingRepository $settingRepository, Request $request, PaymentMethodService $service): \Yiisoft\DataResponse\DataResponse
    {
         $canEdit = $this->rbac();
         $parameters = [
      
          's'=>$settingRepository,
          'canEdit' => $canEdit,
          'payment_methods' => $this->paymentmethods($paymentmethodRepository),
          'alert' => $this->alert()
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
    public function add(ViewRenderer $head,Request $request, 
                       FormHydrator $formHydrator,
                        SettingRepository $settingRepository                        

    ): Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['paymentmethod/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'head'=>$head,
        ];
        
        if ($request->getMethod() === Method::POST) {
            $form = new PaymentMethodForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                $this->paymentmethodService->savePaymentMethod(new PaymentMethod(),$form);
                return $this->webService->getRedirectResponse('paymentmethod/index');
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
     * @param PaymentMethodRepository $paymentmethodRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute,
                        FormHydrator $formHydrator,
                        PaymentMethodRepository $paymentmethodRepository, 
                        SettingRepository $settingRepository                        

    ): Response {
        $payment_method = $this->paymentmethod($currentRoute, $paymentmethodRepository);
        if ($payment_method) {
            $parameters = [
                'title' => 'Edit',
                'action' => ['paymentmethod/edit', ['id' => $payment_method->getId()]],
                'errors' => [],
                'body' => $this->body($payment_method),
                'head'=>$head,
                's'=>$settingRepository,            
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new PaymentMethodForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->paymentmethodService->savePaymentMethod($payment_method, $form);
                    return $this->webService->getRedirectResponse('paymentmethod/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
            }
            return $this->viewRenderer->render('_form', $parameters);
        } // if payment_method
        return $this->webService->getRedirectResponse('paymentmethod/index');
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentMethodRepository $paymentmethodRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, PaymentMethodRepository $paymentmethodRepository 
    ): Response {
        try {
            $payment_method = $this->paymentmethod($currentRoute, $paymentmethodRepository);
            if ($payment_method) {
                $this->paymentmethodService->deletePaymentMethod($payment_method);               
                return $this->webService->getRedirectResponse('paymentmethod/index'); 
            }
            return $this->webService->getRedirectResponse('paymentmethod/index'); 
	} catch (\Exception $e) {
            unset($e);
            $this->flash_message('danger', $this->translator->translate('invoice.payment.method.history'));
            return $this->webService->getRedirectResponse('paymentmethod/index'); 
        }
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param PaymentMethodRepository $paymentmethodRepository
     * @param SettingRepository $settingRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute, PaymentMethodRepository $paymentmethodRepository,
        SettingRepository $settingRepository
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $payment_method = $this->paymentmethod($currentRoute, $paymentmethodRepository);
        $parameters = [];
        if ($payment_method) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['paymentmethod/edit', ['id' => $payment_method->getId()]],
                'errors' => [],
                'body' => $this->body($payment_method),
                's'=>$settingRepository,             
                'paymentmethod'=>$paymentmethodRepository->repoPaymentMethodquery($payment_method->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('paymentmethod/index'); 
    }
    
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('paymentmethod/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentMethodRepository $paymentmethodRepository
     * @return PaymentMethod|null
     */
    private function paymentmethod(CurrentRoute $currentRoute, 
                                   PaymentMethodRepository $paymentmethodRepository) : PaymentMethod|null 
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $paymentmethod = $paymentmethodRepository->repoPaymentMethodquery($id);
            return $paymentmethod;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function paymentmethods(PaymentMethodRepository $paymentmethodRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $paymentmethods = $paymentmethodRepository->findAllPreloaded();        
        return $paymentmethods;
    }
    
    /**
     * 
     * @param PaymentMethod $paymentmethod
     * @return array
     */
    private function body(PaymentMethod $paymentmethod): array {
        $body = [                
          'id'=>$paymentmethod->getId(),
          'name'=>$paymentmethod->getName()
        ];
        return $body;
    }
}