<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\SalesOrder;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\SalesOrderAmount\SalesOrderAmount;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeService;
use App\Invoice\InvCustom\InvCustomService;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvTaxRate\InvTaxRateService;
use App\Invoice\Inv\InvService;
use App\Invoice\SalesOrder\SalesOrderController;
use App\Invoice\SalesOrder\SalesOrderRepository as SoR;
use App\Invoice\SalesOrder\SalesOrderService;
use App\Invoice\SalesOrder\SoControllerBaseDeps;
use App\Invoice\SalesOrder\SoControllerInvDeps;
use App\Invoice\SalesOrder\SoControllerMiscDeps;
use App\Invoice\SalesOrder\SoUrlKeyDeps;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SoAR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SoIR;
use App\Invoice\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository as ACSOIR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository as SoIAR;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository as SoTRR;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\Service\WebControllerService;
use App\User\UserService;
use App\Widget\SalesOrderToolbar;
use Mockery as m;
use Psr\Http\Message\ResponseInterface as Response;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * SalesOrderController::urlKey()/renderSalesOrderForGuest() -- the public
 * guest salesorder view -- had zero unit test coverage before this. Added
 * alongside the PublicDocumentAssetHelper wiring fix (see
 * project_public_document_css_fix memory) specifically to cover the new
 * PublicDocumentAssetHelper::resolve(...) call site renderSalesOrderForGuest()
 * now makes. Unlike Quote/Inv, this flow lives directly on the controller
 * class (no shared trait to compose a minimal harness around), but
 * SalesOrderController's own constructor only needs three lightweight Deps
 * objects (SoControllerBaseDeps/SoControllerInvDeps/SoControllerMiscDeps),
 * all inert at construction time -- so a real instance is used directly.
 */
#[Test]
final class UrlKeyTest
{
    /**
     * @param array<array-key, object> $items
     */
    private function entityReaderOf(array $items): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        /** @var \Yiisoft\Data\Cycle\Reader\EntityReader&m\MockInterface $reader */
        $reader = m::mock(\Yiisoft\Data\Cycle\Reader\EntityReader::class);
        $reader->shouldReceive('getIterator')->andReturnUsing(
            static function () use ($items) {
                yield from $items;
            },
        );
        return $reader;
    }

    private function makeController(WebViewRenderer&m\MockInterface $webViewRenderer, User $activeUser): SalesOrderController
    {
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        $webService->shouldReceive('getNotFoundResponse')->andReturn(m::mock(Response::class));

        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn(true);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn(true);
        // Called repeatedly, uncached, at every ?->reqId() site in
        // urlKey()/renderSalesOrderForGuest().
        $userService->shouldReceive('getUser')->andReturn($activeUser);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);

        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('public_salesorder_template')->andReturn('');

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);

        // BaseController::initializeViewRenderer() -- viewInv=editInv=true,
        // tfa_verified=true takes the EDIT_INV branch.
        $webViewRenderer->shouldReceive('withControllerName')->once()->with('invoice/salesorder')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->once()
            ->with('@views/invoice/layout/fullpage-loader.php')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->once()
            ->with('@views/layout/invoice.php')->andReturnSelf();
        $webViewRenderer->shouldReceive('renderPartialAsString')
            ->with('//invoice/layout/alert', m::type('array'))
            ->andReturn('<div>alert</div>');

        // None of these are reached by urlKey()/renderSalesOrderForGuest() --
        // present only because SalesOrderController's constructor requires
        // them, unrelated to the trait/method under test.
        /** @var InvService&m\MockInterface $invService */
        $invService = m::mock(InvService::class);
        /** @var InvAllowanceChargeService&m\MockInterface $invAllowanceChargeService */
        $invAllowanceChargeService = m::mock(InvAllowanceChargeService::class);
        /** @var InvCustomService&m\MockInterface $invCustomService */
        $invCustomService = m::mock(InvCustomService::class);
        /** @var InvItemService&m\MockInterface $invItemService */
        $invItemService = m::mock(InvItemService::class);
        /** @var InvTaxRateService&m\MockInterface $invTaxRateService */
        $invTaxRateService = m::mock(InvTaxRateService::class);
        /** @var DataResponseFactoryInterface&m\MockInterface $factory */
        $factory = m::mock(DataResponseFactoryInterface::class);
        /** @var SalesOrderService&m\MockInterface $salesorderService */
        $salesorderService = m::mock(SalesOrderService::class);
        /** @var SalesOrderToolbar&m\MockInterface $salesOrderToolbar */
        $salesOrderToolbar = m::mock(SalesOrderToolbar::class);

        $base = new SoControllerBaseDeps($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        $inv = new SoControllerInvDeps(
            $invService, $invAllowanceChargeService, $invCustomService, $invItemService, $invTaxRateService,
        );
        $misc = new SoControllerMiscDeps($factory, $salesorderService, $salesOrderToolbar);

        return new SalesOrderController($base, $inv, $misc);
    }

    public function urlKeyRendersTheGuestSalesOrderViewForAnAuthorizedActiveUser(): void
    {
        $urlKey = 'test-url-key';
        $salesOrderId = 11;
        $clientId = 3;
        $userId = 9;

        /** @var SalesOrder&m\MockInterface $salesorder */
        $salesorder = m::mock(SalesOrder::class);
        $salesorder->shouldReceive('reqId')->andReturn($salesOrderId);
        $salesorder->shouldReceive('reqClientId')->andReturn($clientId);
        $salesorder->shouldReceive('getStatusId')->andReturn(3);
        $salesorder->shouldReceive('getClient')->andReturn(null);
        $salesorder->shouldReceive('reqUserId')->andReturn($userId);

        /** @var SoR&m\MockInterface $soR */
        $soR = m::mock(SoR::class);
        $soR->shouldReceive('repoUrlKeyGuestCount')->once()->with($urlKey)->andReturn(1);
        $soR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with($urlKey)->andReturn($salesorder);
        $soR->shouldReceive('save')->once()->with($salesorder);

        /** @var SoTRR&m\MockInterface $sotrR */
        $sotrR = m::mock(SoTRR::class);
        $sotrR->shouldReceive('repoCount')->once()->with($salesOrderId)->andReturn(0);

        /** @var UIR&m\MockInterface $uiR */
        $uiR = m::mock(UIR::class);
        $uiR->shouldReceive('repoUserInvUserIdcount')->with($userId)->andReturn(1);
        /** @var UserInv&m\MockInterface $userInv */
        $userInv = m::mock(UserInv::class);
        $userInv->shouldReceive('getActive')->andReturn(true);
        $userInv->shouldReceive('getType')->andReturn(1);
        $uiR->shouldReceive('repoUserInvUserIdquery')->with($userId)->andReturn($userInv);

        /** @var UCR&m\MockInterface $ucR */
        $ucR = m::mock(UCR::class);
        $ucR->shouldReceive('repoUserClientqueryCount')->once()->with($userId, $clientId)->andReturn(1);

        /** @var CFR&m\MockInterface $cfR */
        $cfR = m::mock(CFR::class);
        $cfR->shouldReceive('repoTablequery')->with('inv_custom')->andReturn($this->entityReaderOf([]));
        $cfR->shouldReceive('repoTablequery')->with('client_custom')->andReturn($this->entityReaderOf([]));
        $cfR->shouldReceive('repoTablequery')->with('sales_order')->andReturn($this->entityReaderOf([]));

        /** @var SoAR&m\MockInterface $soaR */
        $soaR = m::mock(SoAR::class);
        $soaR->shouldReceive('repoSalesOrderAmountCount')->once()->with($salesOrderId)->andReturn(1);
        $soaR->shouldReceive('repoSalesOrderquery')->once()->with($salesOrderId)->andReturn(m::mock(SalesOrderAmount::class));

        /** @var SoIR&m\MockInterface $soiR */
        $soiR = m::mock(SoIR::class);
        $soiR->shouldReceive('repoSalesOrderquery')->once()->with($salesOrderId)->andReturn($this->entityReaderOf([]));

        /** @var SoIAR&m\MockInterface $soiaR */
        $soiaR = m::mock(SoIAR::class);

        /** @var ACSOIR&m\MockInterface $acsoiR */
        $acsoiR = m::mock(ACSOIR::class);

        /** @var AssetManager&m\MockInterface $assetManager */
        $assetManager = m::mock(AssetManager::class);
        $assetManager->shouldReceive('getUrl')->twice()->andReturn('https://example.test/bootstrap.min.css');

        // PublicDocumentAssetHelper reads via the real '@root' alias plus a
        // literal path (see project_public_document_css_fix memory) --
        // pointing '@root' at the real project root here reads the real,
        // already-committed custom-pdf.css.
        /** @var Aliases&m\MockInterface $aliases */
        $aliases = m::mock(Aliases::class);
        $aliases->shouldReceive('get')->once()->with('@root')->andReturn(dirname(__DIR__, 4));

        $deps = new SoUrlKeyDeps($cfR, $soaR, $soiR, $soiaR, $acsoiR, $soR, $sotrR, $uiR, $ucR, $assetManager, $aliases);

        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('renderPartialAsString')
            ->with('//invoice/salesorder/terms_and_conditions_file')
            ->andReturn('<div>terms</div>');
        $webViewRenderer->shouldReceive('renderPartialAsString')
            ->with('//invoice/template/salesorder/public/SalesOrder_Web', m::type('array'))
            ->andReturn('<html></html>');
        /** @var Response&m\MockInterface $response */
        $response = m::mock(Response::class);
        $webViewRenderer->shouldReceive('render')->once()
            ->with('url_key', m::type('array'))->andReturn($response);

        /** @var CurrentRoute&m\MockInterface $currentRoute */
        $currentRoute = m::mock(CurrentRoute::class);
        $currentRoute->shouldReceive('getArgument')->once()->with('key')->andReturn($urlKey);

        /** @var CurrentUser&m\MockInterface $currentUser */
        $currentUser = m::mock(CurrentUser::class);
        $currentUser->shouldReceive('isGuest')->andReturn(true);

        /** @var User&m\MockInterface $activeUser */
        $activeUser = m::mock(User::class);
        $activeUser->shouldReceive('reqId')->andReturn($userId);

        $result = $this->makeController($webViewRenderer, $activeUser)
            ->urlKey($currentRoute, $currentUser, $deps);
        Assert::same($result, $response);
    }
}
