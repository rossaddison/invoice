<?php

declare(strict_types=1);

namespace App\Invoice\DeliveryParty;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Infrastructure\Persistence\DeliveryParty\DeliveryParty;
use App\Invoice\Setting\SettingRepository as sR;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;
use Exception;

final class DeliveryPartyController extends BaseController
{
    protected string $controllerName = 'invoice/deliveryparty';

    public function __construct(
        private DeliveryPartyService $deliveryPartyService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        $this->deliveryPartyService = $deliveryPartyService;
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
        $form = new DeliveryPartyForm();
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('add'),
            'actionName' => 'deliveryparty/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->deliveryPartyService->saveDeliveryParty(new DeliveryParty(), $body);
                    return $this->webService->getRedirectResponse('deliveryparty/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->webViewRenderer->render('_form', $parameters);
    }

    /**
     * @param DeliveryPartyRepository $deliverypartyRepository
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
        return $this->webViewRenderer->render('index', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        DeliveryPartyRepository $deliverypartyRepository,
    ): Response {
        try {
            $deliveryparty = $this->deliveryparty($currentRoute, $deliverypartyRepository);
            if ($deliveryparty) {
                $this->deliveryPartyService->deleteDeliveryParty($deliveryparty);
                $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));
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
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        DeliveryPartyRepository $deliverypartyRepository,
    ): Response {
        $deliveryparty = $this->deliveryparty($currentRoute, $deliverypartyRepository);
        if ($deliveryparty) {
            $form = DeliveryPartyForm::show($deliveryparty);
            $parameters = [
                'canEdit' => $this->rbac(),
                'form' => $form,
                'title' => $this->translator->translate('edit'),
                'actionName' => 'deliveryparty/edit',
                'actionArguments' => ['id' => $deliveryparty->reqId()],
                'errors' => [],
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->deliveryPartyService->saveDeliveryParty($deliveryparty, $body);
                        return $this->webService->getRedirectResponse('deliveryparty/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->webViewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('deliveryparty/index');
    }

    //For rbac refer to AccessChecker

    /**
     * @param CurrentRoute $curR
     * @param DeliveryPartyRepository $dpR
     * @return DeliveryParty|null
     */
    private function deliveryparty(
        CurrentRoute $curR,
        DeliveryPartyRepository $dpR): ?DeliveryParty
    {
        return $dpR->repoDeliveryPartyquery((int) $curR->getArgument('id'));
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function deliveryparties(DeliveryPartyRepository $dpR): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $dpR->findAllPreloaded();
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission(Permissions::EDIT_INV);
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse('clientnote/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function view(CurrentRoute $currentRoute, DeliveryPartyRepository $deliverypartyRepository): \Psr\Http\Message\ResponseInterface
    {
        $deliveryparty = $this->deliveryparty($currentRoute, $deliverypartyRepository);
        if ($deliveryparty) {
            $form = DeliveryPartyForm::show($deliveryparty);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'deliveryparty/view',
                'actionArguments' => ['id' => $deliveryparty->reqId()],
                'form' => $form,
                'deliveryparty' => $deliveryparty,
            ];
            return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('deliveryparty/index');
    }
}
