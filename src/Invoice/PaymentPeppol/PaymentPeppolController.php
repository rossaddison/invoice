<?php
declare(strict_types=1); 

namespace App\Invoice\PaymentPeppol;

use App\Invoice\Entity\PaymentPeppol;
use App\Invoice\PaymentPeppol\PaymentPeppolService;
use App\Invoice\PaymentPeppol\PaymentPeppolRepository;

use App\Invoice\Setting\SettingRepository;

use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use Yiisoft\Yii\View\ViewRenderer;

use \Exception;

final class PaymentPeppolController
{
    private Flash $flash;
    private SessionInterface $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private PaymentPeppolService $paymentpeppolService;
    private TranslatorInterface $translator;
    
    public function __construct(
        SessionInterface $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        PaymentPeppolService $paymentpeppolService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/paymentpeppol')
                                           // The Controller layout dir is now redundant: replaced with an alias 
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->paymentpeppolService = $paymentpeppolService;
        $this->translator = $translator;
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

    ) : Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['paymentpeppol/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'head'=>$head,
            
        ];
        
        if ($request->getMethod() === Method::POST) {
            $form = new PaymentPeppolForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                $this->paymentpeppolService->savePaymentPeppol(new PaymentPeppol(),$form);
                return $this->webService->getRedirectResponse('paymentpeppol/index');
            }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('_form', $parameters);
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
     * @param PaymentPeppol $paymentpeppol     * @return array
     */
    private function body(PaymentPeppol $paymentpeppol) : array {
        $body = [
          'inv_id'=>$paymentpeppol->getInv_id(),
          'id'=>$paymentpeppol->getId(),
          'auto_reference'=>$paymentpeppol->getAuto_reference(),
          'provider'=>$paymentpeppol->getProvider()
        ];
        return $body;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentPeppolRepository $paymentpeppolRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function index(CurrentRoute $currentRoute, PaymentPeppolRepository $paymentpeppolRepository, SettingRepository $settingRepository): Response
    {      
      $page = (int) $currentRoute->getArgument('page', '1');
      $paymentpeppols = $paymentpeppolRepository->findAllPreloaded();
      $paginator = (new OffsetPaginator($paymentpeppols))
      ->withPageSize((int) $settingRepository->get_setting('default_list_limit'))
      ->withCurrentPage($page)
      ->withNextPageToken((string) $page);
      $parameters = [
      'paymentpeppols' => $this->paymentpeppols($paymentpeppolRepository),
      'paginator' => $paginator,
      'alert' => $this->alert(),
      'max' => (int) $settingRepository->get_setting('default_list_limit'),
      'grid_summary' => $settingRepository->grid_summary($paginator, $this->translator, (int) $settingRepository->get_setting('default_list_limit'), $this->translator->translate('invoice.paymentpeppol.reference.plural'), ''),
    ];
    return $this->viewRenderer->render('/invoice/paymentpeppol/index', $parameters);
    }
        
    /**
     * @param SettingRepository $settingRepository
     * @param CurrentRoute $currentRoute
     * @param PaymentPeppolRepository $paymentpeppolRepository
     * @return Response
     */
    public function delete(SettingRepository $settingRepository, CurrentRoute $currentRoute,PaymentPeppolRepository $paymentpeppolRepository 
    ): Response {
        try {
            $paymentpeppol = $this->paymentpeppol($currentRoute, $paymentpeppolRepository);
            if ($paymentpeppol) {
                $this->paymentpeppolService->deletePaymentPeppol($paymentpeppol);               
                $this->flash_message('info', $settingRepository->trans('record_successfully_deleted'));
                return $this->webService->getRedirectResponse('paymentpeppol/index'); 
            }
            return $this->webService->getRedirectResponse('paymentpeppol/index'); 
	} catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('paymentpeppol/index'); 
        }
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param PaymentPeppolRepository $paymentpeppolRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */    
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute, 
                        FormHydrator $formHydrator,
                        PaymentPeppolRepository $paymentpeppolRepository, 
                        SettingRepository $settingRepository,                        

    ): Response {
        $paymentpeppol = $this->paymentpeppol($currentRoute, $paymentpeppolRepository);
        if ($paymentpeppol){
            $parameters = [
                'title' => $this->translator->translate('invoice.edit'),
                'action' => ['paymentpeppol/edit', ['id' => $paymentpeppol->getId()]],
                'errors' => [],
                'body' => $this->body($paymentpeppol),
                'head'=>$head,
                's'=>$settingRepository,
                
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new PaymentPeppolForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->paymentpeppolService->savePaymentPeppol($paymentpeppol,$form);
                    return $this->webService->getRedirectResponse('paymentpeppol/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('paymentpeppol/index');
    }
    
    //For rbac refer to AccessChecker    
    
    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentPeppolRepository $paymentpeppolRepository
     * @return PaymentPeppol|null
     */
    private function paymentpeppol(CurrentRoute $currentRoute,PaymentPeppolRepository $paymentpeppolRepository) : PaymentPeppol|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $paymentpeppol = $paymentpeppolRepository->repoPaymentPeppolLoadedquery($id);
            return $paymentpeppol;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function paymentpeppols(PaymentPeppolRepository $paymentpeppolRepository) : \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $paymentpeppols = $paymentpeppolRepository->findAllPreloaded();        
        return $paymentpeppols;
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentPeppolRepository $paymentpeppolRepository
     * @param SettingRepository $settingRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute,PaymentPeppolRepository $paymentpeppolRepository,
        SettingRepository $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $paymentpeppol = $this->paymentpeppol($currentRoute, $paymentpeppolRepository); 
        if ($paymentpeppol) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['paymentpeppol/view', ['id' => $paymentpeppol->getId()]],
                'errors' => [],
                'body' => $this->body($paymentpeppol),
                'paymentpeppol'=>$paymentpeppol,
            ];        
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('paymentpeppol/index');
    }
}

