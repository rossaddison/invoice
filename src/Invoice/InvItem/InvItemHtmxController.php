<?php

declare(strict_types=1);

namespace App\Invoice\InvItem;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\InvItemAmount\InvItemAmountService as IIAS;
use App\Invoice\Setting\SettingRepository as SR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\DataResponse\ResponseFactory\HtmlResponseFactory;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class InvItemHtmxController extends BaseController
{
    protected string $controllerName = 'invoice/invitem';

    public function __construct(
        private readonly InvItemService $invItemService,
        private readonly HtmlResponseFactory $htmlResponseFactory,
        SessionInterface $session,
        SR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator,
            $webViewRenderer, $session, $sR, $flash);
    }

    public function addProduct(
        Request $request,
        FormHydrator $formHydrator,
        InvItemHtmxDependencies $d,
    ): Response {
        $inv_id = (int) $this->session->get('inv_id');
        $form = new InvItemForm();

        if ($request->getMethod() === Method::POST && $request->hasHeader('Hx-Request')) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body) && empty($body['order'])) {
                $body['order'] = (string) $d->iiR->repoInvquery($inv_id)->count();
                $request = $request->withParsedBody($body);
            }
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->invItemService->addInvItemProduct(
                        new InvItem(), $body, (string) $inv_id,
                        $d->pR, $d->trR, new IIAS($d->iiaR, $d->iiR), $d->iiaR, $this->sR, $d->uR,
                    );
                    return $this->renderPartial($inv_id, $d);
                }
            }
            return $this->htmlResponseFactory->createResponse('', 422);
        }

        return $this->webService->getRedirectResponse(
            'inv/view', ['id' => (string) $inv_id]
        );
    }

    public function addTask(
        Request $request,
        FormHydrator $formHydrator,
        InvItemHtmxDependencies $d,
    ): Response {
        $inv_id = (int) $this->session->get('inv_id');
        $form = new InvItemForm();

        if ($request->getMethod() === Method::POST && $request->hasHeader('Hx-Request')) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body) && empty($body['order'])) {
                $body['order'] = (string) $d->iiR->repoInvquery($inv_id)->count();
                $request = $request->withParsedBody($body);
            }
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->invItemService->addInvItemTask(
                        new InvItem(), $body, (string) $inv_id,
                        $d->taskR, $d->trR, new IIAS($d->iiaR, $d->iiR), $d->iiaR,
                    );
                    return $this->renderPartial($inv_id, $d);
                }
            }
            return $this->htmlResponseFactory->createResponse('', 422);
        }

        return $this->webService->getRedirectResponse(
            'inv/view', ['id' => (string) $inv_id]
        );
    }

    private function renderPartial(int $inv_id, InvItemHtmxDependencies $d): Response
    {
        $numberHelper = new NumberHelper($this->sR);
        $numberHelper->calculateInv(
            $inv_id, $d->aciR, $d->iiR, $d->iiaR, $d->itrR, $d->iaR, $d->iR, $d->pymR,
        );

        $invAmount = $d->iaR->repoInvAmountquery($inv_id);
        if ($invAmount === null) {
            return $this->htmlResponseFactory->createResponse()
                ->withHeader('HX-Refresh', 'true');
        }

        $inv = $d->iR->repoInvLoadedquery($inv_id);
        if ($inv === null) {
            return $this->htmlResponseFactory->createResponse()
                ->withHeader('HX-Refresh', 'true');
        }

        $draft        = $inv->reqStatusId() == '1';
        $showButtons  = !$inv->getIsReadOnly()
            || $this->sR->getSetting('disable_read_only') === '1';
        $userCanEdit  = $this->userService->hasPermission(Permissions::EDIT_INV);
        $invTaxRates  = $d->itrR->repoCount($inv_id) > 0
            ? $d->itrR->repoInvquery($inv_id)
            : null;

        $html = $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/partial_item_table',
            [
                'packHandleShipTotal' => $d->aciR->getPackHandleShipTotal($inv_id),
                'aciiR'               => $d->aciiR,
                'draft'               => $draft,
                'piR'                 => $d->piR,
                'showButtons'         => $showButtons,
                'included'            => $this->translator->translate('item.tax.included'),
                'excluded'            => $this->translator->translate('item.tax.excluded'),
                'products'            => $d->pR->findAllPreloadedWithPrice(),
                'tasks'               => $d->taskR->repoTaskStatusquery(3),
                'userCanEdit'         => $userCanEdit,
                'invItems'            => $d->iiR->repoInvquery($inv_id),
                'invItemAmountR'      => $d->iiaR,
                'invTaxRates'         => $invTaxRates,
                'invAmount'           => $invAmount,
                'inv'                 => $inv,
                'taxRates'            => $d->trR->findAllPreloaded(),
                'units'               => $d->uR->findAllPreloaded(),
                'numberHelper'        => $numberHelper,
            ],
        );
        return $this->htmlResponseFactory->createResponse($html);
    }
}
