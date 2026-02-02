<?php

declare(strict_types=1);

namespace App\Invoice\ClientNote;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Invoice\Entity\ClientNote;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class ClientNoteController extends BaseController
{
    protected string $controllerName = 'invoice/clientnote';

    public function __construct(
        private ClientNoteService $clientNoteService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer,
                $session, $sR, $flash);
        $this->clientNoteService = $clientNoteService;
    }

    /**
     * @param ClientNoteRepository $clientnoteRepository
     * @param Request $request
     * @param ClientNoteService $service
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function index(ClientNoteRepository $clientnoteRepository, Request $request): \Yiisoft\DataResponse\DataResponse
    {
        $paginator = (new OffsetPaginator($clientnoteRepository->findAllPreloaded()));
        $parameters = [
            'alert' => $this->alert(),
            'paginator' => $paginator,
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function add(
        Request $request,
        FormHydrator $formHydrator,
        ClientRepository $clientRepository,
    ): Response {
        $clientnote = new ClientNote();
        $form = new ClientNoteForm($clientnote);
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'clientnote/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'clients' => $clientRepository->findAllPreloaded(),
        ];

        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->clientNoteService->addClientNote($clientnote, $body);
                    return $this->webService->getRedirectResponse('clientnote/index');
                }
            }
            $parameters['errors'] =
                $form->getValidationResult()->getErrorMessagesIndexedByProperty();
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
    public function edit(
        Request $request,
        FormHydrator $formHydrator,
        ClientNoteRepository $clientnoteRepository,
        ClientRepository $clientRepository,
        CurrentRoute $currentRoute,
    ): Response {
        $client_note = $this->clientnote($currentRoute, $clientnoteRepository);
        if (null !== $client_note) {
            $form = new ClientNoteForm($client_note);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'clientnote/edit',
                'actionArguments' => ['id' => $client_note->getId()],
                'errors' => [],
                'form' => $form,
                'clients' => $clientRepository->findAllPreloaded(),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    if ($formHydrator->populateFromPostAndValidate(
                                                             $form, $request)) {
                        $this->clientNoteService->saveClientNote(
                                                            $client_note, $body);
                        return $this->webService->getRedirectResponse(
                                                            'clientnote/index');
                    }
                    $parameters['form'] = $form;
                    $parameters['error'] =
                    $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                }
            }
            return $this->viewRenderer->render('_form', $parameters);
        } //client note
        return $this->webService->getRedirectResponse('clientnote/index');
    }

    /**
     * @param ClientNoteRepository $clientnoteRepository
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function delete(
        ClientNoteRepository $clientnoteRepository,
        CurrentRoute $currentRoute,
    ): Response {
        $client_note = $this->clientnote($currentRoute, $clientnoteRepository);
        if ($client_note) {
            $this->clientNoteService->deleteClientNote($client_note);
            return $this->webService->getRedirectResponse('clientnote/index');
        }
        return $this->webService->getRedirectResponse('clientnote/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ClientNoteRepository $clientnoteRepository
     * @param ClientRepository $clientRepository
     * @param UCR $ucR
     * @param UIR $uiR
     * @return Response
     */
    public function view(
        CurrentRoute $currentRoute,
        ClientNoteRepository $clientnoteRepository,
        ClientRepository $clientRepository,
        UCR $ucR,
        UIR $uiR    
    ): Response {
        $clientNote = $this->clientnote($currentRoute, $clientnoteRepository);
        if ($clientNote) {
            $form = new ClientNoteForm($clientNote);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'clientnote/edit',
                'actionArguments' => ['id' => $clientNote->getId()],
                'form' => $form,
                'clients' => $clientRepository->findAllPreloaded(),
            ];
            if ($this->rbacObserver(
                                    $clientNote->getClient_id(), $ucR, $uiR)) {
                return $this->viewRenderer->render('_view', $parameters);
            }
        }
        return $this->webService->getRedirectResponse('clientnote/index');
    }
    
    private function rbacObserver(
                                    string $clientId, UCR $ucR, UIR $uiR): bool {
        $userClient = $ucR->repoUserquery($clientId);
        if (null!==$userClient) {
            $userId = $userClient->getUser_id();
            $userInv = $uiR->repoUserInvUserIdquery($userId);
            if (null !== $userInv && $userInv->getActive()) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param ClientNoteRepository $clientnoteRepository
     * @return ClientNote|null
     */
    private function clientnote(CurrentRoute $currentRoute,
                        ClientNoteRepository $clientnoteRepository): ?ClientNote
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $clientnoteRepository->repoClientNotequery($id);
        }
        return null;
    }
}
