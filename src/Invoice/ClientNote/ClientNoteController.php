<?php

declare(strict_types=1);

namespace App\Invoice\ClientNote;

use App\Invoice\BaseController;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Entity\ClientNote;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\FormModel\FormHydrator;
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
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->clientNoteService = $clientNoteService;
    }

    public function index(ClientNoteRepository $clientnoteRepository, Request $request): \Yiisoft\DataResponse\DataResponse
    {
        $this->rbac();
        $paginator  = (new OffsetPaginator($clientnoteRepository->findAllPreloaded()));
        $parameters = [
            'alert'     => $this->alert(),
            'paginator' => $paginator,
        ];

        return $this->viewRenderer->render('index', $parameters);
    }

    public function add(
        Request $request,
        FormHydrator $formHydrator,
        ClientRepository $clientRepository,
    ): Response {
        $clientnote = new ClientNote();
        $form       = new ClientNoteForm($clientnote);
        $parameters = [
            'title'           => $this->translator->translate('add'),
            'actionName'      => 'clientnote/add',
            'actionArguments' => [],
            'errors'          => [],
            'form'            => $form,
            'clients'         => $clientRepository->findAllPreloaded(),
        ];

        if (Method::POST === $request->getMethod()) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->clientNoteService->addClientNote($clientnote, $body);

                    return $this->webService->getRedirectResponse('clientnote/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form']   = $form;
        }

        return $this->viewRenderer->render('_form', $parameters);
    }

    public function edit(
        Request $request,
        FormHydrator $formHydrator,
        ClientNoteRepository $clientnoteRepository,
        ClientRepository $clientRepository,
        CurrentRoute $currentRoute,
    ): Response {
        $client_note = $this->clientnote($currentRoute, $clientnoteRepository);
        if (null !== $client_note) {
            $form       = new ClientNoteForm($client_note);
            $parameters = [
                'title'           => $this->translator->translate('edit'),
                'actionName'      => 'clientnote/edit',
                'actionArguments' => ['id' => $client_note->getId()],
                'errors'          => [],
                'form'            => $form,
                'clients'         => $clientRepository->findAllPreloaded(),
            ];
            if (Method::POST === $request->getMethod()) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                        $this->clientNoteService->saveClientNote($client_note, $body);

                        return $this->webService->getRedirectResponse('clientnote/index');
                    }
                    $parameters['form']  = $form;
                    $parameters['error'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                }
            }

            return $this->viewRenderer->render('_form', $parameters);
        } // client note

        return $this->webService->getRedirectResponse('clientnote/index');
    }

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

    public function view(
        CurrentRoute $currentRoute,
        ClientNoteRepository $clientnoteRepository,
        ClientRepository $clientRepository,
    ): Response {
        $client_note = $this->clientnote($currentRoute, $clientnoteRepository);
        if ($client_note) {
            $form       = new ClientNoteForm($client_note);
            $parameters = [
                'title'           => $this->translator->translate('view'),
                'actionName'      => 'clientnote/edit',
                'actionArguments' => ['id' => $client_note->getId()],
                'form'            => $form,
                'clients'         => $clientRepository->findAllPreloaded(),
            ];

            return $this->viewRenderer->render('_view', $parameters);
        }

        return $this->webService->getRedirectResponse('clientnote/index');
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));

            return $this->webService->getRedirectResponse('clientnote/index');
        }

        return $canEdit;
    }

    private function clientnote(CurrentRoute $currentRoute, ClientNoteRepository $clientnoteRepository): ?ClientNote
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $clientnoteRepository->repoClientNotequery($id);
        }

        return null;
    }
}
