<?php

declare(strict_types=1);

namespace App\Invoice\ClientNote;

use App\Invoice\Entity\ClientNote;
use App\Invoice\ClientNote\ClientNoteService;
use App\Invoice\ClientNote\ClientNoteRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Traits\FlashMessage;
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
    use FlashMessage;
    
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
    ) {
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
        $this->rbac();
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
        ClientRepository $clientRepository
    ): Response {
        $clientnote = new ClientNote();
        $form = new ClientNoteForm($clientnote);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
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
                    $this->clientnoteService->addClientNote($clientnote, $body);
                    return $this->webService->getRedirectResponse('clientnote/index');
                }    
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
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
        CurrentRoute $currentRoute
    ): Response {
        $client_note = $this->clientnote($currentRoute, $clientnoteRepository);
        if (null !== $client_note) {
            $form = new ClientNoteForm($client_note);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'clientnote/edit',
                'actionArguments' => ['id' => $client_note->getId()],
                'errors' => [],
                'form' => $form,
                'clients' => $clientRepository->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                        $this->clientnoteService->saveClientNote($client_note, $body);
                        return $this->webService->getRedirectResponse('clientnote/index');
                    }
                    $parameters['form'] = $form;
                    $parameters['error'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                } 
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
    public function delete(
        ClientNoteRepository $clientnoteRepository,
        CurrentRoute $currentRoute
    ): Response {
        $client_note = $this->clientnote($currentRoute, $clientnoteRepository);
        if ($client_note) {
            $this->clientnoteService->deleteClientNote($client_note);
            return $this->webService->getRedirectResponse('clientnote/index');
        }
        return $this->webService->getRedirectResponse('clientnote/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ClientNoteRepository $clientnoteRepository
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function view(
        CurrentRoute $currentRoute,
        ClientNoteRepository $clientnoteRepository,
        ClientRepository $clientRepository
    ): Response {
        $client_note = $this->clientnote($currentRoute, $clientnoteRepository);
        if ($client_note) {
            $form = new ClientNoteForm($client_note);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'clientnote/edit',
                'actionArguments' => ['id' => $client_note->getId()],
                'form' => $form,
                'clients' => $clientRepository->findAllPreloaded(),
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
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('invoice.permission'));
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
        if (null !== $id) {
            $clientnote = $clientnoteRepository->repoClientNotequery($id);
            return $clientnote;
        }
        return null;
    }

    /**
      * @return string
      */
    private function alert(): string
    {
        return $this->viewRenderer->renderPartialAsString(
            '//invoice/layout/alert',
            [
        'flash' => $this->flash
      ]
        );
    }
}
