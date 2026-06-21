<?php

declare(strict_types=1);

namespace App\Invoice\InvItemAllowanceCharge;

use App\Invoice\BaseController;
use App\Infrastructure\Persistence\InvItemAllowanceCharge\InvItemAllowanceCharge;
use App\Invoice\Helpers\CalcInvDeps;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\InvAmount\InvAmountService;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository;
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
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class InvItemAllowanceChargeController extends BaseController
{
    protected string $controllerName = 'invoice/invitemallowancecharge';

    public function __construct(
        private NumberHelper $numberHelper,
        private InvItemAllowanceChargeService $aciiService,
        private InvAmountService $invAmountService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        $this->numberHelper = $numberHelper;
        $this->aciiService = $aciiService;
        $this->invAmountService = $invAmountService;
    }

    public function add(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        IiacAddDeps $deps,
    ): Response {
        $inv_item_id = $currentRoute->getArgument('inv_item_id');
        $inv_item = $deps->iiR->repoInvItemquery((int) $inv_item_id);
        if (!$inv_item) {
            return $this->webService->getNotFoundResponse();
        }
        $inv_item_ac = new InvItemAllowanceCharge();
        $form = new InvItemAllowanceChargeForm();
        $inv_id = $inv_item->reqInvId();
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'invitemallowancecharge/add',
            'actionArguments' => ['inv_item_id' => $inv_item_id],
            'errors' => [],
            'form' => $form,
            'allowance_charges' => $deps->acR->findAllPreloaded(),
            'inv_item_id' => $inv_item_id,
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $body['inv_id'] = $inv_id;
                $body['inv_item_id'] = $inv_item_id;
                /** @var string $allowance_charge_id */
                $allowance_charge_id = $body['allowance_charge_id'];
                $allowance_charge = $deps->acR->repoAllowanceChargequery((int) $allowance_charge_id);
                if ($allowance_charge) {
                    $amount = (float) $body['amount'];
                    $percent = $allowance_charge->getTaxRate()?->getTaxRatePercent() ?? 0.00;
                    $vat = $amount * $percent / 100.00;
                    if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                        return $this->performAddSave($inv_item_ac, $body, $vat, $inv_id, $inv_item_id, $deps);
                    }
                    $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                    $parameters['form'] = $form;
                }
            }
        }
        return $this->webViewRenderer->render('_form', $parameters);
    }

    /**
     * Related logic: see ...www\invoice\resources\views\invoice\inv\partial_item_table.php search ... Make sure to fill
     */
    public function index(Request $request, InvItemAllowanceChargeRepository $iiacR): Response
    {
        $params = $request->getQueryParams();
        /** @var string $params['inv_item_id'] */
        $inv_item_id = $params['inv_item_id'] ?? '';
        $this->flashMessage('info', $this->translator->translate('peppol.allowance.or.charge.inherit.inv'));
        // retrieve all the allowances or charges associated with the inv_item_id
        $invoice_item_allowances_or_charges = $iiacR->repoInvItemquery((int) $inv_item_id);
        $paginator = (new OffsetPaginator($invoice_item_allowances_or_charges));
        $parameters = [
            'alert' => $this->alert(),
            'inv_item_id' => $inv_item_id,
            'paginator' => $paginator,
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    public function delete(
        CurrentRoute $currentRoute,
        IiacDeleteSubEntityDeps $subDeps,
        IiacDeleteFinancialDeps $financialDeps,
    ): Response {
        $acii = $this->acii($currentRoute, $subDeps->aciiR);
        if (null !== $acii) {
            $inv_id = $acii->reqInvId();
            // delete the inv item allowance/charge and update the related inv item amount record
            $this->aciiService->deleteInvItemAllowanceCharge($acii, $subDeps->iiaR, $subDeps->aciiR);
            // update the inv amount record
            $this->numberHelper->calculateInv($inv_id, new CalcInvDeps($subDeps->aciR, $subDeps->iiR, $subDeps->iiaR, $financialDeps->itrR, $financialDeps->iaR, $financialDeps->iR, $financialDeps->pymR));
            $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));
            return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
        }
        return $this->webService->getNotFoundResponse();
    }

    public function edit(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        IiacEditDeps $deps,
    ): Response {
        $acii = $this->acii($currentRoute, $deps->aciiR);
        if (!$acii) {
            return $this->webService->getRedirectResponse('index');
        }
        $inv_item_id = $acii->reqInvItemId();
        $inv_item = $acii->getInvItem();
        $inv_id = $inv_item?->reqInvId();
        $form = InvItemAllowanceChargeForm::show($acii, $inv_item_id);
        $parameters = [
            'title' => $this->translator->translate('edit'),
            'actionName' => 'invitemallowancecharge/edit',
            'actionArguments' => ['id' => $acii->reqId()],
            'errors' => [],
            'form' => $form,
            'allowance_charges' => $deps->acR->findAllPreloaded(),
            'inv_id' => $inv_id,
            'inv_item_id' => $inv_item_id,
        ];
        $body = $request->getParsedBody() ?? [];
        $response = null;
        if (is_array($body)) {
            $allowance_charge_id = (int) ($body['allowance_charge_id'] ?? 0);
            $amount = (float) ($body['amount'] ?? 0.00);
            if ($request->getMethod() === Method::POST) {
                if ($allowance_charge_id > 0) {
                    $ac = $deps->acR->repoAllowanceChargequery($allowance_charge_id);
                    if ($ac === null || !$formHydrator->populateFromPostAndValidate($form, $request)) {
                        $response = $this->webService->getNotFoundResponse();
                    } else {
                        $percent = $ac->getTaxRate()?->getTaxRatePercent() ?? 0.00;
                        $vat = $amount * $percent / 100.00;
                        $response = $this->performEditSave($acii, $body, $vat, $inv_id, $inv_item_id, $deps);
                    }
                } else {
                    $parameters['form'] = $form;
                }
            }
        }
        if ($response !== null) {
            return $response;
        }
        return $this->webViewRenderer->render('_form', $parameters);
    }

    /** @param array<array-key, mixed> $body */
    private function performAddSave(
        InvItemAllowanceCharge $inv_item_ac,
        array $body,
        float $vat,
        int $inv_id,
        ?string $inv_item_id,
        IiacAddDeps $deps,
    ): Response {
        $this->aciiService->saveInvItemAllowanceCharge($inv_item_ac, $body, $vat);
        $all_charges = 0.00;
        $all_charges_vat = 0.00;
        $all_allowances = 0.00;
        $all_allowances_vat = 0.00;
        $aciis = $deps->aciiR->repoInvItemquery((int) $inv_item_id);
        $inv_item_amount = $deps->iiaR->repoInvItemAmountquery((int) $inv_item_id);
        if (null !== $inv_item_amount) {
            /** @var InvItemAllowanceCharge $aciiItem */
            foreach ($aciis as $aciiItem) {
                if ($aciiItem->getAllowanceCharge()?->getIdentifier() == '1') {
                    $all_charges += (float) $aciiItem->getAmount();
                    $all_charges_vat += (float) $aciiItem->getVatOrTax();
                } else {
                    $all_allowances += (float) $aciiItem->getAmount();
                    $all_allowances_vat += (float) $aciiItem->getVatOrTax();
                }
            }
            $inv_item_amount->setCharge($all_charges);
            $inv_item_amount->setAllowance($all_allowances);
            $all_vat_or_tax = $all_charges_vat - $all_allowances_vat;
            $current_item_quantity = $inv_item_amount->getInvItem()?->getQuantity() ?? 0.00;
            $current_item_price = $inv_item_amount->getInvItem()?->getPrice() ?? 0.00;
            $discount_per_item = $inv_item_amount->getInvItem()?->getDiscountAmount() ?? 0.00;
            $quantity_price = $current_item_quantity * $current_item_price;
            $current_discount_item_total = $current_item_quantity * $discount_per_item;
            $qpIncAc = $quantity_price + $all_charges - $all_allowances;
            $tax_percent = $inv_item_amount->getInvItem()?->getTaxRate()?->getTaxRatePercent();
            $current_tax_total = ($quantity_price - $current_discount_item_total) * ($tax_percent ?? 0.00) / 100.00;
            $new_tax_total = $current_tax_total + $all_vat_or_tax;
            $inv_item_amount->setSubtotal($qpIncAc);
            $inv_item_amount->setDiscount($current_discount_item_total);
            $inv_item_amount->setTaxTotal($new_tax_total);
            $overall_total = $qpIncAc - $current_discount_item_total + $new_tax_total;
            $inv_item_amount->setTotal($overall_total);
            $deps->iiaR->save($inv_item_amount);
            $this->invAmountService->updateInvAmount($inv_id, $deps->iaR, $deps->iiaR, $deps->itrR, $this->numberHelper);
        }
        return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
    }

    /** @param array<array-key, mixed> $body */
    private function performEditSave(
        InvItemAllowanceCharge $acii,
        array $body,
        float $vat,
        ?int $inv_id,
        int $inv_item_id,
        IiacEditDeps $deps,
    ): Response {
        $this->aciiService->saveInvItemAllowanceCharge($acii, $body, $vat);
        $all_charges = 0.00;
        $all_allowances = 0.00;
        $all_allowances_vat = 0.00;
        $all_charges_vat = 0.00;
        $aciis = $deps->aciiR->repoInvItemquery($inv_item_id);
        $inv_item_amount = $deps->iiaR->repoInvItemAmountquery($inv_item_id);
        if (null !== $inv_item_amount) {
            /** @var InvItemAllowanceCharge $aciiItem */
            foreach ($aciis as $aciiItem) {
                if ($aciiItem->getAllowanceCharge()?->getIdentifier() == '1') {
                    $all_charges += (float) $aciiItem->getAmount();
                    $all_charges_vat += (float) $aciiItem->getVatOrTax();
                } else {
                    $all_allowances += (float) $aciiItem->getAmount();
                    $all_allowances_vat += (float) $aciiItem->getVatOrTax();
                }
            }
            $inv_item_amount->setCharge($all_charges);
            $inv_item_amount->setAllowance($all_allowances);
            $all_vat = $all_charges_vat - $all_allowances_vat;
            $current_item_quantity = $inv_item_amount->getInvItem()?->getQuantity() ?? 0.00;
            $current_item_price = $inv_item_amount->getInvItem()?->getPrice() ?? 0.00;
            $discount_per_item = $inv_item_amount->getInvItem()?->getDiscountAmount() ?? 0.00;
            $quantity_price = $current_item_quantity * $current_item_price;
            $current_discount_item_total = $current_item_quantity * $discount_per_item;
            $tax_percent = $inv_item_amount->getInvItem()?->getTaxRate()?->getTaxRatePercent();
            $qpIncAc = $quantity_price + $all_charges - $all_allowances;
            $current_tax_total = ($qpIncAc - $current_discount_item_total) * ($tax_percent ?? 0.00) / 100.00;
            $new_tax_total = $current_tax_total + ($this->sR->getSetting('enable_vat_registration') == '0' ? 0.00 : $all_vat);
            $inv_item_amount->setSubtotal($qpIncAc);
            $inv_item_amount->setDiscount($current_discount_item_total);
            $inv_item_amount->setTaxTotal($new_tax_total);
            $overall_total = $qpIncAc - $current_discount_item_total + $new_tax_total;
            $inv_item_amount->setTotal($overall_total);
            $deps->iiaR->save($inv_item_amount);
            $this->invAmountService->updateInvAmount((int) $inv_id, $deps->iaR, $deps->iiaR, $deps->itrR, $this->numberHelper);
        }
        return null !== $inv_item_amount
            ? $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id])
            : $this->webService->getNotFoundResponse();
    }

    //For rbac refer to AccessChecker

    private function acii(CurrentRoute $currentRoute, InvItemAllowanceChargeRepository $aciiRepository): ?InvItemAllowanceCharge
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $aciiRepository->repoInvItemAllowanceChargequery((int) $id);
        }
        return null;
    }

    public function view(
        CurrentRoute $currentRoute,
        InvItemAllowanceChargeRepository $aciiRepository,
        AllowanceChargeRepository $acR,
    ): \Psr\Http\Message\ResponseInterface {
        $acii = $this->acii($currentRoute, $aciiRepository);
        if ($acii) {
            $form = InvItemAllowanceChargeForm::show($acii, $acii->reqInvItemId());
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'invitemallowancecharge/view',
                'actionArguments' => ['id' => $acii->reqId()],
                'allowance_charges' => $acR->findAllPreloaded(),
                'form' => $form,
                'acii' => $acii,
            ];
            return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('acii/index');
    }
}
