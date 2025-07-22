<?php

declare(strict_types=1);

namespace App\Invoice\UnitPeppol;

use App\Invoice\BaseController;
use App\Invoice\Entity\Unit;
use App\Invoice\Entity\UnitPeppol;
use App\Invoice\Helpers\Peppol\Peppol_UNECERec20_11e;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Unit\UnitRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class UnitPeppolController extends BaseController
{
    protected string $controllerName = 'invoice/unitpeppol';

    public function __construct(
        private UnitPeppolService $unitpeppolService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->unitpeppolService = $unitpeppolService;
    }

    public function add(
        Request $request,
        FormHydrator $formHydrator,
        UnitRepository $unitRepository,
    ): Response {
        $enece = new Peppol_UNECERec20_11e();
        /** @var array $enece_array */
        $enece_array = $enece->getUNECERec20_11e();
        $units       = $unitRepository->findAllPreloaded();
        $unitPeppol  = new UnitPeppol();
        $form        = new UnitPeppolForm($unitPeppol);
        $parameters  = [
            'title'             => $this->translator->translate('unit.peppol.add'),
            'actionName'        => 'unitpeppol/add',
            'actionArguments'   => [],
            'form'              => $form,
            'errors'            => [],
            'eneces'            => $enece_array,
            'optionsDataEneces' => $this->optionsDataEneces($enece_array),
            'optionsDataUnits'  => $this->optionsDataUnits($units),
        ];

        if (Method::POST === $request->getMethod()) {
            $body = $request->getParsedBody() ?? [];
            /**
             * @var string $body['code']
             */
            $key = (int) $body['code'];
            /*
             *  @var array $enece_array[$key]
             *  @var string $enece_array[$key]['Name']
             *  @psalm-suppress PossiblyInvalidArrayAssignment $body['name']
             */
            $body['name'] = $enece_array[$key]['Name'];

            /**
             * @var string $enece_array[$key]['Description']
             * @var string $body['description']
             *
             * @psalm-suppress PossiblyInvalidArrayAssignment $body['description']
             */
            if (array_key_exists('Description', $enece_array[$key]) && !isset($body['description'])) {
                $body['description'] = $enece_array[$key]['Description'];
            }
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->unitpeppolService->saveUnitPeppol($unitPeppol, $body);

                    return $this->webService->getRedirectResponse('unitpeppol/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form']   = $form;
        }

        return $this->viewRenderer->render('_form', $parameters);
    }

    public function index(UnitPeppolRepository $unitpeppolRepository): Response
    {
        $paginator  = new OffsetPaginator($this->unitpeppols($unitpeppolRepository));
        $parameters = [
            'alert'        => $this->alert(),
            'unitpeppols'  => $this->unitpeppols($unitpeppolRepository),
            'grid_summary' => $this->sR->grid_summary(
                $paginator,
                $this->translator,
                (int) $this->sR->getSetting('default_list_limit'),
                $this->translator->translate('unit.peppol'),
                '',
            ),
            'paginator' => $paginator,
        ];

        return $this->viewRenderer->render('index', $parameters);
    }

    public function delete(
        CurrentRoute $currentRoute,
        UnitPeppolRepository $unitpeppolRepository,
    ): Response {
        try {
            $unitpeppol = $this->unitpeppol($currentRoute, $unitpeppolRepository);
            if ($unitpeppol) {
                $this->unitpeppolService->deleteUnitPeppol($unitpeppol);
                $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));

                return $this->webService->getRedirectResponse('unitpeppol/index');
            }

            return $this->webService->getRedirectResponse('unitpeppol/index');
        } catch (\Exception $e) {
            $this->flashMessage('danger', $e->getMessage());

            return $this->webService->getRedirectResponse('unitpeppol/index');
        }
    }

    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        UnitPeppolRepository $unitpeppolRepository,
        UnitRepository $unitRepository,
    ): Response {
        $unitPeppol  = $this->unitpeppol($currentRoute, $unitpeppolRepository);
        $units       = $unitRepository->findAllPreloaded();
        $enece       = new Peppol_UNECERec20_11e();
        $enece_array = $enece->getUNECERec20_11e();
        if ($unitPeppol) {
            $form       = new UnitPeppolForm($unitPeppol);
            $parameters = [
                'title'             => $this->translator->translate('edit'),
                'actionName'        => 'unitpeppol/edit',
                'actionArguments'   => ['id' => $unitPeppol->getId()],
                'eneces'            => $enece_array,
                'errors'            => [],
                'form'              => $form,
                'optionsDataEneces' => $this->optionsDataEneces($enece_array),
                'optionsDataUnits'  => $this->optionsDataUnits($units),
            ];
            if (Method::POST === $request->getMethod()) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        $this->unitpeppolService->saveUnitPeppol($unitPeppol, $body);

                        return $this->webService->getRedirectResponse('unitpeppol/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form']   = $form;
            }

            return $this->viewRenderer->render('_form', $parameters);
        }

        return $this->webService->getRedirectResponse('unitpeppol/index');
    }

    // For rbac refer to AccessChecker

    private function unitpeppol(CurrentRoute $currentRoute, UnitPeppolRepository $unitpeppolRepository): ?UnitPeppol
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $unitpeppolRepository->repoUnitPeppolLoadedquery($id);
        }

        return null;
    }

    /**
     * @psalm-return EntityReader
     */
    private function unitpeppols(UnitPeppolRepository $unitpeppolRepository): EntityReader
    {
        return $unitpeppolRepository->findAllPreloaded();
    }

    public function view(
        CurrentRoute $currentRoute,
        UnitRepository $unitRepository,
        UnitPeppolRepository $unitpeppolRepository,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $unitPeppol = $this->unitpeppol($currentRoute, $unitpeppolRepository);
        $units      = $unitRepository->findAllPreloaded();
        $enece      = new Peppol_UNECERec20_11e();
        $eneceArray = $enece->getUNECERec20_11e();
        if ($unitPeppol) {
            $form       = new UnitPeppolForm($unitPeppol);
            $parameters = [
                'title'             => $this->translator->translate('view'),
                'actionName'        => 'unitpeppol/view',
                'actionArguments'   => ['id' => $unitPeppol->getId()],
                'form'              => $form,
                'eneces'            => $eneceArray,
                'optionsDataEneces' => $this->optionsDataEneces($eneceArray),
                'optionsDataUnits'  => $this->optionsDataUnits($units),
            ];

            return $this->viewRenderer->render('_view', $parameters);
        }

        return $this->webService->getRedirectResponse('unitpeppol/index');
    }

    public function optionsDataEneces(array $eneces): array
    {
        $optionsDataEneces = [];
        /**
         * @var string $key
         * @var array  $value
         */
        foreach ($eneces as $key => $value) {
            /**
             * @var array  $eneces[$key]
             * @var string $eneces[$key]['Description']
             * @var string $eneces[$key]['Id']
             * @var string $eneces[$key]['Name']
             */
            $description = (array_key_exists('Description', $eneces[$key]) ? $eneces[$key]['Description'] : '');
            $cell        = ' '.$eneces[$key]['Id'].' -------- '.$eneces[$key]['Name'].' ------ '.$description;
            /*
             * @var int $value['Id']
             */
            $optionsDataEneces[$value['Id']] = $cell;
        }

        return $optionsDataEneces;
    }

    public function optionsDataUnits(EntityReader $units): array
    {
        $optionsDataUnits = [];
        /**
         * @var Unit $unit
         */
        foreach ($units as $unit) {
            $key                                    = $unit->getUnit_id();
            null !== $key ? $optionsDataUnits[$key] = $unit->getUnit_name().' '.$unit->getUnit_name_plrl() : '';
        }

        return $optionsDataUnits;
    }
}
