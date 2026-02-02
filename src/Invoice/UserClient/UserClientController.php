<?php

declare(strict_types=1);

namespace App\Invoice\UserClient;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Invoice\Entity\UserClient;
use App\Invoice\Entity\UserInv;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class UserClientController extends BaseController
{
    protected string $controllerName = 'invoice/userclient';

    public function __construct(
        private UserClientService $userclientService,
        private DataResponseFactoryInterface $factory,
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
        $this->userclientService = $userclientService;
        $this->factory = $factory;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param UserClientRepository $userclientRepository
     * @param UIR $uiR
     * @param IR $iR
     * @param QR $qR
     * @param SOR $soR
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function delete(
        CurrentRoute $currentRoute,
        UserClientRepository $userclientRepository,
        UIR $uiR,
        IR $iR,
        QR $qR,
        SOR $soR    
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $user_client = $this->userclient($currentRoute, $userclientRepository);
        if (null !== $user_client) {
            $user_id = (int) $user_client->getUser_Id();
            $client_id = (int) $user_client->getClient_id();
            if (($iR->countAllWithUserClient($user_id, $client_id) === 0)
             && ($qR->countAllWithUserClient($user_id, $client_id) === 0)
             && ($soR->countAllWithUserClient($user_id, $client_id) === 0)){   
                $this->userclientService->deleteUserClient($user_client);
                $user_inv = $uiR->repoUserInvUserIdquery((string) $user_id);
                if (null !== $user_inv) {
                    $this->flashMessage('info', $this->translator->translate(
                                                    'record.successfully.deleted'));
                    return $this->factory->createResponse(
                        $this->viewRenderer->renderPartialAsString(
                            '//invoice/setting/userclient_successful',
                            [
                                'heading' => $this->translator->translate('client'),
                                'message' => $this->translator->translate(
                                                    'record.successfully.deleted'),
                                'url' => 'userinv/client','id' => $user_inv->getId(),
                            ],
                        ),
                    );
                }
                return $this->webService->getRedirectResponse('userinv/index');
            }
            $this->flashMessage('danger', $this->translator->translate(
                                                    'user.client.delete.not'));
        }
        return $this->webService->getRedirectResponse('userinv/index');
    }

// The preceding url is userinv/client/{userinv_id} showing the currently
// assigned clients to this user
// Retrieves userclient/new.php which offers an 'all client option'
// and an individual client option

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param CurrentRoute $currentRoute
     * @param ClientRepository $cR
     * @param UserClientRepository $ucR
     * @param UserClientService $ucS
     * @param UIR $uiR
     * @return Response
     */
    public function new(
        Request $request,
        FormHydrator $formHydrator,
        CurrentRoute $currentRoute,
        ClientRepository $cR,
        UserClientRepository $ucR,
        UserClientService $ucS,
        UIR $uiR,
    ): Response {
        $user_id = $currentRoute->getArgument('user_id');
        if (null !== $user_id) {
        // Get possible client ids as an array that can be presented to this user
            $availableClientIdList = $ucR->get_not_assigned_to_user($user_id, $cR);
            $user_client = new UserClient();
            $form = new UserClientForm($user_client);
            $parameters = [
                'errors' => [],
                'userinv' => $this->user($currentRoute, $uiR),
                // Only provide clients NOT already included ie. available
                'availableClientIdList' => $availableClientIdList,
                'cR' => $cR,
// Initialize the checkbox to zero so that both 'all_clients' and dropdownbox
// is presented on userclient/new.php
                'form' => $form,
            ];

            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                if (is_array($body)) {
                    /** @var string $value */
                    foreach ($body as $key => $value) {
                  // If the user is allowed to see all clients eg. An Accountant
                        if (((string) $key === 'user_all_clients')
                                                          && ($value === '1')) {
                            // Unassign currently assigned clients
                            $ucR->unassign_to_user_client($user_id);
                            // Search for all clients, including new clients and
                            // assign them aswell
                            $ucR->reset_users_all_clients(
                                                $uiR, $cR, $ucS, $formHydrator);
                            return $this->webService->getRedirectResponse(
                                                                'userinv/index');
                        }
                        if ((string) $key === 'client_id') {
                            $form_array = [
                                'user_id' => $user_id,
                                'client_id' => $value,
                            ];
                            if ($formHydrator->populateAndValidate($form, $form_array)
                                // Check that the user client does not exist
                                && !($ucR->repoUserClientqueryCount(
                                        $user_id, $value) > 0)) {
                                $this->userclientService->saveUserClient(
                                        $user_client, $form_array);
                                $this->flashMessage('info',
                                        $this->translator->translate(
                                                'record.successfully.updated'));
                                return $this->webService->getRedirectResponse(
                                                                'userinv/index');
                            }

                            if ($ucR->repoUserClientqueryCount($user_id, $value) > 0) {
                                $this->flashMessage('info',
                                        $this->translator->translate(
                                                      'client.already.exists'));
                                return $this->webService->getRedirectResponse(
                                                                'userinv/index');
                            }
                            $parameters['errors'] =
                                    $form->getValidationResult()
                                         ->getErrorMessagesIndexedByProperty();
                            $parameters['form'] = $form;
                        }
                    }
                }
            }
            return $this->viewRenderer->render('new', $parameters);
        }
        return $this->webService->getRedirectResponse('userinv/index');
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission(Permissions::EDIT_INV);
        if (!$canEdit) {
            $this->flashMessage('warning',
                $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse('userinv/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param UIR $uiR
     * @return UserInv|null
     */
    private function user(CurrentRoute $currentRoute, UIR $uiR): ?UserInv
    {
        $user_id = $currentRoute->getArgument('user_id');
        if (null !== $user_id) {
            return $uiR->repoUserInvUserIdquery($user_id);
        }
        return null;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param UserClientRepository $ucR
     * @return UserClient|null
     */
    private function userclient(CurrentRoute $currentRoute,
                                        UserClientRepository $ucR): ?UserClient
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $ucR->repoUserClientquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function userclients(UserClientRepository $userclientRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $userclientRepository->findAllPreloaded();
    }
}
