<?php

declare(strict_types=1);

namespace App\Invoice\ItemLookup;

use App\Invoice\Entity\ItemLookup;
use App\Invoice\ItemLookup\ItemLookupService;
use App\Invoice\ItemLookup\ItemLookupRepository;
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

final class ItemLookupController
{
    use FlashMessage;
    
    private Session $session;
    private Flash $flash;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private ItemLookupService $itemlookupService;
    private TranslatorInterface $translator;

    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        ItemLookupService $itemlookupService,
        TranslatorInterface $translator
    ) {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/itemlookup')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->itemlookupService = $itemlookupService;
        $this->translator = $translator;
    }

    /**
     * @param ItemLookupRepository $itemlookupRepository
     */
    public function index(ItemLookupRepository $itemlookupRepository, SettingRepository $sR): \Yiisoft\DataResponse\DataResponse
    {
        $canEdit = $this->rbac();
        $itemLookups = $this->itemlookups($itemlookupRepository);
        $paginator = (new OffsetPaginator($itemLookups));
        $parameters = [
         'paginator' => $paginator,
         'canEdit' => $canEdit,
         'alert' => $this->alert()
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
        FormHydrator $formHydrator
    ): Response {
        $itemLookup = new ItemLookup();
        $form = new ItemLookupForm($itemLookup);
        $parameters = [
          'title' => $this->translator->translate('invoice.add'),
          'actionName' => 'itemlookup/add',
          'actionArguments' => [],
          'errors' => [],
          'form' => $form
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->itemlookupService->saveItemLookup($itemLookup, $body);
                    return $this->webService->getRedirectResponse('itemlookup/index');
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
     * @param ItemLookupRepository $itemlookupRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        ItemLookupRepository $itemlookupRepository
    ): Response {
        $lookup = $this->itemlookup($currentRoute, $itemlookupRepository);
        if (null !== $lookup) {
            $form = new ItemLookupForm($lookup);
            $parameters = [
              'title' => $this->translator->translate('i.edit'),
              'actionName' => 'itemlookup/edit',
              'actionArguments' => ['id' => $lookup->getId()],
              'errors' => [],
              'form' => $form
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->itemlookupService->saveItemLookup($lookup, $body);
                        return $this->webService->getRedirectResponse('itemlookup/index');
                    }
                }    
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     *
     * @param CurrentRoute $currentRoute
     * @param ItemLookupRepository $itemlookupRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        ItemLookupRepository $itemlookupRepository
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
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(
        CurrentRoute $currentRoute,
        ItemLookupRepository $itemlookupRepository
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $itemLookup = $this->itemlookup($currentRoute, $itemlookupRepository);
        if (null !== $itemLookup) {
            $form = new ItemLookupForm($itemLookup);
            $parameters = [
              'title' => $this->translator->translate('i.view'),
              'actionName' => 'itemlookup/view',
              'actionArguments' => ['id' => $itemLookup->getId()],
              'form' => $form,
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('itemlookup/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ItemLookupRepository $itemlookupRepository
     * @return ItemLookup|null
     */
    private function itemlookup(CurrentRoute $currentRoute, ItemLookupRepository $itemlookupRepository): ItemLookup|null
    {
        $itemlookup = new ItemLookup();
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            $itemlookup = $itemlookupRepository->repoItemLookupquery($id);
            return $itemlookup;
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
        $itemlookups = $itemlookupRepository->findAllPreloaded();
        return $itemlookups;
    }

    /**
     * @return string
     */
    private function alert(): string
    {
        return $this->viewRenderer->renderPartialAsString(
            '//invoice/layout/alert',
            [
         'flash' => $this->flash
       ]
        );
    }
}
