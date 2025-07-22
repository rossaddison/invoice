<?php

declare(strict_types=1);

namespace App\Invoice\DeliveryParty;

use App\Invoice\BaseController;
use App\Invoice\Entity\DeliveryParty;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class DeliveryPartyController extends BaseController
{
    protected string $controllerName = 'invoice/deliveryparty';

    public function __construct(
        private DeliveryPartyService $deliveryPartyService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->deliveryPartyService = $deliveryPartyService;
    }

    public function add(
        Request $request,
        FormHydrator $formHydrator,
    ): Response {
        $deliveryParty = new DeliveryParty();
        $form          = new DeliveryPartyForm($deliveryParty);
        $parameters    = [
            'canEdit'         => $this->rbac(),
            'title'           => $this->translator->translate('add'),
            'actionName'      => 'deliveryparty/add',
            'actionArguments' => [],
            'errors'          => [],
            'form'            => $form,
        ];
        if (Method::POST === $request->getMethod()) {
            $body = $request->getParsedBody();
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->deliveryPartyService->saveDeliveryParty(new DeliveryParty(), $body);

                    return $this->webService->getRedirectResponse('deliveryparty/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form']   = $form;
        }

        return $this->viewRenderer->render('_form', $parameters);
    }

    public function index(DeliveryPartyRepository $deliverypartyRepository): Response
    {
        $deliveryparties = $this->deliveryparties($deliverypartyRepository);
        $paginator       = (new OffsetPaginator($deliveryparties));
        $parameters      = [
            'canEdit'         => $this->rbac(),
            'paginator'       => $paginator,
            'deliveryparties' => $deliveryparties,
            'alert'           => $this->alert(),
        ];

        return $this->viewRenderer->render('index', $parameters);
    }

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
        } catch (\Exception $e) {
            $this->flashMessage('danger', $e->getMessage());

            return $this->webService->getRedirectResponse('deliveryparty/index');
        }
    }

    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        DeliveryPartyRepository $deliverypartyRepository,
    ): Response {
        $deliveryparty = $this->deliveryparty($currentRoute, $deliverypartyRepository);
        if ($deliveryparty) {
            $form       = new DeliveryPartyForm($deliveryparty);
            $parameters = [
                'canEdit'         => $this->rbac(),
                'form'            => $form,
                'title'           => $this->translator->translate('edit'),
                'actionName'      => 'deliveryparty/edit',
                'actionArguments' => ['id' => $deliveryparty->getId()],
                'errors'          => [],
            ];
            if (Method::POST === $request->getMethod()) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->deliveryPartyService->saveDeliveryParty($deliveryparty, $body);

                        return $this->webService->getRedirectResponse('deliveryparty/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form']   = $form;
            }

            return $this->viewRenderer->render('_form', $parameters);
        }

        return $this->webService->getRedirectResponse('deliveryparty/index');
    }

    // For rbac refer to AccessChecker

    private function deliveryparty(CurrentRoute $currentRoute, DeliveryPartyRepository $deliverypartyRepository): ?DeliveryParty
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $deliverypartyRepository->repoDeliveryPartyquery($id);
        }

        return null;
    }

    /**
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
            $this->flashMessage('warning', $this->translator->translate('permission'));

            return $this->webService->getRedirectResponse('clientnote/index');
        }

        return $canEdit;
    }

    public function view(CurrentRoute $currentRoute, DeliveryPartyRepository $deliverypartyRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $deliveryparty = $this->deliveryparty($currentRoute, $deliverypartyRepository);
        if ($deliveryparty) {
            $form       = new DeliveryPartyForm($deliveryparty);
            $parameters = [
                'title'           => $this->translator->translate('view'),
                'actionName'      => 'deliveryparty/view',
                'actionArguments' => ['id' => $deliveryparty->getId()],
                'form'            => $form,
                'deliveryparty'   => $deliveryparty,
            ];

            return $this->viewRenderer->render('_view', $parameters);
        }

        return $this->webService->getRedirectResponse('deliveryparty/index');
    }
}
