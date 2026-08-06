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
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('getUser')->andReturn($user);
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn(true);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn(false);

        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);

        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();

        // Only actually invoked by flashGuestAccessWarnings() on the
        // not-a-worker/no-client-access path (resolveGuestAccess()) — loosely
        // stubbed here since none of these tests assert on flash content,
        // only on the resulting 404.
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturn('translated');

        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add');

        return new GuestOfflineTestHarness(
            $webService,
            $userService,
            $translator,
            $webViewRenderer,
            $session,
            m::mock(SettingRepository::class),
            $flash,
            $factory,
        );
    }

    private function makeDeps(?UserInv $userInv, ?Worker $worker, InvRepository&m\MockInterface $iR): InvGuestDeps
    {
        $uiR = m::mock(UserInvRepository::class);
        $uiR->shouldReceive('repoUserInvUserIdcount')->andReturn(null !== $userInv ? 1 : 0);
        $uiR->shouldReceive('repoUserInvUserIdquery')->andReturn($userInv);

        $wR = m::mock(WorkerRepository::class);
        $wR->shouldReceive('findByUserId')->andReturn($worker);

        // Only reached when no Worker is linked (resolveGuestAccess()'s
        // fallback to the ordinary client-assignment gate) — none of these
        // tests care about client-scoped access, only that it 404s cleanly.
        $ucR = m::mock(UserClientRepository::class);
        $ucR->shouldReceive('getAssignedToUser')->andReturn([]);

        return new InvGuestDeps(
            m::mock(InvAmountRepository::class),
            m::mock(InvRecurringRepository::class),
            $iR,
            $ucR,
            $uiR,
            m::mock(BacsPaymentService::class),
            $wR,
        );
    }

    private function makeUser(int $id): User&m\MockInterface
    {
        $user = m::mock(User::class);
        $user->shouldReceive('reqId')->andReturn($id);
        return $user;
    }

    private function makeUserInv(): UserInv&m\MockInterface
    {
        $userInv = m::mock(UserInv::class);
        $userInv->shouldReceive('getActive')->andReturn(true);
        return $userInv;
    }

    public function guestOfflineDataReturns404WhenNotLoggedIn(): void
    {
        $factory = m::mock(DataResponseFactoryInterface::class);
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webService = m::mock(WebControllerService::class);
        $notFound = m::mock(Response::class);
        $webService->shouldReceive('getNotFoundResponse')->andReturn($notFound);

        $harness = $this->makeHarness(null, $factory, $webViewRenderer, $webService);
        $result = $harness->guestOfflineData($this->makeDeps(null, null, m::mock(InvRepository::class)));

        Assert::same($notFound, $result);
    }

    public function guestOfflineDataReturns404WhenNotAWorker(): void
    {
        $factory = m::mock(DataResponseFactoryInterface::class);
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webService = m::mock(WebControllerService::class);
        $notFound = m::mock(Response::class);
        $webService->shouldReceive('getNotFoundResponse')->andReturn($notFound);

        $harness = $this->makeHarness($this->makeUser(7), $factory, $webViewRenderer, $webService);
        // A UserInv exists but no linked Worker — the ordinary client-guest path, not offline-eligible.
        $deps = $this->makeDeps($this->makeUserInv(), null, m::mock(InvRepository::class));

        $result = $harness->guestOfflineData($deps);

        Assert::same($notFound, $result);
    }

    public function guestOfflineDataReturnsWorkerScopedInvoicesWithoutAmountFields(): void
    {
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

        $item = m::mock(InvItem::class);
        $item->shouldReceive('getName')->andReturn('Standard Clean');
        $item->shouldReceive('getDescription')->andReturn('Weekly visit');
        $item->shouldReceive('getQuantity')->andReturn(1.0);
        $item->shouldReceive('getProductUnit')->andReturn('visit');
        $item->shouldReceive('getNote')->andReturn(null);

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

        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoWorkerVisible')->with(0, 55)->andReturn($entityReader);
        $iR->shouldReceive('getSpecificStatusArrayLabel')->with('2')->andReturn('Sent');

        $worker = m::mock(Worker::class);
        $worker->shouldReceive('reqId')->andReturn(55);

        $factory = m::mock(DataResponseFactoryInterface::class);
        /** @var string|null $capturedJson */
        $capturedJson = null;
        $response = m::mock(Response::class);
        $factory->shouldReceive('createResponse')
            ->withArgs(function (string $json) use (&$capturedJson): bool {
                $capturedJson = $json;
                return true;
            })
            ->andReturn($response);

        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webService = m::mock(WebControllerService::class);

        $harness = $this->makeHarness($this->makeUser(9), $factory, $webViewRenderer, $webService);
        $deps = $this->makeDeps($this->makeUserInv(), $worker, $iR);

        $result = $harness->guestOfflineData($deps);

        Assert::same($response, $result);
        Assert::notNull($capturedJson);
        /** @var array{downloadedAt: string, invoices: list<array<string, mixed>>} $decoded */
        $decoded = json_decode((string) $capturedJson, true, 512, JSON_THROW_ON_ERROR);
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
        $factory = m::mock(DataResponseFactoryInterface::class);
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webService = m::mock(WebControllerService::class);
        $notFound = m::mock(Response::class);
        $webService->shouldReceive('getNotFoundResponse')->andReturn($notFound);

        $harness = $this->makeHarness($this->makeUser(7), $factory, $webViewRenderer, $webService);
        $deps = $this->makeDeps($this->makeUserInv(), null, m::mock(InvRepository::class));

        $result = $harness->guestOffline($deps);

        Assert::same($notFound, $result);
    }

    public function guestOfflineRendersTheStandaloneShellPageForAWorker(): void
    {
        $factory = m::mock(DataResponseFactoryInterface::class);
        $webService = m::mock(WebControllerService::class);
        $response = m::mock(Response::class);

        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('renderPartial')->with('offline', [])->andReturn($response);

        $worker = m::mock(Worker::class);
        $harness = $this->makeHarness($this->makeUser(9), $factory, $webViewRenderer, $webService);
        $deps = $this->makeDeps($this->makeUserInv(), $worker, m::mock(InvRepository::class));

        $result = $harness->guestOffline($deps);

        Assert::same($response, $result);
    }
}

/**
 * @internal test-only harness — mixes in the real Guest trait against a
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
}
