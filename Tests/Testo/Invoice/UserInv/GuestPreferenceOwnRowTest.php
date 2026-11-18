<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\UserInv;

use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserInv\UserInvController;
use App\Invoice\UserInv\UserInvControllerDeps;
use App\Invoice\UserInv\UserInvRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\ResponseInterface;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * guestlimit, guestStickyNavbar and guestStickyGridHeader take the userinv id from the URL. They used to
 * change whichever row that named, so any observer could change another user's page size or sticky-bar
 * preference. Only the signed-in user's own row may be changed.
 */
#[Test]
final class GuestPreferenceOwnRowTest
{
    private function controller(int $signedInUserId): UserInvController
    {
        $webService = TypedMock::of(WebControllerService::class);
        $webService->shouldReceive('getRedirectResponse')->andReturnUsing(
            static fn (): ResponseInterface => new HttpFactory()->createResponse(302),
        );
        $deps = TypedMock::build(UserInvControllerDeps::class, ['webService' => $webService]);

        $user = TypedMock::of(User::class);
        $user->shouldReceive('reqId')->andReturn($signedInUserId);
        $userService = TypedMock::of(UserService::class);
        $userService->shouldReceive('getUser')->andReturn($user);
        $userService->shouldReceive('hasPermission')->andReturn(true);

        $webViewRenderer = TypedMock::of(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();
        $session = TypedMock::of(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);

        return new UserInvController(
            $deps,
            $session,
            TypedMock::of(SettingRepository::class),
            TypedMock::of(TranslatorInterface::class),
            $userService,
            $webViewRenderer,
            TypedMock::of(Flash::class),
        );
    }

    private function row(int $userId): UserInv
    {
        $row = new UserInv();
        $row->setUserId($userId);
        $row->setListLimit(10);
        $row->setStickyNavbar(false);
        $row->setStickyGridHeader(false);
        return $row;
    }

    private function repository(UserInv $row, bool $expectSave): UserInvRepository
    {
        $repo = TypedMock::of(UserInvRepository::class);
        $repo->shouldReceive('repoUserInvquery')->with(5)->andReturn($row);
        if ($expectSave) {
            $repo->shouldReceive('save')->once()->with($row);
        } else {
            $repo->shouldReceive('save')->never();
        }
        return $repo;
    }

    public function ownPageSizeIsChanged(): void
    {
        $row = $this->row(7);
        $this->controller(7)->guestlimit(5, 'inv', '50', $this->repository($row, true));
        Assert::same(50, $row->getListLimit());
    }

    public function anotherUsersPageSizeIsNotChanged(): void
    {
        $row = $this->row(8);
        $this->controller(7)->guestlimit(5, 'inv', '50', $this->repository($row, false));
        Assert::same(10, $row->getListLimit());
    }

    public function ownStickyPreferencesAreChanged(): void
    {
        $row = $this->row(7);
        $controller = $this->controller(7);
        $controller->guestStickyNavbar(5, '1', 'inv', $this->repository($row, true));
        $controller->guestStickyGridHeader(5, '1', 'inv', $this->repository($row, true));
        Assert::true($row->getStickyNavbar());
        Assert::true($row->getStickyGridHeader());
    }

    public function anotherUsersStickyPreferencesAreNotChanged(): void
    {
        $row = $this->row(8);
        $controller = $this->controller(7);
        $controller->guestStickyNavbar(5, '1', 'inv', $this->repository($row, false));
        $controller->guestStickyGridHeader(5, '1', 'inv', $this->repository($row, false));
        Assert::false($row->getStickyNavbar());
        Assert::false($row->getStickyGridHeader());
    }
}
