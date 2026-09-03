<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv\Trait;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\BaseController;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\Inv\InvRepository as InvRepo;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Inv\InvUrlKeyRepoDeps;
use App\Invoice\Inv\InvUrlKeyUserDeps;
use App\Invoice\Inv\Trait\UrlKey as InvUrlKeyTrait;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PMR;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Upload\UploadRepository as UPR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\Service\WebControllerService;
use App\User\UserRepository as UR;
use App\User\UserService;
use Mockery as m;
use Psr\Http\Message\ResponseInterface as Response;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Inv\Trait\UrlKey::urlKey() -- the public urlKey/{key}/{gateway} guest
 * invoice view -- had zero unit test coverage before this. Added alongside
 * the PublicDocumentAssetHelper wiring fix (see
 * project_public_document_css_fix memory) specifically to cover the new
 * PublicDocumentAssetHelper::resolve(...) call site this trait's
 * renderUrlKey() now makes. Same minimal-harness technique as
 * BaseControllerTest / Quote\Trait\UrlKeyTest: a bare BaseController
 * subclass composed with just this one trait -- InvController itself needs
 * many more constructor deps unrelated to this trait.
 *
 * InvController::activeUser() and Trait\View::viewPartialInvAttachments()
 * are both called by this trait but neither is reachable from a
 * trait-only harness (activeUser() is declared directly on InvController,
 * not in a shared trait; viewPartialInvAttachments() lives in the much
 * larger Trait\View, unrelated to what's under test here) -- both are
 * replaced with harness-local stubs rather than pulled in wholesale.
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

    private function makeHarness(WebViewRenderer&m\MockInterface $webViewRenderer, User $activeUser): InvUrlKeyHarness
    {
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        $webService->shouldReceive('getNotFoundResponse')->andReturn(m::mock(Response::class));

        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn(true);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn(true);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $key) => $key);

        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);
        $session->shouldReceive('set')->with('inv_id', m::type('int'));

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('public_invoice_template')->andReturn('');
        $sR->shouldReceive('getPaymentTermArray')->with($translator)->andReturn([]);
        // Unrelated to this test's own AssetManager/Aliases wiring -- an
        // existing, unrelated param already passed into this template
        // (App\Invoice\Setting\SettingRepository::getImg(), an image-path
        // helper, not Yiisoft\Aliases\Aliases -- see
        // project_public_document_css_fix memory).
        $sR->shouldReceive('getImg')->andReturn(m::mock(Aliases::class));

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);

        // BaseController::initializeViewRenderer() -- viewInv=editInv=true,
        // tfa_verified=true takes the EDIT_INV branch.
        $webViewRenderer->shouldReceive('withControllerName')->once()->with('base')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->once()
            ->with('@views/invoice/layout/fullpage-loader.php')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->once()
            ->with('@views/layout/invoice.php')->andReturnSelf();
        $webViewRenderer->shouldReceive('renderPartialAsString')
            ->with('//invoice/layout/alert', m::type('array'))
            ->andReturn('<div>alert</div>');

        return new InvUrlKeyHarness(
            $webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash, $activeUser,
        );
    }

    public function urlKeyRendersTheGuestInvoiceViewForAnAuthorizedActiveUser(): void
    {
        $urlKey = 'test-url-key';
        $invId = 7;
        $clientId = 3;
        $userId = 9;
        $gateway = 'stripe';
        $language = 'en';

        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        $inv->shouldReceive('reqId')->andReturn($invId);
        $inv->shouldReceive('reqClientId')->andReturn($clientId);
        $inv->shouldReceive('reqStatusId')->andReturn(3);
        $inv->shouldReceive('getPaymentMethod')->andReturn(0);
        $inv->shouldReceive('getDateDue')->andReturn(new \DateTimeImmutable('+30 days'));
        $inv->shouldReceive('getClient')->andReturn(null);

        /** @var InvRepo&m\MockInterface $iR */
        $iR = m::mock(InvRepo::class);
        $iR->shouldReceive('repoUrlKeyGuestCount')->once()->with($urlKey)->andReturn(1);
        $iR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with($urlKey)->andReturn($inv);
        $iR->shouldReceive('save')->once()->with($inv);

        /** @var IAR&m\MockInterface $iaR */
        $iaR = m::mock(IAR::class);
        $iaR->shouldReceive('repoInvAmountCount')->once()->with($invId)->andReturn(1);
        /** @var InvAmount&m\MockInterface $invAmount */
        $invAmount = m::mock(InvAmount::class);
        $invAmount->shouldReceive('getBalance')->andReturn(0.0);
        $iaR->shouldReceive('repoInvquery')->once()->with($invId)->andReturn($invAmount);
        $invAmount->shouldReceive('getTotal')->andReturn(100.0);
        $invAmount->shouldReceive('getPaid')->andReturn(100.0);

        /** @var IIAR&m\MockInterface $iiaR */
        $iiaR = m::mock(IIAR::class);

        /** @var IIR&m\MockInterface $iiR */
        $iiR = m::mock(IIR::class);
        $iiR->shouldReceive('repoInvquery')->with($invId)->andReturn($this->entityReaderOf([]));

        /** @var ITRR&m\MockInterface $itrR */
        $itrR = m::mock(ITRR::class);
        // Called twice in the real source: once for the tax-rate-active
        // guard, once again for the 'inv_tax_rates' parameter -- neither
        // call caches the other's result.
        $itrR->shouldReceive('repoCount')->with($invId)->andReturn(1);
        $itrR->shouldReceive('repoInvquery')->with($invId)->andReturn($this->entityReaderOf([]));

        /** @var CFR&m\MockInterface $cfR */
        $cfR = m::mock(CFR::class);
        $cfR->shouldReceive('repoTablequery')->with('inv_custom')->andReturn($this->entityReaderOf([]));
        $cfR->shouldReceive('repoTablequery')->with('client_custom')->andReturn($this->entityReaderOf([]));

        $repos = new InvUrlKeyRepoDeps($iR, $iaR, $iiaR, $iiR, $itrR, $cfR);

        /** @var UR&m\MockInterface $uR */
        $uR = m::mock(UR::class);
        /** @var UCR&m\MockInterface $ucR */
        $ucR = m::mock(UCR::class);

        /** @var UIR&m\MockInterface $uiR */
        $uiR = m::mock(UIR::class);
        /** @var UserInv&m\MockInterface $userInv */
        $userInv = m::mock(UserInv::class);
        $userInv->shouldReceive('getActive')->andReturn(true);
        $userInv->shouldReceive('getType')->andReturn(1);
        $uiR->shouldReceive('repoUserInvUserIdquery')->with($userId)->andReturn($userInv);
        $uiR->shouldReceive('repoUserInvUserIdcount')->with($userId)->andReturn(1);

        /** @var PMR&m\MockInterface $pmR */
        $pmR = m::mock(PMR::class);

        /** @var UPR&m\MockInterface $upR */
        $upR = m::mock(UPR::class);

        /** @var ACIIR&m\MockInterface $aciiR */
        $aciiR = m::mock(ACIIR::class);

        /** @var AssetManager&m\MockInterface $assetManager */
        $assetManager = m::mock(AssetManager::class);
        $assetManager->shouldReceive('getUrl')->twice()->andReturn('https://example.test/bootstrap.min.css');

        // PublicDocumentAssetHelper reads via the real '@root' alias plus a
        // literal path (see project_public_document_css_fix memory) --
        // pointing '@root' at the real project root here reads the real,
        // already-committed custom-pdf.css.
        /** @var Aliases&m\MockInterface $aliases */
        $aliases = m::mock(Aliases::class);
        $aliases->shouldReceive('get')->once()->with('@root')->andReturn(dirname(__DIR__, 5));

        $ud = new InvUrlKeyUserDeps($uR, $ucR, $uiR, $pmR, $upR, $aciiR, $assetManager, $aliases);

        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('renderPartialAsString')
            ->with('//invoice/template/invoice/public/Invoice_Web', m::type('array'))
            ->andReturn('<html></html>');
        /** @var Response&m\MockInterface $response */
        $response = m::mock(Response::class);
        $webViewRenderer->shouldReceive('render')->once()
            ->with('url_key', m::type('array'))->andReturn($response);

        /** @var CurrentUser&m\MockInterface $currentUser */
        $currentUser = m::mock(CurrentUser::class);
        $currentUser->shouldReceive('isGuest')->andReturn(false);

        /** @var User&m\MockInterface $activeUser */
        $activeUser = m::mock(User::class);
        $activeUser->shouldReceive('reqId')->andReturn($userId);

        $result = $this->makeHarness($webViewRenderer, $activeUser)
            ->urlKey($urlKey, $gateway, $language, $currentUser, $repos, $ud);
        Assert::same($result, $response);
    }
}

/**
 * Test-only harness -- composes BaseController with just the Inv urlKey
 * trait under test, sidestepping InvController's own constructor entirely.
 * activeUser()/viewPartialInvAttachments() are stubbed rather than
 * replicated or pulled in from their real (InvController-only /
 * Trait\View-only) locations -- see the class docblock above.
 */
final class InvUrlKeyHarness extends BaseController
{
    use InvUrlKeyTrait;

    /** @var list<array{int, UR, UCR, UIR}> */
    public array $activeUserCalls = [];

    /** @var list<array{string, string, int, UPR}> */
    public array $viewPartialInvAttachmentsCalls = [];

    public function __construct(
        WebControllerService $webService,
        UserService $userService,
        TranslatorInterface $translator,
        WebViewRenderer $webViewRenderer,
        SessionInterface $session,
        SettingRepository $sR,
        Flash $flash,
        private readonly ?User $activeUserStub,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
    }

    // Stub -- InvController's real activeUser() isn't reachable from a
    // trait-only harness (it's declared directly on InvController, not in a
    // shared trait). Records every call's arguments so a test can assert on
    // exactly what the trait under test passed, rather than ignoring them.
    protected function activeUser(int $client_id, UR $uR, UCR $ucR, UIR $uiR): ?User
    {
        $this->activeUserCalls[] = [$client_id, $uR, $ucR, $uiR];
        return $this->activeUserStub;
    }

    // Stub -- avoids pulling in the much larger Trait\View (many unrelated
    // private methods) just for this one dependency. Records every call's
    // arguments so a test can assert on them, rather than ignoring them.
    protected function viewPartialInvAttachments(string $_language, string $url_key, int $client_id, UPR $upR): string
    {
        $this->viewPartialInvAttachmentsCalls[] = [$_language, $url_key, $client_id, $upR];
        return '<div>attachments</div>';
    }
}
