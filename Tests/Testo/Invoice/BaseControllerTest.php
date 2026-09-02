<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Invoice\BaseController;
use App\Invoice\CustomField\CustomFieldRepository;
use App\Invoice\CustomFieldProcessor;
use App\Invoice\CustomValue\CustomValueRepository;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use Mockery as m;
use Psr\Http\Message\ResponseInterface as Response;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\FormModel\FormModelInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * BaseController had 0% direct coverage before this (only 13% indirect, via
 * other tests constructing concrete controller subclasses -- and that 13%
 * only ever exercised the constructor plus one of initializeViewRenderer()'s
 * three permission branches). This file targets the previously-untested
 * logic directly: the three permission branches, m()'s five match arms,
 * fetchCustomFieldsAndValues(), viewPartialDeliveryLocation()'s three paths,
 * and -- the highest-risk part -- processCustomFields()'s create/update
 * branching plus normalizeCustomFieldData()/processAjaxRawItem()'s
 * regex-based parsing of the two different custom-field submission shapes
 * (both exercised only indirectly here, through processCustomFields(),
 * since they're private).
 *
 * Same minimal-harness technique as SetWorkerTest/GuestOfflineTest: a bare
 * BaseController subclass with public wrappers around the protected/private
 * methods under test, rather than constructing a full concrete controller.
 */
#[Test]
final class BaseControllerTest
{
    /**
     * @param bool $viewInv
     * @param bool $editInv
     * @param bool|null $tfaVerified
     */
    private function makeHarness(
        WebViewRenderer&m\MockInterface $webViewRenderer,
        bool $viewInv = true,
        bool $editInv = true,
        ?bool $tfaVerified = true,
    ): BaseControllerTestHarness {
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);

        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn($viewInv);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn($editInv);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);

        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn($tfaVerified);

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);

        return new BaseControllerTestHarness(
            $webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash,
        );
    }

    // -----------------------------------------------------------------
    // initializeViewRenderer() -- three permission branches
    // -----------------------------------------------------------------

    public function neitherPermissionUsesTheSoletraderLayout(): void
    {
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->once()->with('base')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->once()
            ->with('@views/invoice/layout/fullpage-loader.php')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->once()
            ->with('@views/layout/templates/soletrader/main.php')->andReturnSelf();

        $this->makeHarness($webViewRenderer, viewInv: false, editInv: false, tfaVerified: null);
    }

    public function viewOnlyWithTfaVerifiedUsesTheGuestLayout(): void
    {
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->once()->with('base')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->once()
            ->with('@views/invoice/layout/fullpage-loader.php')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->once()
            ->with('@views/layout/guest.php')->andReturnSelf();

        $this->makeHarness($webViewRenderer, viewInv: true, editInv: false, tfaVerified: true);
    }

    public function editPermissionWithTfaVerifiedUsesTheInvoiceLayout(): void
    {
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->once()->with('base')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->once()
            ->with('@views/invoice/layout/fullpage-loader.php')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->once()
            ->with('@views/layout/invoice.php')->andReturnSelf();

        $this->makeHarness($webViewRenderer, viewInv: true, editInv: true, tfaVerified: true);
    }

    public function editPermissionWithoutTfaVerifiedLeavesTheLayoutUnset(): void
    {
        // A real gap in the elseif chain: EDIT_INV alone, without
        // tfa_verified === true, matches none of the three branches --
        // the renderer is never reconfigured at all. Documenting the
        // current behaviour (no withControllerName()/withLayout() call)
        // rather than silently letting it drift.
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldNotReceive('withControllerName');
        $webViewRenderer->shouldNotReceive('withLayout');

        $this->makeHarness($webViewRenderer, viewInv: true, editInv: true, tfaVerified: false);
    }

    // -----------------------------------------------------------------
    // m()
    // -----------------------------------------------------------------

    public function mCreatedSuccessfullyReturnsAnInfoFlash(): void
    {
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();
        $harness = $this->makeHarness($webViewRenderer);

        $harness->translatorMock->shouldReceive('translate')
            ->with('record.successfully.created')->andReturn('Created!');
        $harness->flashMock->shouldReceive('has')->with('Created!')->andReturn(false);
        $harness->flashMock->shouldReceive('add')->once()->with('info', 'Created!', true);

        Assert::same($harness->callM('CS'), $harness->flashMock);
    }

    public function mCreatedNotReturnsAWarningFlash(): void
    {
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();
        $harness = $this->makeHarness($webViewRenderer);

        $harness->translatorMock->shouldReceive('translate')
            ->with('record.successfully.created.not')->andReturn('Not created!');
        $harness->flashMock->shouldReceive('has')->with('Not created!')->andReturn(false);
        $harness->flashMock->shouldReceive('add')->once()->with('warning', 'Not created!', true);

        Assert::same($harness->callM('CN'), $harness->flashMock);
    }

    public function mDeletedSuccessfullyReturnsAnInfoFlash(): void
    {
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();
        $harness = $this->makeHarness($webViewRenderer);

        $harness->translatorMock->shouldReceive('translate')
            ->with('record.successfully.deleted')->andReturn('Deleted!');
        $harness->flashMock->shouldReceive('has')->with('Deleted!')->andReturn(false);
        $harness->flashMock->shouldReceive('add')->once()->with('info', 'Deleted!', true);

        Assert::same($harness->callM('DS'), $harness->flashMock);
    }

    public function mDeletedNotReturnsAWarningFlash(): void
    {
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();
        $harness = $this->makeHarness($webViewRenderer);

        $harness->translatorMock->shouldReceive('translate')
            ->with('record.successfully.deleted.not')->andReturn('Not deleted!');
        $harness->flashMock->shouldReceive('has')->with('Not deleted!')->andReturn(false);
        $harness->flashMock->shouldReceive('add')->once()->with('warning', 'Not deleted!', true);

        Assert::same($harness->callM('DN'), $harness->flashMock);
    }

    public function mWithAnUnknownKeyReturnsNullAndNeverFlashes(): void
    {
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();
        $harness = $this->makeHarness($webViewRenderer);

        $harness->flashMock->shouldNotReceive('has');
        $harness->flashMock->shouldNotReceive('add');

        Assert::null($harness->callM('unknown-key'));
    }

    // -----------------------------------------------------------------
    // render() / alert()
    // -----------------------------------------------------------------

    public function renderDelegatesStraightToTheViewRenderer(): void
    {
        /** @var Response&m\MockInterface $response */
        $response = m::mock(Response::class);

        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();
        $webViewRenderer->shouldReceive('render')->once()
            ->with('inv/view', ['id' => 1])->andReturn($response);
        $harness = $this->makeHarness($webViewRenderer);

        Assert::same($harness->callRender('inv/view', ['id' => 1]), $response);
    }

    public function alertRendersTheAlertPartialWithTheCurrentFlash(): void
    {
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();
        $webViewRenderer->shouldReceive('renderPartialAsString')->once()
            ->with('//invoice/layout/alert', m::on(
                static fn (mixed $arg): bool => is_array($arg) && array_key_exists('flash', $arg),
            ))->andReturn('<div>alert</div>');
        $harness = $this->makeHarness($webViewRenderer);

        Assert::same($harness->callAlert(), '<div>alert</div>');
    }

    // -----------------------------------------------------------------
    // fetchCustomFieldsAndValues()
    // -----------------------------------------------------------------

    public function fetchCustomFieldsAndValuesCombinesBothRepositoryCalls(): void
    {
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();
        $harness = $this->makeHarness($webViewRenderer);

        /** @var EntityReader&m\MockInterface $customFields */
        $customFields = m::mock(EntityReader::class);

        /** @var CustomFieldRepository&m\MockInterface $cfR */
        $cfR = m::mock(CustomFieldRepository::class);
        $cfR->shouldReceive('repoTablequery')->once()->with('inv_custom')->andReturn($customFields);

        /** @var CustomValueRepository&m\MockInterface $cvR */
        $cvR = m::mock(CustomValueRepository::class);
        $cvR->shouldReceive('fixCfValueToCf')->once()->with($customFields)->andReturn(['1' => 'a']);

        $result = $harness->callFetchCustomFieldsAndValues($cfR, $cvR, 'inv_custom');

        Assert::same($result['customFields'], $customFields);
        Assert::same($result['customValues'], ['1' => 'a']);
    }

    // -----------------------------------------------------------------
    // viewPartialDeliveryLocation()
    // -----------------------------------------------------------------

    public function viewPartialDeliveryLocationReturnsEmptyStringForANullId(): void
    {
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();
        $harness = $this->makeHarness($webViewRenderer);

        /** @var DeliveryLocationRepository&m\MockInterface $dlr */
        $dlr = m::mock(DeliveryLocationRepository::class);
        $dlr->shouldNotReceive('repoDeliveryLocationquery');

        Assert::same($harness->callViewPartialDeliveryLocation('en', $dlr, null), '');
    }

    public function viewPartialDeliveryLocationReturnsEmptyStringWhenNotFound(): void
    {
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();
        $harness = $this->makeHarness($webViewRenderer);

        /** @var DeliveryLocationRepository&m\MockInterface $dlr */
        $dlr = m::mock(DeliveryLocationRepository::class);
        $dlr->shouldReceive('repoDeliveryLocationquery')->once()->with(5)->andReturn(null);

        Assert::same($harness->callViewPartialDeliveryLocation('en', $dlr, 5), '');
    }

    public function viewPartialDeliveryLocationRendersThePartialWhenFound(): void
    {
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();
        $webViewRenderer->shouldReceive('renderPartialAsString')->once()
            ->with('//invoice/inv/partial_inv_delivery_location', m::type('array'))
            ->andReturn('<div>rendered</div>');
        $harness = $this->makeHarness($webViewRenderer);
        $harness->translatorMock->shouldReceive('translate')->with('delivery.location')->andReturn('Delivery location');

        /** @var DeliveryLocation&m\MockInterface $del */
        $del = m::mock(DeliveryLocation::class);
        $del->shouldReceive('getBuildingNumber')->andReturn('12');
        $del->shouldReceive('getAddress1')->andReturn('Street 1');
        $del->shouldReceive('getAddress2')->andReturn('');
        $del->shouldReceive('getCity')->andReturn('London');
        $del->shouldReceive('getZip')->andReturn('AB1 2CD');
        $del->shouldReceive('getCountry')->andReturn('UK');
        $del->shouldReceive('getGlobalLocationNumber')->andReturn('');

        /** @var DeliveryLocationRepository&m\MockInterface $dlr */
        $dlr = m::mock(DeliveryLocationRepository::class);
        $dlr->shouldReceive('repoDeliveryLocationquery')->once()->with(5)->andReturn($del);

        Assert::same($harness->callViewPartialDeliveryLocation('en', $dlr, 5), '<div>rendered</div>');
    }

    // -----------------------------------------------------------------
    // processCustomFields() / normalizeCustomFieldData() / processAjaxRawItem()
    // -----------------------------------------------------------------

    private function makeProcessCustomFieldsHarness(): BaseControllerTestHarness
    {
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();
        return $this->makeHarness($webViewRenderer);
    }

    public function processCustomFieldsDoesNothingWhenRequestDataIsNotAnArray(): void
    {
        $harness = $this->makeProcessCustomFieldsHarness();

        /** @var FormHydrator&m\MockInterface $formHydrator */
        $formHydrator = m::mock(FormHydrator::class);

        /** @var CustomFieldProcessor&m\MockInterface $processor */
        $processor = m::mock(CustomFieldProcessor::class);
        $processor->shouldNotReceive('exists');

        $harness->callProcessCustomFields(null, $formHydrator, $processor, 42);
    }

    public function processCustomFieldsCreatesANewRecordFromDirectArrayFormat(): void
    {
        $harness = $this->makeProcessCustomFieldsHarness();

        $newEntity = new \stdClass();
        /** @var FormModelInterface&m\MockInterface $form */
        $form = m::mock(FormModelInterface::class);

        /** @var CustomFieldProcessor&m\MockInterface $processor */
        $processor = m::mock(CustomFieldProcessor::class);
        $processor->shouldReceive('exists')->once()->with(42, 5)->andReturn(false);
        $processor->shouldReceive('findExisting')->never();
        $processor->shouldReceive('createEntity')->once()->andReturn($newEntity);
        $processor->shouldReceive('createForm')->once()->with($newEntity)->andReturn($form);
        $processor->shouldReceive('prepareInputData')->once()
            ->with(42, 5, 'new value')->andReturn(['value' => 'new value']);
        $processor->shouldReceive('save')->once()->with($newEntity, ['value' => 'new value']);

        /** @var FormHydrator&m\MockInterface $formHydrator */
        $formHydrator = m::mock(FormHydrator::class);
        $formHydrator->shouldReceive('populateAndValidate')->once()
            ->with($form, ['value' => 'new value'])->andReturn(true);

        $harness->callProcessCustomFields(['custom' => ['5' => 'new value']], $formHydrator, $processor, 42);
    }

    public function processCustomFieldsUpdatesAnExistingRecordFromDirectArrayFormat(): void
    {
        $harness = $this->makeProcessCustomFieldsHarness();

        $existingEntity = new \stdClass();
        /** @var FormModelInterface&m\MockInterface $form */
        $form = m::mock(FormModelInterface::class);

        /** @var CustomFieldProcessor&m\MockInterface $processor */
        $processor = m::mock(CustomFieldProcessor::class);
        $processor->shouldReceive('exists')->once()->with(42, 5)->andReturn(true);
        $processor->shouldReceive('createEntity')->never();
        $processor->shouldReceive('findExisting')->once()->with(42, 5)->andReturn($existingEntity);
        $processor->shouldReceive('createForm')->once()->with($existingEntity)->andReturn($form);
        $processor->shouldReceive('prepareInputData')->once()
            ->with(42, 5, 'updated value')->andReturn(['value' => 'updated value']);
        $processor->shouldReceive('save')->once()->with($existingEntity, ['value' => 'updated value']);

        /** @var FormHydrator&m\MockInterface $formHydrator */
        $formHydrator = m::mock(FormHydrator::class);
        $formHydrator->shouldReceive('populateAndValidate')->once()
            ->with($form, ['value' => 'updated value'])->andReturn(true);

        $harness->callProcessCustomFields(['custom' => ['5' => 'updated value']], $formHydrator, $processor, 42);
    }

    public function processCustomFieldsNeverSavesWhenValidationFails(): void
    {
        $harness = $this->makeProcessCustomFieldsHarness();

        $newEntity = new \stdClass();
        /** @var FormModelInterface&m\MockInterface $form */
        $form = m::mock(FormModelInterface::class);

        /** @var CustomFieldProcessor&m\MockInterface $processor */
        $processor = m::mock(CustomFieldProcessor::class);
        $processor->shouldReceive('exists')->once()->with(42, 5)->andReturn(false);
        $processor->shouldReceive('createEntity')->once()->andReturn($newEntity);
        $processor->shouldReceive('createForm')->once()->with($newEntity)->andReturn($form);
        $processor->shouldReceive('prepareInputData')->once()->andReturn(['value' => 'bad']);
        $processor->shouldReceive('save')->never();

        /** @var FormHydrator&m\MockInterface $formHydrator */
        $formHydrator = m::mock(FormHydrator::class);
        $formHydrator->shouldReceive('populateAndValidate')->once()->andReturn(false);

        $harness->callProcessCustomFields(['custom' => ['5' => 'bad']], $formHydrator, $processor, 42);
    }

    public function processCustomFieldsParsesTheAjaxSingleValueFormat(): void
    {
        $harness = $this->makeProcessCustomFieldsHarness();

        $newEntity = new \stdClass();
        /** @var FormModelInterface&m\MockInterface $form */
        $form = m::mock(FormModelInterface::class);

        /** @var CustomFieldProcessor&m\MockInterface $processor */
        $processor = m::mock(CustomFieldProcessor::class);
        $processor->shouldReceive('exists')->once()->with(42, 7)->andReturn(false);
        $processor->shouldReceive('createEntity')->once()->andReturn($newEntity);
        $processor->shouldReceive('createForm')->once()->with($newEntity)->andReturn($form);
        $processor->shouldReceive('prepareInputData')->once()
            ->with(42, 7, 'ajax value')->andReturn(['value' => 'ajax value']);
        $processor->shouldReceive('save')->once();

        /** @var FormHydrator&m\MockInterface $formHydrator */
        $formHydrator = m::mock(FormHydrator::class);
        $formHydrator->shouldReceive('populateAndValidate')->once()->andReturn(true);

        $requestData = ['custom' => [['name' => 'custom[7]', 'value' => 'ajax value']]];
        $harness->callProcessCustomFields($requestData, $formHydrator, $processor, 42);
    }

    public function processCustomFieldsParsesTheAjaxRepeatedArrayFormat(): void
    {
        $harness = $this->makeProcessCustomFieldsHarness();

        $newEntity = new \stdClass();
        /** @var FormModelInterface&m\MockInterface $form */
        $form = m::mock(FormModelInterface::class);

        /** @var CustomFieldProcessor&m\MockInterface $processor */
        $processor = m::mock(CustomFieldProcessor::class);
        $processor->shouldReceive('exists')->once()->with(42, 9)->andReturn(false);
        $processor->shouldReceive('createEntity')->once()->andReturn($newEntity);
        $processor->shouldReceive('createForm')->once()->with($newEntity)->andReturn($form);
        $processor->shouldReceive('prepareInputData')->once()
            ->with(42, 9, ['a', 'b'])->andReturn(['value' => ['a', 'b']]);
        $processor->shouldReceive('save')->once();

        /** @var FormHydrator&m\MockInterface $formHydrator */
        $formHydrator = m::mock(FormHydrator::class);
        $formHydrator->shouldReceive('populateAndValidate')->once()->andReturn(true);

        $requestData = ['custom' => [
            ['name' => 'custom[9][]', 'value' => 'a'],
            ['name' => 'custom[9][]', 'value' => 'b'],
        ]];
        $harness->callProcessCustomFields($requestData, $formHydrator, $processor, 42);
    }

    public function processCustomFieldsIgnoresAjaxItemsMissingNameOrValue(): void
    {
        $harness = $this->makeProcessCustomFieldsHarness();

        /** @var FormHydrator&m\MockInterface $formHydrator */
        $formHydrator = m::mock(FormHydrator::class);

        /** @var CustomFieldProcessor&m\MockInterface $processor */
        $processor = m::mock(CustomFieldProcessor::class);
        $processor->shouldNotReceive('exists');

        $requestData = ['custom' => [
            ['name' => 'custom[3]'],
            ['value' => 'orphaned'],
        ]];
        $harness->callProcessCustomFields($requestData, $formHydrator, $processor, 42);
    }

    public function processCustomFieldsIgnoresAjaxItemsWhoseNameIsNeitherStringNorNumeric(): void
    {
        $harness = $this->makeProcessCustomFieldsHarness();

        /** @var FormHydrator&m\MockInterface $formHydrator */
        $formHydrator = m::mock(FormHydrator::class);

        /** @var CustomFieldProcessor&m\MockInterface $processor */
        $processor = m::mock(CustomFieldProcessor::class);
        $processor->shouldNotReceive('exists');

        $requestData = ['custom' => [
            ['name' => ['not', 'a', 'scalar'], 'value' => 'x'],
        ]];
        $harness->callProcessCustomFields($requestData, $formHydrator, $processor, 42);
    }
}

/**
 * Test-only harness -- exposes BaseController's protected/private methods
 * via thin public wrappers, and its constructor-injected mock collaborators
 * (translator/flash) as public readonly properties so tests can set
 * per-scenario expectations on them after construction.
 */
final class BaseControllerTestHarness extends BaseController
{
    public readonly TranslatorInterface&m\MockInterface $translatorMock;
    public readonly FlashInterface&m\MockInterface $flashMock;

    /**
     * $userService and $flash are deliberately plain-typed (UserService,
     * Flash), not intersected with Mockery\MockInterface, on this
     * constructor's parameters -- Psalm accepts `Interface&MockInterface`
     * as an inhabited type but not `ConcreteClass&MockInterface`, for any
     * concrete class (TranslatorInterface -- an interface -- was never
     * flagged; Flash and UserService -- both concrete classes -- both
     * were, independent of Flash not being readonly/final in the same
     * way UserService is). Plain-typing the parameters lets
     * parent::__construct() (which requires the exact concrete Flash
     * type) receive them with no friction. $flashMock is re-typed via a
     * local `@var` cast (a normal Psalm narrowing hint, not a
     * suppression) to FlashInterface&MockInterface -- FlashInterface is
     * the interface Flash implements -- purely so tests can call
     * ->shouldReceive() on the exposed property afterward; the object
     * underneath is still the same real, mocked Flash instance.
     */
    public function __construct(
        WebControllerService $webService,
        UserService $userService,
        TranslatorInterface&m\MockInterface $translator,
        WebViewRenderer $webViewRenderer,
        SessionInterface $session,
        SettingRepository $sR,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        $this->translatorMock = $translator;
        /** @var FlashInterface&m\MockInterface $flash */
        $this->flashMock = $flash;
    }

    public function callM(string $m): ?Flash
    {
        return $this->m($m);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function callRender(string $view, array $parameters = []): Response
    {
        return $this->render($view, $parameters);
    }

    public function callAlert(): string
    {
        return $this->alert();
    }

    /** @return array{customFields: mixed, customValues: array<array-key, mixed>} */
    public function callFetchCustomFieldsAndValues(
        CustomFieldRepository $cfR, CustomValueRepository $cvR, string $tableName): array
    {
        return $this->fetchCustomFieldsAndValues($cfR, $cvR, $tableName);
    }

    public function callProcessCustomFields(
        array|object|null $requestData,
        FormHydrator $formHydrator,
        CustomFieldProcessor $processor,
        int $entityId,
    ): void {
        $this->processCustomFields($requestData, $formHydrator, $processor, $entityId);
    }

    public function callViewPartialDeliveryLocation(
        string $_language, DeliveryLocationRepository $dlr, ?int $delivery_location_id): string
    {
        return $this->viewPartialDeliveryLocation($_language, $dlr, $delivery_location_id);
    }
}
