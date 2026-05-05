<?php

declare(strict_types=1);

namespace App\Invoice\ClientNote;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Infrastructure\Persistence\ClientNote\ClientNote;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class ClientNoteController extends BaseController
{
    protected string $controllerName = 'invoice/clientnote';

    public function __construct(
        private ClientNoteService $clientNoteService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer,
                $session, $sR, $flash);
        $this->clientNoteService = $clientNoteService;
    }

    /**
     * @param ClientNoteRepository $clientnoteRepository
     * @param int $page
     * @param int|null $queryPage
     * @param string|null $querySort
     * @return Response
     */
    public function index(
        ClientNoteRepository $clientnoteRepository,
        #[RouteArgument('page')]
        int $page = 1,
        #[Query('page')]
        ?int $queryPage = null,
        #[Query('sort')]
        ?string $querySort = null,
    ): Response {
        $page = $queryPage ?? $page;
        $parameters = [
            'alert' => $this->alert(),
            'clientNotes' => $clientnoteRepository->findAllPreloaded(),
            'defaultPageSizeOffsetPaginator' =>
                (int) $this->sR->getSetting('default_list_limit') ?: 1,
            'page' => $page > 0 ? $page : 1,
            'sortString' => $querySort ?? '-id',
        ];
        return $this->webViewRenderer->render('index', $parameters);
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
        $form = new ClientNoteForm();
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
                    $this->translator->translate('record.successfully.created');
                    return $this->webService->getRedirectResponse('clientnote/index');
                }
            }
            $parameters['errors'] =
                $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->webViewRenderer->render('_form', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param ClientNoteRepository $clientnoteRepository
     * @param ClientRepository $clientRepository
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
            $form = ClientNoteForm::show($client_note);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'clientnote/edit',
                'actionArguments' => ['id' => $client_note->reqId()],
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
                        $this->flashMessage('info',
                        $this->translator->translate('record.successfully.updated'));
                        return $this->webService->getRedirectResponse(
                                                            'clientnote/index');
                    }
                    $parameters['form'] = $form;
                    $parameters['error'] =
                    $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                }
            }
            return $this->webViewRenderer->render('_form', $parameters);
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
        try {
            $client_note = $this->clientnote($currentRoute, $clientnoteRepository);
            if ($client_note) {
                $this->clientNoteService->deleteClientNote($client_note);
                $this->flashMessage('info',
                    $this->translator->translate('record.successfully.deleted'));
                return $this->webService->getRedirectResponse('clientnote/index');
            }
            return $this->webService->getRedirectResponse('clientnote/index');
        } catch (\Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('clientnote/index');
        }
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
            $form = ClientNoteForm::show($clientNote);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'clientnote/edit',
                'actionArguments' => ['id' => $clientNote->reqId()],
                'form' => $form,
                'clients' => $clientRepository->findAllPreloaded(),
            ];
            if ($this->rbacObserver($clientNote->reqClientId(), $ucR, $uiR)) {
                return $this->webViewRenderer->render('_view', $parameters);
            }
        }
        return $this->webService->getRedirectResponse('clientnote/index');
    }

    private function rbacObserver(int $clientId, UCR $ucR, UIR $uiR): bool {
        $userClient = $ucR->repoUserquery($clientId);
        if (null!==$userClient) {
            $userId = $userClient->reqUserId();
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
        $id = (int) $currentRoute->getArgument('id');
        return $clientnoteRepository->repoClientNotequery($id);
    }
}
