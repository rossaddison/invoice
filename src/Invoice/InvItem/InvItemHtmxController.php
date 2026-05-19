<?php

declare(strict_types=1);

namespace App\Invoice\InvItem;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvItemAmount\InvItemAmountService as IIAS;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Payment\PaymentRepository as PYMR;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\ProductImage\ProductImageRepository as PIR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Task\TaskRepository as TaskR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UR;
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
        PR $pR,
        UR $uR,
        TRR $trR,
        IIR $iiR,
        IIAR $iiaR,
        IAR $iaR,
        IR $iR,
        ITRR $itrR,
        ACIR $aciR,
        ACIIR $aciiR,
        PIR $piR,
        TaskR $taskR,
        PYMR $pymR,
    ): Response {
        $inv_id = (int) $this->session->get('inv_id');
        $form = new InvItemForm();

        if ($request->getMethod() === Method::POST && $request->hasHeader('Hx-Request')) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body) && empty($body['order'])) {
                $body['order'] = (string) $iiR->repoInvquery($inv_id)->count();
                $request = $request->withParsedBody($body);
            }
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->invItemService->addInvItemProduct(
                        new InvItem(), $body, (string) $inv_id,
                        $pR, $trR, new IIAS($iiaR, $iiR), $iiaR, $this->sR, $uR,
                    );
                    return $this->renderPartial(
                        $inv_id, $pR, $uR, $trR, $iiR, $iiaR, $iaR,
                        $iR, $itrR, $aciR, $aciiR, $piR, $taskR, $pymR,
                    );
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
        TaskR $taskR,
        TRR $trR,
        IIR $iiR,
        IIAR $iiaR,
        IAR $iaR,
        IR $iR,
        ITRR $itrR,
        ACIR $aciR,
        ACIIR $aciiR,
        PIR $piR,
        PR $pR,
        UR $uR,
        PYMR $pymR,
    ): Response {
        $inv_id = (int) $this->session->get('inv_id');
        $form = new InvItemForm();

        if ($request->getMethod() === Method::POST && $request->hasHeader('Hx-Request')) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body) && empty($body['order'])) {
                $body['order'] = (string) $iiR->repoInvquery($inv_id)->count();
                $request = $request->withParsedBody($body);
            }
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->invItemService->addInvItemTask(
                        new InvItem(), $body, (string) $inv_id,
                        $taskR, $trR, new IIAS($iiaR, $iiR), $iiaR,
                    );
                    return $this->renderPartial(
                        $inv_id, $pR, $uR, $trR, $iiR, $iiaR, $iaR,
                        $iR, $itrR, $aciR, $aciiR, $piR, $taskR, $pymR,
                    );
                }
            }
            return $this->htmlResponseFactory->createResponse('', 422);
        }

        return $this->webService->getRedirectResponse(
            'inv/view', ['id' => (string) $inv_id]
        );
    }

    private function renderPartial(
        int $inv_id,
        PR $pR,
        UR $uR,
        TRR $trR,
        IIR $iiR,
        IIAR $iiaR,
        IAR $iaR,
        IR $iR,
        ITRR $itrR,
        ACIR $aciR,
        ACIIR $aciiR,
        PIR $piR,
        TaskR $taskR,
        PYMR $pymR,
    ): Response {
        $numberHelper = new NumberHelper($this->sR);
        $numberHelper->calculateInv($inv_id, $aciR, $iiR, $iiaR, $itrR, $iaR, $iR, $pymR);

        $invAmount = $iaR->repoInvAmountquery($inv_id);
        if ($invAmount === null) {
            return $this->htmlResponseFactory->createResponse()
                ->withHeader('HX-Refresh', 'true');
        }

        $inv = $iR->repoInvLoadedquery($inv_id);
        if ($inv === null) {
            return $this->htmlResponseFactory->createResponse()
                ->withHeader('HX-Refresh', 'true');
        }

        $draft        = $inv->reqStatusId() == '1';
        $showButtons  = !$inv->getIsReadOnly()
            || $this->sR->getSetting('disable_read_only') === '1';
        $userCanEdit  = $this->userService->hasPermission(Permissions::EDIT_INV);
        $invTaxRates  = $itrR->repoCount($inv_id) > 0
            ? $itrR->repoInvquery($inv_id)
            : null;

        $html = $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/partial_item_table',
            [
                'packHandleShipTotal' => $aciR->getPackHandleShipTotal($inv_id),
                'aciiR'               => $aciiR,
                'draft'               => $draft,
                'piR'                 => $piR,
                'showButtons'         => $showButtons,
                'included'            => $this->translator->translate('item.tax.included'),
                'excluded'            => $this->translator->translate('item.tax.excluded'),
                'products'            => $pR->findAllPreloadedWithPrice(),
                'tasks'               => $taskR->repoTaskStatusquery(3),
                'userCanEdit'         => $userCanEdit,
                'invItems'            => $iiR->repoInvquery($inv_id),
                'invItemAmountR'      => $iiaR,
                'invTaxRates'         => $invTaxRates,
                'invAmount'           => $invAmount,
                'inv'                 => $inv,
                'taxRates'            => $trR->findAllPreloaded(),
                'units'               => $uR->findAllPreloaded(),
                'numberHelper'        => $numberHelper,
            ],
        );
        return $this->htmlResponseFactory->createResponse($html);
    }
}
