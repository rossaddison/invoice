<?php

declare(strict_types=1);

namespace App\Invoice\ItemLookup;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Invoice\Entity\ItemLookup;
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

final class ItemLookupController extends BaseController
{
    protected string $controllerName = 'invoice/itemlookup';

    public function __construct(
        private ItemLookupService $itemlookupService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator,
                                        $viewRenderer, $session, $sR, $flash);
        $this->itemlookupService = $itemlookupService;
    }

    /**
     * @param ItemLookupRepository $itemlookupRepository
     */
    public function index(ItemLookupRepository $itemlookupRepository):
                                            \Yiisoft\DataResponse\DataResponse
    {
        $itemLookups = $this->itemlookups($itemlookupRepository);
        $paginator = (new OffsetPaginator($itemLookups));
        $parameters = [
            'paginator' => $paginator,
            'alert' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(
        Request $request,
        FormHydrator $formHydrator,
    ): Response {
        $itemLookup = new ItemLookup();
        $form = new ItemLookupForm($itemLookup);
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'itemlookup/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->itemlookupService->saveItemLookup($itemLookup, $body);
                    return $this->webService->getRedirectResponse(
                                                            'itemlookup/index');
                }
            }
            $parameters['errors'] =
              $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param ItemLookupRepository $itemlookupRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        ItemLookupRepository $itemlookupRepository,
    ): Response {
        $lookup = $this->itemlookup($currentRoute, $itemlookupRepository);
        if (null !== $lookup) {
            $form = new ItemLookupForm($lookup);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'itemlookup/edit',
                'actionArguments' => ['id' => $lookup->getId()],
                'errors' => [],
                'form' => $form,
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->itemlookupService->saveItemLookup($lookup, $body);
                        return $this->webService->getRedirectResponse(
                                                            'itemlookup/index');
                    }
                }
                $parameters['errors'] =
                $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ItemLookupRepository $itemlookupRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        ItemLookupRepository $itemlookupRepository,
    ): Response {
        $lookup = $this->itemlookup($currentRoute, $itemlookupRepository);
        if ($lookup) {
            $this->itemlookupService->deleteItemLookup($lookup);
            return $this->webService->getRedirectResponse('itemlookup/index');
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ItemLookupRepository $itemlookupRepository
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(
        CurrentRoute $currentRoute,
        ItemLookupRepository $itemlookupRepository,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $itemLookup = $this->itemlookup($currentRoute, $itemlookupRepository);
        if (null !== $itemLookup) {
            $form = new ItemLookupForm($itemLookup);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'itemlookup/view',
                'actionArguments' => ['id' => $itemLookup->getId()],
                'form' => $form,
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getNotFoundResponse();
    }
   
    /**
     * @param CurrentRoute $currentRoute
     * @param ItemLookupRepository $itemlookupRepository
     * @return ItemLookup|null
     */
    private function itemlookup(CurrentRoute $currentRoute,
                        ItemLookupRepository $itemlookupRepository): ?ItemLookup
    {
        $itemlookup = new ItemLookup();
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $itemlookupRepository->repoItemLookupquery($id);
        }
        return $itemlookup;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function itemlookups(ItemLookupRepository $itemlookupRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $itemlookupRepository->findAllPreloaded();
    }
}
