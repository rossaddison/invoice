<?php

declare(strict_types=1);

namespace App\Invoice\FromDropDown;

use App\Invoice\Entity\FromDropDown;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Traits\FlashMessage;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Exception;

final class FromDropDownController
{
    use FlashMessage;

    private Flash $flash;
    private ViewRenderer $viewRenderer;

    public function __construct(
        private Session $session,
        ViewRenderer $viewRenderer,
        private WebControllerService $webService,
        private UserService $userService,
        private FromDropDownService $fromService,
        private TranslatorInterface $translator
    ) {
        $this->flash = new Flash($this->session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/from')
                                           // The Controller layout dir is now redundant: replaced with an alias
                                           ->withLayout('@views/layout/invoice.php');
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(Request $request, FormHydrator $formHydrator): Response
    {
        $entity = new FromDropDown();
        $form = new FromDropDownForm($entity);
        $parameters = [
            'title' => $this->translator->translate('i.add'),
            'actionName' => 'from/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->fromService->saveFromDropDown($entity, $body);
                    return $this->webService->getRedirectResponse('from/index');
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

    public function index(CurrentRoute $currentRoute, FromDropDownRepository $fromRepository, SettingRepository $settingRepository): Response
    {
        $page = (int)$currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        $from = $fromRepository->findAllPreloaded();
        $paginator = (new OffsetPaginator($from))
        ->withPageSize($settingRepository->positiveListLimit())
        ->withCurrentPage($currentPageNeverZero)
        ->withToken(PageToken::next((string)$page));
        $parameters = [
            'froms' => $this->froms($fromRepository),
            'paginator' => $paginator,
            'alert' => $this->alert(),
            'max' => (int) $settingRepository->getSetting('default_list_limit'),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param FromDropDownRepository $fromRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        FromDropDownRepository $fromRepository
    ): Response {
        try {
            $from = $this->from($currentRoute, $fromRepository);
            if ($from) {
                $this->fromService->deleteFromDropDown($from);
                $this->flashMessage('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->webService->getRedirectResponse('from/index');
            }
            return $this->webService->getRedirectResponse('from/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('from/index');
        }
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param FromDropDownRepository $fromRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        FromDropDownRepository $fromRepository
    ): Response {
        $from = $this->from($currentRoute, $fromRepository);
        if ($from) {
            $form = new FromDropDownForm($from);
            $parameters = [
                'title' => $this->translator->translate('invoice.edit'),
                'actionName' => 'from/edit',
                'actionArguments' => ['id' => $from->getId()],
                'errors' => [],
                'form' => $form,
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->fromService->saveFromDropDown($from, $body);
                        return $this->webService->getRedirectResponse('from/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('from/index');
    }

    //For rbac refer to AccessChecker

    /**
     * @param CurrentRoute $currentRoute
     * @param FromDropDownRepository $fromRepository
     * @return FromDropDown|null
     */
    private function from(CurrentRoute $currentRoute, FromDropDownRepository $fromRepository): FromDropDown|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $fromRepository->repoFromDropDownLoadedquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function froms(FromDropDownRepository $fromRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $fromRepository->findAllPreloaded();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param FromDropDownRepository $fromRepository
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(CurrentRoute $currentRoute, FromDropDownRepository $fromRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $from = $this->from($currentRoute, $fromRepository);
        if ($from) {
            $form = new FromDropDownForm($from);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'from/view',
                'actionArguments' => ['id' => $from->getId()],
                'errors' => [],
                'form' => $form,
                'from' => $from,
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('from/index');
    }
}
