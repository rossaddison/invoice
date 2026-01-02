<?php

declare(strict_types=1);

namespace App\Invoice\QuoteAllowanceCharge;

use App\Invoice\BaseController;
use App\Invoice\Entity\QuoteAllowanceCharge;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository;
use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeRepository as acqR;
use App\Invoice\Setting\SettingRepository as sR;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Http\Method;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Exception;

final class QuoteAllowanceChargeController extends BaseController
{
    protected string $controllerName = 'invoice/quoteallowancecharge';

    public function __construct(
        private QuoteAllowanceChargeService $qacService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator,
            $viewRenderer, $session, $sR, $flash);
        $this->qacService = $qacService;
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @return Response
     */
    public function add(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        AllowanceChargeRepository $allowanceChargeRepository,
    ): Response {
        $quoteAllowanceCharge = new QuoteAllowanceCharge();
        $quote_id = $currentRoute->getArgument('quote_id');
        $form = new QuoteAllowanceChargeForm($quoteAllowanceCharge,
                (int) $quote_id);
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'quoteallowancecharge/add',
            'actionArguments' => ['quote_id' => $quote_id],
            'errors' => [],
            'form' => $form,
            'optionsDataAllowanceCharges' =>
                $allowanceChargeRepository->optionsDataAllowanceCharges(),
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $body['quote_id'] = $quote_id;
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $this->qacService->saveQuoteAllowanceCharge(
                        $quoteAllowanceCharge, $body);
                    // Redirect to the quote / view after adding an overall
                    // allowance or charge
                    // The quote view will automatically recalculate the totals
                    // using NumberHelper calculate_quote
                    return $this->webService->getRedirectResponse('quote/view',
                        ['id' => $quote_id]);
                }
                $parameters['errors'] =
                $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            } // is_array
        }
        return $this->viewRenderer->render('modal_add_allowance_charge_form',
            $parameters);
    }

    public function index(
        acqR $acqR,
        #[RouteArgument('_language')]
        string $_language,
        #[RouteArgument('page')]
        string $page = '1',
        #[Query('page')]
        ?string $queryPage = null,
        #[Query('sort')]
        ?string $querySort = null,
        #[Query('filterQuoteNumber')]
        ?string $queryFilterQuoteNumber = null,
        #[Query('filterReasonCode')]
        ?string $queryFilterReasonCode = null,
        #[Query('filterReason')]
        ?string $queryFilterReason = null,
    ): Response {
        // If the language dropdown changes
        $this->session->set('_language', $_language);
        $quoteAllowanceCharges = $acqR->findAllPreloaded();
        if (isset($queryFilterReasonCode) && !empty($queryFilterReasonCode)) {
            $quoteAllowanceCharges =
                $acqR->repoReasonCodeQuery($queryFilterReasonCode);
        }
        if (isset($queryFilterReason) && !empty($queryFilterReason)) {
            $quoteAllowanceCharges =
                $acqR->repoReasonQuery($queryFilterReason);
        }
        if (isset($queryFilterQuoteNumber) && !empty($queryFilterQuoteNumber)) {
            $quoteAllowanceCharges =
                $acqR->repoQuoteNumberQuery($queryFilterQuoteNumber);
        }
        $page = $queryPage ?? $page;
        $parameters = [
            'defaultPageSizeOffsetPaginator' =>
                (int) $this->sR->getSetting('default_list_limit') ?: 1,
            'quoteAllowanceCharges' => $quoteAllowanceCharges,
            'optionsDataQuoteNumberDropDownFilter' =>
                $this->optionsDataQuoteNumberFilter($acqR),
            'page' => (int) $page > 0 ? (int) $page : 1,
            'sortString' => $querySort ?? '-id',
            'alert' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param acqR $acqR
     * @return array
     */
    public function optionsDataQuoteNumberFilter(acqR $acqR): array
    {
        $optionsDataQuoteNumbers = [];
        $acqs = $acqR->findAllPreloaded();
        /**
         * @var QuoteAllowanceCharge $quoteAllowanceCharge
         */
        foreach ($acqs as $quoteAllowanceCharge) {
            $quoteNumber = $quoteAllowanceCharge->getQuote()?->getNumber();
            if (null !== $quoteNumber) {
                if (!in_array($quoteNumber, $optionsDataQuoteNumbers)) {
                    $optionsDataQuoteNumbers[$quoteNumber] = $quoteNumber;
                }
            }
        }
        return $optionsDataQuoteNumbers;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param acqR $acqR
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        acqR $acqR,
    ): Response {
        try {
            $quoteAllowanceCharge =
                $this->quoteallowancecharge($currentRoute, $acqR);
            if ($quoteAllowanceCharge) {
                $quoteId = $quoteAllowanceCharge->getId();
                $this->qacService->deleteQuoteAllowanceCharge(
                    $quoteAllowanceCharge);
                $this->flashMessage('info', $this->translator->translate(
                    'record.successfully.deleted'));
                return $this->webService->getRedirectResponse('quote/view',
                    ['id' => $quoteId]);
            }
            return $this->webService->getRedirectResponse(
                'quoteallowancecharge/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse(
                'quoteallowancecharge/index');
        }
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @param acqR $acqR
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        AllowanceChargeRepository $allowanceChargeRepository,
        acqR $acqR,
    ): Response {
        $quoteAllowanceCharge = $this->quoteallowancecharge($currentRoute, $acqR);
        if ($quoteAllowanceCharge) {
            $quote_id = $quoteAllowanceCharge->getQuote_id();
            $form = new QuoteAllowanceChargeForm($quoteAllowanceCharge,
                (int) $quote_id);
            $parameters = [
                'title' => $this->translator->translate('allowance.or.charge'),
                'actionName' => 'quoteallowancecharge/edit',
                'actionArguments' => ['id' => $quoteAllowanceCharge->getId()],
                'errors' => [],
                'form' => $form,
                'optionsDataAllowanceCharges' =>
                    $allowanceChargeRepository->optionsDataAllowanceCharges(),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->qacService->saveQuoteAllowanceCharge(
                            $quoteAllowanceCharge, $body);
                        return $this->webService->getRedirectResponse(
                            'quoteallowancecharge/index');
                    }
                }
                $parameters['errors'] =
                $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse(
            'quoteallowancecharge/index');
    }

    //For rbac refer to AccessChecker

    /**
     * @param CurrentRoute $currentRoute
     * @param QuoteAllowanceChargeRepository $qacRepository
     * @return QuoteAllowanceCharge|null
     */
    private function quoteallowancecharge(CurrentRoute $currentRoute,
        QuoteAllowanceChargeRepository $qacRepository):
            ?QuoteAllowanceCharge
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $qacRepository->repoQuoteAllowanceChargeLoadedquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function quoteallowancecharges(acqR $acqR):
        \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $acqR->findAllPreloaded();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param acqR $acqR
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(
        CurrentRoute $currentRoute,
        acqR $acqR,
        AllowanceChargeRepository $allowanceChargeRepository,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $quoteAllowanceCharge = $this->quoteallowancecharge(
            $currentRoute, $acqR);
        if ($quoteAllowanceCharge) {
            $quote_id = $quoteAllowanceCharge->getQuote_id();
            $form = new QuoteAllowanceChargeForm($quoteAllowanceCharge,
                (int) $quote_id);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'quoteallowancecharge/view',
                'actionArguments' => ['id' => $quoteAllowanceCharge->getId()],
                'form' => $form,
                'optionsDataAllowanceCharges' =>
                    $allowanceChargeRepository->optionsDataAllowanceCharges(),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('quoteallowancecharge/index');
    }
}
