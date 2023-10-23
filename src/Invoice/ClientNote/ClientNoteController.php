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
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

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
     * @param DateHelper $dateHelper
     * @param SettingRepository $settingRepository
     * @param Request $request
     * @param ClientNoteService $service
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function index(ClientNoteRepository $clientnoteRepository, DateHelper $dateHelper, SettingRepository $settingRepository, Request $request, ClientNoteService $service): \Yiisoft\DataResponse\DataResponse
    {
         $canEdit = $this->rbac($this->session);
         $parameters = [
          'd'=>$dateHelper,
          's'=>$settingRepository,
          'canEdit' => $canEdit,
          'clientnotes' => $this->clientnotes($clientnoteRepository),
          'alert' => $this->alert() 
         ];
        
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param DateHelper $dateHelper
     * @param SettingRepository $settingRepository
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request, 
                        FormHydrator $formHydrator,
                        DateHelper $dateHelper, 
                        SettingRepository $settingRepository,                        
                        ClientRepository $clientRepository
    ): Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['clientnote/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            'd'=>$dateHelper,
            's'=>$settingRepository,
            'head'=>$head,
            'clients'=>$clientRepository->findAllPreloaded(),
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new ClientNoteForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                $this->clientnoteService->addClientNote(new ClientNote(),$form, $settingRepository);
                return $this->webService->getRedirectResponse('clientnote/index');
            }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param ClientNoteRepository $clientnoteRepository
     * @param SettingRepository $settingRepository
     * @param ClientRepository $clientRepository
     * @param DateHelper $dateHelper
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, 
                        FormHydrator $formHydrator,
                        ClientNoteRepository $clientnoteRepository, 
                        SettingRepository $settingRepository,                        
                        ClientRepository $clientRepository,
                        DateHelper $dateHelper, 
                        CurrentRoute $currentRoute
    ): Response {
        $client_note = $this->clientnote($currentRoute, $clientnoteRepository);
        if ($client_note) {
            $parameters = [
                'title' => $settingRepository->trans('edit'),
                'action' => ['clientnote/edit', ['id' => $client_note->getId()]],
                'errors' => [],
                'body' => $this->body($client_note),
                'head'=>$head,
                'd'=>$dateHelper,
                's'=>$settingRepository,
                'clients'=>$clientRepository->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new ClientNoteForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->clientnoteService->saveClientNote($client_note, $form, $settingRepository);
                    return $this->webService->getRedirectResponse('clientnote/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
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
     * @param DateHelper $dateHelper
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function view(CurrentRoute $currentRoute, ClientNoteRepository $clientnoteRepository, DateHelper $dateHelper,
        SettingRepository $settingRepository
        ): Response {
        $client_note = $this->clientnote($currentRoute, $clientnoteRepository);
        if ($client_note) { 
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['clientnote/edit', ['id' => $client_note->getId()]],
                'errors' => [],
                'body' => $this->body($client_note),
                'd'=>$dateHelper,
                's'=>$settingRepository,             
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
    private function rbac(SessionInterface $session): bool|Response 
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
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function clientnotes(ClientNoteRepository $clientnoteRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $clientnotes = $clientnoteRepository->findAllPreloaded();        
        return $clientnotes;
    }
    
    /**
     * @param ClientNote $clientnote
     * @return array
     */
    private function body(ClientNote $clientnote): array {
        $body = [
          'id'=>$clientnote->getId(),
          'client_id'=>$clientnote->getClient_id(),
          'date'=>$clientnote->getDate(),
          'note'=>$clientnote->getNote()
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