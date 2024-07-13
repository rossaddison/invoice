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
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

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
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(Request $request, 
                        FormHydrator $formHydrator) : Response
    {
        $paymentPeppol = new PaymentPeppol();
        $form = new PaymentPeppolForm($paymentPeppol);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'actionName' => 'paymentpeppol/add',
            'errors' => [],
            'form' => $form,
            'response' => $this->viewRenderer->renderPartial('//invoice/layout/header_buttons',
            [
                'hide_submit_button' => false ,
                'hide_cancel_button' => false
            ])        
        ];
        
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                $this->paymentpeppolService->savePaymentPeppol($paymentPeppol, $body);
                return $this->webService->getRedirectResponse('paymentpeppol/index');
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
  /**
   * @return string
   */
   private function alert(): string {
     return $this->viewRenderer->renderPartialAsString('//invoice/layout/alert',
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
     * @param CurrentRoute $routeCurrent
     * @param PaymentPeppolRepository $paymentpeppolRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function index(CurrentRoute $routeCurrent, PaymentPeppolRepository $paymentpeppolRepository, SettingRepository $settingRepository): Response
    {      
      $page = $routeCurrent->getArgument('page', '1');
      $paymentpeppols = $paymentpeppolRepository->findAllPreloaded();
      $paginator = (new OffsetPaginator($paymentpeppols))
      ->withPageSize((int) $settingRepository->get_setting('default_list_limit'))
      ->withCurrentPage((int)$page)
      ->withToken(PageToken::next((string)$page));
      $parameters = [
      'paymentpeppols' => $this->paymentpeppols($paymentpeppolRepository),
      'paginator' => $paginator,
      'alert' => $this->alert(),
      'routeCurrent' => $routeCurrent
    ];
    return $this->viewRenderer->render('paymentpeppol/index', $parameters);
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentPeppolRepository $paymentpeppolRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, PaymentPeppolRepository $paymentpeppolRepository) : Response {
        try {
            $paymentpeppol = $this->paymentpeppol($currentRoute, $paymentpeppolRepository);
            if (null!==$paymentpeppol) {
                $this->paymentpeppolService->deletePaymentPeppol($paymentpeppol);               
                $this->flash_message('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->webService->getRedirectResponse('paymentpeppol/index'); 
            }
            return $this->webService->getRedirectResponse('paymentpeppol/index'); 
	} catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('paymentpeppol/index'); 
        }
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param PaymentPeppolRepository $paymentpeppolRepository
     * @return Response
     */    
    public function edit(Request $request, CurrentRoute $currentRoute, 
                        FormHydrator $formHydrator,
                        PaymentPeppolRepository $paymentpeppolRepository): Response
    {
        $paymentPeppol = $this->paymentpeppol($currentRoute, $paymentpeppolRepository);
        if ($paymentPeppol){
            $form = new PaymentPeppolForm($paymentPeppol);
            $parameters = [
                'title' => $this->translator->translate('invoice.edit'),
                'actionName' => 'paymentpeppol/edit', 
                'actionArguments' => ['id' => $paymentPeppol->getId()],
                'errors' => [],
                'form' => $form,
                'response' => $this->viewRenderer->renderPartial('//invoice/layout/header_buttons',
                [
                    'hide_submit_button' => false ,
                    'hide_cancel_button' => false
                ])      
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body
                     */
                    $this->paymentpeppolService->savePaymentPeppol($paymentPeppol, $body);
                    return $this->webService->getRedirectResponse('paymentpeppol/index');
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
                $parameters['form'] = $form;
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
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function paymentpeppols(PaymentPeppolRepository $paymentpeppolRepository) : \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        $paymentpeppols = $paymentpeppolRepository->findAllPreloaded();        
        return $paymentpeppols;
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentPeppolRepository $paymentpeppolRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute,PaymentPeppolRepository $paymentpeppolRepository) 
        : \Yiisoft\DataResponse\DataResponse|Response 
    {
        $paymentPeppol = $this->paymentpeppol($currentRoute, $paymentpeppolRepository); 
        if ($paymentPeppol) {
            $form = new PaymentPeppolForm($paymentPeppol);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'paymentpeppol/view', 
                'actionArguments' => ['id' => $paymentPeppol->getId()],
                'form' => $form,
                'paymentpeppol' => $paymentPeppol,
                'response' => $this->viewRenderer->renderPartial('//invoice/layout/header_buttons',
                [
                    'hide_submit_button' => false ,
                    'hide_cancel_button' => false
                ])      
            ];        
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('paymentpeppol/index');
    }
}

