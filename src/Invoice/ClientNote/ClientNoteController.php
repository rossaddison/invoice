<?php

declare(strict_types=1); 

namespace App\Invoice\ClientNote;

use App\Invoice\Entity\ClientNote;
use App\Invoice\ClientNote\ClientNoteService;
use App\Invoice\ClientNote\ClientNoteRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Helpers\DateHelper;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class ClientNoteController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private ClientNoteService $clientnoteService;
    private SessionInterface $session;
    private Flash $flash;
    private TranslatorInterface $translator;
    
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        ClientNoteService $clientnoteService,
        SessionInterface $session,      
        TranslatorInterface $translator
    )    
    {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/clientnote')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->clientnoteService = $clientnoteService;
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->translator = $translator;
    }
    
    /**
     * @param ClientNoteRepository $clientnoteRepository
     * @param SettingRepository $settingRepository
     * @param Request $request
     * @param ClientNoteService $service
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function index(ClientNoteRepository $clientnoteRepository, SettingRepository $settingRepository, Request $request, ClientNoteService $service): \Yiisoft\DataResponse\DataResponse
    {
        $canEdit = $this->rbac();
        $clientnotes = $clientnoteRepository->findAllPreloaded(); 
        $paginator = (new OffsetPaginator($clientnotes));
        $parameters = [
            'canEdit' => $canEdit,
            'clientnotes' => $this->clientnotes($clientnoteRepository),
            'alert' => $this->alert(),
            'grid_summary'=> $settingRepository->grid_summary($paginator, $this->translator, (int)$settingRepository->get_setting('default_list_limit'), $this->translator->translate('invoice.client.notes'), ''),
            'paginator'=>$paginator,   
        ];
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function add(Request $request, 
                        FormHydrator $formHydrator,
                        ClientRepository $clientRepository
    ): Response
    {
        $clientnote = new ClientNote();
        $form = new ClientNoteForm($clientnote);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['clientnote/add'],
            'errors' => [],
            'form' => $form,
            'clients' => $clientRepository->findAllPreloaded(),
        ];
        
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
               $body = $request->getParsedBody(); 
               /**
                * @psalm-suppress PossiblyInvalidArgument $body 
                */ 
                $this->clientnoteService->addClientNote($clientnote, $body);
                return $this->webService->getRedirectResponse('clientnote/index');
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param ClientNoteRepository $clientnoteRepository
     * @param ClientRepository $clientRepository
     * @param DateHelper $dateHelper
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function edit(Request $request, 
                        FormHydrator $formHydrator,
                        ClientNoteRepository $clientnoteRepository,                  
                        ClientRepository $clientRepository,
                        CurrentRoute $currentRoute
    ): Response {
        $client_note = $this->clientnote($currentRoute, $clientnoteRepository);
        if (null!==$client_note) {
            $form = new ClientNoteForm($client_note);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'action' => ['clientnote/edit', ['id' => $client_note->getId()]],
                'errors' => [],
                'form' => $form,
                'clients' => $clientRepository->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                $body = $request->getParsedBody();
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body
                     */
                    $this->clientnoteService->saveClientNote($client_note, $body);
                    return $this->webService->getRedirectResponse('clientnote/index');
                }
                $parameters['form'] = $form;
                $parameters['error'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
            }
            return $this->viewRenderer->render('_form', $parameters);
        } //client note
        return $this->webService->getRedirectResponse('clientnote/index');   
    }
    
    /**
     * 
     * @param ClientNoteRepository $clientnoteRepository
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function delete(ClientNoteRepository $clientnoteRepository, CurrentRoute $currentRoute
    ): Response {
        $client_note = $this->clientnote($currentRoute, $clientnoteRepository);
        if ($client_note) {
            $this->clientnoteService->deleteClientNote($client_note);               
            return $this->webService->getRedirectResponse('clientnote/index');        
        }
        return $this->webService->getRedirectResponse('clientnote/index');
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param ClientNoteRepository $clientnoteRepository
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function view(CurrentRoute $currentRoute, ClientNoteRepository $clientnoteRepository, ClientRepository $clientRepository
        ): Response {
        $client_note = $this->clientnote($currentRoute, $clientnoteRepository);
        if ($client_note) {
            $form = new ClientNoteForm($client_note);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'action' => ['clientnote/edit', ['id' => $client_note->getId()]],
                'errors' => [],
                'form' => $form,
                'clients'=>$clientRepository->findAllPreloaded(),
                'clientnote'=>$client_note,
            ];
            return $this->viewRenderer->render('_view', $parameters);
        } else {
            return $this->webService->getRedirectResponse('clientnote/index');
        } 
    }
    
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('clientnote/index');
        }
        return $canEdit;
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param ClientNoteRepository $clientnoteRepository
     * @return ClientNote|null
     */
    private function clientnote(CurrentRoute $currentRoute, ClientNoteRepository $clientnoteRepository): ClientNote|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $clientnote = $clientnoteRepository->repoClientNotequery($id);
            return $clientnote;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function clientnotes(ClientNoteRepository $clientnoteRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        $clientnotes = $clientnoteRepository->findAllPreloaded();        
        return $clientnotes;
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
}