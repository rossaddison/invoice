<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Quote\Trait;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\QuoteAmount\QuoteAmount;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\BaseController;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\QuoteItem\QuoteItemRepository as QIR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as QTRR;
use App\Invoice\Quote\QuoteUrlKeyRepoDeps;
use App\Invoice\Quote\QuoteUrlKeyUserDeps;
use App\Invoice\Quote\Trait\UrlKey as QuoteUrlKeyTrait;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\Service\WebControllerService;
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
 * Quote\Trait\UrlKey::urlKey() -- the public urlKey() guest-approval flow --
 * had zero unit test coverage before this (only reachable, in practice,
 * through the full public urlKey/{key} route). Added alongside the
 * PublicDocumentAssetHelper wiring fix (see project_public_document_css_fix
 * memory) specifically to cover the new
 * PublicDocumentAssetHelper::resolve(...) call site this trait's
 * renderUrlKeyView() now makes -- SonarCloud's new_coverage gate flagged it
 * as 0% covered new code, and there is no QuoteControllerTest precedent to
 * extend (QuoteController itself needs six heavy Deps objects unrelated to
 * this trait). Same minimal-harness technique as BaseControllerTest: a bare
 * BaseController subclass composed with just this one trait, sidestepping
 * QuoteController's constructor entirely.
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

    private function makeHarness(WebViewRenderer&m\MockInterface $webViewRenderer): QuoteUrlKeyHarness
    {
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        // urlKey() unconditionally seeds $result with the not-found response
        // before the guard chain potentially overrides it -- always called,
        // regardless of whether this scenario expects it to end up unused.
        $webService->shouldReceive('getNotFoundResponse')->andReturn(m::mock(Response::class));

        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn(true);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn(true);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);

        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('public_quote_template')->andReturn('');

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);

        // BaseController::initializeViewRenderer() -- viewInv=editInv=true,
        // tfa_verified=true takes the EDIT_INV branch.
        $webViewRenderer->shouldReceive('withControllerName')->once()->with('base')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->once()
            ->with('@views/invoice/layout/fullpage-loader.php')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->once()
            ->with('@views/layout/invoice.php')->andReturnSelf();

        return new QuoteUrlKeyHarness($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
    }

    public function urlKeyRendersTheGuestQuoteViewForAnAuthorizedActiveUser(): void
    {
        $urlKey = 'test-url-key';
        $quoteId = 5;
        $userId = 9;
        $clientId = 3;

        /** @var Quote&m\MockInterface $quote */
        $quote = m::mock(Quote::class);
        $quote->shouldReceive('reqId')->andReturn($quoteId);
        $quote->shouldReceive('reqStatusId')->andReturn(3);
        $quote->shouldReceive('reqUserId')->andReturn($userId);
        $quote->shouldReceive('reqClientId')->andReturn($clientId);
        $quote->shouldReceive('getDateExpires')->andReturn(new \DateTimeImmutable('+30 days'));
        $quote->shouldReceive('getClient')->andReturn(null);

        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $qR->shouldReceive('repoUrlKeyGuestCount')->once()->with($urlKey)->andReturn(1);
        $qR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with($urlKey)->andReturn($quote);
        $qR->shouldReceive('save')->once()->with($quote);

        /** @var QAR&m\MockInterface $qaR */
        $qaR = m::mock(QAR::class);
        $qaR->shouldReceive('repoQuoteAmountCount')->once()->with($quoteId)->andReturn(1);
        /** @var QuoteAmount&m\MockInterface $quoteAmount */
        $quoteAmount = m::mock(QuoteAmount::class);
        $qaR->shouldReceive('repoQuotequery')->once()->with($quoteId)->andReturn($quoteAmount);

        /** @var QIR&m\MockInterface $qiR */
        $qiR = m::mock(QIR::class);
        $qiR->shouldReceive('repoQuotequery')->once()->with($quoteId)->andReturn($this->entityReaderOf([]));

        /** @var QIAR&m\MockInterface $qiaR */
        $qiaR = m::mock(QIAR::class);

        /** @var ACQIR&m\MockInterface $acqiR */
        $acqiR = m::mock(ACQIR::class);

        /** @var CFR&m\MockInterface $cfR */
        $cfR = m::mock(CFR::class);
        $cfR->shouldReceive('repoTablequery')->with('inv_custom')->andReturn($this->entityReaderOf([]));
        $cfR->shouldReceive('repoTablequery')->with('client_custom')->andReturn($this->entityReaderOf([]));

        $repos = new QuoteUrlKeyRepoDeps($cfR, $qaR, $qiR, $qiaR, $acqiR, $qR);

        /** @var QTRR&m\MockInterface $qtrR */
        $qtrR = m::mock(QTRR::class);
        $qtrR->shouldReceive('repoCount')->once()->with($quoteId)->andReturn(0);

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

        /** @var AssetManager&m\MockInterface $assetManager */
        $assetManager = m::mock(AssetManager::class);
        $assetManager->shouldReceive('getUrl')->twice()->andReturn('https://example.test/bootstrap.min.css');

        // PublicDocumentAssetHelper reads via the real '@root' alias plus a
        // literal path (see its own docblock/project_public_document_css_fix
        // memory for why '@invoice' -- used in the first version of this
        // fix -- doesn't exist as a registered alias); pointing '@root' at
        // the real project root here reads the real, already-committed
        // custom-pdf.css rather than a fake standalone file.
        /** @var Aliases&m\MockInterface $aliases */
        $aliases = m::mock(Aliases::class);
        $aliases->shouldReceive('get')->once()->with('@root')->andReturn(dirname(__DIR__, 5));

        $ud = new QuoteUrlKeyUserDeps($qtrR, $uiR, $ucR, $assetManager, $aliases);

        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('renderPartialAsString')
            ->with('//invoice/quote/modal_purchase_order_number', ['urlKey' => $urlKey])
            ->andReturn('<div>modal</div>');
        $webViewRenderer->shouldReceive('renderPartialAsString')
            ->with('//invoice/layout/alert', m::type('array'))
            ->andReturn('<div>alert</div>');
        $webViewRenderer->shouldReceive('renderPartialAsString')
            ->with('//invoice/template/quote/public/Quote_Web', m::type('array'))
            ->andReturn('<html></html>');
        /** @var Response&m\MockInterface $response */
        $response = m::mock(Response::class);
        $webViewRenderer->shouldReceive('render')->once()
            ->with('url_key', m::type('array'))->andReturn($response);

        /** @var CurrentUser&m\MockInterface $currentUser */
        $currentUser = m::mock(CurrentUser::class);
        $currentUser->shouldReceive('isGuest')->andReturn(true);

        $result = $this->makeHarness($webViewRenderer)->urlKey($urlKey, $currentUser, $repos, $ud);
        Assert::same($result, $response);
    }
}

/**
 * Test-only harness -- composes BaseController with just the Quote urlKey
 * trait under test, sidestepping QuoteController's own six-Deps-object
 * constructor (unrelated to this trait) entirely.
 */
final class QuoteUrlKeyHarness extends BaseController
{
    use QuoteUrlKeyTrait;
}
