<?php

declare(strict_types=1);

namespace App\Invoice\DeliveryParty;

use App\Invoice\Entity\DeliveryParty;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Traits\FlashMessage;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Exception;

final class DeliveryPartyController
{
    use FlashMessage;

    private SessionInterface $session;
    private Flash $flash;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private DeliveryPartyService $deliverypartyService;
    private TranslatorInterface $translator;

    public function __construct(
        SessionInterface $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        DeliveryPartyService $deliverypartyService,
        TranslatorInterface $translator
    ) {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/deliveryparty')
                                           // The Controller layout dir is now redundant: replaced with an alias
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->deliverypartyService = $deliverypartyService;
        $this->translator = $translator;
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function add(
        Request $request,
        FormHydrator $formHydrator
    ): Response {
        $deliveryParty = new DeliveryParty();
        $form = new DeliveryPartyForm($deliveryParty);
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('i.add'),
            'actionName' => 'deliveryparty/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->deliverypartyService->saveDeliveryParty(new DeliveryParty(), $body);
                    return $this->webService->getRedirectResponse('deliveryparty/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }

    /**
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @param DeliveryPartyService $service
     * @return Response
     */
    public function index(DeliveryPartyRepository $deliverypartyRepository): Response
    {
        $deliveryparties = $this->deliveryparties($deliverypartyRepository);
        $paginator = (new OffsetPaginator($deliveryparties));
        $parameters = [
            'canEdit' => $this->rbac(),
            'paginator' => $paginator,
            'deliveryparties' => $deliveryparties,
            'alert' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        DeliveryPartyRepository $deliverypartyRepository
    ): Response {
        try {
            $deliveryparty = $this->deliveryparty($currentRoute, $deliverypartyRepository);
            if ($deliveryparty) {
                $this->deliverypartyService->deleteDeliveryParty($deliveryparty);
                $this->flashMessage('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->webService->getRedirectResponse('deliveryparty/index');
            }
            return $this->webService->getRedirectResponse('deliveryparty/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('deliveryparty/index');
        }
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        DeliveryPartyRepository $deliverypartyRepository
    ): Response {
        $deliveryparty = $this->deliveryparty($currentRoute, $deliverypartyRepository);
        if ($deliveryparty) {
            $form = new DeliveryPartyForm($deliveryparty);
            $parameters = [
                'canEdit' => $this->rbac(),
                'form' => $form,
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'deliveryparty/edit',
                'actionArguments' => ['id' => $deliveryparty->getId()],
                'errors' => [],
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->deliverypartyService->saveDeliveryParty($deliveryparty, $body);
                        return $this->webService->getRedirectResponse('deliveryparty/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('deliveryparty/index');
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

    //For rbac refer to AccessChecker

    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @return DeliveryParty|null
     */
    private function deliveryparty(CurrentRoute $currentRoute, DeliveryPartyRepository $deliverypartyRepository): DeliveryParty|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $deliverypartyRepository->repoDeliveryPartyquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function deliveryparties(DeliveryPartyRepository $deliverypartyRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $deliverypartyRepository->findAllPreloaded();
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('clientnote/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(CurrentRoute $currentRoute, DeliveryPartyRepository $deliverypartyRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $deliveryparty = $this->deliveryparty($currentRoute, $deliverypartyRepository);
        if ($deliveryparty) {
            $form = new DeliveryPartyForm($deliveryparty);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'deliveryparty/view',
                'actionArguments' => ['id' => $deliveryparty->getId()],
                'form' => $form,
                'deliveryparty' => $deliveryparty,
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('deliveryparty/index');
    }
}
