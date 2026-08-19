<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Infrastructure\Persistence\Worker\Worker;
use App\Invoice\BaseController;
use App\Invoice\Inv\InvGuestDeps;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Inv\Trait\Guest;
use App\Invoice\UserClient\UserClientRepository as UCR;
use Yiisoft\Data\Reader\DataReaderInterface as DRI;
use Yiisoft\Data\Reader\SortableDataInterface as SDI;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\InvRecurring\InvRecurringRepository;
use App\Invoice\PaymentInformation\Service\BacsPaymentService;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\Invoice\Worker\WorkerRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Http\Message\ResponseInterface as Response;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Covers Guest.php's HomeCare-offline-PWA additions —
 * guestOfflineData()/guestOffline() — against a minimal harness that mixes
 * in the real Guest trait rather than constructing a full InvController (it
 * has 4 dependency-group constructor args covering dozens of unrelated
 * collaborators; none of them matter to these two actions). Worker/UserInv/
 * Inv/InvItem/Client are plain (non-final) classes, mockable directly;
 * WebViewRenderer/UserService/SettingRepository/Flash are final, mockable
 * only via DG\BypassFinals (Tests/bootstrap.php-equivalent, already enabled
 * for Testo via testo.php).
 */
#[Test]
final class GuestOfflineTest
{
    private function makeHarness(
        ?User $user,
        DataResponseFactoryInterface&m\MockInterface $factory,
        WebViewRenderer&m\MockInterface $webViewRenderer,
        WebControllerService&m\MockInterface $webService,
    ): GuestOfflineTestHarness {
        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('getUser')->andReturn($user);
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn(true);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn(false);

        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);

        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();

        // Only actually invoked by flashGuestAccessWarnings() on the
        // not-a-worker/no-client-access path (resolveGuestAccess()) — loosely
        // stubbed here since none of these tests assert on flash content,
        // only on the resulting 404.
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturn('translated');

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add');

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        return new GuestOfflineTestHarness(
            $webService,
            $userService,
            $translator,
            $webViewRenderer,
            $session,
            $sR,
            $flash,
            $factory,
        );
    }

    private function makeDeps(?UserInv $userInv, ?Worker $worker, InvRepository&m\MockInterface $iR): InvGuestDeps
    {
        /** @var UserInvRepository&m\MockInterface $uiR */
        $uiR = m::mock(UserInvRepository::class);
        $uiR->shouldReceive('repoUserInvUserIdcount')->andReturn(null !== $userInv ? 1 : 0);
        $uiR->shouldReceive('repoUserInvUserIdquery')->andReturn($userInv);

        /** @var WorkerRepository&m\MockInterface $wR */
        $wR = m::mock(WorkerRepository::class);
        $wR->shouldReceive('findByUserId')->andReturn($worker);

        // Only reached when no Worker is linked (resolveGuestAccess()'s
        // fallback to the ordinary client-assignment gate) — none of these
        // tests care about client-scoped access, only that it 404s cleanly.
        /** @var UserClientRepository&m\MockInterface $ucR */
        $ucR = m::mock(UserClientRepository::class);
        $ucR->shouldReceive('getAssignedToUser')->andReturn([]);

        /** @var InvAmountRepository&m\MockInterface $iaR */
        $iaR = m::mock(InvAmountRepository::class);
        /** @var InvRecurringRepository&m\MockInterface $irR */
        $irR = m::mock(InvRecurringRepository::class);
        /** @var BacsPaymentService&m\MockInterface $bacsPaymentService */
        $bacsPaymentService = m::mock(BacsPaymentService::class);
        return new InvGuestDeps(
            $iaR,
            $irR,
            $iR,
            $ucR,
            $uiR,
            $bacsPaymentService,
            $wR,
        );
    }

    private function makeUser(int $id): User&m\MockInterface
    {
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $user->shouldReceive('reqId')->andReturn($id);
        return $user;
    }

    private function makeUserInv(): UserInv&m\MockInterface
    {
        /** @var UserInv&m\MockInterface $userInv */
        $userInv = m::mock(UserInv::class);
        $userInv->shouldReceive('getActive')->andReturn(true);
        return $userInv;
    }

    public function guestOfflineDataReturns404WhenNotLoggedIn(): void
    {
        /** @var DataResponseFactoryInterface&m\MockInterface $factory */
        $factory = m::mock(DataResponseFactoryInterface::class);
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        $webService->shouldReceive('getNotFoundResponse')->andReturn($notFound);

        $harness = $this->makeHarness(null, $factory, $webViewRenderer, $webService);
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $result = $harness->guestOfflineData($this->makeDeps(null, null, $iR));

        Assert::same($notFound, $result);
    }

    public function guestOfflineDataReturns404WhenNotAWorker(): void
    {
        /** @var DataResponseFactoryInterface&m\MockInterface $factory */
        $factory = m::mock(DataResponseFactoryInterface::class);
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        $webService->shouldReceive('getNotFoundResponse')->andReturn($notFound);

        $harness = $this->makeHarness($this->makeUser(7), $factory, $webViewRenderer, $webService);
        // A UserInv exists but no linked Worker — the ordinary client-guest path, not offline-eligible.
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $deps = $this->makeDeps($this->makeUserInv(), null, $iR);

        $result = $harness->guestOfflineData($deps);

        Assert::same($notFound, $result);
    }

    public function guestOfflineDataReturnsWorkerScopedInvoicesWithoutAmountFields(): void
    {
        /** @var Client&m\MockInterface $client */
        $client = m::mock(Client::class);
        $client->shouldReceive('getClientFullName')->andReturn('Jane Smith');
        $client->shouldReceive('getClientAddress1')->andReturn('1 High Street');
        $client->shouldReceive('getClientAddress2')->andReturn(null);
        $client->shouldReceive('getClientBuildingNumber')->andReturn(null);
        $client->shouldReceive('getClientCity')->andReturn('Springfield');
        $client->shouldReceive('getClientState')->andReturn(null);
        $client->shouldReceive('getClientZip')->andReturn('SP1 1AA');
        $client->shouldReceive('getClientCountry')->andReturn('UK');
        $client->shouldReceive('getClientPhone')->andReturn('01234 567890');
        $client->shouldReceive('getClientMobile')->andReturn(null);
        $client->shouldReceive('getClientEmail')->andReturn('jane@example.com');

        /** @var InvItem&m\MockInterface $item */
        $item = m::mock(InvItem::class);
        $item->shouldReceive('getName')->andReturn('Standard Clean');
        $item->shouldReceive('getDescription')->andReturn('Weekly visit');
        $item->shouldReceive('getQuantity')->andReturn(1.0);
        $item->shouldReceive('getProductUnit')->andReturn('visit');
        $item->shouldReceive('getNote')->andReturn(null);

        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        $inv->shouldReceive('reqId')->andReturn(101);
        $inv->shouldReceive('getNumber')->andReturn('INV-101');
        $inv->shouldReceive('reqStatusId')->andReturn(2);
        $inv->shouldReceive('getDateCreated')->andReturn(new \DateTimeImmutable('2026-08-01'));
        $inv->shouldReceive('getDateDue')->andReturn(new \DateTimeImmutable('2026-08-15'));
        $inv->shouldReceive('getNote')->andReturn('Ring doorbell twice');
        $inv->shouldReceive('getDoNotSend')->andReturn(false);
        $inv->shouldReceive('getDoNotSendReason')->andReturn('');
        $inv->shouldReceive('getUrlKey')->andReturn('abc123urlkey');
        $inv->shouldReceive('getClient')->andReturn($client);
        $inv->shouldReceive('getItems')->andReturn(new ArrayCollection([$item]));

        // repoWorkerVisible() returns EntityReader (final, Cycle-backed —
        // mockable here only via BypassFinals); it's an IteratorAggregate
        // per DataReaderInterface, so a stubbed getIterator() is enough to
        // exercise guestOfflineData()'s own foreach without a real ORM query.
        $entityReader = m::mock(\Yiisoft\Data\Cycle\Reader\EntityReader::class);
        $entityReader->shouldReceive('getIterator')->andReturn((static function () use ($inv) {
            yield $inv;
        })());

        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoWorkerVisible')->with(0, 55)->andReturn($entityReader);
        $iR->shouldReceive('getSpecificStatusArrayLabel')->with('2')->andReturn('Sent');

        /** @var Worker&m\MockInterface $worker */
        $worker = m::mock(Worker::class);
        $worker->shouldReceive('reqId')->andReturn(55);

        /** @var DataResponseFactoryInterface&m\MockInterface $factory */
        $factory = m::mock(DataResponseFactoryInterface::class);
        /** @var string|null $capturedJson */
        $capturedJson = null;
        /** @var Response&m\MockInterface $response */
        $response = m::mock(Response::class);
        $factory->shouldReceive('createResponse')
            ->withArgs(function (string $json) use (&$capturedJson): bool {
                $capturedJson = $json;
                return true;
            })
            ->andReturn($response);

        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);

        $harness = $this->makeHarness($this->makeUser(9), $factory, $webViewRenderer, $webService);
        $deps = $this->makeDeps($this->makeUserInv(), $worker, $iR);

        $result = $harness->guestOfflineData($deps);

        Assert::same($response, $result);
        Assert::notNull($capturedJson);
        /**
         * @var array{downloadedAt: string, invoices: list<array{
         *     number: string, statusLabel: string, urlKey: string,
         *     client: array{fullName: string, email: string},
         *     items: list<array{name: string}>,
         * }>} $decoded
         */
        $decoded = json_decode($capturedJson, true, 512, JSON_THROW_ON_ERROR);
        Assert::same(1, count($decoded['invoices']));

        $payloadInvoice = $decoded['invoices'][0];
        Assert::same('INV-101', $payloadInvoice['number']);
        Assert::same('Sent', $payloadInvoice['statusLabel']);
        Assert::same('abc123urlkey', $payloadInvoice['urlKey']);
        Assert::same('Jane Smith', $payloadInvoice['client']['fullName']);
        Assert::same('jane@example.com', $payloadInvoice['client']['email']);
        Assert::same('Standard Clean', $payloadInvoice['items'][0]['name']);

        // Deliberately excluded — see Guest.php::serializeInvoiceForOffline()'s
        // own docblock: matches the worker RBAC role's existing
        // Permissions::VIEW_PAYMENT restriction on the live inv/guest grid.
        Assert::false(array_key_exists('total', $payloadInvoice));
        Assert::false(array_key_exists('balance', $payloadInvoice));
        Assert::false(array_key_exists('paid', $payloadInvoice));
        Assert::false(array_key_exists('price', $payloadInvoice['items'][0]));
        Assert::false(array_key_exists('amount', $payloadInvoice));
    }

    public function guestOfflineReturns404WhenNotAWorker(): void
    {
        /** @var DataResponseFactoryInterface&m\MockInterface $factory */
        $factory = m::mock(DataResponseFactoryInterface::class);
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        $webService->shouldReceive('getNotFoundResponse')->andReturn($notFound);

        $harness = $this->makeHarness($this->makeUser(7), $factory, $webViewRenderer, $webService);
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $deps = $this->makeDeps($this->makeUserInv(), null, $iR);

        $result = $harness->guestOffline($deps);

        Assert::same($notFound, $result);
    }

    public function guestOfflineRendersTheStandaloneShellPageForAWorker(): void
    {
        /** @var DataResponseFactoryInterface&m\MockInterface $factory */
        $factory = m::mock(DataResponseFactoryInterface::class);
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        /** @var Response&m\MockInterface $response */
        $response = m::mock(Response::class);

        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('renderPartial')->with('offline', [])->andReturn($response);

        /** @var Worker&m\MockInterface $worker */
        $worker = m::mock(Worker::class);
        $harness = $this->makeHarness($this->makeUser(9), $factory, $webViewRenderer, $webService);
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $deps = $this->makeDeps($this->makeUserInv(), $worker, $iR);

        $result = $harness->guestOffline($deps);

        Assert::same($response, $result);
    }
}

/**
 * Test-only harness — mixes in the real Guest trait against a
 * minimal BaseController subclass, avoiding InvController's much larger
 * unrelated dependency graph. $factory matches InvController's own
 * private readonly DataResponseFactoryInterface property exactly, since
 * PHP traits resolve $this->factory against whatever the host class
 * declares, regardless of visibility.
 */
final class GuestOfflineTestHarness extends BaseController
{
    use Guest;

    public function __construct(
        WebControllerService $webService,
        UserService $userService,
        TranslatorInterface $translator,
        WebViewRenderer $webViewRenderer,
        SessionInterface $session,
        SettingRepository $sR,
        Flash $flash,
        private readonly DataResponseFactoryInterface $factory,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
    }

    // Guest::open() (a different action from the two this harness actually
    // tests, guestOfflineData()/guestOffline()) calls these via $this->,
    // requiring App\Invoice\Inv\Trait\OptionsData's methods to resolve
    // against *some* declared shape — real signatures, never actually
    // invoked by guestOfflineData()/guestOffline() themselves. Declared
    // directly rather than `use OptionsData;`, which would pull in that
    // trait's other methods (editOptionsData() etc., needing a $dateHelper
    // property this harness deliberately doesn't have) too.

    /** @return array<string, string> */
    public function optionsDataUserClientsFilter(UCR $ucR, int $userId): array
    {
        throw new \LogicException('Not exercised by GuestOfflineTest.');
    }

    /** @return array<string, string> */
    public function optionsDataInvNumberGuestFilter(SDI&DRI $invs): array
    {
        throw new \LogicException('Not exercised by GuestOfflineTest.');
    }

    /** @return array<string, string> */
    public function optionsDataCreditInvNumberGuestFilter(SDI&DRI $invs, InvRepository $iR): array
    {
        throw new \LogicException('Not exercised by GuestOfflineTest.');
    }

    /** @return array<string, string> */
    public function optionsDataStatusFilter(InvRepository $iR): array
    {
        throw new \LogicException('Not exercised by GuestOfflineTest.');
    }
}
