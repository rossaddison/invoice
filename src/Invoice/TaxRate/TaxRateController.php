<?php

declare(strict_types=1);

namespace App\Invoice\TaxRate;

use App\Invoice\Entity\TaxRate;
use App\Invoice\Enum\StoreCoveTaxType;
use App\Invoice\Helpers\Peppol\PeppolArrays;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class TaxRateController
{
    private Flash $flash;
    private Session $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private TaxRateService $taxRateService;
    private UserService $userService;
    private Translator $translator;

    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        TaxRateService $taxRateService,
        UserService $userService,
        Translator $translator,
    ) {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/taxrate')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->taxRateService = $taxRateService;
        $this->userService = $userService;
        $this->translator = $translator;
    }

    /**
     * @param TaxRateRepository $taxRateRepository
     * @param SettingRepository $settingRepository
     * @param Request $request
     */
    public function index(TaxRateRepository $taxRateRepository, SettingRepository $settingRepository, Request $request): \Yiisoft\DataResponse\DataResponse
    {
        $pageNum = (int)$request->getAttribute('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $pageNum > 0 ? $pageNum : 1;
        $paginator = (new OffsetPaginator($this->taxrates($taxRateRepository)))
        ->withPageSize((int)$settingRepository->getSetting('default_list_limit'))
        ->withCurrentPage($currentPageNeverZero);

        $canEdit = $this->rbac();
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
    public function add(Request $request, FormHydrator $formHydrator): Response
    {
        $peppolArrays = new PeppolArrays();
        $taxRate = new TaxRate();
        $form = new TaxRateForm($taxRate);
        $parameters = [
            'title' => $this->translator->translate('invoice.tax.rate.add'),
            'actionName' => 'taxrate/add',
            'actionArguments' => [],
            'form' => $form,
            'errors' => [],
            'optionsDataPeppolTaxRateCode' => $this->optionsDataPeppolTaxRateCode($peppolArrays->getUncl5305()),
            'optionsDataStoreCoveTaxType' => $this->optionsDataStoreCoveTaxType()
        ];
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody();
                if (is_array($body)) {
                    $this->taxRateService->saveTaxRate($taxRate, $body);
                    $this->flashMessage('success', $this->translator->translate('i.record_successfully_created'));
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
        FormHydrator $formHydrator
    ): Response {
        $taxRate = $this->taxRate($currentRoute, $taxRateRepository);
        $peppolArrays = new PeppolArrays();
        if ($taxRate) {
            $form = new TaxRateForm($taxRate);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'taxrate/edit',
                'actionArguments' => ['tax_rate_id' => $taxRate->getTaxRateId()],
                'form' => $form,
                'errors' => [],
                'optionsDataPeppolTaxRateCode' => $this->optionsDataPeppolTaxRateCode($peppolArrays->getUncl5305()),
                'optionsDataStoreCoveTaxType' => $this->optionsDataStoreCoveTaxType()
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody();
                    if (is_array($body)) {
                        $this->taxRateService->saveTaxRate($taxRate, $body);
                        $this->flashMessage('success', $this->translator->translate('i.record_successfully_updated'));
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
            $this->flashMessage('danger', $this->translator->translate('invoice.tax.rate.history.exists'));
            return $this->webService->getRedirectResponse('taxrate/index');
        }
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param TaxRateRepository $taxRateRepository
     */
    public function view(
        CurrentRoute $currentRoute,
        TaxRateRepository $taxRateRepository
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $taxRate = $this->taxRate($currentRoute, $taxRateRepository);
        $peppolArrays = new PeppolArrays();
        if ($taxRate) {
            $form = new TaxRateForm($taxRate);
            $parameters = [
                'title' =>  $this->translator->translate('i.view'),
                'actionName' => 'taxrate/view',
                'actionArguments' => ['tax_rate_id' => $taxRate->getTaxRateId()],
                'form' => $form,
                'optionsDataPeppolTaxRateCode' => $this->optionsDataPeppolTaxRateCode($peppolArrays->getUncl5305()),
                'optionsDataStoreCoveTaxType' => $this->optionsDataStoreCoveTaxType()
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
            $this->flashMessage('warning', $this->translator->translate('invoice.permission'));
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
            $taxRate = $taxRateRepository->repoTaxRatequery($tax_rate_id);
            return $taxRate;
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
        $taxRates = $taxRateRepository->findAllPreloaded();
        return $taxRates;
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

    /**
     * @param string $level
     * @param string $message
     * @return Flash|null
     */
    private function flashMessage(string $level, string $message): Flash|null
    {
        if (strlen($message) > 0) {
            $this->flash->add($level, $message, true);
            return $this->flash;
        }
        return null;
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
            $optionsDataPeppolTaxRateCode[$value['Id']] = $value['Id'] . str_repeat("-", 10) . $value['Name'] . str_repeat("-", 10) . $value['Description'];
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
