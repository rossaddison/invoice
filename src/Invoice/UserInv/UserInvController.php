<?php

declare(strict_types=1);

namespace App\Invoice\UserInv;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Auth\TokenRepository as tR;
use App\Invoice\Client\ClientRepository as cR;
use App\Invoice\Entity\Client;
use App\Invoice\Entity\UserClient;
use App\Invoice\Entity\UserInv;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\UserClient\UserClientRepository as ucR;
use App\Invoice\UserInv\UserInvRepository as uiR;
use App\Service\WebControllerService;
use App\User\UserService;
use App\User\UserRepository as uR;
use App\Widget\Button;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Rbac\AssignmentsStorageInterface as Assignment;
use Yiisoft\Rbac\ItemsStorageInterface as ItemStorage;
use Yiisoft\Rbac\Manager as Manager;
use Yiisoft\Rbac\RuleFactoryInterface as Rule;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Security\TokenMask;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class UserInvController extends BaseController
{
    protected string $controllerName = 'invoice/userinv';

    public function __construct(
        // add, save, remove, clear, children, parents
        private ItemStorage $itemstorage,
        private Assignment $assignment,
        private Rule $rule,
        private Manager $manager,
        private UrlGenerator $urlGenerator,
        private UserInvService $userinvService,
        private \App\Widget\FormFields $formFields,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        // Related logic: see yiisoft/rbac-php
        $this->itemstorage = $itemstorage;
        $this->assignment = $assignment;
        $this->rule = $rule;
        $this->manager = new Manager($this->itemstorage, $this->assignment, $this->rule);
        $this->urlGenerator = $urlGenerator;
        $this->userinvService = $userinvService;
    }

    /**
     * Related logic: see Purpose: Transfer i.e add, newly signed up users, sitting in user Table, into userInv Table
     * @param Request $request
     * @param string $_language
     * @param FormHydrator $formHydrator
     * @param uR $uR
     * @param uiR $uiR
     * @return Response
     */
    public function add(
        Request $request,
        #[RouteArgument('_language')]
        string $_language,
        FormHydrator $formHydrator,
        uR $uR,
        uiR $uiR,
    ): Response {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__),
            '@language' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Language']);
        $countries = new CountryHelper();
        $userinv = new UserInv();
        $form = new UserInvForm($userinv);
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'userinv/add',
            'actionArguments' => [],
            'aliases' => $aliases,
            'errors' => [],
            'form' => $form,
            'formFields' => $this->formFields,
            // Only include newly signed up user ids in user Table in dropdown list i.e exclude those users already added and linked with client(s)
            'selected_country' => $this->sR->getSetting('default_country'),
            'selected_language' => $this->sR->getSetting('default_language'),
            'countries' => $countries->get_country_list($_language),
            'uR' => $uR,
            'uiR' => $uiR,
        ];

        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                // assign the observer role by default to a new user inv if it is not admin
                // and has not been assigned the observer role
                // form dropdown type 0 => admin, type 1 => guest
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    /**
                     * @var string $body['type']
                     */
                    $type = $body['type'];
                    if (null !== $form->getUser_id()) {
                        // the user is not admin(1) and the guest dropdown type(1) has been selected
                        if ($form->getUser_id() != '1' && $type == '1') {
                            $roles = $this->manager->getRolesByUserId($form->getUser_id());
                            if (!array_key_exists('observer', $roles)) {
                                $this->manager->assign('observer', $form->getUser_id());
                                $this->flashMessage('info', $this->translator->translate('user.inv.role.all.new'));
                            } else {
                                $this->flashMessage('info', $this->translator->translate('user.inv.role.observer.assigned.already'));
                            }
                            $this->userinvService->saveUserInv($userinv, $body);
                        }
                        // the user is not admin(1) and the type administrator(0) was selected in the dropdown on the form
                        if ($form->getUser_id() != '1' && $type == '0') {
                            $this->flashMessage('warning', $this->translator->translate('user.inv.type.cannot.allocate.administrator.type.to.non.administrator'));
                        }
                        // the user is admin and the type administrator was selected in the dropdown on the form
                        if ($form->getUser_id() == '1' && $type == '0') {
                            // if the admin role has not yet been assigned, assign it now
                            $roles = $this->manager->getRolesByUserId($form->getUser_id());
                            if (!array_key_exists('admin', $roles)) {
                                $this->manager->assign('admin', $form->getUser_id());
                                $this->flashMessage('info', $this->translator->translate('user.inv.role.administrator.assigned'));
                            } else {
                                $this->flashMessage('info', $this->translator->translate('user.inv.role.administrator.already.assigned'));
                            }
                            $this->userinvService->saveUserInv($userinv, $body);
                        }
                        // the user is an admin and the type guest was selected in the dropdown on the form
                        if ($form->getUser_id() == '1' && $type == '1') {
                            $this->flashMessage('warning', $this->translator->translate('user.inv.type.cannot.allocate.guest.type.to.administrator'));
                        }
                        return $this->webService->getRedirectResponse('userinv/index');
                    } // null!== $form->getUser_id()
                } // is_array
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form_add', $parameters);
    }

    // UserInv  is the extension Table of User
    // Users that have been signed up through the demo must be added
    // to the invoicing system
    // using Setting...Invoice User Account

    /**
     * @param cR $cR
     * @param uiR $uiR
     * @param ucR $ucR
     * @param sR $sR
     * @param string $_language
     * @param string $page
     * @param string $active
     * @param string $queryPage
     * @param string $querySort
     * @param string $queryFilterUser
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function index(
        cR $cR,
        uiR $uiR,
        ucR $ucR,
        sR $sR,
        #[RouteArgument('_language')]
        string $_language,
        #[RouteArgument('page')]
        string $page = '1',
        #[RouteArgument('active')]
        string $active = '2',
        #[Query('page')]
        string $queryPage = null,
        #[Query('sort')]
        string $querySort = null,
        #[Query('filterUser')]
        string $queryFilterUser = null,
    ): \Yiisoft\DataResponse\DataResponse {
        $canEdit = $this->rbac();
        $page = $queryPage ?? $page;
        $activeInt = (int) $active;
        $sortString = $querySort ?? '-user_id';
        $sort = Sort::only(['user_id', 'name'])
            ->withOrderString($sortString);
        $userinvs = $this->userinvs_active_with_sort($uiR, $activeInt, $sort);
        if (isset($queryFilterUser) && !empty($queryFilterUser)) {
            $userinvs = $uiR->filterUserInvs($queryFilterUser);
        }
        $parameters = [
            'cR' => $cR,
            'uiR' => $uiR,
            // get a count of clients allocated to the user
            'ucR' => $ucR,
            'active' => $activeInt,
            'canEdit' => $canEdit,
            'userinvs' => $userinvs,
            'locale' => $_language,
            'alert' => $this->alert(),
            // Parameters for GridView->requestArguments
            'page' => (int) $page > 0 ? (int) $page : 1,
            'sortOrder' => $querySort ?? '',
            'manager' => $this->manager,
            'optionsDataFilterUserInvLoginDropDown' => $this->optionsDataFilterUserInvLogin($uiR),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param uiR $uiR
     * @return Response
     */
    public function guest(
        Request $request,
        FormHydrator $formHydrator,
        uiR $uiR,
    ): Response {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__),
            '@language' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Language']);
        if (null !== $this->userService->getUser()) {
            $id = $this->userService->getUser()?->getId();
            if (null !== $id) {
                $userinv = $uiR->repoUserInvUserIdquery($id);
                if ($userinv) {
                    $form = new UserInvForm($userinv);
                    $parameters = [
                        'title' => $this->translator->translate('edit'),
                        'actionName' => 'userinv/guest',
                        'actionArguments' => [],
                        'errors' => [],
                        'form' => $form,
                        'aliases' => $aliases,
                    ];
                    if ($request->getMethod() === Method::POST) {
                        $body = $request->getParsedBody() ?? [];
                        if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                            if (is_array($body)) {
                                $this->userinvService->saveUserInv($userinv, $body);
                                return $this->webService->getRedirectResponse('invoice/index');
                            }
                        }
                        $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                        $parameters['form'] = $form;
                    }
                    return $this->viewRenderer->render('_form_guest', $parameters);
                }
                return $this->webService->getRedirectResponse('invoice/index');
            } // nul!== $id
            return $this->webService->getNotFoundResponse();
        } // null!==$this->userService->getUser()
        return $this->webService->getNotFoundResponse();
    }

    /**
     * Related logic: see src\Widget\PageSizeLimiter buttonsGuest function
     * Related logic: see ..\resources\views\invoice\inv\guest.php
     * Related logic: see InvController\guest
     * @param string $userInvId
     * @param string $origin
     * @param string $limit
     * @param uiR $uiR
     * @return Response
     */
    public function guestlimit(
        #[RouteArgument('userinv_id')]
        string $userInvId,
        #[RouteArgument('origin')]
        string $origin,
        #[RouteArgument('limit')]
        string $limit,
        uiR $uiR,
    ): Response {
        if (strlen($userInvId) > 0 && strlen($origin) > 0) {
            $limitInt = (int) $limit;
            $userInv = $uiR->repoUserInvquery($userInvId);
            if (null !== $userInv) {
                $userInv->setListLimit($limitInt);
                $uiR->save($userInv);
            }
        }
        /**
         * Related logic: see config/common/routes.php Route::get('/client_invoices[/page/{page:\d+}[/status/{status:\d+}]]')
         */
        return $this->webService->getRedirectResponse(strlen($origin) > 0 ? $origin . '/guest' : 'client/guest');
    }

    /**
     * @param string $user_id
     * @return Response
     */
    public function assignObserverRole(#[RouteArgument('user_id')] string $user_id): Response
    {
        if (strlen($user_id) > 0) {
            $this->manager->revokeAll($user_id);
            $this->manager->assign('observer', $user_id);
            $this->flashMessage('info', $this->translator->translate('user.inv.role.observer.assigned'));
        }
        return $this->webService->getRedirectResponse('userinv/index');
    }

    /**
     * @param string $user_id
     * @return Response
     */
    public function assignAccountantRole(#[RouteArgument('user_id')] string $user_id): Response
    {
        if (strlen($user_id) > 0) {
            $this->manager->revokeAll($user_id);
            $this->manager->assign('accountant', $user_id);
            $this->flashMessage('info', $this->translator->translate('user.inv.role.accountant.assigned'));
            $this->flashMessage('info', $this->translator->translate('user.inv.role.accountant.default'));
        }
        return $this->webService->getRedirectResponse('userinv/index');
    }

    /**
     * @param string $user_id
     * @return Response
     */
    public function revokeAllRoles(#[RouteArgument('user_id')] string $user_id): Response
    {
        if (strlen($user_id) > 0) {
            $this->manager->revokeAll($user_id);
            $this->flashMessage('info', $this->translator->translate('user.inv.role.revoke.all'));
        }
        return $this->webService->getRedirectResponse('userinv/index');
    }

    private function getRolesByUserId(string $userId): array
    {
        return $this->manager->getRolesByUserId($userId);
    }

    /**
     * @param Request $request
     * @param int $id
     * @param FormHydrator $formHydrator
     * @param UserInvRepository $userinvRepository
     * @param uR $uR
     * @return Response
     */
    public function edit(
        Request $request,
        #[RouteArgument('id')]
        int $id,
        FormHydrator $formHydrator,
        uiR $userinvRepository,
        uR $uR,
    ): Response {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__),
            '@language' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Language']);
        $userinv = $this->userinv($id, $userinvRepository);
        if ($userinv) {
            $form = new UserInvForm($userinv);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'userinv/edit',
                'actionArguments' => ['id' => $userinv->getId()],
                'errors' => [],
                'form' => $form,
                'formFields' => $this->formFields,
                'aliases' => $aliases,
                'uR' => $uR,
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                        /**
                         * @var string $body['type']
                         */
                        $type = $body['type'];
                        if (null !== $form->getUser_id()) {
                            // the user is not admin(1) and the guest dropdown type(1) has been selected
                            if ($form->getUser_id() != '1' && $type == '1') {
                                $roles = $this->manager->getRolesByUserId($form->getUser_id());
                                if (!array_key_exists('observer', $roles)) {
                                    $this->manager->assign('observer', $form->getUser_id());
                                    $this->flashMessage('info', $this->translator->translate('user.inv.role.all.new'));
                                } else {
                                    $this->flashMessage('warning', $this->translator->translate('user.inv.role.observer.assigned.already'));
                                }
                                $this->userinvService->saveUserInv($userinv, $body);
                            }
                            // the user is not admin(1) and the type administrator(0) was selected in the dropdown on the form
                            if ($form->getUser_id() != '1' && $type == '0') {
                                $this->flashMessage('warning', $this->translator->translate('user.inv.type.cannot.allocate.administrator.type.to.non.administrator'));
                            }
                            // the user is admin and the type administrator was selected in the dropdown on the form
                            if ($form->getUser_id() == '1' && $type == '0') {
                                // if the admin role has not yet been assigned, assign it now
                                $roles = $this->manager->getRolesByUserId($form->getUser_id());
                                if (!array_key_exists('admin', $roles)) {
                                    $this->manager->assign('admin', $form->getUser_id());
                                    $this->flashMessage('info', $this->translator->translate('user.inv.role.administrator.assigned'));
                                } else {
                                    $this->flashMessage('warning', $this->translator->translate('user.inv.role.administrator.already.assigned'));
                                }
                                $this->userinvService->saveUserInv($userinv, $body);
                            }
                            // the user is an admin and the type guest was selected in the dropdown on the form
                            if ($form->getUser_id() == '1' && $type == '1') {
                                $this->flashMessage('warning', $this->translator->translate('user.inv.type.cannot.allocate.guest.type.to.administrator'));
                            }
                            return $this->webService->getRedirectResponse('userinv/index');
                        } // null!== user_id
                    }
                } // is_array
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form_edit', $parameters);
        }
        return $this->webService->getRedirectResponse('userinv/index');
    }

    /**
     * @param int $id
     * @param ucR $ucR
     * @param uiR $uiR
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function client(#[RouteArgument('id')] int $id, ucR $ucR, uiR $uiR): \Yiisoft\DataResponse\DataResponse|Response
    {
        // Use the primary key 'id' passed in userinv/index's urlGenerator to retrieve the user_id
        $userinv = $this->userinv($id, $uiR);
        if (null !== $userinv) {
            $user_id = $userinv->getUser_Id();
            if ($user_id) {
                $parameters = [
                    'alert' => $this->alert(),
                    // Get all clients that this user will deal with
                    'ucR' => $ucR,
                    'userInv' => $uiR->repoUserInvUserIdquery($user_id),
                ];
                return $this->viewRenderer->render('field', $parameters);
            }
            return $this->webService->getRedirectResponse('userinv/index');
        }
        return $this->webService->getRedirectResponse('userinv/index');
    }

    /**
     * @param TranslatorInterface $translator
     * @param int $id
     * @param UserInvRepository $userinvRepository
     * @return Response
     */
    public function delete(
        TranslatorInterface $translator,
        #[RouteArgument('id')]
        int $id,
        uiR $userinvRepository,
    ): Response {
        $userinv = $this->userinv($id, $userinvRepository);
        if ($userinv) {
            $this->userinvService->deleteUserInv($userinv);
            $this->flashMessage('info', $translator->translate('deleted'));
            return $this->webService->getRedirectResponse('userinv/index');
        }
        return $this->webService->getRedirectResponse('userinv/index');
    }

    /**
     * After the user has clicked on their received email link in their email account, their hyperlink takes them to this function
     *
     * @param string $_language
     * @param string $language
     * @param string $token
     * @param cR $cR
     * @param uiR $uiR
     * @param ucR $ucR
     * @param sR $sR
     * @param tR $tR
     * @return Response
     */
    public function signup(
        // cldr e.g. 'en'
        #[RouteArgument('_language')]
        string $_language,
        // language e.g. 'English'
        #[RouteArgument('language')]
        string $language,
        #[RouteArgument('token')]
        string $tokenMasked,
        #[RouteArgument('tokenType')]
        string $tokenType,
        cR $cR,
        uiR $uiR,
        ucR $ucR,
        sR $sR,
        tR $tR,
    ): Response {
        // A token consists of a random 32 length string concatenated with a timestamp and then Masked.
        $unMaskedToken = TokenMask::remove($tokenMasked);
        $positionFromUnderscore = strrpos($unMaskedToken, '_');
        if ($positionFromUnderscore > -1) {
            $timestamp = substr($unMaskedToken, $positionFromUnderscore + 1);
            $lengthTimeStamp = strlen($timestamp);
            $tokenWithoutTimestamp = substr($unMaskedToken, 0, -($lengthTimeStamp + 1));
            if ((int) $timestamp + 3600 >= time()) {
                $identity = $tR->findIdentityByToken($tokenWithoutTimestamp, $tokenType);
                if (null !== $identity) {
                    $userId = $identity->getUser()?->getId();
                    if (null !== $userId) {
                        $userInv = $uiR->repoUserInvUserIdquery($userId);
                        if (null !== $userInv) {
                            $userInv->setActive(true);
                            $uiR->save($userInv);
                            $userId = $userInv->getUser_id();
                            // the status is now active i.e. 1, now make sure the token cannot be used again
                            $tokenEntity = $tR->findTokenByTokenAndType($tokenWithoutTimestamp, $tokenType);
                            if (null !== $tokenEntity) {
                                /**
                                 * Related logic: see https://github.com/search?q=repo%3Ayiisoft%2Fyii2-app-advanced%20already_&type=code
                                 */
                                $tokenEntity->setToken('already_used_token_' . (string) time());
                                $tR->save($tokenEntity);
                                // $email address field in signup form and a field in the user table
                                $email = $identity->getUser()?->getEmail();
                                if (null !== $email) {
                                    /**
                                     * The client will have to be assigned to the user by the admin manually if this is not set
                                     * Related logic: see InvoiceController 'signup_automatically_assign_client' => 0
                                     */
                                    if ($sR->getSetting('signup_automatically_assign_client') == '1') {
                                        $client = new Client();
                                        // set the client as active so that invoices can be created for the client
                                        $client->setClient_active(true);
                                        $client->setClient_email($email);
                                        $client->setClient_language($language);
                                        $client->setClient_age($sR->getSetting('signup_default_age_minimum_eighteen') == '1' ? 18 : 0);
                                        $cR->save($client);
                                        $this->flashMessage('info', $this->translator->translate('assign.client.on.signup.done'));
                                        if (null !== ($clientId = $client->getClient_id())) {
                                            $userClient = new UserClient();
                                            $userClient->setUser_id((int) $userInv->getUser_id());
                                            $userClient->setClient_id($clientId);
                                            $ucR->save($userClient);
                                        }
                                        if (strlen($userId) > 0 && $userId > 1) {
                                            $this->manager->revokeAll($userId);
                                            $this->manager->assign('observer', $userId);
                                            $this->flashMessage('info', $this->translator->translate('user.inv.role.observer.assigned'));
                                        }
                                        if (strlen($userId) > 0 && $userId == 1) {
                                            $this->manager->revokeAll($userId);
                                            $this->manager->assign('admin', $userId);
                                            $this->flashMessage('info', $this->translator->translate('user.inv.role.admin.assigned'));
                                        }
                                    } else {
                                        $this->flashMessage('warning', 'Client not signed up automatically because setting not on. As Admin you should click on this button to enable clients to be assigned to users automatically.' . ' ' .
                                            Button::setOrUnsetAssignClientToUserAutomatically($this->urlGenerator, $_language));
                                    }
                                }
                            }
                        } else {
                            $this->flashMessage('warning', 'No User Inv');
                        }
                    } else {
                        $this->flashMessage('warning', 'No User');
                    }
                } else {
                    $this->flashMessage('warning', 'No token');
                }
            }
        } else {
            $this->flashMessage('warning', 'No separating underscore in token');
        }
        return $this->webService->getRedirectResponse('site/index');
    }

    /**
     * @param int $id
     * @param UserInvRepository $userinvRepository
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(#[RouteArgument('id')] int $id, uiR $userinvRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__),
            '@language' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Language']);
        $userinv = $this->userinv($id, $userinvRepository);
        if ($userinv) {
            $form = new UserInvForm($userinv);
            $parameters = [
                'aliases' => $aliases,
                'title' => $this->translator->translate('view'),
                'actionName' => 'userinv/view',
                'actionArguments' => ['id' => $userinv->getId()],
                'errors' => [],
                'form' => $form,
                'userinv' => $userinvRepository->repoUserInvquery((string) $userinv->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
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
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse('userinv/index');
        }
        return $canEdit;
    }

    /**
     * @param UserInvRepository $uiR
     * @param int $active
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader|\Yiisoft\Data\Reader\SortableDataInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface|\Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function userinvs_active_with_sort(uiR $uiR, int $active, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface|\Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $uiR->findAllWithActive($active)
                        ->withSort($sort);
    }

    /**
     * @param int $id
     * @param UserInvRepository $userinvRepository
     * @return UserInv|null
     */
    private function userinv(int $id, uiR $userinvRepository): UserInv|null
    {
        if ($id) {
            return $userinvRepository->repoUserInvquery((string) $id);
        }
        return null;
    }

    /**
     * @param UserInvRepository $uiR
     * @return array
     */
    public function optionsDataFilterUserInvLogin(uiR $uiR): array
    {
        $optionsDataUserInvs = [];
        $userInvs = $uiR->findAllPreloaded();
        /**
         * @var UserInv $userInv
         */
        foreach ($userInvs as $userInv) {
            $login = $userInv->getUser()?->getLogin();
            if (null !== $login) {
                $optionsDataUserInvs[$login] = $login;
            }
        }
        return $optionsDataUserInvs;
    }
}
