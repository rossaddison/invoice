<?php

declare(strict_types=1);

namespace App\Invoice\FromDropDown;

use App\Invoice\BaseController;
use App\Invoice\Entity\FromDropDown;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class FromDropDownController extends BaseController
{
    protected string $controllerName = 'invoice/fromdropdown';

    public function __construct(
        private FromDropDownService $fromService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->fromService = $fromService;
    }

    public function add(Request $request, FormHydrator $formHydrator): Response
    {
        $entity     = new FromDropDown();
        $form       = new FromDropDownForm($entity);
        $parameters = [
            'title'           => $this->translator->translate('add'),
            'actionName'      => 'from/add',
            'actionArguments' => [],
            'errors'          => [],
            'form'            => $form,
        ];
        if (Method::POST === $request->getMethod()) {
            $body = $request->getParsedBody() ?? [];
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->fromService->saveFromDropDown($entity, $body);

                    return $this->webService->getRedirectResponse('from/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form']   = $form;
        }

        return $this->viewRenderer->render('_form', $parameters);
    }

    public function index(CurrentRoute $currentRoute, FromDropDownRepository $fromRepository): Response
    {
        $page = (int) $currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        $from                 = $fromRepository->findAllPreloaded();
        $paginator            = (new OffsetPaginator($from))
            ->withPageSize($this->sR->positiveListLimit())
            ->withCurrentPage($currentPageNeverZero)
            ->withToken(PageToken::next((string) $page));
        $parameters = [
            'froms'     => $this->froms($fromRepository),
            'paginator' => $paginator,
            'alert'     => $this->alert(),
            'max'       => (int) $this->sR->getSetting('default_list_limit'),
        ];

        return $this->viewRenderer->render('index', $parameters);
    }

    public function delete(
        CurrentRoute $currentRoute,
        FromDropDownRepository $fromRepository,
    ): Response {
        try {
            $from = $this->from($currentRoute, $fromRepository);
            if ($from) {
                $this->fromService->deleteFromDropDown($from);
                $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));

                return $this->webService->getRedirectResponse('from/index');
            }

            return $this->webService->getRedirectResponse('from/index');
        } catch (\Exception $e) {
            $this->flashMessage('danger', $e->getMessage());

            return $this->webService->getRedirectResponse('from/index');
        }
    }

    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        FromDropDownRepository $fromRepository,
    ): Response {
        $from = $this->from($currentRoute, $fromRepository);
        if ($from) {
            $form       = new FromDropDownForm($from);
            $parameters = [
                'title'           => $this->translator->translate('edit'),
                'actionName'      => 'from/edit',
                'actionArguments' => ['id' => $from->getId()],
                'errors'          => [],
                'form'            => $form,
            ];
            if (Method::POST === $request->getMethod()) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->fromService->saveFromDropDown($from, $body);

                        return $this->webService->getRedirectResponse('from/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form']   = $form;
            }

            return $this->viewRenderer->render('_form', $parameters);
        }

        return $this->webService->getRedirectResponse('from/index');
    }

    // For rbac refer to AccessChecker

    private function from(CurrentRoute $currentRoute, FromDropDownRepository $fromRepository): ?FromDropDown
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $fromRepository->repoFromDropDownLoadedquery($id);
        }

        return null;
    }

    /**
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function froms(FromDropDownRepository $fromRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $fromRepository->findAllPreloaded();
    }

    public function view(CurrentRoute $currentRoute, FromDropDownRepository $fromRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $from = $this->from($currentRoute, $fromRepository);
        if ($from) {
            $form       = new FromDropDownForm($from);
            $parameters = [
                'title'           => $this->translator->translate('view'),
                'actionName'      => 'from/view',
                'actionArguments' => ['id' => $from->getId()],
                'errors'          => [],
                'form'            => $form,
                'from'            => $from,
            ];

            return $this->viewRenderer->render('_view', $parameters);
        }

        return $this->webService->getRedirectResponse('from/index');
    }
}
