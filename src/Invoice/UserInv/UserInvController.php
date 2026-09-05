<?php

declare(strict_types=1);

namespace App\Invoice\UserInv;

use App\Auth\Permissions;
use App\Invoice\AppConstants;
use App\Invoice\BaseController;
use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\UserClient\UserClient;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\UserClient\UserClientRepository as ucR;
use App\Invoice\UserInv\UserInvRepository as uiR;
use App\Invoice\UserInv\UserRbacLinkRepository;
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
use Yiisoft\Rbac\Manager as Manager;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Security\TokenMask;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use App\Invoice\UserInv\Trait\UserInvRoleTrait;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class UserInvController extends BaseController
{
    use UserInvRoleTrait;

    protected string $controllerName = 'invoice/userinv';

    private const REDIRECT_USERINV_INDEX = 'userinv/index';

    private Manager $manager;
    private UrlGenerator $urlGenerator;
    private UserInvService $userinvService;
    private \App\Widget\UserInvFormFields $formFields;

    public function __construct(
        UserInvControllerDeps $d,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        Flash $flash,
    ) {
        parent::__construct($d->webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        // Related logic: see yiisoft/rbac-php
        $this->manager = new Manager($d->itemstorage, $d->assignment, $d->rule);
        $this->urlGenerator = $d->urlGenerator;
        $this->userinvService = $d->userinvService;
        $this->formFields = $d->formFields;
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
        $form = new UserInvForm();
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
            'countries' => $countries->getCountryList($_language),
            'uR' => $uR,
            'uiR' => $uiR,
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if ($formHydrator->populateFromPostAndValidate($form, $request)
                && is_array($body)
                && null !== $form->user_id) {
                /** @var string $body['type'] */
                $this->applyRolePolicyOnAdd((string) $form->user_id, $body['type'], $userinv, $body);
                return $this->webService->getRedirectResponse(self::REDIRECT_USERINV_INDEX);
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->webViewRenderer->render('_form_add', $parameters);
    }

    // UserInv  is the extension Table of User
    // Users that have been signed up through the demo must be added
    // to the invoicing system
    // using Setting...Invoice User Account

    /**
     * @param UserInvIndexDeps $d
     * @param string $_language
     * @param string $page
     * @param string $active
     * @param string $queryPage
     * @param string $querySort
     * @param string $queryFilterUser
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(
        UserInvIndexDeps $d,
        #[RouteArgument('_language')]
        string $_language,
        #[RouteArgument('page')]
        string $page = '1',
        #[RouteArgument('active')]
        string $active = '2',
        #[Query('page')]
        ?string $queryPage = null,
        #[Query('sort')]
        ?string $querySort = null,
        #[Query('filterUser')]
        ?string $queryFilterUser = null,
    ): \Psr\Http\Message\ResponseInterface {
        $canEdit = $this->rbac();
        $page = $queryPage ?? $page;
        $activeInt = (int) $active;
        $sortString = $querySort ?? '-user_id';
        $sort = Sort::only(['user_id', 'name'])
            ->withOrderString($sortString);
        $userinvs = $this->userinvsActiveWithSort($d->uiR, $activeInt, $sort);
        if (isset($queryFilterUser) && !empty($queryFilterUser)) {
            $userinvs = $d->uiR->filterUserInvs($queryFilterUser);
        }
        $parameters = [
            'cR' => $d->cR,
            'uiR' => $d->uiR,
            // get a count of clients allocated to the user
            'ucR' => $d->ucR,
            'active' => $activeInt,
            'canEdit' => $canEdit,
            'userinvs' => $userinvs,
            'locale' => $_language,
            'alert' => $this->alert(),
            // Parameters for GridView->requestArguments
            'page' => (int) $page > 0 ? (int) $page : 1,
            'sortOrder' => $querySort ?? '',
            'manager' => $this->manager,
            'optionsDataFilterUserInvLoginDropDown' => $this->optionsDataFilterUserInvLogin($d->uiR),
            'linkedUserIdMap' => $d->urlR->findLinkedUserIdMap(),
            'wR' => $d->wR,
        ];
        return $this->webViewRenderer->render('index', $parameters);
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
        if (null === $this->userService->getUser()) {
            return $this->webService->getNotFoundResponse();
        }
        $id = $this->userService->getUser()?->reqId();
        $userinv = $uiR->repoUserInvUserIdquery((int) $id);
        if (!$userinv) {
            return $this->webService->getRedirectResponse('invoice/index');
        }
        $form = UserInvForm::show($userinv);
        $parameters = [
            'title' => $this->translator->translate('edit'),
            'actionName' => 'userinv/guest',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'aliases' => $aliases,
        ];
        $redirect = null;
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if ($formHydrator->populateFromPostAndValidate($form, $request) && is_array($body)) {
                $this->userinvService->saveUserInv($userinv, $body);
                $redirect = $this->webService->getRedirectResponse('invoice/index');
            } else {
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
        }
        return $redirect ?? $this->webViewRenderer->render('_form_guest', $parameters);
    }

    /**
     * Related logic: see src\Widget\PageSizeLimiter buttonsGuest function
     * Related logic: see ..\resources\views\invoice\inv\guest.php
     * Related logic: see InvController\guest
     *
     * Redirect target: prefers `$redirect` (the observer's actual current
     * page -- these page-size buttons live in the shared guest layout
     * menu's Settings dropdown, present on every guest page, not only the
     * "X/guest" content pages the `$origin`-derived guess below assumes)
     * over `$origin . '/guest'`, same fix and same reasoning as
     * UserInvController::toggleGuestBooleanPreference()'s own docblock.
     * `$origin` stays as a same-shape fallback only for a stale
     * bookmarked/cached button URL predating this fix.
     * @param int $userInvId
     * @param string $origin
     * @param string $limit
     * @param string|null $redirect
     * @param uiR $uiR
     * @return Response
     */
    public function guestlimit(
        #[RouteArgument('userinv_id')]
        int $userInvId,
        #[RouteArgument('origin')]
        string $origin,
        #[RouteArgument('limit')]
        string $limit,
        uiR $uiR,
        #[Query('redirect')]
        ?string $redirect = null,
    ): Response {
        if ($userInvId > 0 && strlen($origin) > 0) {
            $limitInt = (int) $limit;
            $userInv = $uiR->repoUserInvquery($userInvId);
            if (null !== $userInv) {
                $userInv->setListLimit($limitInt);
                $uiR->save($userInv);
            }
        }
        if (null !== $redirect && strlen($redirect) > 0) {
            return $this->webService->getRedirectToSameOriginPathResponse($redirect);
        }
        /**
         * Related logic: see config/common/routes.php Route::get('/client_invoices[/page/{page:\d+}[/status/{status:\d+}]]')
         */
        return $this->webService->getRedirectResponse(strlen($origin) > 0 ? $origin . '/guest' : 'client/guest');
    }

    /**
     * Per-observer preference toggle, not the admin-controlled shared
     * 'bootstrap5_layout_invoice_navbar_sticky' setting --
     * see docs/STICKY_NAVBAR_AND_GRID_HEADER_SEPTEMBER_2026.md.
     * Related logic: see ..\resources\views\layout\guest.php
     * Related logic: see App\ViewInjection\LayoutViewInjection::resolveUserState()
     * @param int $userInvId
     * @param string $origin
     * @param string|null $redirect
     * @param uiR $uiR
     * @return Response
     */
    public function guestStickyNavbar(
        #[RouteArgument('userinv_id')]
        int $userInvId,
        #[RouteArgument('origin')]
        string $origin,
        uiR $uiR,
        #[Query('redirect')]
        ?string $redirect = null,
    ): Response {
        return $this->toggleGuestBooleanPreference(
            $userInvId,
            $origin,
            $redirect,
            $uiR,
            static function (UserInv $userInv): void {
                $userInv->setStickyNavbar(!$userInv->getStickyNavbar());
            },
        );
    }

    /**
     * Per-observer preference toggle, not the admin-controlled shared
     * 'grid_sticky_header' setting -- same reasoning as guestStickyNavbar()
     * above.
     * Related logic: see ..\resources\views\invoice\inv\guest.php
     * @param int $userInvId
     * @param string $origin
     * @param string|null $redirect
     * @param uiR $uiR
     * @return Response
     */
    public function guestStickyGridHeader(
        #[RouteArgument('userinv_id')]
        int $userInvId,
        #[RouteArgument('origin')]
        string $origin,
        uiR $uiR,
        #[Query('redirect')]
        ?string $redirect = null,
    ): Response {
        return $this->toggleGuestBooleanPreference(
            $userInvId,
            $origin,
            $redirect,
            $uiR,
            static function (UserInv $userInv): void {
                $userInv->setStickyGridHeader(!$userInv->getStickyGridHeader());
            },
        );
    }

    /**
     * Shared by guestStickyNavbar()/guestStickyGridHeader() -- flips a
     * boolean then redirects back, sharing one load/save/redirect shape
     * (the exact SonarCloud duplication shape
     * SettingToggleController::toggleBooleanSettingCreatingAtOne() was
     * already extracted for on the admin-setting side of this feature).
     *
     * Redirect target: prefers `$redirect` (the observer's actual current
     * page, filled in by LayoutViewInjection::resolveUserState() from
     * `CurrentRoute::getUri()`, validated same-origin here since it's
     * still request input) over the `$origin`-derived guess `guestlimit()`
     * itself uses, because these two buttons live in the shared guest
     * layout menu -- present on every guest page, not only the "X/guest"
     * content pages `$origin . '/guest'` assumes. Confirmed broken live:
     * an observer on `invoice/index` (route name has no "X/guest"
     * counterpart) got a RouteNotFoundException for "invoice/guest" on
     * click. `$origin` stays as a same-shape fallback only for a stale
     * bookmarked/cached toggle URL predating this fix.
     * @param int $userInvId
     * @param string $origin
     * @param string|null $redirect
     * @param uiR $uiR
     * @param \Closure(UserInv):void $toggle
     * @return Response
     */
    private function toggleGuestBooleanPreference(int $userInvId, string $origin, ?string $redirect, uiR $uiR, \Closure $toggle): Response
    {
        if ($userInvId > 0) {
            $userInv = $uiR->repoUserInvquery($userInvId);
            if (null !== $userInv) {
                $toggle($userInv);
                $uiR->save($userInv);
            }
        }
        if (null !== $redirect && strlen($redirect) > 0) {
            return $this->webService->getRedirectToSameOriginPathResponse($redirect);
        }
        return $this->webService->getRedirectResponse(strlen($origin) > 0 ? $origin . '/guest' : 'client/guest');
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
        if (!$userinv) {
            return $this->webService->getRedirectResponse(self::REDIRECT_USERINV_INDEX);
        }
        $form = UserInvForm::show($userinv);
        $parameters = [
            'title' => $this->translator->translate('edit'),
            'actionName' => 'userinv/edit',
            'actionArguments' => ['id' => $userinv->reqId()],
            'errors' => [],
            'form' => $form,
            'formFields' => $this->formFields,
            'aliases' => $aliases,
            'uR' => $uR,
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)
                && $formHydrator->populateFromPostAndValidate($form, $request)
                && null !== $form->user_id) {
                /** @var string $body['type'] */
                $this->applyRolePolicyOnEdit((string) $form->user_id, $body['type'], $userinv, $body);
                return $this->webService->getRedirectResponse(self::REDIRECT_USERINV_INDEX);
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->webViewRenderer->render('_form_edit', $parameters);
    }

    /**
     * @param int $id
     * @param ucR $ucR
     * @param uiR $uiR
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function client(#[RouteArgument('id')] int $id, ucR $ucR, uiR $uiR): \Psr\Http\Message\ResponseInterface
    {
        // Use the primary key 'id' passed in userinv/index's urlGenerator to retrieve the user_id
        $userinv = $this->userinv($id, $uiR);
        if (null !== $userinv) {
            $user_id = $userinv->reqUserId();
            if ($user_id) {
                $parameters = [
                    'alert' => $this->alert(),
                    // Get all clients that this user will deal with
                    'ucR' => $ucR,
                    'userInv' => $uiR->repoUserInvUserIdquery($user_id),
                ];
                return $this->webViewRenderer->render('field', $parameters);
            }
            return $this->webService->getRedirectResponse(self::REDIRECT_USERINV_INDEX);
        }
        return $this->webService->getRedirectResponse(self::REDIRECT_USERINV_INDEX);
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
            $roles = $this->manager->getRolesByUserId((string) $userinv->reqUserId());
            if ($roles !== []) {
                $this->flashMessage(
                    'warning',
                    $translator->translate('user.inv.delete.blocked.roles')
                    . ' ' . implode(', ', array_keys($roles))
                );
                $this->flashMessage(
                    'info',
                    $translator->translate('user.inv.delete.blocked.callback')
                );
                return $this->webService->getRedirectResponse(self::REDIRECT_USERINV_INDEX);
            }
            $this->userinvService->deleteUserInv($userinv);
            $this->flashMessage('info', $translator->translate('deleted'));
            return $this->webService->getRedirectResponse(self::REDIRECT_USERINV_INDEX);
        }
        return $this->webService->getRedirectResponse(self::REDIRECT_USERINV_INDEX);
    }

    /**
     * After the user has clicked on their received email link in their
     * email account, their hyperlink takes them to this function
     * @param string $_language
     * @param string $language
     * @param string $tokenMasked
     * @param string $tokenType
     * @param UserInvSignupDeps $d
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
        UserInvSignupDeps $d,
    ): Response {
        // A token consists of a random 32 length string concatenated with a timestamp and then Masked.
        $unMaskedToken = TokenMask::remove($tokenMasked);
        $positionFromUnderscore = strrpos($unMaskedToken, '_');
        if ($positionFromUnderscore === false) {
            $this->flashMessage('warning', 'No separating underscore in token');
            return $this->webService->getRedirectResponse('site/index');
        }
        $timestamp = substr($unMaskedToken, $positionFromUnderscore + 1);
        $tokenWithoutTimestamp = substr($unMaskedToken, 0, -(strlen($timestamp) + 1));
        if ((int) $timestamp + 3600 >= time()) {
            $this->processValidToken($tokenWithoutTimestamp, $tokenType, $d, $language, $_language);
        }
        return $this->webService->getRedirectResponse('site/index');
    }

    /**
     * @param int $id
     * @param UserInvRepository $userinvRepository
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function view(#[RouteArgument('id')] int $id, uiR $userinvRepository): \Psr\Http\Message\ResponseInterface
    {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__),
            '@language' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Language']);
        $userinv = $this->userinv($id, $userinvRepository);
        if ($userinv) {
            $form = UserInvForm::show($userinv);
            $parameters = [
                'aliases' => $aliases,
                'title' => $this->translator->translate('view'),
                'actionName' => 'userinv/view',
                'actionArguments' => ['id' => $userinv->reqId()],
                'errors' => [],
                'form' => $form,
                'userinv' => $userinvRepository->repoUserInvquery($userinv->reqId()),
            ];
            return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse(self::REDIRECT_USERINV_INDEX);
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission(Permissions::EDIT_INV);
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse(self::REDIRECT_USERINV_INDEX);
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
    private function userinvsActiveWithSort(uiR $uiR, int $active, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface|\Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $uiR->findAllWithActive($active)
                        ->withSort($sort);
    }

    /**
     * @param int $id
     * @param UserInvRepository $userinvRepository
     * @return UserInv|null
     */
    private function userinv(int $id, uiR $userinvRepository): ?UserInv
    {
        if ($id) {
            return $userinvRepository->repoUserInvquery($id);
        }
        return null;
    }

    private function processValidToken(
        string $tokenWithoutTimestamp,
        string $tokenType,
        UserInvSignupDeps $d,
        string $language,
        string $_language,
    ): void {
        $identity = $d->tR->findIdentityByToken($tokenWithoutTimestamp, $tokenType);
        $userId = $identity?->getUser()?->reqId();
        $userInv = null !== $userId ? $d->uiR->repoUserInvUserIdquery($userId) : null;
        if (null === $identity) {
            $this->flashMessage('warning', 'No token');
            return;
        }
        if (null === $userId || null === $userInv) {
            $this->flashMessage('warning', null === $userId ? 'No User' : 'No User Inv');
            return;
        }
        $userInv->setActive(true);
        $d->uiR->save($userInv);
        $userId = $userInv->reqUserId();
        // invalidate the token so it cannot be used again
        $tokenEntity = $d->tR->findTokenByTokenAndType($tokenWithoutTimestamp, $tokenType);
        if (null === $tokenEntity) {
            return;
        }
        $tokenEntity->setToken('already_used_token_' . (string) time());
        $d->tR->save($tokenEntity);
        $email = $identity->getUser()?->getEmail();
        if (null !== $email) {
            $this->handleSignupClientAssignment($email, $language, $_language, $userId, $userInv, $d);
        }
    }

    private function handleSignupClientAssignment(
        string $email,
        string $language,
        string $_language,
        int $userId,
        UserInv $userInv,
        UserInvSignupDeps $d,
    ): void {
        if ($this->sR->getSetting('signup_automatically_assign_client') != '1') {
            $this->flashMessage('warning', 'Client not signed up automatically because setting not on. As Admin you should click on this button to enable clients to be assigned to users automatically.' . ' '
                . Button::setOrUnsetAssignClientToUserAutomatically($this->urlGenerator, $_language));
            return;
        }
        $client = new Client();
        $client->setClientActive(true);
        $client->setClientEmail($email);
        $client->setClientLanguage($language);
        $client->setClientAge($this->sR->getSetting('signup_default_age_minimum_eighteen') == '1' ? 18 : 0);
        $d->cR->save($client);
        $this->flashMessage('info', $this->translator->translate('assign.client.on.signup.done'));
        $userClient = new UserClient();
        $userClient->setUserId($userInv->reqUserId());
        $userClient->setClientId($client->reqId());
        $d->ucR->save($userClient);
        $this->assignSignupRole($userId, $userInv->reqId(), $d->urlR);
    }

    private function assignSignupRole(int $userId, int $userInvId, UserRbacLinkRepository $urlR): void
    {
        if ($userId <= 0) {
            return;
        }
        $this->manager->revokeAll($userId);
        if ($userId > 1) {
            $this->manager->assign(AppConstants::ROLE_OBSERVER, $userId);
            $urlR->upsert($userInvId, $userId);
            $this->flashMessage('info', $this->translator->translate('user.inv.role.observer.assigned'));
        } else {
            $this->manager->assign(AppConstants::ROLE_ADMIN, $userId);
            $urlR->upsert($userInvId, $userId);
            $this->flashMessage('info', $this->translator->translate('user.inv.role.admin.assigned'));
        }
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
