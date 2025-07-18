<?php

declare(strict_types=1);

namespace App\Invoice\TaxRate;

use App\Invoice\BaseController;
use App\Invoice\Entity\TaxRate;
use App\Invoice\Enum\StoreCoveTaxType;
use App\Invoice\Helpers\Peppol\PeppolArrays;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Http\Method;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class TaxRateController extends BaseController
{
    protected string $controllerName = 'invoice/taxrate';

    public function __construct(
        private TaxRateService $taxRateService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->taxRateService = $taxRateService;
    }

    /**
     * @param int $page
     * @param TaxRateRepository $taxRateRepository
     */
    public function index(TaxRateRepository $taxRateRepository, #[Query('page')] int $page = null): \Yiisoft\DataResponse\DataResponse
    {
        $canEdit = $this->rbac();
        $parameters = [
            'taxrates' => $this->taxRates($taxRateRepository),
            'page' => $page > 0 ? $page : 1,
            'canEdit' => $canEdit,
            'alert' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(Request $request, FormHydrator $formHydrator): Response
    {
        $peppolArrays = new PeppolArrays();
        $taxRate = new TaxRate();
        $form = new TaxRateForm($taxRate);
        $parameters = [
            'title' => $this->translator->translate('tax.rate.add'),
            'actionName' => 'taxrate/add',
            'actionArguments' => [],
            'form' => $form,
            'errors' => [],
            'optionsDataPeppolTaxRateCode' => $this->optionsDataPeppolTaxRateCode($peppolArrays->getUncl5305()),
            'optionsDataStoreCoveTaxType' => $this->optionsDataStoreCoveTaxType(),
        ];
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody();
                if (is_array($body)) {
                    $this->taxRateService->saveTaxRate($taxRate, $body);
                    $this->flashMessage('success', $this->translator->translate('record.successfully.created'));
                }
                return $this->webService->getRedirectResponse('taxrate/index');
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('__form', $parameters);
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param SettingRepository $settingRepository
     * @param TaxRateRepository $taxrateRepository
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        TaxRateRepository $taxRateRepository,
        FormHydrator $formHydrator,
    ): Response {
        $taxRate = $this->taxRate($currentRoute, $taxRateRepository);
        $peppolArrays = new PeppolArrays();
        if ($taxRate) {
            $form = new TaxRateForm($taxRate);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'taxrate/edit',
                'actionArguments' => ['tax_rate_id' => $taxRate->getTaxRateId()],
                'form' => $form,
                'errors' => [],
                'optionsDataPeppolTaxRateCode' => $this->optionsDataPeppolTaxRateCode($peppolArrays->getUncl5305()),
                'optionsDataStoreCoveTaxType' => $this->optionsDataStoreCoveTaxType(),
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody();
                    if (is_array($body)) {
                        $this->taxRateService->saveTaxRate($taxRate, $body);
                        $this->flashMessage('success', $this->translator->translate('record.successfully.updated'));
                    }
                    return $this->webService->getRedirectResponse('taxrate/index');
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('__form', $parameters);
        }
        return $this->webService->getRedirectResponse('taxrate/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param TaxRateRepository $taxRateRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, TaxRateRepository $taxRateRepository): Response
    {
        try {
            $taxRate = $this->taxrate($currentRoute, $taxRateRepository);
            if ($taxRate) {
                $this->taxRateService->deleteTaxRate($taxRate);
            }
            return $this->webService->getRedirectResponse('taxrate/index');
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger', $this->translator->translate('tax.rate.history.exists'));
            return $this->webService->getRedirectResponse('taxrate/index');
        }
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param TaxRateRepository $taxRateRepository
     */
    public function view(
        CurrentRoute $currentRoute,
        TaxRateRepository $taxRateRepository,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $taxRate = $this->taxRate($currentRoute, $taxRateRepository);
        $peppolArrays = new PeppolArrays();
        if ($taxRate) {
            $form = new TaxRateForm($taxRate);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'taxrate/view',
                'actionArguments' => ['tax_rate_id' => $taxRate->getTaxRateId()],
                'form' => $form,
                'optionsDataPeppolTaxRateCode' => $this->optionsDataPeppolTaxRateCode($peppolArrays->getUncl5305()),
                'optionsDataStoreCoveTaxType' => $this->optionsDataStoreCoveTaxType(),
            ];
            return $this->viewRenderer->render('__view', $parameters);
        }
        return $this->webService->getRedirectResponse('taxrate/index');
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse('taxrate/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param TaxRateRepository $taxRateRepository
     * @return TaxRate|null
     */
    private function taxrate(CurrentRoute $currentRoute, TaxRateRepository $taxRateRepository): TaxRate|null
    {
        $tax_rate_id = $currentRoute->getArgument('tax_rate_id');
        if (null !== $tax_rate_id) {
            return $taxRateRepository->repoTaxRatequery($tax_rate_id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function taxRates(TaxRateRepository $taxRateRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $taxRateRepository->findAllPreloaded();
    }

    /**
     * @param array $peppolTaxRateCodeArray
     * @return array
     */
    private function optionsDataPeppolTaxRateCode(array $peppolTaxRateCodeArray): array
    {
        $optionsDataPeppolTaxRateCode = [];
        /**
         * @var array $value
         */
        foreach ($peppolTaxRateCodeArray as $key => $value) {
            /**
             * @var string $value['Id']
             * @var string $value['Name']
             * @var string $value['Description']
             */
            $optionsDataPeppolTaxRateCode[$value['Id']] = $value['Id'] . str_repeat('-', 10) . $value['Name'] . str_repeat('-', 10) . $value['Description'];
        }
        return $optionsDataPeppolTaxRateCode;
    }

    /**
     * @return array
     */
    private function optionsDataStoreCoveTaxType(): array
    {
        $optionsDataStoreCoveTaxType = [];
        foreach (array_column(StoreCoveTaxType::cases(), 'value') as $key => $value) {
            $optionsDataStoreCoveTaxType[$value] = str_replace('_', ' ', ucfirst($value));
        }
        return $optionsDataStoreCoveTaxType;
    }
}
