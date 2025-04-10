<?php

declare(strict_types=1);

namespace App\Invoice\AllowanceCharge;

use App\Invoice\BaseController;
use App\Invoice\Entity\AllowanceCharge;
use App\Invoice\Helpers\Peppol\PeppolArrays;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\TaxRate\TaxRateRepository;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Exception;

final class AllowanceChargeController extends BaseController
{
    protected string $controllerName = 'invoice';

    public function __construct(
        private AllowanceChargeService $allowanceChargeService,
        SessionInterface $session,
        sR $sR,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        TranslatorInterface $translator
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR);
        $this->allowanceChargeService = $allowanceChargeService;
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param TaxRateRepository $tR
     * @return Response
     */
    public function add_allowance(
        Request $request,
        FormHydrator $formHydrator,
        TaxRateRepository $tR
    ): Response {
        $allowanceCharge = new AllowanceCharge();
        $form = new AllowanceChargeForm($allowanceCharge);
        $peppolArrays = new PeppolArrays();
        $allowances = $peppolArrays->getAllowancesSubsetArray();
        $parameters = [
            'title' => $this->translator->translate('invoice.invoice.allowance.or.charge.add'),
            'actionName' => 'allowancecharge/add_allowance',
            'actionArguments' => [],
            'allowances' => $allowances,
            'errors' => [],
            'form' => $form,
            'taxRates' => $tR->findAllPreloaded(),
        ];

        /**
         * @var array $body
         */
        $body = $request->getParsedBody();
        // true => allowance; false => charge
        /**
         * @var bool $body['identifier']
         */
        $body['identifier'] = false;
        /**
         * @var string $body['reason']
         */
        $reason = $body['reason'] ?? '';
        /**
         * @var string $value
         */
        foreach ($allowances as $key => $value) {
            if ($value === $reason) {
                /**
                 * @var string $body['reason_code']
                 */
                $body['reason_code'] = $key;
            }
        }
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $this->allowanceChargeService->saveAllowanceCharge($allowanceCharge, $body);
                $this->flashMessage('info', $this->translator->translate('i.record_successfully_created'));
                return $this->webService->getRedirectResponse('allowancecharge/index');
            }
            $parameters['form'] = $form;
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        }
        return $this->viewRenderer->render('_form_allowance', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param TaxRateRepository $tR
     * @return Response
     */
    public function add_charge(
        Request $request,
        FormHydrator $formHydrator,
        TaxRateRepository $tR
    ): Response {
        $allowanceCharge = new AllowanceCharge();
        $form = new AllowanceChargeForm($allowanceCharge);
        $peppolArrays = new PeppolArrays();
        $charges = $peppolArrays->getChargesArray();
        $parameters = [
            'title' => $this->translator->translate('invoice.invoice.allowance.or.charge.add'),
            'actionName' => 'allowancecharge/add_charge',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'charges' => $charges,
            'taxRates' => $tR->findAllPreloaded(),
        ];
        /**
         * @var array $body
         */
        $body = $request->getParsedBody();
        // true => allowance; false => charge
        /**
         * @var bool $body['identifier']
         */
        $body['identifier'] = true;
        /**
         * @var string $body['reason']
         */
        $reason = $body['reason'] ?? '';
        /**
         * @var array $value
         * @var string $value[0]
         */
        foreach ($charges as $key => $value) {
            if ($value[0] === $reason) {
                /**
                 * @var string $body['reason_code']
                 */
                $body['reason_code'] = $key;
            }
        }
        if ($request->getMethod() === Method::POST) {
            $form = new AllowanceChargeForm($allowanceCharge);
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $this->allowanceChargeService->saveAllowanceCharge($allowanceCharge, $body);
                $this->flashMessage('info', $this->translator->translate('i.record_successfully_created'));
                return $this->webService->getRedirectResponse('allowancecharge/index');
            }
            $parameters['form'] = $form;
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        }
        return $this->viewRenderer->render('_form_charge', $parameters);
    }

    /**
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @return Response
     */
    public function index(AllowanceChargeRepository $allowanceChargeRepository): Response
    {
        $allowanceCharges = $allowanceChargeRepository->findAllPreloaded();
        $paginator = (new OffsetPaginator($allowanceCharges));
        $parameters = [
            'canEdit' => $this->userService->hasPermission('editInv') ? true : false,
            'allowanceCharges' => $this->allowanceCharges($allowanceChargeRepository),
            'alert' => $this->alert(),
            'paginator' => $paginator,
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        AllowanceChargeRepository $allowanceChargeRepository
    ): Response {
        try {
            $allowanceCharge = $this->allowanceCharge($currentRoute, $allowanceChargeRepository);
            if ($allowanceCharge) {
                $this->allowanceChargeService->deleteAllowanceCharge($allowanceCharge);
                $this->flashMessage('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->webService->getRedirectResponse('allowancecharge/index');
            }
            return $this->webService->getRedirectResponse('allowancecharge/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('allowancecharge/index');
        }
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @param TaxRateRepository $tR
     * @return Response
     */
    public function edit_allowance(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        AllowanceChargeRepository $allowanceChargeRepository,
        TaxRateRepository $tR
    ): Response {
        $allowanceCharge = $this->allowanceCharge($currentRoute, $allowanceChargeRepository);
        $body = $request->getParsedBody() ?? [];
        if (is_array($body)) {
            if (null !== $allowanceCharge) {
                $form = new AllowanceChargeForm($allowanceCharge);
                $peppolArrays = new PeppolArrays();
                $allowances = $peppolArrays->getAllowancesSubsetArray();
                $parameters = [
                    'title' => $this->translator->translate('invoice.invoice.allowance.or.charge.edit.allowance'),
                    'actionName' => 'allowancecharge/edit_allowance',
                    'actionArguments' => ['id' => $allowanceCharge->getId()],
                    'errors' => [],
                    'form' => $form,
                    'taxRates' => $tR->findAllPreloaded(),
                    'allowances' => $allowances,
                ];
                if ($request->getMethod() === Method::POST) {
                    if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                        $this->allowanceChargeService->saveAllowanceCharge($allowanceCharge, $body);
                        $this->flashMessage('info', $this->translator->translate('i.record_successfully_updated'));
                        return $this->webService->getRedirectResponse('allowancecharge/index');
                    }
                    $parameters['form'] = $form;
                    $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                }
                return $this->viewRenderer->render('_form_allowance', $parameters);
            }
        }
        return $this->webService->getRedirectResponse('allowancecharge/index');
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @param TaxRateRepository $tR
     * @return Response
     */
    public function edit_charge(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        AllowanceChargeRepository $allowanceChargeRepository,
        TaxRateRepository $tR
    ): Response {
        $allowanceCharge = $this->allowanceCharge($currentRoute, $allowanceChargeRepository);
        $body = $request->getParsedBody() ?? [];
        if (null !== $allowanceCharge) {
            $form = new AllowanceChargeForm($allowanceCharge);
            $peppolArrays = new PeppolArrays();
            $charges = $peppolArrays->getChargesArray();
            $parameters = [
                'title' => $this->translator->translate('invoice.invoice.allowance.or.charge.edit.charge'),
                'actionName' => 'allowancecharge/edit_allowance',
                'actionArguments' => ['id' => $allowanceCharge->getId()],
                'errors' => [],
                'form' => $form,
                'taxRates' => $tR->findAllPreloaded(),
                'charges' => $charges,
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new AllowanceChargeForm($allowanceCharge);
                if (is_array($body)) {
                    if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                        $this->allowanceChargeService->saveAllowanceCharge($allowanceCharge, $body);
                        $this->flashMessage('info', $this->translator->translate('i.record_successfully_updated'));
                        return $this->webService->getRedirectResponse('allowancecharge/index');
                    }
                    $parameters['form'] = $form;
                    $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                }
            }
            return $this->viewRenderer->render('_form_charge', $parameters);
        }
        return $this->webService->getRedirectResponse('allowancecharge/index');
    }

    //For rbac refer to AccessChecker

    /**
     * @param CurrentRoute $currentRoute
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @return AllowanceCharge|null
     */
    private function allowanceCharge(CurrentRoute $currentRoute, AllowanceChargeRepository $allowanceChargeRepository): AllowanceCharge|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $allowanceChargeRepository->repoAllowanceChargequery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function allowanceCharges(AllowanceChargeRepository $allowanceChargeRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $allowanceChargeRepository->findAllPreloaded();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(CurrentRoute $currentRoute, AllowanceChargeRepository $allowanceChargeRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $allowanceCharge = $this->allowanceCharge($currentRoute, $allowanceChargeRepository);
        if ($allowanceCharge) {
            $form = new AllowanceChargeForm($allowanceCharge);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'allowancecharge/view',
                'actionArguments' => ['id' => $allowanceCharge->getId()],
                'form' => $form,
                'allowanceCharge' => $allowanceCharge,
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('allowancecharge/index');
    }
}
