<?php

declare(strict_types=1);

namespace App\Invoice\Profile;

use App\Invoice\Company\CompanyRepository;
use App\Invoice\Entity\Profile;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Traits\FlashMessage;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class ProfileController
{
    use FlashMessage;

    private Flash $flash;
    private ViewRenderer $viewRenderer;

    public function __construct(
        private Session $session,
        ViewRenderer $viewRenderer,
        private WebControllerService $webService,
        private UserService $userService,
        private ProfileService $profileService,
        private TranslatorInterface $translator
    ) {
        $this->flash = new Flash($this->session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/profile')
                                           ->withLayout('@views/layout/invoice.php');
    }

    /**
     * @param ProfileRepository $profileRepository
     * @param SettingRepository $settingRepository
     */
    public function index(CurrentRoute $currentRoute, ProfileRepository $profileRepository, SettingRepository $settingRepository): \Yiisoft\DataResponse\DataResponse
    {
        $page = (int)$currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        $canEdit = $this->rbac();
        $this->flashMessage('info', $this->translator->translate('invoice.profile.new'));
        $paginator = (new OffsetPaginator($this->profiles($profileRepository)))
        ->withPageSize($settingRepository->positiveListLimit())
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
        CompanyRepository $companyRepository
    ): Response {
        $form = new ProfileForm(new Profile(), $this->translator);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
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
     * @return string
     */
    private function alert(): string
    {
        return $this->viewRenderer->renderPartialAsString(
            '//invoice/layout/alert',
            [
                'flash' => $this->flash,
            ]
        );
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
        CompanyRepository $companyRepository
    ): Response {
        $profile = $this->profile($currentRoute, $profileRepository);
        if ($profile) {
            $form = new ProfileForm($profile, $this->translator);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
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
        ProfileRepository $profileRepository
    ): Response {
        try {
            $profile = $this->profile($currentRoute, $profileRepository);
            if ($profile) {
                if ($this->profileService->deleteProfile($profile)) {
                    $this->flashMessage('info', $this->translator->translate('invoice.profile.deleted'));
                } else {
                    $this->flashMessage('info', $this->translator->translate('invoice.profile.not.deleted'));
                }
            }
            return $this->webService->getRedirectResponse('profile/index');
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger', $this->translator->translate('invoice.profile.history'));
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
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'profile/view',
                'actionArguments' => ['id' => $profile->getId()],
                'companies' => $companyRepository->findAllPreloaded(),
                'form' => $form,
                'errors' => [],
                'profile' => $profileRepository->repoProfilequery((string)$profile->getId()),
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
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('invoice.permission'));
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
