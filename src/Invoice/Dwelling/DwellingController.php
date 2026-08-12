<?php

declare(strict_types=1);

namespace App\Invoice\Dwelling;

use App\Invoice\BaseController;
use App\Infrastructure\Persistence\Dwelling\Dwelling;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;
use Exception;

final class DwellingController extends BaseController
{
    protected string $controllerName = 'invoice/dwelling';

    public function __construct(
        private DwellingService $dwellingService,
        private DataResponseFactoryInterface $factory,
        SessionInterface $session,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        sR $sR,
        TranslatorInterface $translator,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        $this->dwellingService = $dwellingService;
        $this->factory = $factory;
    }

    public function add(
        Request $request,
        FormHydrator $formHydrator,
        FamilyRepository $familyRepository,
    ): Response {
        $dwelling = new Dwelling();
        $form = new DwellingForm();
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'dwelling/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'families' => $familyRepository->optionsDataAllFamilyNames(),
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $this->dwellingService->saveDwelling($dwelling, $body);
                    return $this->webService->getRedirectResponse('dwelling/index');
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
        }
        return $this->webViewRenderer->render('_form', $parameters);
    }

    public function index(
        DwellingRepository $dwellingRepository,
        sR $settingRepository,
        #[RouteArgument('page')]
        int $page = 1,
    ): Response {
        $dwellings = $dwellingRepository->findAllPreloaded();
        $currentPageNeverZero = $page > 0 ? $page : 1;
        $paginator = (new OffsetPaginator($dwellings))
            ->withPageSize($settingRepository->positiveListLimit())
            ->withCurrentPage($currentPageNeverZero)
            ->withToken(PageToken::next((string) $page));
        $parameters = [
            'dwellings' => $dwellings,
            'paginator' => $paginator,
            'alert' => $this->alert(),
            'defaultPageSizeOffsetPaginator' => $settingRepository->getSetting('default_list_limit')
                ? (int) $settingRepository->getSetting('default_list_limit') : 1,
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    public function delete(
        DwellingRepository $dwellingRepository,
        #[RouteArgument('id')]
        int $id,
    ): Response {
        try {
            $dwelling = $this->dwelling($dwellingRepository, $id);
            if ($dwelling) {
                $this->dwellingService->deleteDwelling($dwelling);
                $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));
                return $this->webService->getRedirectResponse('dwelling/index');
            }
            return $this->webService->getRedirectResponse('dwelling/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('dwelling/index');
        }
    }

    public function edit(
        Request $request,
        FormHydrator $formHydrator,
        DwellingRepository $dwellingRepository,
        FamilyRepository $familyRepository,
        #[RouteArgument('id')]
        int $id,
    ): Response {
        $dwelling = $this->dwelling($dwellingRepository, $id);
        if ($dwelling) {
            $form = DwellingForm::show($dwelling);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'dwelling/edit',
                'actionArguments' => ['id' => $id],
                'families' => $familyRepository->optionsDataAllFamilyNames(),
                'errors' => [],
                'form' => $form,
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                        $this->dwellingService->saveDwelling($dwelling, $body);
                        return $this->webService->getRedirectResponse('dwelling/index');
                    }
                    $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                    $parameters['form'] = $form;
                }
            }
            return $this->webViewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('dwelling/index');
    }

    public function view(
        DwellingRepository $dwellingRepository,
        FamilyRepository $familyRepository,
        #[RouteArgument('id')]
        int $id,
    ): Response {
        $dwelling = $this->dwelling($dwellingRepository, $id);
        if ($dwelling) {
            $form = DwellingForm::show($dwelling);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'dwelling/view',
                'actionArguments' => ['id' => $id],
                'form' => $form,
                'dwelling' => $dwelling,
                'families' => $familyRepository->optionsDataAllFamilyNames(),
            ];
            return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('dwelling/index');
    }

    private function dwelling(DwellingRepository $dwellingRepository, int $id): ?Dwelling
    {
        if ($id) {
            return $dwellingRepository->repoDwellingQuery($id);
        }
        return null;
    }
}
