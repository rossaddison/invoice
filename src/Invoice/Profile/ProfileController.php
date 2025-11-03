<?php

declare(strict_types=1);

namespace App\Invoice\Profile;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Invoice\Company\CompanyRepository;
use App\Invoice\Entity\Profile;
use App\Invoice\Setting\SettingRepository as sR;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class ProfileController extends BaseController
{
    protected string $controllerName = 'invoice/profile';

    public function __construct(
        private ProfileService $profileService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->profileService = $profileService;
    }

    /**
     * @param ProfileRepository $profileRepository
     */
    public function index(CurrentRoute $currentRoute, ProfileRepository $profileRepository): \Yiisoft\DataResponse\DataResponse
    {
        $page = (int) $currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        $canEdit = $this->rbac();
        $this->flashMessage('info', $this->translator->translate('profile.new'));
        $paginator = (new OffsetPaginator($this->profiles($profileRepository)))
        ->withPageSize($this->sR->positiveListLimit())
        ->withCurrentPage($currentPageNeverZero);

        $parameters = [
            'canEdit' => $canEdit,
            'paginator' => $paginator,
            'profiles' => $this->profiles($profileRepository),
            'alert' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param CompanyRepository $companyRepository
     * @return Response
     */
    public function add(
        Request $request,
        FormHydrator $formHydrator,
        CompanyRepository $companyRepository,
    ): Response {
        $form = new ProfileForm(new Profile(), $this->translator);
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'profile/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'companies' => $companyRepository->findAllPreloaded(),
        ];

        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->profileService->saveProfile(new Profile(), $body);
                    return $this->webService->getRedirectResponse('profile/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param ProfileRepository $profileRepository
     * @param CompanyRepository $companyRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        ProfileRepository $profileRepository,
        CompanyRepository $companyRepository,
    ): Response {
        $profile = $this->profile($currentRoute, $profileRepository);
        if ($profile) {
            $form = new ProfileForm($profile, $this->translator);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'profile/edit',
                'actionArguments' => ['id' => $profile->getId()],
                'form' => $form,
                'errors' => [],
                'companies' => $companyRepository->findAllPreloaded(),
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        $this->profileService->saveProfile($profile, $body);
                        return $this->webService->getRedirectResponse('profile/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('profile/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ProfileRepository $profileRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        ProfileRepository $profileRepository,
    ): Response {
        try {
            $profile = $this->profile($currentRoute, $profileRepository);
            if ($profile) {
                if ($this->profileService->deleteProfile($profile)) {
                    $this->flashMessage('info', $this->translator->translate('profile.deleted'));
                } else {
                    $this->flashMessage('info', $this->translator->translate('profile.not.deleted'));
                }
            }
            return $this->webService->getRedirectResponse('profile/index');
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger', $this->translator->translate('profile.history'));
            return $this->webService->getRedirectResponse('profile/index');
        }
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ProfileRepository $profileRepository
     * @param CompanyRepository $companyRepository
     */
    public function view(CurrentRoute $currentRoute, ProfileRepository $profileRepository, CompanyRepository $companyRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $profile = $this->profile($currentRoute, $profileRepository);
        if ($profile) {
            $form = new ProfileForm($profile, $this->translator);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'profile/view',
                'actionArguments' => ['id' => $profile->getId()],
                'companies' => $companyRepository->findAllPreloaded(),
                'form' => $form,
                'errors' => [],
                'profile' => $profileRepository->repoProfilequery((string) $profile->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('profile/index');
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission(Permissions::EDIT_INV);
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse('profile/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ProfileRepository $profileRepository
     * @return Profile|null
     */
    private function profile(CurrentRoute $currentRoute, ProfileRepository $profileRepository): Profile|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $profileRepository->repoProfilequery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function profiles(ProfileRepository $profileRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $profileRepository->findAllPreloaded();
    }
}
